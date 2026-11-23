<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * Builds the one identifier type that accepts the value, or throws {@see AmbiguousValueException} listing every type
 * that does: a 12-character value can be an ISIN and a FIGI, six letters without `I` and `O` a CFI and a WKN. The
 * factory never picks silently and never narrows to a subset.
 *
 * {@see Valor} takes no part: it accepts any one to nine letters or digits (a bound no SIX rule confirms), so it would
 * join every MIC, WKN, CFI, SEDOL and CUSIP and the factory could return none of them. Build a `Valor` directly. The
 * candidate set is frozen for 1.x: a type added in a minor release joins the factory in the next major.
 */
final class SecurityIdentifierFactory
{
    /** @var non-empty-list<class-string<SecurityIdentifier&AbstractStringValueObject>> */
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
    ];

    private function __construct()
    {
    }

    /**
     * @throws AmbiguousValueException when several types accept the value
     * @throws InvalidSecurityIdentifierException when none does
     */
    public static function fromString(string $value): SecurityIdentifier
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
            default => throw new AmbiguousValueException('securities identifier', self::classesOf($matches)),
        };
    }

    /**
     * @param non-empty-list<SecurityIdentifier> $matches
     *
     * @return non-empty-list<class-string<SecurityIdentifier>>
     */
    private static function classesOf(array $matches): array
    {
        return \array_map(static fn (SecurityIdentifier $match): string => $match::class, $matches);
    }
}
