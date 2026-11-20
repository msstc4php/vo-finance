<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * Turns the bytes of a payload declared in a single-byte character set into UTF-8 with ext-mbstring, which the
 * `fromBytes()` constructors need (composer `suggest`).
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
     */
    public static function toUtf8(string $bytes, string $encoding, string $type): string
    {
        if (!\function_exists('mb_convert_encoding')) {
            throw new LogicException(\sprintf('%s::fromBytes() requires ext-mbstring.', $type));
        }

        if (!\mb_check_encoding($bytes, $encoding)) {
            throw new InvalidPaymentQrException(\sprintf('%s bytes are not valid in the declared character set.', $type));
        }

        $utf8 = \mb_convert_encoding($bytes, 'UTF-8', $encoding);
        if ($utf8 === false) {
            throw new InvalidPaymentQrException(\sprintf('%s bytes are not valid in the declared character set.', $type));
        }

        return $utf8;
    }
}
