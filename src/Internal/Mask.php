<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

/**
 * Masks an account identifier the way vo-base `Iban` does in its messages: the first two and the last four characters
 * stay, every other character becomes an asterisk. A value of six characters or fewer is masked whole.
 *
 * @internal
 */
final class Mask
{
    /**
     * @return non-empty-string
     */
    public static function keepEnds(string $value): string
    {
        $characters = \preg_split('//u', $value, -1, \PREG_SPLIT_NO_EMPTY);
        if ($characters === false || $characters === []) {
            return '*';
        }

        $count = \count($characters);
        if ($count <= 6) {
            return \str_repeat('*', $count);
        }

        return $characters[0] . $characters[1] . \str_repeat('*', $count - 6) . \implode('', \array_slice($characters, -4));
    }
}
