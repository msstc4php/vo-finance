<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Internal\SedolCheckDigit;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Stock Exchange Daily Official List number: six digits or capital consonants and a check digit with the weights
 * 1, 3, 1, 7, 3, 9. A numeric first character is accepted, because legacy numeric SEDOLs stay in circulation.
 *
 * @see https://www.lseg.com/content/dam/lseg/en_us/documents/sedol/sedol-masterfile-service-and-technical-guide-v8.8.pdf SEDOL Masterfile Service and Technical Guide v8.8, section 8
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "SEDOL, London Stock Exchange Group"
 */
final class Sedol extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 7;

    private const string TYPE = 'SEDOL';

    /**
     * Builds the SEDOL from its first six characters, computing the check digit.
     *
     * @throws InvalidSecurityIdentifierException when the body is not six digits or capital consonants
     */
    public static function fromParts(string $body): self
    {
        $body = \strtoupper($body);
        if (\preg_match('/^[0-9BCDFGHJKLMNPQRSTVWXYZ]{6}$/D', $body) !== 1) {
            throw SecurityRules::fail(self::TYPE, 'body must be 6 digits or capital consonants');
        }

        return new self($body . SedolCheckDigit::compute($body));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape($value, self::TYPE, self::MAX_LENGTH, '/^[0-9BCDFGHJKLMNPQRSTVWXYZ]{6}[0-9]$/D', 'digits or capital consonants, then a digit');

        if (!SedolCheckDigit::isValid($value)) {
            throw SecurityRules::fail(self::TYPE, 'has a wrong check digit');
        }

        return $value;
    }
}
