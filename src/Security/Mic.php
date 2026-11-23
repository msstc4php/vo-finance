<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Market Identifier Code (ISO 10383), format only: four uppercase letters or digits. The list changes monthly and the
 * operating/segment distinction needs it, so no list is embedded and there is no `operatingMic()`.
 *
 * @see https://www.iso20022.org/sites/default/files/2023-01/FAQ_ISO_10383_January2023.pdf ISO 10383 Registration Authority, FAQ January 2023
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "MIC, ISO 10383"
 */
final class Mic extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 4;

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape($value, 'MIC', self::MAX_LENGTH, '/^[A-Z0-9]{4}$/D', 'uppercase letters or digits');

        return $value;
    }
}
