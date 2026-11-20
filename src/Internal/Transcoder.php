<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * Turns the bytes of a payload declared in a single-byte character set into UTF-8 with ext-mbstring, which the
 * `fromBytes()` constructors need (composer `suggest`). `$mbstring` overrides the extension check, for tests.
 *
 * @internal
 */
final class Transcoder
{
    /**
     * @param non-empty-string $encoding an mbstring encoding name
     * @param non-empty-string $type
     *
     * @throws InvalidPaymentQrException when the bytes are not valid in the declared character set
     * @throws LogicException without ext-mbstring
     */
    public static function toUtf8(string $bytes, string $encoding, string $type, ?bool $mbstring = null): string
    {
        if (!($mbstring ?? self::hasMbstring())) {
            throw new LogicException(\sprintf('%s::fromBytes() requires ext-mbstring.', $type));
        }

        $utf8 = \mb_check_encoding($bytes, $encoding) ? \mb_convert_encoding($bytes, 'UTF-8', $encoding) : false;
        if ($utf8 === false) {
            throw new InvalidPaymentQrException(\sprintf('%s bytes are not valid in the declared character set.', $type));
        }

        return $utf8;
    }

    private static function hasMbstring(): bool
    {
        return \function_exists('mb_convert_encoding');
    }
}
