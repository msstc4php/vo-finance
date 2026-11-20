<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Financial\Card\CardScheme;

/**
 * An inclusive range of eight-digit IINs assigned to a scheme.
 *
 * @internal
 */
final readonly class IinRange
{
    /**
     * @param string $start eight digits
     * @param string $end eight digits
     */
    public function __construct(
        public CardScheme $scheme,
        public string $start,
        public string $end,
    ) {
        if (\preg_match('/^\d{8}$/D', $start) !== 1 || \preg_match('/^\d{8}$/D', $end) !== 1 || $start > $end) {
            throw new InvalidArgumentException('An IIN range needs two ordered eight-digit bounds.');
        }
    }

    /**
     * @param string $iin eight digits
     */
    public function contains(string $iin): bool
    {
        return $iin >= $this->start && $iin <= $this->end;
    }
}
