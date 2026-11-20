<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

/**
 * The control key of a Russian 20-digit account number (its 9th digit), checked against the BIC: the account is
 * preceded by the three-digit conditional number of the RKC (BIC digits 5 and 6 with a leading zero) or of the credit
 * institution (BIC digits 7 to 9), the 23 digits are weighted 7, 1, 3 repeating, and the sum of the last digits of the
 * products must end in 0. Which number applies depends on where the account is opened, which a payment payload does not
 * say, so an account is valid when either number gives a correct key.
 *
 * Scope: the order serves the account scheme "приведенная в Правилах ведения бухгалтерского учета в кредитных
 * организациях", for accounts opened "в РКЦ" or "в кредитной организации". A treasury account of the Federal Treasury
 * (first digit 0, numbered outside that scheme since 2021) is outside it; {@see isInScope()} tells the two apart and
 * such an account is never refused.
 *
 * @internal
 *
 * @see https://pravo.ppt.ru/poryadok/98817 Bank of Russia, Order 515 of 08.09.1997 "Порядок расчета контрольного ключа в номере лицевого счета", items 2 to 7 (as quoted)
 * @see docs/sources/FINANCE.md "GOST R 56042-2014 and the Bank of Russia account key"
 */
final class RussianAccountKey
{
    private const array WEIGHTS = [7, 1, 3];

    /**
     * Whether the order applies: 20 digits not starting with 0.
     */
    public static function isInScope(string $account): bool
    {
        return \preg_match('/^[1-9]\d{19}$/D', $account) === 1;
    }

    public static function isValid(string $account, string $bic): bool
    {
        if (\preg_match('/^\d{20}$/D', $account) !== 1 || \preg_match('/^\d{9}$/D', $bic) !== 1) {
            return false;
        }

        return self::sumEndsInZero(\substr($bic, 6, 3) . $account)
            || self::sumEndsInZero('0' . \substr($bic, 4, 2) . $account);
    }

    private static function sumEndsInZero(string $digits): bool
    {
        $sum = 0;
        foreach (\str_split($digits) as $position => $digit) {
            $sum += ((int) $digit * self::WEIGHTS[$position % 3]) % 10;
        }

        return $sum % 10 === 0;
    }
}
