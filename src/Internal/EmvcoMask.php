<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;

/**
 * Masks root data objects fail-closed: an object stays only on the allowlist and in its shape, everything else is
 * hidden. Visible: payload format indicator (00, two digits), point of initiation (01, 11 or 12), MCC (52, four
 * digits), currency (53, three digits), amount and fixed fee (54, 56, digits with an optional decimal part, at most 13
 * characters), tip indicator (55, 01 to 03), percentage fee (57, at most 5 characters) and country (58, two capital
 * letters). Asterisks of the same character count: merchant accounts 02 to 51, templates 80 to 99 and the CRC. The
 * merchant name (59) keeps its first character; city (60), postal code (61), RFU 65 to 79 and a visible ID of another
 * shape become `***`. In the Additional Data Field Template (62) the bill, store, reference and terminal labels (01,
 * 03, 05, 07) keep their first and last character; the consumer data request (09, `A`, `M`, `E`) and the merchant
 * channel (11, three digits) stay in their shape; the mobile number, loyalty number, customer label and merchant tax
 * ID (02, 04, 06, 10) and the payment-system templates (50 to 99) become asterisks; everything else, the purpose of
 * transaction (08) and RFU 12 to 49 included, becomes `***`. In the language template (64) the language preference
 * (00, two letters) stays, the alternate name (01) keeps its first character and everything else becomes `***`. A 62
 * or 64 that is not TLV is masked whole. The result is no valid payload.
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

    private const string AMOUNT = '/^(?=[0-9.]{1,13}$)\d+(\.\d*)?$/D';

    /** @var array<array-key, non-empty-string> numeric IDs become integer keys */
    private const array ROOT_SHAPES = [
        '00' => '/^\d{2}$/D',
        '01' => '/^1[12]$/D',
        '52' => '/^\d{4}$/D',
        '53' => '/^\d{3}$/D',
        '54' => self::AMOUNT,
        '55' => '/^0[1-3]$/D',
        '56' => self::AMOUNT,
        '57' => '/^(?=[0-9.]{1,5}$)\d+(\.\d*)?$/D',
        '58' => '/^[A-Z]{2}$/D',
    ];

    /** @var non-empty-list<non-empty-string> */
    private const array LABELS = ['01', '03', '05', '07'];

    /** @var non-empty-list<non-empty-string> */
    private const array PERSONAL_ADDITIONAL_DATA = ['02', '04', '06', '10'];

    /** @var array<array-key, non-empty-string> */
    private const array ADDITIONAL_SHAPES = ['09' => '/^[AME]{1,3}$/D', '11' => '/^\d{3}$/D'];

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
                $object->id === self::ADDITIONAL_DATA => self::additionalData($object),
                $object->id === self::LANGUAGE_TEMPLATE => self::languageTemplate($object),
                default => self::shaped($object, self::ROOT_SHAPES),
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
     * @param array<array-key, non-empty-string> $shapes
     */
    private static function shaped(DataObject $object, array $shapes): DataObject
    {
        $shape = $shapes[$object->id] ?? null;

        return $shape !== null && \preg_match($shape, $object->value) === 1 ? $object : new DataObject($object->id, Mask::TEXT);
    }

    /**
     * A template that is not TLV, or that its masks would push past 99 characters, is masked whole.
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
            $value .= EmvcoTlv::encode(match (true) {
                \in_array($subField->id, self::LABELS, true) => new DataObject($subField->id, Mask::reference($subField->value)),
                \in_array($subField->id, self::PERSONAL_ADDITIONAL_DATA, true), $subField->id >= '50' => self::asterisks($subField),
                default => self::shaped($subField, self::ADDITIONAL_SHAPES),
            });
        }

        \assert($value !== '');

        return self::fits($value) ? new DataObject($template->id, $value) : self::asterisks($template);
    }

    /**
     * A template that is not TLV, or that its masks would push past 99 characters, is masked whole.
     */
    private static function languageTemplate(DataObject $template): DataObject
    {
        try {
            $subFields = EmvcoTlv::parse($template->value);
        } catch (InvalidPaymentQrException) {
            return new DataObject($template->id, Mask::TEXT);
        }

        $value = '';
        foreach ($subFields as $subField) {
            $value .= EmvcoTlv::encode(match ($subField->id) {
                '01' => new DataObject($subField->id, Mask::name($subField->value)),
                default => self::shaped($subField, ['00' => '/^[A-Za-z]{2}$/D']),
            });
        }

        \assert($value !== '');

        return self::fits($value) ? new DataObject($template->id, $value) : new DataObject($template->id, Mask::TEXT);
    }

    /**
     * A mask can be longer than its value (`x` → `***`), so a rebuilt template near the bound may not fit; it is then
     * masked whole.
     */
    private static function fits(string $value): bool
    {
        return EmvcoTlv::length($value) <= 99;
    }

    private static function isSecret(string $id): bool
    {
        return ($id >= '02' && $id <= '51') || $id === '63' || ($id >= '80' && $id <= '99');
    }
}
