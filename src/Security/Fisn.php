<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Financial Instrument Short Name (ISO 18774): issuer name, `/`, instrument description, as UTF-8. Enforced from the
 * ANNA FISN Guidelines: at most 35 characters including the delimiter, a description of at most 19, uppercase only
 * (ASCII capitals, digits, punctuation and the ISO 8859-1 capitals `À` to `Þ` without `×`), no double blank, exactly
 * one `/`. Lowercase ASCII input is stored uppercase. The issuer name is "at most 15" except for collective investment
 * vehicles and OTC derivatives, where it may run to the total limit, so only the total is enforced for it. The
 * guidelines' list of permitted special characters was not reached, so any printable ASCII punctuation is accepted.
 *
 * @see https://anna-web.org/wp-content/uploads/2026/06/FISN-Guidelines-Version-1-Jun-2026.pdf ANNA, FISN Guidelines, June 2026, section 3.2
 * @see docs/sources/FINANCE.md "FISN, ISO 18774"
 */
final class Fisn extends AbstractStringValueObject
{
    private const string TYPE = 'FISN';

    private const int MAX_LENGTH = 35;

    private const int MAX_DESCRIPTION = 19;

    private const string CHARACTER = '[ -.0-`{-~\x{C0}-\x{D6}\x{D8}-\x{DE}]';

    public function issuerName(): string
    {
        return \explode('/', $this->value, 2)[0];
    }

    public function description(): string
    {
        return \explode('/', $this->value, 2)[1];
    }

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);
        $length = \preg_match_all('/./su', $value);

        if ($length > self::MAX_LENGTH) {
            throw SecurityRules::fail(self::TYPE, \sprintf('must have at most %d characters, got %d', self::MAX_LENGTH, $length));
        }

        if (\preg_match('/^' . self::CHARACTER . '+\/' . self::CHARACTER . '+$/Du', $value) !== 1 || \str_contains($value, '  ')) {
            throw SecurityRules::fail(self::TYPE, 'must be an issuer name, "/" and a description in uppercase with single blanks');
        }

        if (\preg_match_all('/./su', \explode('/', $value, 2)[1]) > self::MAX_DESCRIPTION) {
            throw SecurityRules::fail(self::TYPE, \sprintf('description must have at most %d characters', self::MAX_DESCRIPTION));
        }

        return $value;
    }
}
