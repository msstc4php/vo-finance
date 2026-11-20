<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * The bound against abusive input every payment payload shares: 7089 characters, the largest content a QR code holds
 * (numeric mode, version 40-L). Not a rule of any payload's specification.
 *
 * @internal
 */
final class PayloadBound
{
    public const int MAX_CHARACTERS = 7089;

    /**
     * Text that is not UTF-8 passes; the payload's own check refuses it.
     *
     * @param non-empty-string $type the class name used in the message
     *
     * @throws InvalidPaymentQrException when the payload has more than {@see MAX_CHARACTERS} characters
     */
    public static function ensure(string $payload, string $type): void
    {
        if (\strlen($payload) > self::MAX_CHARACTERS * 4 || \preg_match_all('/./su', $payload) > self::MAX_CHARACTERS) {
            throw self::exceeded($type);
        }
    }

    /**
     * For bytes in a single-byte character set, checked before they are transcoded.
     *
     * @param non-empty-string $type the class name used in the message
     *
     * @throws InvalidPaymentQrException when the payload has more than {@see MAX_CHARACTERS} bytes
     */
    public static function ensureSingleByte(string $bytes, string $type): void
    {
        if (\strlen($bytes) > self::MAX_CHARACTERS) {
            throw self::exceeded($type);
        }
    }

    /**
     * @param non-empty-string $type
     */
    private static function exceeded(string $type): InvalidPaymentQrException
    {
        return new InvalidPaymentQrException(\sprintf('%s must not exceed %d characters.', $type, self::MAX_CHARACTERS));
    }
}
