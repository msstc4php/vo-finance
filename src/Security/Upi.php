<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Unique Product Identifier (ISO 4914), format only: 12 uppercase letters or digits. ISO 4914 clause 4 and Annex C
 * (the check character) and the ANNA DSB technical guidance could not be reached, so no check is enforced and the
 * length is not quoted from an official text.
 *
 * @see https://cdn.standards.iteh.ai/samples/80506/11803bc283ce4b6d8c633ca72ef6ab03/ISO-4914-2021.pdf ISO 4914:2021 (preview, contents only)
 * @see docs/sources/FINANCE.md "UPI, ISO 4914"
 */
final class Upi extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        SecurityRules::shape($value, 'UPI', 12, '/^[A-Z0-9]{12}$/D', 'uppercase letters or digits');

        return $value;
    }
}
