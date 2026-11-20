<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
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
 * Builds a payment payload by its leading marker: `BCD` an {@see EpcQr}, `SPC` a {@see QrBill}, `ST0001` a {@see
 * Gost56042Payload}, `https://qr.nspk.ru/` an {@see SbpPaymentLink} and `upi://` an {@see UpiPaymentUri} (the last two
 * compared case-insensitively, as URI schemes and hosts are). The markers exclude one another and the EMVCo `000201`,
 * so a marked payload that its class refuses throws that class's exception. Any other payload is EMVCo: the factory
 * tries the national profiles the payload identifies (the Pix GUI `br.gov.bcb.pix` in a template 26 to 51, a
 * PromptPay AID in template 29 or 30, the DuitNow AID in template 26, a template 29 or 30 with country `KH` for KHQR,
 * the GUI `jp.or.paymentsjapan` for JPQR) and builds the one that accepts it. When the payload identifies profiles but
 * none accepts it, the first identified profile's exception is thrown. Only a payload that identifies no built profile
 * (one that is not built, such as SGQR or QRIS) gives {@see MerchantPresentedQr}. A payload that fits several
 * profiles, which a code carrying several arrangements can, throws {@see AmbiguousValueException} listing them; the
 * factory never picks silently. `PaymentPayload` names every class it builds.
 *
 * @phpstan-type PaymentPayload (
 *     EpcQr|QrBill|Gost56042Payload|SbpPaymentLink|UpiPaymentUri
 *     |PixBrCode|PromptPayQr|DuitNowQr|KhqrCode|JpqrCode|MerchantPresentedQr
 * )
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

    private const string PIX_GUI = 'br.gov.bcb.pix';

    private const string PROMPTPAY_CREDIT_TRANSFER_AID = 'A000000677010111';

    /** @var non-empty-list<non-empty-string> */
    private const array PROMPTPAY_BILL_PAYMENT_AIDS = ['A000000677010112', 'A000000677012006'];

    private const string DUITNOW_AID = 'A0000006150001';

    private const string JPQR_GUI = 'jp.or.paymentsjapan';

    /**
     * Builds from scanned bytes: an EPC or GOST payload goes through its own `fromBytes()`, which transcodes a declared
     * single-byte character set; any other payload must be UTF-8 and is handled as by {@see fromString()}.
     *
     * @return PaymentPayload
     *
     * @throws AmbiguousValueException when several profiles accept the payload
     * @throws InvalidPaymentQrException when no class accepts it, or other bytes are not UTF-8
     * @throws LogicException without ext-mbstring, for an EPC or GOST payload in a single-byte character set
     */
    public static function fromBytes(string $bytes): EpcQr|QrBill|Gost56042Payload|SbpPaymentLink|UpiPaymentUri|PixBrCode|PromptPayQr|DuitNowQr|KhqrCode|JpqrCode|MerchantPresentedQr
    {
        if (\str_starts_with($bytes, 'BCD')) {
            return EpcQr::fromBytes($bytes);
        }

        if (\str_starts_with($bytes, 'ST0001')) {
            return Gost56042Payload::fromBytes($bytes);
        }

        if (\preg_match('//u', $bytes) !== 1) {
            throw new InvalidPaymentQrException('A payment payload other than EPC or GOST must be UTF-8.');
        }

        return self::fromString($bytes);
    }

    /**
     * @return PaymentPayload
     *
     * @throws AmbiguousValueException when several profiles accept the payload
     * @throws InvalidPaymentQrException when the marked class, every identified profile or the generic format does not
     *                                   accept it
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

        $identified = self::identifiedProfiles($payload);
        if ($identified === []) {
            return new MerchantPresentedQr($payload);
        }

        $matches = [];
        $refusal = null;
        foreach ($identified as $class) {
            try {
                $matches[] = new $class($payload);
            } catch (InvalidPaymentQrException $e) {
                $refusal ??= $e;
            }
        }

        if ($matches === []) {
            \assert($refusal instanceof InvalidPaymentQrException);

            throw $refusal;
        }

        return match (\count($matches)) {
            1 => $matches[0],
            default => throw new AmbiguousValueException('payment QR profile', self::classesOf($matches)),
        };
    }

    /**
     * The profiles whose marker the payload carries, in {@see PROFILES} order; empty when the payload is too large or
     * not TLV, which the generic format then refuses.
     *
     * @return list<class-string<PixBrCode|PromptPayQr|DuitNowQr|KhqrCode|JpqrCode>>
     */
    private static function identifiedProfiles(string $payload): array
    {
        try {
            PayloadBound::ensure($payload, 'PaymentQrFactory');
            $objects = EmvcoTlv::parse($payload);
        } catch (InvalidPaymentQrException) {
            return [];
        }

        $country = null;
        $templates = [];
        foreach ($objects as $object) {
            if ($object->id === '58') {
                $country = $object->value;
            }

            $gui = self::guiOf($object);
            if ($gui !== null) {
                $templates[] = [$object->id, $gui];
            }
        }

        $marked = [];
        foreach ($templates as [$id, $gui]) {
            $marked[] = match (true) {
                \strtolower($gui) === self::PIX_GUI => PixBrCode::class,
                \strtolower($gui) === self::JPQR_GUI => JpqrCode::class,
                $id === '26' && \strtoupper($gui) === self::DUITNOW_AID => DuitNowQr::class,
                \in_array(\strtoupper($gui), self::promptPayAids($id), true) => PromptPayQr::class,
                default => null,
            };
            if (($id === '29' || $id === '30') && $country === 'KH') {
                $marked[] = KhqrCode::class;
            }
        }

        $identified = [];
        foreach (self::PROFILES as $class) {
            if (\in_array($class, $marked, true)) {
                $identified[] = $class;
            }
        }

        return $identified;
    }

    /**
     * @return list<non-empty-string>
     */
    private static function promptPayAids(string $templateId): array
    {
        return match ($templateId) {
            '29' => [self::PROMPTPAY_CREDIT_TRANSFER_AID],
            '30' => self::PROMPTPAY_BILL_PAYMENT_AIDS,
            default => [],
        };
    }

    /**
     * The GUI (sub-ID 00) of a merchant account template 26 to 51; null for any other object or a template that is not
     * TLV.
     */
    private static function guiOf(DataObject $object): ?string
    {
        if ($object->id < '26' || $object->id > '51') {
            return null;
        }

        try {
            $subFields = EmvcoTlv::parse($object->value);
        } catch (InvalidPaymentQrException) {
            return null;
        }

        foreach ($subFields as $subField) {
            if ($subField->id === '00') {
                return $subField->value;
            }
        }

        return null;
    }

    /**
     * @param non-empty-list<ValueObject> $matches
     *
     * @return non-empty-list<class-string<ValueObject>>
     */
    private static function classesOf(array $matches): array
    {
        return \array_map(static fn (ValueObject $match): string => $match::class, $matches);
    }
}
