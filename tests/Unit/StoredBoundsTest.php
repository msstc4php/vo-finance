<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit;

use MSSTC4PHP\ValueObject\Financial\Card\PaymentCardNumber;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Epc\EpcQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\India\UpiPaymentUri;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentPayload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\Gost56042Payload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpPaymentLink;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBill;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use MSSTC4PHP\ValueObject\Financial\Reference\QrReference;
use MSSTC4PHP\ValueObject\Financial\Security\Cfi;
use MSSTC4PHP\ValueObject\Financial\Security\Cusip;
use MSSTC4PHP\ValueObject\Financial\Security\Figi;
use MSSTC4PHP\ValueObject\Financial\Security\Fisn;
use MSSTC4PHP\ValueObject\Financial\Security\Isin;
use MSSTC4PHP\ValueObject\Financial\Security\Lei;
use MSSTC4PHP\ValueObject\Financial\Security\Mic;
use MSSTC4PHP\ValueObject\Financial\Security\SecurityIdentifier;
use MSSTC4PHP\ValueObject\Financial\Security\Sedol;
use MSSTC4PHP\ValueObject\Financial\Security\Upi;
use MSSTC4PHP\ValueObject\Financial\Security\Valor;
use MSSTC4PHP\ValueObject\Financial\Security\Wkn;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The public bounds are the stored-value contract the bridges size their columns by: each one equals the longest value
 * the class accepts, apart from Valor, whose bound leaves room above the inferred nine characters.
 */
#[CoversNothing]
final class StoredBoundsTest extends TestCase
{
    /**
     * @return iterable<string, array{positive-int, AbstractStringValueObject}>
     */
    public static function longest(): iterable
    {
        yield 'ISIN' => [Isin::MAX_LENGTH, new Isin('US0378331005')];
        yield 'LEI' => [Lei::MAX_LENGTH, new Lei('F50EOCWSQFAUVO9Q8Z97')];
        yield 'UPI' => [Upi::MAX_LENGTH, new Upi('QZ4TM8GJN9XW')];
        yield 'CFI' => [Cfi::MAX_LENGTH, new Cfi('ESVUFR')];
        yield 'FISN' => [Fisn::MAX_LENGTH, new Fisn('ISSUERISSUERISSUERISS/DESCRIPTION01')];
        yield 'MIC' => [Mic::MAX_LENGTH, new Mic('XNAS')];
        yield 'FIGI' => [Figi::MAX_LENGTH, new Figi('BBG000BLNQ16')];
        yield 'CUSIP' => [Cusip::MAX_LENGTH, new Cusip('037833100')];
        yield 'SEDOL' => [Sedol::MAX_LENGTH, new Sedol('B1F3M59')];
        yield 'WKN' => [Wkn::MAX_LENGTH, new Wkn('A0D9PT')];
        yield 'payment card number' => [PaymentCardNumber::MAX_LENGTH, new PaymentCardNumber('6011000000000000001')];
        yield 'QR reference' => [QrReference::MAX_LENGTH, new QrReference('210000000003139471430009017')];
        yield 'creditor reference' => [CreditorReference::MAX_LENGTH, new CreditorReference('RF40123456789012345678901')];
    }

    #[DataProvider('longest')]
    public function testTheBoundIsTheLongestAcceptedValue(int $bound, AbstractStringValueObject $value): void
    {
        self::assertSame($bound, \mb_strlen((string) $value));
    }

    public function testTheValorBoundLeavesRoomAboveTheAcceptedNineCharacters(): void
    {
        self::assertSame('123456789', (string) new Valor('123456789'));

        $this->expectException(InvalidSecurityIdentifierException::class);
        new Valor('1234567890');
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function securityIdentifiers(): iterable
    {
        foreach ([Isin::class, Lei::class, Upi::class, Cfi::class, Fisn::class, Mic::class, Figi::class, Cusip::class, Sedol::class, Wkn::class, Valor::class] as $class) {
            yield $class => [$class];
        }
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('securityIdentifiers')]
    public function testEverySecurityIdentifierImplementsTheInterface(string $class): void
    {
        self::assertTrue(\is_subclass_of($class, SecurityIdentifier::class));
    }

    /**
     * @return iterable<string, array{class-string, int}>
     */
    public static function paymentPayloads(): iterable
    {
        yield 'EMVCo' => [MerchantPresentedQr::class, MerchantPresentedQr::MAX_LENGTH];
        yield 'Pix' => [PixBrCode::class, PixBrCode::MAX_LENGTH];
        yield 'PromptPay' => [PromptPayQr::class, PromptPayQr::MAX_LENGTH];
        yield 'DuitNow' => [DuitNowQr::class, DuitNowQr::MAX_LENGTH];
        yield 'KHQR' => [KhqrCode::class, KhqrCode::MAX_LENGTH];
        yield 'JPQR' => [JpqrCode::class, JpqrCode::MAX_LENGTH];
        yield 'EPC' => [EpcQr::class, EpcQr::MAX_LENGTH];
        yield 'QR-bill' => [QrBill::class, QrBill::MAX_LENGTH];
        yield 'GOST' => [Gost56042Payload::class, Gost56042Payload::MAX_LENGTH];
        yield 'SBP' => [SbpPaymentLink::class, SbpPaymentLink::MAX_LENGTH];
        yield 'UPI' => [UpiPaymentUri::class, UpiPaymentUri::MAX_LENGTH];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('paymentPayloads')]
    public function testEveryPaymentPayloadImplementsTheInterfaceAndHasABound(string $class, int $bound): void
    {
        self::assertTrue(\is_subclass_of($class, PaymentPayload::class));
        self::assertContains($bound, [331, 512, 997, 7089]);
    }
}
