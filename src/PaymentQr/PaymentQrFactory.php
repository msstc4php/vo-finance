<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Epc\EpcQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\India\UpiPaymentUri;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\Gost56042Payload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpPaymentLink;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBill;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;

/**
 * Builds a payment payload by its leading marker: `BCD` an {@see EpcQr}, `SPC` a {@see QrBill}, `ST0001` a
 * {@see Gost56042Payload}, `https://qr.nspk.ru/` an {@see SbpPaymentLink} and `upi://` an {@see UpiPaymentUri} (the last
 * two compared case-insensitively, as URI schemes and hosts are). The markers exclude one another and the EMVCo
 * `000201`, so a marked payload that its class refuses throws that class's exception. Any other payload is EMVCo: the
 * factory builds the national profile that accepts an EMVCo merchant-presented payload. A payload that no profile accepts but
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
     * @throws InvalidPaymentQrException when the marked class or the generic format does not accept it
     */
    public static function fromString(string $payload): EpcQr|QrBill|Gost56042Payload|SbpPaymentLink|UpiPaymentUri|PixBrCode|PromptPayQr|DuitNowQr|KhqrCode|JpqrCode|MerchantPresentedQr
    {
        $marked = match (true) {
            \str_starts_with($payload, 'BCD') => new EpcQr($payload),
            \str_starts_with($payload, 'SPC') => new QrBill($payload),
            \str_starts_with($payload, 'ST0001') => new Gost56042Payload($payload),
            \strncasecmp($payload, 'https://qr.nspk.ru/', 19) === 0 => new SbpPaymentLink($payload),
            \strncasecmp($payload, 'upi://', 6) === 0 => new UpiPaymentUri($payload),
            default => null,
        };
        if ($marked !== null) {
            return $marked;
        }

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
