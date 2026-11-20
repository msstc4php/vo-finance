<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;

/**
 * @internal
 *
 * The ISIN check digit, the modulus 10 "double-add-double" formula: each letter of the prefix and basic number becomes
 * two digits (`A` = 10 ... `Z` = 35) and the digit string is checked with {@see Luhn}.
 *
 * @see https://anna-web.org/identifiers/ ANNA, "The final character is a check digit computed according to the modulus 10 "Double-Add-Double" formula"
 * @see https://standards.iteh.ai/catalog/standards/sist/b80d38b9-fca3-47d4-b4f1-ae10b5f2ca62/iso-6166-2013 ISO 6166:2013, clause 4.1 (preview; Annex C is not in it)
 */
final class IsinCheckDigit
{
    private const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @return numeric-string one digit
     */
    public static function compute(string $body): string
    {
        if ($body === '' || \strspn($body, self::ALPHABET) !== \strlen($body)) {
            throw new LogicException('ISIN input must be digits and capital letters.');
        }

        $digits = '';
        foreach (\str_split($body) as $character) {
            $digits .= \strcspn(self::ALPHABET, $character);
        }

        return Luhn::compute($digits);
    }

    /**
     * Whether `$value` is 12 characters whose last one is the check digit of the 11 before it.
     */
    public static function isValid(string $value): bool
    {
        if (\strlen($value) !== 12 || \strspn(\substr($value, 0, 11), self::ALPHABET) !== 11) {
            return false;
        }

        return self::compute(\substr($value, 0, 11)) === $value[11];
    }
}
