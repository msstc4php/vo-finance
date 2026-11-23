<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Committee on Uniform Securities Identification Procedures number, nine characters.
 *
 * The first eight are letters, digits or the private-placement characters `*`, `@`, `#`; the last is a digit.
 * Lowercase input is stored uppercase.
 *
 * The check digit is verified only for an all-digit CUSIP, because no reached CGS publication gives the value of
 * letters or of the private-placement characters in the sum. Any other CUSIP is checked for format only.
 *
 * @see https://www.cusip.com/pdf/news/CUSIP-ACommonLanguageForEfficientMarkets_2022.pdf CUSIP Global Services, "Modulus 10 Double Add Double"
 * @see https://www.cusip.com/pdf/fact_sheet/CGS_Private_Placement_Numbers.pdf CGS, the characters `*`, `@`, `#`
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "CUSIP, CUSIP Global Services"
 */
final class Cusip extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 9;

    private const string TYPE = 'CUSIP';

    /**
     * Builds an all-digit CUSIP from its first eight digits, computing the check digit. A body with letters or `*`, `@`,
     * `#` is refused: no reached CGS publication gives their values in the sum.
     *
     * @throws InvalidSecurityIdentifierException when the body is not eight digits
     */
    public static function fromParts(string $body): self
    {
        if (\preg_match('/^[0-9]{8}$/D', $body) !== 1) {
            throw SecurityRules::fail(self::TYPE, 'body must be 8 digits: no published check-digit values exist for letters or *, @, #');
        }

        return new self($body . CusipCheckDigit::compute($body));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape($value, self::TYPE, self::MAX_LENGTH, '/^[A-Z0-9*@#]{8}[0-9]$/D', 'uppercase letters, digits, "*", "@", "#", then a digit');

        if (\ctype_digit($value) && !CusipCheckDigit::isValid($value)) {
            throw SecurityRules::fail(self::TYPE, 'has a wrong check digit');
        }

        return $value;
    }
}
