<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;

/**
 * Reader and writer of the EMVCo ID, length, value encoding. Lengths count characters, so the payload is split into
 * characters first, and no slice is taken before its length is checked against what is left.
 *
 * @internal
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, section 2
 */
final class EmvcoTlv
{
    /** The largest QR content (numeric mode, version 40-L); a bound against abusive input, not an EMVCo rule. */
    public const int MAX_PAYLOAD_LENGTH = 7089;

    /**
     * @return list<DataObject>
     *
     * @throws InvalidPaymentQrException when the data is not UTF-8, is cut, or a length overruns it; repeated IDs are
     *                                   kept
     */
    public static function parse(string $data): array
    {
        $characters = \preg_split('//u', $data, -1, \PREG_SPLIT_NO_EMPTY);
        if ($characters === false) {
            throw new InvalidPaymentQrException('MerchantPresentedQr must be valid UTF-8.');
        }

        $total = \count($characters);
        $position = 0;
        $objects = [];

        while ($position < $total) {
            if ($total - $position < 4) {
                throw new InvalidPaymentQrException('MerchantPresentedQr has a data object header cut short.');
            }

            $id = $characters[$position] . $characters[$position + 1];
            $length = $characters[$position + 2] . $characters[$position + 3];
            if (\preg_match('/^[0-9]{2}$/D', $id) !== 1 || \preg_match('/^[0-9]{2}$/D', $length) !== 1) {
                throw new InvalidPaymentQrException('MerchantPresentedQr has a data object ID or length that is not two digits.');
            }

            $size = (int) $length;
            $position += 4;
            if ($size < 1) {
                throw new InvalidPaymentQrException('MerchantPresentedQr has a data object with a length of zero.');
            }

            if ($size > $total - $position) {
                throw new InvalidPaymentQrException('MerchantPresentedQr has a data object longer than the data that holds it.');
            }

            $value = \implode('', \array_slice($characters, $position, $size));
            \assert($value !== '');
            $objects[] = new DataObject($id, $value);
            $position += $size;
        }

        return $objects;
    }

    public static function encode(DataObject $object): string
    {
        return $object->id . \sprintf('%02d', self::length($object->value)) . $object->value;
    }

    /**
     * @return non-negative-int the number of characters
     *
     * @throws InvalidPaymentQrException when the value is not valid UTF-8
     */
    public static function length(string $value): int
    {
        $count = \preg_match_all('/./su', $value);
        if ($count === false) {
            throw new InvalidPaymentQrException('MerchantPresentedQr must be valid UTF-8.');
        }

        return $count;
    }
}
