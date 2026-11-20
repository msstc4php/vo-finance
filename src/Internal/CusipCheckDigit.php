<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;

/**
 * @internal
 *
 * The CUSIP check digit, the "Modulus 10 Double Add Double" technique over the first eight characters: digits are
 * themselves, letters `A` = 10 ... `Z` = 35, and the private-placement characters `*`, `@`, `#` are 36, 37, 38; every
 * second value, starting with the second character, is doubled, the digits of each result are added, and the check
 * digit is the complement of the sum's last digit.
 *
 * @see https://www.cusip.com/pdf/news/CUSIP-ACommonLanguageForEfficientMarkets_2022.pdf CUSIP Global Services, "The check digit is the complement of the final number of the sum ... calculated using the Modulus 10 Double Add Double technique"
 * @see https://www.cusip.com/pdf/fact_sheet/CGS_Private_Placement_Numbers.pdf CGS, the characters *, @, # in position 6, 7 or 8 identify PPNs
 */
final class CusipCheckDigit
{
    private const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ*@#';

    /**
     * @return numeric-string one digit
     */
    public static function compute(string $body): string
    {
        if (\strlen($body) !== 8 || \strspn($body, self::ALPHABET) !== 8) {
            throw new LogicException('CUSIP input must be eight digits, capital letters, *, @ or #.');
        }

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $value = \strcspn(self::ALPHABET, $body[$i]);
            if ($i % 2 === 1) {
                $value *= 2;
            }
            $sum += \intdiv($value, 10) + $value % 10;
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    /**
     * Whether `$value` is nine characters whose last one is the check digit of the eight before it.
     */
    public static function isValid(string $value): bool
    {
        if (\strlen($value) !== 9 || \strspn(\substr($value, 0, 8), self::ALPHABET) !== 8) {
            return false;
        }

        return self::compute(\substr($value, 0, 8)) === $value[8];
    }
}
