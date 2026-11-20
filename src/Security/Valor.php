<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Swiss valor number, format only: one to nine ASCII letters or digits, lowercase stored uppercase. SIX publishes no
 * length, character set or check digit; the nine-character bound is an inference from the ISIN basic number, into which
 * a national number is zero-padded.
 *
 * @see https://www.six-group.com/en/products-services/financial-information/data-standards.html SIX, the Swiss numbering agency
 * @see docs/sources/FINANCE.md "Valor, SIX"
 */
final class Valor extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        if (\preg_match('/^[A-Z0-9]{1,9}$/D', $value) !== 1) {
            throw SecurityRules::fail('Valor', 'must have 1 to 9 ASCII letters or digits');
        }

        return $value;
    }
}
