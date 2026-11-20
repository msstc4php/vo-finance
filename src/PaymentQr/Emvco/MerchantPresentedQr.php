<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * EMVCo merchant-presented QR payload (EMV QRCPS, merchant-presented mode 1.1): a sequence of ID, length, value data
 * objects, templates 26 to 51 and 62 nested, ending in a CRC-16/CCITT-FALSE. The payload is kept as given, a lowercase
 * CRC included. Lengths count characters.
 *
 * Enforced: the TLV shape; `00` with value `01`; the CRC as the last object and correct; no repeated root ID; at least
 * one of IDs 02 to 51; `52`, `53`, `58`, `59`, `60` present with the fixed lengths of 52, 53, 58; point of initiation
 * `11` or `12`; a GUI of at most 32 characters in every template 26 to 51; the Common Character Set (printable ASCII,
 * 0x20 to 0x7E, the minimum of EMV Book 4's set) in numeric and alphanumeric-special objects, digits in numeric ones;
 * at most 7089 characters, the largest QR content and only a bound against abusive input. UTF-8 is accepted only
 * where a String-format object allows it: sub-fields 01 to 99 of templates 26 to 51, 62 sub-IDs 12 to 99, and the
 * templates 64 to 99. Not enforced (see docs/sources/FINANCE.md): the "var. up to" maxima, the 512-character
 * recommendation, the content of IDs 02 to 25, and the amount beyond its character set; {@see amount()} gives null for
 * a non-decimal.
 *
 * {@see masked()} replaces with asterisks the values of IDs 02 to 51, 80 to 99 and the CRC; the CRC is not recomputed.
 * {@see additionalData()} keys are strings that PHP turns into integers for IDs such as "10"; look values up with the
 * string ID as written.
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, sections 2 and 4
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf Banco Central do Brasil, Manual do BR Code 2.0.1, sections 2.1 and 2.2
 * @see https://mvallim.github.io/emv-qrcode/docs/EMVCo-Merchant-Presented-QR-Specification-v1.1.pdf EMV QRCPS Merchant-Presented Mode 1.1 (third-party copy), sections 3.2, 4.2 to 4.8, Annex B
 * @see docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final class MerchantPresentedQr extends AbstractStringValueObject implements Maskable
{
    private const string TYPE = 'MerchantPresentedQr';

    private const int MAX_GUI_LENGTH = 32;

    /** @var list<DataObject> */
    private array $objects = [];

    public function pointOfInitiation(): ?PointOfInitiation
    {
        $value = $this->get('01');

        return $value === null ? null : PointOfInitiation::tryFrom($value);
    }

    /**
     * @return list<MerchantAccount> the objects with IDs 02 to 51, in payload order
     */
    public function merchantAccounts(): array
    {
        $accounts = [];
        foreach ($this->objects as $object) {
            if (!$this->isMerchantAccountId($object->id)) {
                continue;
            }

            if ((int) $object->id < 26) {
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
     * @return non-empty-string four digits
     */
    public function merchantCategoryCode(): string
    {
        return $this->required('52');
    }

    /**
     * Null for a numeric code vo-base does not know.
     */
    public function currency(): ?CurrencyCode
    {
        try {
            return CurrencyCode::fromNumeric($this->required('53'));
        } catch (InvalidValueObjectException) {
            return null;
        }
    }

    /**
     * Normalised ("10.50" gives 10.5; the written form is {@see get()} with "54"). Null when the amount is absent (the
     * payer enters it) or is not digits with one optional decimal mark.
     */
    public function amount(): ?Decimal
    {
        $amount = $this->get('54');
        if ($amount === null || \preg_match('/^([0-9]+\.?[0-9]*|\.[0-9]+)$/D', $amount) !== 1) {
            return null;
        }

        return Decimal::tryFrom(\rtrim(\str_starts_with($amount, '.') ? '0' . $amount : $amount, '.'));
    }

    /**
     * Null for a code vo-base does not know.
     */
    public function countryCode(): ?CountryCode
    {
        return CountryCode::tryFrom($this->required('58'));
    }

    /**
     * @return non-empty-string
     */
    public function merchantName(): string
    {
        return $this->required('59');
    }

    /**
     * @return non-empty-string
     */
    public function merchantCity(): string
    {
        return $this->required('60');
    }

    /**
     * @return non-empty-string|null
     */
    public function postalCode(): ?string
    {
        return $this->get('61');
    }

    /**
     * The sub-fields of the Additional Data Field Template (ID 62) by sub-ID; a repeated sub-ID keeps its last value.
     *
     * @return array<array-key, non-empty-string>
     */
    public function additionalData(): array
    {
        $template = $this->get('62');
        if ($template === null) {
            return [];
        }

        $data = [];
        foreach (EmvcoTlv::parse($template) as $subField) {
            $data[$subField->id] = $subField->value;
        }

        return $data;
    }

    /**
     * The value of a data object under the root as written, or null when it is absent.
     *
     * @return non-empty-string|null
     */
    public function get(string $id): ?string
    {
        return array_find($this->objects, static fn (DataObject $object): bool => $object->id === $id)?->value;
    }

    #[Override]
    public function masked(): string
    {
        return EmvcoMask::apply($this->objects);
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (\strlen($value) > EmvcoTlv::MAX_PAYLOAD_LENGTH * 4 || EmvcoTlv::length($value) > EmvcoTlv::MAX_PAYLOAD_LENGTH) {
            throw new InvalidPaymentQrException(\sprintf('%s must not exceed %d characters.', self::TYPE, EmvcoTlv::MAX_PAYLOAD_LENGTH));
        }

        $objects = EmvcoTlv::parse($value);

        $ids = \array_map(static fn (DataObject $object): string => $object->id, $objects);
        if (\count(\array_unique($ids)) !== \count($ids)) {
            throw new InvalidPaymentQrException(\sprintf('%s repeats a root data object ID.', self::TYPE));
        }

        $last = $objects[\count($objects) - 1] ?? null;
        if ($last === null || $last->id !== '63' || EmvcoTlv::length($last->value) !== 4 || !Crc16CcittFalse::isValid($value)) {
            throw new InvalidPaymentQrException(\sprintf('%s must end with a correct CRC.', self::TYPE));
        }

        $this->objects = $objects;
        $this->assertRootObjects();

        return $value;
    }

    private function assertRootObjects(): void
    {
        if ($this->get('00') !== '01') {
            throw new InvalidPaymentQrException(\sprintf('%s needs the payload format indicator 01.', self::TYPE));
        }

        $hasAccount = false;
        foreach ($this->objects as $object) {
            $this->assertCharacterSet($object);
            if (!$this->isMerchantAccountId($object->id)) {
                continue;
            }

            $hasAccount = true;
            if ((int) $object->id >= 26) {
                $this->assertTemplate($object->value);
            }
        }

        if (!$hasAccount) {
            throw new InvalidPaymentQrException(\sprintf('%s needs a merchant account data object (ID 02 to 51).', self::TYPE));
        }

        $pointOfInitiation = $this->get('01');
        if ($pointOfInitiation !== null && PointOfInitiation::tryFrom($pointOfInitiation) === null) {
            throw new InvalidPaymentQrException(\sprintf('%s has a point of initiation other than 11 or 12.', self::TYPE));
        }

        foreach ([['52', 4], ['53', 3], ['58', 2]] as [$id, $length]) {
            $value = $this->get($id);
            if ($value === null || EmvcoTlv::length($value) !== $length) {
                throw new InvalidPaymentQrException(\sprintf('%s needs data object %s with %d characters.', self::TYPE, $id, $length));
            }
        }

        foreach (['59', '60'] as $id) {
            if ($this->get($id) === null) {
                throw new InvalidPaymentQrException(\sprintf('%s needs data object %s.', self::TYPE, $id));
            }
        }

        $additional = $this->get('62');
        if ($additional !== null) {
            foreach (EmvcoTlv::parse($additional) as $subField) {
                if ($subField->id >= '01' && $subField->id <= '11') {
                    $this->assertAlphanumeric($subField->value);
                }
            }
        }
    }

    private function assertCharacterSet(DataObject $object): void
    {
        if (\in_array($object->id, ['00', '01', '52', '53', '55'], true)) {
            if (\preg_match('/^[0-9]+$/D', $object->value) !== 1) {
                throw new InvalidPaymentQrException(\sprintf('%s has a numeric data object with other characters than digits.', self::TYPE));
            }
        } elseif (($object->id >= '02' && $object->id <= '25') || \in_array($object->id, ['54', '56', '57', '58', '59', '60', '61', '63'], true)) {
            $this->assertAlphanumeric($object->value);
        }
    }

    private function assertAlphanumeric(string $value): void
    {
        if (\preg_match('/^[\x20-\x7E]+$/D', $value) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s has an alphanumeric data object outside the Common Character Set.', self::TYPE));
        }
    }

    private function assertTemplate(string $template): void
    {
        $guid = array_find(EmvcoTlv::parse($template), static fn (DataObject $object): bool => $object->id === '00');
        if ($guid === null || EmvcoTlv::length($guid->value) > self::MAX_GUI_LENGTH) {
            throw new InvalidPaymentQrException(\sprintf('%s needs a globally unique identifier of at most %d characters in every template 26 to 51.', self::TYPE, self::MAX_GUI_LENGTH));
        }

        $this->assertAlphanumeric($guid->value);
    }

    /**
     * @return non-empty-string
     */
    private function required(string $id): string
    {
        return $this->get($id) ?? throw new LogicException('A validated payload lacks a mandatory data object.');
    }

    private function isMerchantAccountId(string $id): bool
    {
        return $id >= '02' && $id <= '51';
    }
}
