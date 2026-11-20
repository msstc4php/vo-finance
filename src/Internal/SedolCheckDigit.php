<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;

/**
 * The SEDOL check digit: each of the first six characters is multiplied by the weights 1, 3, 1, 7, 3, 9; digits are
 * themselves, letters `B`-`Z` without vowels are `B` = 11 ... `Z` = 35; the check digit is the amount that brings the
 * sum up to a multiple of ten.
 *
 * @internal
 *
 * @see https://www.lseg.com/content/dam/lseg/en_us/documents/sedol/sedol-masterfile-service-and-technical-guide-v8.8.pdf SEDOL Masterfile Service and Technical Guide v8.8, sections 8.2 to 8.4
 */
final class SedolCheckDigit
{
    private const string ALPHABET = '0123456789BCDFGHJKLMNPQRSTVWXYZ';

    private const array VALUES = [
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
        'B' => 11, 'C' => 12, 'D' => 13, 'F' => 15, 'G' => 16, 'H' => 17, 'J' => 19, 'K' => 20, 'L' => 21,
        'M' => 22, 'N' => 23, 'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'T' => 29, 'V' => 31, 'W' => 32,
        'X' => 33, 'Y' => 34, 'Z' => 35,
    ];

    private const array WEIGHTS = [1, 3, 1, 7, 3, 9];

    /**
     * @return numeric-string one digit
     */
    public static function compute(string $body): string
    {
        if (\strlen($body) !== 6 || \strspn($body, self::ALPHABET) !== 6) {
            throw new LogicException('SEDOL input must be six digits or capital consonants.');
        }

        $sum = 0;
        foreach (self::WEIGHTS as $i => $weight) {
            $sum += self::VALUES[$body[$i]] * $weight;
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    /**
     * Whether `$value` is seven characters whose last one is the check digit of the six before it.
     */
    public static function isValid(string $value): bool
    {
        if (\strlen($value) !== 7 || \strspn(\substr($value, 0, 6), self::ALPHABET) !== 6) {
            return false;
        }

        return self::compute(\substr($value, 0, 6)) === $value[6];
    }
}
