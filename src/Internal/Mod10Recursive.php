<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;

/**
 * The recursive modulo 10 check digit of the Swiss QR reference: the digits are processed from the left with a carry
 * that starts at 0; the carry and the digit select the next carry in the standard's matrix, and the check digit is read
 * off the last carry.
 *
 * @internal
 *
 * @see https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf Swiss Implementation Guidelines for the QR-bill 2.3, Annex B, Figure 21
 */
final class Mod10Recursive
{
    /**
     * The first row of the matrix; row `r` is this sequence read from position `r`.
     */
    private const array ROW = [0, 9, 4, 6, 8, 2, 7, 1, 3, 5];

    /**
     * @return numeric-string one digit
     */
    public static function compute(string $digits): string
    {
        if ($digits === '' || \strspn($digits, '0123456789') !== \strlen($digits)) {
            throw new LogicException('Modulo 10 recursive input must be ASCII digits.');
        }

        $carry = 0;
        for ($i = 0, $length = \strlen($digits); $i < $length; $i++) {
            $carry = self::ROW[($carry + \ord($digits[$i]) - 48) % 10];
        }

        return (string) ((10 - $carry) % 10);
    }

    /**
     * Whether the last digit of `$value` is the check digit of the digits before it; false for fewer than two
     * characters or any non-digit.
     */
    public static function isValid(string $value): bool
    {
        if (\strlen($value) < 2 || \strspn($value, '0123456789') !== \strlen($value)) {
            return false;
        }

        return self::compute(\substr($value, 0, -1)) === \substr($value, -1);
    }
}
