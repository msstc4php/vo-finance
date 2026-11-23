<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\CfiGroups;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Classification of Financial Instruments (ISO 10962): six uppercase letters, `X` meaning "not applicable". Any six
 * letters are accepted; {@see category()} and {@see group()} decode from the SIX list and are null for a letter the
 * list does not know (the registry is used to decode, never to refuse).
 *
 * @see https://cdn.standards.iteh.ai/samples/81140/575a3327c8d34576ad2932719988f966/ISO-10962-2021.pdf ISO 10962:2021, clause 5 (preview)
 * @see https://www.six-group.com/dam/download/financial-information/data-center/cfi/cfi-20210507-current.xlsx SIX, ISO 10962 CFI list
 * @see docs/sources/FINANCE.md "CFI, ISO 10962"
 */
final class Cfi extends AbstractStringValueObject implements SecurityIdentifier
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 6;

    public function category(): ?CfiCategory
    {
        return CfiCategory::tryFrom($this->value[0]);
    }

    /**
     * @return non-empty-string|null the name of the group, for example "Common/ordinary shares"
     */
    public function group(): ?string
    {
        return CfiGroups::NAMES[\substr($this->value, 0, 2)] ?? null;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $value = \strtoupper($value);

        SecurityRules::shape($value, 'CFI', self::MAX_LENGTH, '/^[A-Z]{6}$/D', 'uppercase letters');

        return $value;
    }
}
