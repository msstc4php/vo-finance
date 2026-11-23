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
 * one `/`. Lowercase input, ASCII or ISO 8859-1, is stored uppercase.
 *
 * The issuer name is "at most 15" except for collective investment vehicles and OTC derivatives, where it may run to
 * the total limit, so only the total is enforced for it. The guidelines' list of permitted special characters was not
 * reached, so any printable ASCII punctuation is accepted.
 *
 * @see https://anna-web.org/wp-content/uploads/2026/06/FISN-Guidelines-Version-1-Jun-2026.pdf ANNA, FISN Guidelines, June 2026, section 3.2
 * @see docs/sources/FINANCE.md "FISN, ISO 18774"
 */
final class Fisn extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The longest stored value, in characters; frozen for 1.x. */
    public const int MAX_LENGTH = 35;

    private const string TYPE = 'FISN';

    private const int MAX_DESCRIPTION = 19;

    private const string CHARACTER = '[ -.0-`{-~\x{C0}-\x{D6}\x{D8}-\x{DE}]';

    /**
     * @return non-empty-string
     */
    public function issuerName(): string
    {
        $name = \explode('/', $this->value, 2)[0];
        \assert($name !== '');

        return $name;
    }

    /**
     * @return non-empty-string
     */
    public function description(): string
    {
        $description = \explode('/', $this->value, 2)[1] ?? '';
        \assert($description !== '');

        return $description;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtr(\strtoupper($value), $this->lowercaseLatin1());
        if (\preg_match_all('/./su', $value) > self::MAX_LENGTH) {
            throw SecurityRules::fail(self::TYPE, \sprintf('must have at most %d characters', self::MAX_LENGTH));
        }

        $shape = '/^' . self::CHARACTER . '+\/' . self::CHARACTER . '+$/Du';
        if (\preg_match($shape, $value) !== 1 || \str_contains($value, '  ')) {
            $rule = 'must be an issuer name, "/" and a description in uppercase with single blanks';

            throw SecurityRules::fail(self::TYPE, $rule);
        }

        if (\preg_match_all('/./su', \explode('/', $value, 2)[1]) > self::MAX_DESCRIPTION) {
            $rule = \sprintf('description must have at most %d characters', self::MAX_DESCRIPTION);

            throw SecurityRules::fail(self::TYPE, $rule);
        }

        return $value;
    }

    /**
     * UTF-8 lowercase ISO 8859-1 letters (U+00E0 to U+00FE without U+00F7) to their capitals (U+00C0 to U+00DE).
     *
     * @return array<string, string>
     */
    private function lowercaseLatin1(): array
    {
        $map = [];
        for ($byte = 0xA0; $byte <= 0xBE; $byte++) {
            if ($byte !== 0xB7) {
                $map["\xC3" . \chr($byte)] = "\xC3" . \chr($byte - 0x20);
            }
        }

        return $map;
    }
}
