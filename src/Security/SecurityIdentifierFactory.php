<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;

/**
 * Builds the one identifier type that accepts the value, or throws {@see AmbiguousValueException} naming the types
 * that do (a 12-character value can be an ISIN and a FIGI, six digits a WKN and a Valor). Never picks silently.
 *
 * {@see Upi} does not take part, because its unconfirmed 12-character format would make every ISIN ambiguous.
 * {@see Valor} takes part for digit strings only, because it accepts letters too and would match nearly everything.
 */
final class SecurityIdentifierFactory
{
    private const array CANDIDATES = [
        Isin::class,
        Lei::class,
        Figi::class,
        Cfi::class,
        Fisn::class,
        Mic::class,
        Cusip::class,
        Sedol::class,
        Wkn::class,
        Valor::class,
    ];

    public static function fromString(string $value): ValueObject
    {
        $matches = [];
        foreach (self::CANDIDATES as $class) {
            if ($class === Valor::class && !\ctype_digit($value)) {
                continue;
            }

            $candidate = $class::tryFrom($value);
            if ($candidate !== null) {
                $matches[] = $candidate;
            }
        }

        return match (\count($matches)) {
            0 => throw new InvalidSecurityIdentifierException('Value is not a recognised securities identifier.'),
            1 => $matches[0],
            default => throw new AmbiguousValueException('securities identifier', \array_map(static fn (ValueObject $match): string => $match::class, $matches)),
        };
    }
}
