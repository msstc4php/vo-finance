<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;

/**
 * @internal
 *
 * The CUSIP check digit of an all-digit CUSIP, the "Modulus 10 Double Add Double" technique over the first eight
 * digits: every second digit, starting with the second, is doubled, the digits of each result are added, and the
 * check digit is the complement of the sum's last digit. Letters and the private-placement characters `*`, `@`, `#`
 * have no value in any official publication reached, so a body with one throws \LogicException and the caller checks
 * the format only.
 *
 * @see https://www.cusip.com/pdf/news/CUSIP-ACommonLanguageForEfficientMarkets_2022.pdf CUSIP Global Services, "The check digit is the complement of the final number of the sum of the numbers in the identifier when it is calculated using the Modulus 10 Double Add Double technique"
 */
final class CusipCheckDigit
{
    /**
     * @return numeric-string one digit
     */
    public static function compute(string $body): string
    {
        if (\strlen($body) !== 8 || \strspn($body, '0123456789') !== 8) {
            throw new LogicException('CUSIP check digit input must be eight ASCII digits.');
        }

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $value = \ord($body[$i]) - 48;
            if ($i % 2 === 1) {
                $value *= 2;
            }
            $sum += \intdiv($value, 10) + $value % 10;
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    /**
     * Whether `$value` is nine digits whose last one is the check digit of the eight before it.
     */
    public static function isValid(string $value): bool
    {
        if (\strlen($value) !== 9 || \strspn($value, '0123456789') !== 9) {
            return false;
        }

        return self::compute(\substr($value, 0, 8)) === $value[8];
    }
}
