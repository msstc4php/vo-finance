<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;

/**
 * Stateless validation and field derivation of an EMVCo merchant-presented payload, shared by
 * {@see MerchantPresentedQr} (bounded) and the national profiles (validating a modified copy, unbounded: they check the
 * bound on the original). The rules and their sources are those of {@see MerchantPresentedQr}.
 *
 * @internal
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, sections 2 and 4
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf Banco Central do Brasil, Manual do BR Code 2.0.1, sections 2.1 and 2.2
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final class EmvcoMerchantPresented
{
    private const string TYPE = 'MerchantPresentedQr';

    private const int MAX_GUI_LENGTH = 32;

    /**
     * @param bool $bounded whether to refuse a payload over {@see PayloadBound::MAX_CHARACTERS} characters
     *
     * @return list<DataObject> the root data objects
     *
     * @throws InvalidPaymentQrException
     */
    public static function validate(string $payload, bool $bounded): array
    {
        if ($bounded) {
            PayloadBound::ensure($payload, self::TYPE);
        }

        $objects = EmvcoTlv::parse($payload);

        $ids = \array_map(static fn (DataObject $object): string => $object->id, $objects);
        if (\count(\array_unique($ids)) !== \count($ids)) {
            throw new InvalidPaymentQrException(\sprintf('%s repeats a root data object ID.', self::TYPE));
        }

        $last = $objects[\count($objects) - 1] ?? null;
        if ($last === null || $last->id !== '63' || EmvcoTlv::length($last->value) !== 4 || !Crc16CcittFalse::isValid($payload)) {
            throw new InvalidPaymentQrException(\sprintf('%s must end with a correct CRC.', self::TYPE));
        }

        self::assertRootObjects($objects);

        return $objects;
    }

    /**
     * @param list<DataObject> $objects
     *
     * @return non-empty-string|null
     */
    public static function get(array $objects, string $id): ?string
    {
        return array_find($objects, static fn (DataObject $object): bool => $object->id === $id)?->value;
    }

    /**
     * @param list<DataObject> $objects
     *
     * @return non-empty-string
     */
    public static function required(array $objects, string $id): string
    {
        return self::get($objects, $id) ?? throw new LogicException('A validated payload lacks a mandatory data object.');
    }

    /**
     * @param list<DataObject> $objects
     */
    public static function pointOfInitiation(array $objects): ?PointOfInitiation
    {
        $value = self::get($objects, '01');

        return $value === null ? null : PointOfInitiation::tryFrom($value);
    }

    /**
     * @param list<DataObject> $objects
     *
     * @return list<MerchantAccount> the objects with IDs 02 to 51, in payload order
     */
    public static function accounts(array $objects): array
    {
        $accounts = [];
        foreach ($objects as $object) {
            if (!self::isMerchantAccountId($object->id)) {
                continue;
            }

            if ($object->id < '26') {
                $accounts[] = new MerchantAccount($object->id, $object->value);

                continue;
            }

            $guid = null;
            $subFields = [];
            foreach (EmvcoTlv::parse($object->value) as $subField) {
                if ($subField->id === '00' && $guid === null) {
                    $guid = $subField->value;
                } else {
                    $subFields[] = $subField;
                }
            }

            $accounts[] = new MerchantAccount($object->id, $object->value, $guid, $subFields);
        }

        return $accounts;
    }

    /**
     * @param list<DataObject> $objects
     */
    public static function currency(array $objects): ?CurrencyCode
    {
        try {
            return CurrencyCode::fromNumeric(self::required($objects, '53'));
        } catch (InvalidValueObjectException) {
            return null;
        }
    }

    /**
     * @param list<DataObject> $objects
     */
    public static function amount(array $objects): ?Decimal
    {
        $amount = self::get($objects, '54');
        if ($amount === null || \preg_match('/^([0-9]+\.?[0-9]*|\.[0-9]+)$/D', $amount) !== 1) {
            return null;
        }

        return Decimal::tryFrom(\rtrim(\str_starts_with($amount, '.') ? '0' . $amount : $amount, '.'));
    }

    /**
     * @param list<DataObject> $objects
     */
    public static function countryCode(array $objects): ?CountryCode
    {
        return CountryCode::tryFrom(self::required($objects, '58'));
    }

    /**
     * @param list<DataObject> $objects
     *
     * @return list<DataObject> the sub-fields of template 62 in payload order
     */
    public static function additionalData(array $objects): array
    {
        $template = self::get($objects, '62');

        return $template === null ? [] : EmvcoTlv::parse($template);
    }

    /**
     * @param list<DataObject> $objects
     *
     * @return non-empty-string|null the last sub-field of template 62 with this sub-ID
     */
    public static function additionalDataField(array $objects, string $subId): ?string
    {
        $found = null;
        foreach (self::additionalData($objects) as $subField) {
            if ($subField->id === $subId) {
                $found = $subField->value;
            }
        }

        return $found;
    }

    /**
     * @param list<DataObject> $objects
     */
    private static function assertRootObjects(array $objects): void
    {
        if (self::get($objects, '00') !== '01') {
            throw new InvalidPaymentQrException(\sprintf('%s needs the payload format indicator 01.', self::TYPE));
        }

        $hasAccount = false;
        foreach ($objects as $object) {
            self::assertCharacterSet($object);
            if (!self::isMerchantAccountId($object->id)) {
                continue;
            }

            $hasAccount = true;
            if ($object->id >= '26') {
                self::assertTemplate($object->value);
            }
        }

        if (!$hasAccount) {
            throw new InvalidPaymentQrException(\sprintf('%s needs a merchant account data object (ID 02 to 51).', self::TYPE));
        }

        $pointOfInitiation = self::get($objects, '01');
        if ($pointOfInitiation !== null && PointOfInitiation::tryFrom($pointOfInitiation) === null) {
            throw new InvalidPaymentQrException(\sprintf('%s has a point of initiation other than 11 or 12.', self::TYPE));
        }

        foreach ([['52', 4], ['53', 3], ['58', 2]] as [$id, $length]) {
            $value = self::get($objects, $id);
            if ($value === null || EmvcoTlv::length($value) !== $length) {
                throw new InvalidPaymentQrException(\sprintf('%s needs data object %s with %d characters.', self::TYPE, $id, $length));
            }
        }

        foreach (['59', '60'] as $id) {
            if (self::get($objects, $id) === null) {
                throw new InvalidPaymentQrException(\sprintf('%s needs data object %s.', self::TYPE, $id));
            }
        }

        $additional = self::get($objects, '62');
        if ($additional !== null) {
            foreach (EmvcoTlv::parse($additional) as $subField) {
                if ($subField->id >= '01' && $subField->id <= '11') {
                    self::assertAlphanumeric($subField->value);
                }
            }
        }
    }

    private static function assertCharacterSet(DataObject $object): void
    {
        if (\in_array($object->id, ['00', '01', '52', '53', '55'], true)) {
            if (\preg_match('/^[0-9]+$/D', $object->value) !== 1) {
                throw new InvalidPaymentQrException(\sprintf('%s has a numeric data object with other characters than digits.', self::TYPE));
            }
        } elseif (($object->id >= '02' && $object->id <= '25') || \in_array($object->id, ['54', '56', '57', '58', '59', '60', '61', '63'], true)) {
            self::assertAlphanumeric($object->value);
        }
    }

    private static function assertAlphanumeric(string $value): void
    {
        if (\preg_match('/^[\x20-\x7E]+$/D', $value) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s has an alphanumeric data object outside the Common Character Set.', self::TYPE));
        }
    }

    private static function assertTemplate(string $template): void
    {
        $guid = array_find(EmvcoTlv::parse($template), static fn (DataObject $object): bool => $object->id === '00');
        if ($guid === null || EmvcoTlv::length($guid->value) > self::MAX_GUI_LENGTH) {
            throw new InvalidPaymentQrException(\sprintf('%s needs a globally unique identifier of at most %d characters in every template 26 to 51.', self::TYPE, self::MAX_GUI_LENGTH));
        }

        self::assertAlphanumeric($guid->value);
    }

    private static function isMerchantAccountId(string $id): bool
    {
        return $id >= '02' && $id <= '51';
    }
}
