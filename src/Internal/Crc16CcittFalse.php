<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

/**
 * @internal
 *
 * CRC-16/CCITT-FALSE as the EMVCo merchant-presented QR payload uses it: polynomial 0x1021, initial value 0xFFFF, no
 * reflection, no final XOR, written as four uppercase hex digits. For a payload the checksum covers everything up to and
 * including the `6304` of the CRC data object.
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, section 4.1, quoting the EMV QRCPS rule: ISO/IEC 13239, polynomial '1021', initial value 'FFFF'
 */
final class Crc16CcittFalse
{
    /**
     * @return non-empty-string four uppercase hex digits
     */
    public static function compute(string $body): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $length = \strlen($body); $i < $length; $i++) {
            $crc ^= \ord($body[$i]) << 8;
            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) !== 0 ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
            }
        }

        return \sprintf('%04X', $crc);
    }

    /**
     * Whether the last four hex digits of `$value`, in either case, are the checksum of the characters before them.
     */
    public static function isValid(string $value): bool
    {
        if (\strlen($value) <= 4) {
            return false;
        }

        $checksum = \substr($value, -4);

        return \ctype_xdigit($checksum) && self::compute(\substr($value, 0, -4)) === \strtoupper($checksum);
    }
}
