<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * Checks shared by the national profiles. A failure names the profile and the rule, never the payload.
 *
 * @internal
 */
final class ProfileRule
{
    /**
     * @param non-empty-string $profile
     * @param non-empty-string $requirement
     *
     * @throws InvalidPaymentQrException when the condition does not hold
     */
    public static function ensure(bool $condition, string $profile, string $requirement): void
    {
        if (!$condition) {
            throw new InvalidPaymentQrException(\sprintf('%s requires %s.', $profile, $requirement));
        }
    }

    /**
     * Whether a present value has at most `$max` characters; an absent value passes.
     *
     * @param positive-int $max
     */
    public static function atMost(?string $value, int $max): bool
    {
        return $value === null || EmvcoTlv::length($value) <= $max;
    }
}
