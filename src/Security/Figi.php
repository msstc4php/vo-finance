<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\FigiCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Financial Instrument Global Identifier (OMG FIGI 1.0): two consonants other than the prefixes BS, BM, GG, GB, GH, KY
 * and VG, `G`, eight consonants or digits, and a check digit. The specification lists five excluded prefixes in its
 * conformance table and seven in section 6.1 and its regular expression; the seven are refused.
 *
 * @see https://www.omg.org/spec/FIGI/1.0 OMG Financial Instrument Global Identifier 1.0 (formal/2015-12-01), section 6.1
 * @see docs/sources/FINANCE.md "FIGI, OMG"
 */
final class Figi extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 12;

    private const string TYPE = 'FIGI';

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape(
            $value,
            self::TYPE,
            self::MAX_LENGTH,
            '/^(?!BS|BM|GG|GB|GH|KY|VG)[BCDFGHJKLMNPQRSTVWXYZ]{2}G[BCDFGHJKLMNPQRSTVWXYZ0-9]{8}[0-9]$/D',
            'two consonants outside the excluded prefixes, G, eight consonants or digits, a digit',
        );

        if (!FigiCheckDigit::isValid($value)) {
            throw SecurityRules::fail(self::TYPE, 'has a wrong check digit');
        }

        return $value;
    }
}
