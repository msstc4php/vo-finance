<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

/**
 * Masks an account identifier so that at most about a third of it stays visible: six characters or fewer are masked
 * whole, 7 to 11 keep the last two, 12 to 17 the last four, and 18 or more the first two and the last four (an IBAN's
 * country code and its end). Every other character becomes an asterisk, so the length is kept.
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
        [$head, $tail] = match (true) {
            $count <= 6 => [0, 0],
            $count <= 11 => [0, 2],
            $count <= 17 => [0, 4],
            default => [2, 4],
        };

        $masked = \implode('', \array_slice($characters, 0, $head))
            . \str_repeat('*', $count - $head - $tail)
            . \implode('', \array_slice($characters, $count - $tail));
        \assert($masked !== '');

        return $masked;
    }
}
