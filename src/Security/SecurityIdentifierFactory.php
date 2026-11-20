<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * Builds the one identifier type that accepts the value, or throws {@see AmbiguousValueException} listing every type
 * that does (a 12-character value can be an ISIN and a FIGI, six digits a WKN and a Valor, nine digits a CUSIP and a
 * Valor). Every type takes part; the factory never picks silently and never narrows to a subset.
 */
final class SecurityIdentifierFactory
{
    /** @var non-empty-list<class-string<AbstractStringValueObject>> */
    private const array CANDIDATES = [
        Isin::class,
        Lei::class,
        Upi::class,
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
