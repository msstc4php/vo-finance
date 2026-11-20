<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Swiss valor number, format only: one to nine digits. SIX publishes no check digit; digits-only follows the owner's
 * ruling, and the nine-digit bound is an inference from the ISIN basic number, into which a national number is
 * zero-padded.
 *
 * @see https://www.six-group.com/en/products-services/financial-information/data-standards.html SIX, the Swiss numbering agency
 * @see docs/sources/FINANCE.md "Valor, SIX"
 */
final class Valor extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match('/^[0-9]{1,9}$/D', $value) !== 1) {
            throw SecurityRules::fail('Valor', 'must have 1 to 9 digits');
        }

        return $value;
    }
}
