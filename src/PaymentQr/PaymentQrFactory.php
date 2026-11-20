<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;

/**
 * Builds the national profile that accepts an EMVCo merchant-presented payload. A payload that no profile accepts but
 * the generic format does (a profile that is not built, such as SGQR or QRIS) gives {@see MerchantPresentedQr}. A payload
 * that fits several profiles, which a code carrying several arrangements can, throws {@see AmbiguousValueException}
 * listing them; the factory never picks silently.
 */
final class PaymentQrFactory
{
    /** @var non-empty-list<class-string<PixBrCode|PromptPayQr|DuitNowQr|KhqrCode|JpqrCode>> */
    private const array PROFILES = [
        PixBrCode::class,
        PromptPayQr::class,
        DuitNowQr::class,
        KhqrCode::class,
        JpqrCode::class,
    ];

    /**
     * @throws AmbiguousValueException when several profiles accept the payload
     * @throws InvalidPaymentQrException when the generic format does not accept it either
     */
    public static function fromString(string $payload): PixBrCode|PromptPayQr|DuitNowQr|KhqrCode|JpqrCode|MerchantPresentedQr
    {
        $matches = [];
        foreach (self::PROFILES as $class) {
            $candidate = $class::tryFrom($payload);
            if ($candidate !== null) {
                $matches[] = $candidate;
            }
        }

        return match (\count($matches)) {
            0 => new MerchantPresentedQr($payload),
            1 => $matches[0],
            default => throw new AmbiguousValueException('payment QR profile', \array_map(static fn (ValueObject $match): string => $match::class, $matches)),
        };
    }
}
