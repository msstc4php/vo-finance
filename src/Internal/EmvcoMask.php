<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;

/**
 * Writes root data objects with the values of the account-bearing IDs replaced by asterisks of the same character
 * count: merchant accounts 02 to 51, unreserved templates 80 to 99 and the CRC. In the Additional Data Field Template
 * (62) the mobile number (62-02) and the customer label (62-06), personal data in a person-to-person code, are masked
 * the same way. The merchant name (59), on a person-to-person code a person's name, keeps its first character; the
 * city (60) and the postal code (61) become `***`, and so do, in the language template (64), the alternate name
 * (64-01, first character kept) and city (64-02). Headers and all other objects stay visible; the result is no valid
 * payload.
 *
 * @internal
 *
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf Banco Central do Brasil, Manual do BR Code 2.0.1, sections 2.1 and 2.2
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final class EmvcoMask
{
    private const string ADDITIONAL_DATA = '62';

    private const string LANGUAGE_TEMPLATE = '64';

    private const string NAME = '59';

    /** @var non-empty-list<non-empty-string> */
    private const array ADDRESS = ['60', '61'];

    /** @var non-empty-list<non-empty-string> */
    private const array PERSONAL_ADDITIONAL_DATA = ['02', '06'];

    /**
     * @param list<DataObject> $objects the root data objects of a payload
     *
     * @return non-empty-string
     */
    public static function apply(array $objects): string
    {
        $masked = '';
        foreach ($objects as $object) {
            $masked .= EmvcoTlv::encode(match (true) {
                self::isSecret($object->id) => self::asterisks($object),
                $object->id === self::NAME => new DataObject($object->id, Mask::name($object->value)),
                \in_array($object->id, self::ADDRESS, true) => new DataObject($object->id, Mask::ADDRESS),
                $object->id === self::ADDITIONAL_DATA => self::additionalData($object),
                $object->id === self::LANGUAGE_TEMPLATE => self::languageTemplate($object),
                default => $object,
            });
        }

        \assert($masked !== '');

        return $masked;
    }

    private static function asterisks(DataObject $object): DataObject
    {
        $mask = \str_repeat('*', EmvcoTlv::length($object->value));
        \assert($mask !== '');

        return new DataObject($object->id, $mask);
    }

    /**
     * A template that is not TLV is masked whole.
     */
    private static function additionalData(DataObject $template): DataObject
    {
        try {
            $subFields = EmvcoTlv::parse($template->value);
        } catch (InvalidPaymentQrException) {
            return self::asterisks($template);
        }

        $value = '';
        foreach ($subFields as $subField) {
            $isPersonal = \in_array($subField->id, self::PERSONAL_ADDITIONAL_DATA, true);
            $value .= EmvcoTlv::encode($isPersonal ? self::asterisks($subField) : $subField);
        }

        \assert($value !== '');

        return new DataObject($template->id, $value);
    }

    /**
     * A template that is not TLV is masked whole.
     */
    private static function languageTemplate(DataObject $template): DataObject
    {
        try {
            $subFields = EmvcoTlv::parse($template->value);
        } catch (InvalidPaymentQrException) {
            return new DataObject($template->id, Mask::ADDRESS);
        }

        $value = '';
        foreach ($subFields as $subField) {
            $value .= EmvcoTlv::encode(match ($subField->id) {
                '01' => new DataObject($subField->id, Mask::name($subField->value)),
                '02' => new DataObject($subField->id, Mask::ADDRESS),
                default => $subField,
            });
        }

        \assert($value !== '');

        return new DataObject($template->id, $value);
    }

    private static function isSecret(string $id): bool
    {
        return ($id >= '02' && $id <= '51') || $id === '63' || ($id >= '80' && $id <= '99');
    }
}
