<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Legal Entity Identifier (ISO 17442): 18 uppercase letters or digits and a two-digit ISO/IEC 7064 MOD 97-10 check
 * pair. Lowercase input is stored uppercase. {@see louPrefix()} is a plain slice of the first four characters;
 * no official text reached confirms what they mean, so nothing is verified about them.
 *
 * @see https://cdn.standards.iteh.ai/samples/59771/b4526b6c9dc04897b014c659ed70ad27/ISO-17442-2012.pdf ISO 17442:2012, clauses 4 and 5
 * @see docs/sources/FINANCE.md "LEI, ISO 17442"
 */
final class Lei extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 20;

    private const string TYPE = 'LEI';

    /**
     * @return non-empty-string four characters
     */
    public function louPrefix(): string
    {
        $prefix = \substr($this->value, 0, 4);
        \assert($prefix !== '');

        return $prefix;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape($value, self::TYPE, self::MAX_LENGTH, '/^[A-Z0-9]{18}[0-9]{2}$/D', '18 uppercase letters or digits, two digits');

        if (!Iso7064Mod97::isValid($value)) {
            throw SecurityRules::fail(self::TYPE, 'has a wrong check pair');
        }

        return $value;
    }
}
