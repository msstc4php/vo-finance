<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;

/**
 * The FIGI check digit over the first eleven characters: letters are `B` = 11 ... `Z` = 35 without vowels; from the
 * right, the last value is taken as it is and every second one before it is doubled; the digits of all results are
 * added and the check digit is the amount that brings the sum up to a multiple of ten. It differs from the ISIN's,
 * which works on the expanded digit string.
 *
 * @internal
 *
 * @see https://www.omg.org/spec/FIGI/1.0 OMG Financial Instrument Global Identifier 1.0 (formal/2015-12-01), section 6.1
 */
final class FigiCheckDigit
{
    private const string ALPHABET = '0123456789BCDFGHJKLMNPQRSTVWXYZ';

    private const array VALUES = [
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
        'B' => 11, 'C' => 12, 'D' => 13, 'F' => 15, 'G' => 16, 'H' => 17, 'J' => 19, 'K' => 20, 'L' => 21,
        'M' => 22, 'N' => 23, 'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'T' => 29, 'V' => 31, 'W' => 32,
        'X' => 33, 'Y' => 34, 'Z' => 35,
    ];

    /**
     * @return numeric-string one digit
     */
    public static function compute(string $body): string
    {
        if (\strlen($body) !== 11 || \strspn($body, self::ALPHABET) !== 11) {
            throw new LogicException('FIGI input must be eleven digits or capital consonants.');
        }

        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $value = self::VALUES[$body[$i]];
            if ($i % 2 === 1) {
                $value *= 2;
            }
            $sum += \intdiv($value, 10) + $value % 10;
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    /**
     * Whether `$value` is twelve characters whose last one is the check digit of the eleven before it.
     */
    public static function isValid(string $value): bool
    {
        if (\strlen($value) !== 12 || \strspn(\substr($value, 0, 11), self::ALPHABET) !== 11) {
            return false;
        }

        return self::compute(\substr($value, 0, 11)) === $value[11];
    }
}
