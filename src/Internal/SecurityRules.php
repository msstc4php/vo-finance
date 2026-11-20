<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;

/**
 * Messages name the identifier and the reason, never the input.
 *
 * @internal
 */
final class SecurityRules
{
    /**
     * @param non-empty-string $type
     */
    public static function fail(string $type, string $reason): InvalidSecurityIdentifierException
    {
        return new InvalidSecurityIdentifierException(\sprintf('%s %s.', $type, $reason));
    }

    /**
     * @param non-empty-string $type
     * @param positive-int $length
     * @param non-empty-string $pattern PCRE of the whole value
     */
    public static function shape(string $value, string $type, int $length, string $pattern, string $charset): void
    {
        if (\strlen($value) !== $length) {
            throw self::fail($type, \sprintf('must have %d characters, got %d', $length, \strlen($value)));
        }

        if (\preg_match($pattern, $value) !== 1) {
            throw self::fail($type, \sprintf('has an invalid format (%s)', $charset));
        }
    }
}
