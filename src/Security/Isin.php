<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\IsinCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * International Securities Identification Number (ISO 6166): a two-letter prefix, a nine-character basic number (NSIN)
 * and a check digit. Lowercase input is stored uppercase. The prefix is never refused for being outside ISO 3166
 * (`XS`, `EU`, `EZ`, `XT` and others exist); {@see countryCode()} is null for them.
 *
 * {@see cusip()} reads the NSIN of a US or CA ISIN as a CUSIP, which CGS states ("A CUSIP or CINS identifier will
 * always appear in positions 3-11 of a CGS ISIN"). {@see sedol()} reads the NSIN of a GB or IE ISIN, behind two zeros, as a
 * SEDOL; no official publication reached states that, so it is a convenience that returns null rather than refusing
 * anything.
 *
 * @see https://anna-web.org/identifiers/ ANNA, the ISIN structure and its "Double-Add-Double" check digit
 * @see https://www.set.or.th/en/tsd/information/isin-calculate Stock Exchange of Thailand, worked check-digit examples
 * @see https://www.cusip.com/identifiers.html CUSIP Global Services, the CUSIP inside a US or CA ISIN
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "ISIN, ISO 6166"
 */
final class Isin extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 12;

    private const string TYPE = 'ISIN';

    /**
     * The NSIN is zero-padded on the left to nine characters, as ISO 6166 clause 4.1 says for national numbers.
     */
    public static function fromNsin(string $prefix, string $nsin): self
    {
        if ($nsin === '' || \strlen($nsin) > 9) {
            throw SecurityRules::fail(self::TYPE, 'NSIN must have 1 to 9 characters');
        }

        $body = \strtoupper($prefix) . \str_pad(\strtoupper($nsin), 9, '0', STR_PAD_LEFT);
        SecurityRules::shape($body, self::TYPE, 11, '/^[A-Z]{2}[A-Z0-9]{9}$/D', 'two letters, then nine letters or digits');

        return new self($body . IsinCheckDigit::compute($body));
    }

    /**
     * @return non-empty-string two letters
     */
    public function prefix(): string
    {
        $prefix = \substr($this->value, 0, 2);
        \assert($prefix !== '');

        return $prefix;
    }

    /**
     * @return non-empty-string nine characters
     */
    public function nsin(): string
    {
        $nsin = \substr($this->value, 2, 9);
        \assert($nsin !== '');

        return $nsin;
    }

    public function countryCode(): ?CountryCode
    {
        return CountryCode::tryFrom($this->prefix());
    }

    public function cusip(): ?Cusip
    {
        return \in_array($this->prefix(), ['US', 'CA'], true) ? Cusip::tryFrom($this->nsin()) : null;
    }

    public function sedol(): ?Sedol
    {
        return \in_array($this->prefix(), ['GB', 'IE'], true) && \str_starts_with($this->nsin(), '00')
            ? Sedol::tryFrom(\substr($this->nsin(), 2))
            : null;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);
        SecurityRules::shape($value, self::TYPE, self::MAX_LENGTH, '/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/D', 'two letters, nine letters or digits, a digit');

        if (!IsinCheckDigit::isValid($value)) {
            throw SecurityRules::fail(self::TYPE, 'has a wrong check digit');
        }

        return $value;
    }
}
