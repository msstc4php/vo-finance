<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Unique Product Identifier (ISO 4914): twelve characters, the prefix `QZ` and ten of the digits and capital letters
 * other than `A`, `E`, `I`, `O`, `U` and `Y` (nine body characters and a check character), as the Regulatory Oversight
 * Committee describes it. Lowercase input is stored uppercase. The check character is not verified: ISO 4914 Annex C
 * and the ANNA DSB guidance could not be reached.
 *
 * @see https://www.leiroc.org/international_bodies.htm Regulatory Oversight Committee, the UPI format
 * @see https://cdn.standards.iteh.ai/samples/80506/11803bc283ce4b6d8c633ca72ef6ab03/ISO-4914-2021.pdf ISO 4914:2021 (preview, contents only)
 * @see docs/sources/FINANCE.md "UPI, ISO 4914"
 */
final class Upi extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 12;

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape($value, 'UPI', self::MAX_LENGTH, '/^QZ[0-9B-DF-HJ-NP-TV-XZ]{10}$/D', '"QZ" and ten digits or capital consonants except Y');

        return $value;
    }
}
