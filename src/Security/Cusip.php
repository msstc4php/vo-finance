<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * CUSIP: eight letters, digits or the private-placement characters `*`, `@`, `#`, and a digit. Lowercase input is
 * stored uppercase. The check digit is verified only for an all-digit CUSIP, because no reached CGS publication gives.
 *
 * the value of letters or `*@#` in the sum; any other CUSIP is checked for format only.
 *
 * @see https://www.cusip.com/pdf/news/CUSIP-ACommonLanguageForEfficientMarkets_2022.pdf CUSIP Global Services, "Modulus 10 Double Add Double"
 * @see https://www.cusip.com/pdf/fact_sheet/CGS_Private_Placement_Numbers.pdf CGS, the characters `*`, `@`, `#`
 * @see docs/sources/FINANCE.md "CUSIP, CUSIP Global Services"
 */
final class Cusip extends AbstractStringValueObject
{
    private const string TYPE = 'CUSIP';

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape($value, self::TYPE, 9, '/^[A-Z0-9*@#]{8}[0-9]$/D', 'uppercase letters, digits, "*", "@", "#", then a digit');

        if (\ctype_digit($value) && !CusipCheckDigit::isValid($value)) {
            throw SecurityRules::fail(self::TYPE, 'has a wrong check digit');
        }

        return $value;
    }
}
