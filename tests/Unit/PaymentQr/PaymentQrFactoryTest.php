<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentQrFactory;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayProxyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentQrFactory::class)]
#[UsesClass(PixBrCode::class)]
#[UsesClass(PromptPayQr::class)]
#[UsesClass(PromptPayProxyType::class)]
#[UsesClass(DuitNowQr::class)]
#[UsesClass(KhqrCode::class)]
#[UsesClass(JpqrCode::class)]
#[UsesClass(ProfilePayload::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoMask::class)]
#[UsesClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(InvalidPaymentQrException::class)]
final class PaymentQrFactoryTest extends TestCase
{
    use BuildsPayloads;

    // BCB, Manual de Padroes para Iniciacao do Pix 2.10.0, section 2.6.3.
    private const string PIX = '00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-4266554400005204000053039865802BR5913Fulano de Tal6008BRASILIA62070503***63041D3D';

    // PayNet, DuitNow QR merchant-presented mode 1.5, example 1.
    private const string DUITNOW = '00020201021126410014A000000615000101065016640209123456789520499995303458540510.005802MY5909QRCSDNBHD6005BANGI6304343F';

    // EMVCo QRCPS-MPM 1.1, Annex B.7 (read via a third-party copy): a generic payload with no national profile.
    private const string EMVCO_ANNEX_B7 = '00020101021229300012D156000000000510A93FO3230Q31280012D15600000001030812345678520441115802CN'
        . '5914BEST TRANSPORT6007BEIJING64200002ZH0104最佳运输0202北京540523.7253031565502016233030412340603***0708A60086670902ME'
        . '91320016A0112233449988770708123456786304A13A';

    public function testAPixPayloadGivesPixBrCode(): void
    {
        self::assertInstanceOf(PixBrCode::class, PaymentQrFactory::fromString(self::PIX));
    }

    public function testAPromptPayPayloadGivesPromptPayQr(): void
    {
        self::assertInstanceOf(PromptPayQr::class, PaymentQrFactory::fromString($this->promptPay()));
    }

    public function testADuitNowPayloadGivesDuitNowQr(): void
    {
        self::assertInstanceOf(DuitNowQr::class, PaymentQrFactory::fromString(self::DUITNOW));
    }

    public function testAKhqrPayloadGivesKhqrCode(): void
    {
        // NBC Bakong, KHQR Content Guideline 1.3, page 04: tag 29 solo merchant, static.
        $payload = '00020101021129200016test_bakong@devb5204599953031165802KH5917Lucky Supermarket6010Phnom Penh9917001316339210247786304102F';

        self::assertInstanceOf(KhqrCode::class, PaymentQrFactory::fromString($payload));
    }

    public function testAJpqrPayloadGivesJpqrCode(): void
    {
        // Synthetic, from Payments Japan MPM guideline 2.0 table 2.1-1.
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('01', '11'),
            self::tlv('26', self::tlv('00', 'jp.or.paymentsjapan') . self::tlv('01', '1234567890123') . self::tlv('02', '0001') . self::tlv('03', '000002') . self::tlv('04', '000003')),
            self::tlv('52', '5812'),
            self::tlv('53', '392'),
            self::tlv('58', 'JP'),
            self::tlv('59', 'CASHLESS CAFE'),
            self::tlv('60', 'MINATO-KU'),
            self::tlv('61', '1050001'),
            self::tlv('64', self::tlv('00', 'JA') . self::tlv('01', 'キャッシュレス')),
        );

        self::assertInstanceOf(JpqrCode::class, PaymentQrFactory::fromString($payload));
    }

    public function testAThaiQrWithEveryRootObjectIsNotTakenForKhqr(): void
    {
        // Synthetic: KHQR and Thai QR both use tags 29 and 30; the country tells them apart.
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('01', '11'),
            self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')),
            self::tlv('52', '5999'),
            self::tlv('53', '764'),
            self::tlv('58', 'TH'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'Bangkok'),
        );

        self::assertInstanceOf(PromptPayQr::class, PaymentQrFactory::fromString($payload));
    }

    public function testACambodianCodeCarryingPromptPayIsAmbiguous(): void
    {
        // Synthetic: a Bakong template (30) next to a PromptPay credit transfer (29), country KH.
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('01', '11'),
            self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')),
            self::tlv('30', self::tlv('00', 'shop@bank') . self::tlv('01', '42')),
            self::tlv('52', '5999'),
            self::tlv('53', '116'),
            self::tlv('58', 'KH'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'Phnom Penh'),
        );

        try {
            PaymentQrFactory::fromString($payload);
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousValueException $e) {
            self::assertSame([PromptPayQr::class, KhqrCode::class], $e->candidates());
        }
    }

    public function testABrCodeCarryingJpqrIsAmbiguous(): void
    {
        // Synthetic: a Pix template next to a JPQR template, with the objects both require.
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('01', '11'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'fulano@example.com')),
            self::tlv('27', self::tlv('00', 'jp.or.paymentsjapan') . self::tlv('01', '1234567890123') . self::tlv('02', '0001') . self::tlv('03', '000002') . self::tlv('04', '000003')),
            self::tlv('52', '0000'),
            self::tlv('53', '986'),
            self::tlv('58', 'BR'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'BRASILIA'),
            self::tlv('61', '70074900'),
            self::tlv('62', self::tlv('05', '***')),
            self::tlv('64', self::tlv('00', 'JA') . self::tlv('01', 'Shop')),
        );

        try {
            PaymentQrFactory::fromString($payload);
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousValueException $e) {
            self::assertSame([PixBrCode::class, JpqrCode::class], $e->candidates());
        }
    }

    public function testAGenericEmvcoPayloadGivesMerchantPresentedQr(): void
    {
        $qr = PaymentQrFactory::fromString(self::EMVCO_ANNEX_B7);

        self::assertInstanceOf(MerchantPresentedQr::class, $qr);
        self::assertSame(self::EMVCO_ANNEX_B7, (string) $qr);
    }

    public function testAnUnbuiltNationalProfileParsesAsTheGenericPayload(): void
    {
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'SG.PAYNOW') . self::tlv('01', '2') . self::tlv('02', '+6591234567')),
            self::tlv('52', '0000'),
            self::tlv('53', '702'),
            self::tlv('58', 'SG'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'Singapore'),
        );

        self::assertInstanceOf(MerchantPresentedQr::class, PaymentQrFactory::fromString($payload));
    }

    public function testAPayloadThatFitsSeveralProfilesIsAmbiguous(): void
    {
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'fulano@example.com')),
            self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')),
            self::tlv('52', '0000'),
            self::tlv('53', '986'),
            self::tlv('58', 'BR'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'BRASILIA'),
            self::tlv('62', self::tlv('05', '***')),
        );

        try {
            PaymentQrFactory::fromString($payload);
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousValueException $e) {
            self::assertSame([PixBrCode::class, PromptPayQr::class], $e->candidates());
            self::assertStringNotContainsString('fulano', $e->getMessage());
        }
    }

    public function testAValueThatIsNoPaymentQrIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        PaymentQrFactory::fromString('hello');
    }

    public function testAWrongCrcIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        PaymentQrFactory::fromString(\substr(self::PIX, 0, -4) . '0000');
    }

    private function promptPay(): string
    {
        return self::sealed(
            self::tlv('00', '01'),
            self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')),
            self::tlv('53', '764'),
            self::tlv('58', 'TH'),
            self::tlv('59', 'Shop'),
        );
    }
}
