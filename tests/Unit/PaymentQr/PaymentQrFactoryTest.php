<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso8859;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\Internal\RussianAccountKey;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\Internal\Transcoder;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Epc\EpcQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\India\UpiPaymentUri;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentQrFactory;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\Gost56042Payload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpPaymentLink;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBill;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayProxyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use MSSTC4PHP\ValueObject\Financial\Reference\QrReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
#[UsesClass(EpcQr::class)]
#[UsesClass(QrBill::class)]
#[UsesClass(Gost56042Payload::class)]
#[UsesClass(SbpPaymentLink::class)]
#[UsesClass(UpiPaymentUri::class)]
#[UsesClass(TextLines::class)]
#[UsesClass(QueryString::class)]
#[UsesClass(RussianAccountKey::class)]
#[UsesClass(Mask::class)]
#[UsesClass(Iso8859::class)]
#[UsesClass(Transcoder::class)]
#[UsesClass(QrReference::class)]
#[UsesClass(CreditorReference::class)]
#[UsesClass(Mod10Recursive::class)]
#[UsesClass(Iso7064Mod97::class)]
#[UsesClass(PayloadBound::class)]
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

    public function testAPixPayloadThatOnlyTheGenericFormatAcceptsGivesMerchantPresentedQr(): void
    {
        // BCB Pix manual example with a merchant name of 30 characters (BR Code manual: at most 25).
        $payload = \str_replace('5913Fulano de Tal', '5930' . \str_repeat('A', 30), \substr(self::PIX, 0, -4));
        $payload .= Crc16CcittFalse::compute($payload);
        self::assertNull(PixBrCode::tryFrom($payload));

        $qr = PaymentQrFactory::fromString($payload);

        self::assertInstanceOf(MerchantPresentedQr::class, $qr);
        self::assertSame($payload, (string) $qr);
    }

    public function testAPixPayloadThatTheGenericFormatRefusesToo(): void
    {
        // BCB Pix manual example without the merchant category code: the generic format needs it, Pix too.
        $payload = \str_replace('52040000', '', \substr(self::PIX, 0, -4));
        $payload .= Crc16CcittFalse::compute($payload);

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessageMatches('/^PixBrCode /');

        PaymentQrFactory::fromString($payload);
    }

    public function testADuitNowPayloadThatBreaksADuitNowRuleThrowsTheDuitNowRefusal(): void
    {
        // PayNet example 1 with currency 840 instead of 458.
        $payload = \str_replace('5303458', '5303840', \substr(self::DUITNOW, 0, -4));
        $payload .= Crc16CcittFalse::compute($payload);

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('DuitNowQr requires currency 458.');

        PaymentQrFactory::fromString($payload);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function identifiedButRefused(): iterable
    {
        $base = [self::tlv('00', '01'), self::tlv('52', '5999'), self::tlv('59', 'Shop'), self::tlv('60', 'City')];

        yield 'Pix GUI in uppercase, template 51' => [self::sealed(...$base, ...[self::tlv('51', self::tlv('00', 'BR.GOV.BCB.PIX') . self::tlv('01', 'k')), self::tlv('53', '840'), self::tlv('58', 'US')])];
        yield 'PromptPay credit transfer AID in lowercase' => [self::sealed(...$base, ...[self::tlv('29', self::tlv('00', 'a000000677010111') . self::tlv('01', '1')), self::tlv('53', '764'), self::tlv('58', 'TH')])];
        yield 'PromptPay bill payment AID' => [self::sealed(...$base, ...[self::tlv('30', self::tlv('00', 'A000000677012006') . self::tlv('01', '1')), self::tlv('53', '764'), self::tlv('58', 'TH')])];
        yield 'DuitNow AID in lowercase' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'a0000006150001') . self::tlv('01', '1')), self::tlv('53', '458'), self::tlv('58', 'MY')])];
        yield 'Bakong template 30 with country KH' => [self::sealed(...$base, ...[self::tlv('30', self::tlv('00', 'shop@bank')), self::tlv('53', '116'), self::tlv('58', 'KH'), self::tlv('54', '0')])];
        yield 'JPQR GUI in uppercase' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'JP.OR.PAYMENTSJAPAN') . self::tlv('01', '1')), self::tlv('53', '392'), self::tlv('58', 'JP')])];
    }

    /**
     * Every identified profile refuses, the generic format accepts: one candidate, so it is returned.
     */
    #[DataProvider('identifiedButRefused')]
    public function testAPayloadEveryIdentifiedProfileRefusesFallsBackToTheGenericFormat(string $payload): void
    {
        $qr = PaymentQrFactory::fromString($payload);

        self::assertInstanceOf(MerchantPresentedQr::class, $qr);
        self::assertSame($payload, (string) $qr);
    }

    /**
     * @return iterable<string, array{string, non-empty-string}>
     */
    public static function refusedByEveryCandidate(): iterable
    {
        // No merchant category code: the generic format and Pix, DuitNow, KHQR and JPQR need it; PromptPay is refused
        // for its proxy instead.
        $base = [self::tlv('00', '01'), self::tlv('59', 'Shop'), self::tlv('60', 'City')];

        yield 'Pix' => [self::sealed(...$base, ...[self::tlv('51', self::tlv('00', 'BR.GOV.BCB.PIX') . self::tlv('01', 'k')), self::tlv('53', '840'), self::tlv('58', 'US')]), 'PixBrCode'];
        yield 'PromptPay' => [self::sealed(...$base, ...[self::tlv('29', self::tlv('00', 'a000000677010111') . self::tlv('01', '1')), self::tlv('53', '764'), self::tlv('58', 'TH')]), 'PromptPayQr'];
        yield 'DuitNow' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'a0000006150001') . self::tlv('01', '1')), self::tlv('53', '458'), self::tlv('58', 'MY')]), 'DuitNowQr'];
        yield 'KHQR' => [self::sealed(...$base, ...[self::tlv('30', self::tlv('00', 'shop@bank')), self::tlv('53', '116'), self::tlv('58', 'KH'), self::tlv('54', '0')]), 'KhqrCode'];
        yield 'JPQR' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'JP.OR.PAYMENTSJAPAN') . self::tlv('01', '1')), self::tlv('53', '392'), self::tlv('58', 'JP')]), 'JpqrCode'];
    }

    /**
     * @param non-empty-string $profile
     */
    #[DataProvider('refusedByEveryCandidate')]
    public function testAPayloadTheGenericFormatRefusesTooThrowsTheFirstProfileRefusal(string $payload, string $profile): void
    {
        self::assertNull(MerchantPresentedQr::tryFrom($payload));

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessageMatches('/^' . $profile . ' /');

        PaymentQrFactory::fromString($payload);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function identifyingNoProfile(): iterable
    {
        $base = [self::tlv('00', '01'), self::tlv('52', '5999'), self::tlv('53', '840'), self::tlv('59', 'Shop'), self::tlv('60', 'City')];

        yield 'Pix GUI in a primitive account' => [self::sealed(...$base, ...[self::tlv('25', '0014br.gov.bcb.pix'), self::tlv('58', 'US')])];
        yield 'Pix GUI in a sub-field other than 00' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'other') . self::tlv('01', 'br.gov.bcb.pix')), self::tlv('58', 'US')])];
        yield 'PromptPay credit transfer AID in template 30' => [self::sealed(...$base, ...[self::tlv('30', self::tlv('00', 'A000000677010111')), self::tlv('58', 'US')])];
        yield 'PromptPay AID in template 31' => [self::sealed(...$base, ...[self::tlv('31', self::tlv('00', 'A000000677010112')), self::tlv('58', 'US')])];
        yield 'DuitNow AID in template 27' => [self::sealed(...$base, ...[self::tlv('27', self::tlv('00', 'A0000006150001')), self::tlv('58', 'US')])];
        yield 'template 29 outside Cambodia' => [self::sealed(...$base, ...[self::tlv('29', self::tlv('00', 'shop@bank')), self::tlv('58', 'US')])];
        yield 'template 31 in Cambodia' => [self::sealed(...$base, ...[self::tlv('31', self::tlv('00', 'shop@bank')), self::tlv('58', 'KH')])];
        yield 'template 52 is no account' => [self::sealed(self::tlv('00', '01'), self::tlv('26', self::tlv('00', 'x')), self::tlv('52', '5999'), self::tlv('53', '840'), self::tlv('58', 'US'), self::tlv('59', 'Shop'), self::tlv('60', 'City'))];
    }

    #[DataProvider('identifyingNoProfile')]
    public function testAPayloadIdentifyingNoProfileGivesTheGenericPayload(string $payload): void
    {
        self::assertInstanceOf(MerchantPresentedQr::class, PaymentQrFactory::fromString($payload));
    }

    /**
     * No category code, so the generic format refuses; a profile identified by mistake would throw its own refusal.
     *
     * @return iterable<string, array{string}>
     */
    public static function identifyingNoProfileAndRefusedByTheGenericFormat(): iterable
    {
        $base = [self::tlv('00', '01'), self::tlv('53', '840'), self::tlv('59', 'Shop'), self::tlv('60', 'City')];

        yield 'template 26 with another GUI' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'com.example')), self::tlv('58', 'US')])];
        yield 'template 26 in Cambodia' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'shop@bank')), self::tlv('58', 'KH')])];
        yield 'Pix GUI in template 62' => [self::sealed(...$base, ...[self::tlv('26', self::tlv('00', 'com.example')), self::tlv('58', 'BR'), self::tlv('62', self::tlv('00', 'br.gov.bcb.pix'))])];
    }

    #[DataProvider('identifyingNoProfileAndRefusedByTheGenericFormat')]
    public function testAPayloadIdentifyingNoProfileThrowsTheGenericRefusal(string $payload): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessageMatches('/^MerchantPresentedQr /');

        PaymentQrFactory::fromString($payload);
    }

    public function testAPromptPayBillPaymentGivesPromptPayQr(): void
    {
        // Synthetic, from the BOT guideline section 2.3, tag 30.
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('30', self::tlv('00', 'A000000677010112') . self::tlv('01', '010753600031507') . self::tlv('02', 'INV1')),
            self::tlv('53', '764'),
            self::tlv('58', 'TH'),
            self::tlv('59', 'Shop'),
        );

        self::assertInstanceOf(PromptPayQr::class, PaymentQrFactory::fromString($payload));
    }

    public function testATemplateThatIsNotTlvIdentifiesNoProfile(): void
    {
        $payload = self::sealed(self::tlv('00', '01'), self::tlv('26', '00'), self::tlv('52', '5999'), self::tlv('53', '840'), self::tlv('58', 'US'), self::tlv('59', 'Shop'), self::tlv('60', 'City'));

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('MerchantPresentedQr');

        PaymentQrFactory::fromString($payload);
    }

    public function testAnOversizedPayloadIsRefusedByTheGenericFormat(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('MerchantPresentedQr must not exceed 7089 characters.');

        PaymentQrFactory::fromString('000201' . \str_repeat('26180014br.gov.bcb.pix', 400));
    }

    public function testAProfileThatAcceptsWinsOverAnIdentifiedProfileThatRefuses(): void
    {
        // Synthetic: a valid Pix code that also carries a PromptPay bill payment template PromptPay refuses (no biller).
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'fulano@example.com')),
            self::tlv('30', self::tlv('00', 'A000000677010112')),
            self::tlv('52', '0000'),
            self::tlv('53', '986'),
            self::tlv('58', 'BR'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'BRASILIA'),
            self::tlv('62', self::tlv('05', '***')),
        );
        self::assertNull(PromptPayQr::tryFrom($payload));

        self::assertInstanceOf(PixBrCode::class, PaymentQrFactory::fromString($payload));
    }

    public function testTheFirstIdentifiedProfileRefusalIsThrown(): void
    {
        // Synthetic: Pix and JPQR templates, country US and no category code, which both refuse, the generic format too.
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'jp.or.paymentsjapan') . self::tlv('01', '1')),
            self::tlv('27', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'k')),
            self::tlv('53', '840'),
            self::tlv('58', 'US'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'City'),
        );

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessageMatches('/^PixBrCode /');

        PaymentQrFactory::fromString($payload);
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

    public function testAnEpcPayloadGivesEpcQr(): void
    {
        // EPC069-12 v3.0, section 2.3, example V1.
        $payload = "BCD\n001\n1\nSCT\nBHBLDEHHXXX\nFranz Mustermänn\nDE71110220330123456789\nEUR12.3\nGDDS\nRF18539007547034";

        self::assertInstanceOf(EpcQr::class, PaymentQrFactory::fromString($payload));
    }

    public function testASwissPayloadGivesQrBill(): void
    {
        // SIX IG QR-bill 2.3, Annex A, example 3.
        $payload = \implode("\r\n", [
            'SPC', '0200', '1', 'CH5204835012345671000', 'S', 'Muster Stiftung (sample foundation)', 'P.O. Box', '', '3001',
            'Bern', 'CH', '', '', '', '', '', '', '', '', 'CHF', '', '', '', '', '', '', '', 'NON', '', '', 'EPD',
        ]);

        self::assertInstanceOf(QrBill::class, PaymentQrFactory::fromString($payload));
    }

    public function testAGostPayloadGivesGost56042Payload(): void
    {
        // GOST R 56042-2014, Annex D, mandatory block, in UTF-8.
        $payload = 'ST00012|Name=ООО "Три кита"|PersonalAcc=40702810138250123017|BankName=ОАО "БАНК"|BIC=044525225|CorrespAcc=30101810400000000225';

        self::assertInstanceOf(Gost56042Payload::class, PaymentQrFactory::fromString($payload));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function sbpLinks(): iterable
    {
        // NSPK, "СБП API", the URL-based payload examples, and the hosts in another case.
        yield 'qr.nspk.ru' => ['https://qr.nspk.ru/AS10003P3RH0LJ2A9ROO038L6NT5RU1M?type=01&bank=000000000001&sum=10000&cur=RUB&crc=F3D0'];
        yield 'sub.nspk.ru' => ['https://sub.nspk.ru/AR50803R2RH0LJ2A9RTU038L6NT5RU1M?type=03&bank=000000000001&crc=F3D0'];
        yield 'b2b.cbrpay.ru' => ['https://b2b.cbrpay.ru/AO1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'qr.nspk.ru in capitals' => ['HTTPS://QR.NSPK.RU/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'sub.nspk.ru in capitals' => ['HTTPS://SUB.NSPK.RU/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'b2b.cbrpay.ru in capitals' => ['HTTPS://B2B.CBRPAY.RU/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
    }

    #[DataProvider('sbpLinks')]
    public function testAnSbpLinkGivesSbpPaymentLink(string $link): void
    {
        self::assertInstanceOf(SbpPaymentLink::class, PaymentQrFactory::fromString($link));
        self::assertInstanceOf(SbpPaymentLink::class, PaymentQrFactory::fromBytes($link));
        self::assertInstanceOf(SbpPaymentLink::class, PaymentQrFactory::tryFromString($link));
    }

    public function testAUpiUriGivesUpiPaymentUri(): void
    {
        // Synthetic, with the parameters of NPCI UPI OC 18 (2017), Table 1.
        self::assertInstanceOf(UpiPaymentUri::class, PaymentQrFactory::fromString('upi://pay?pa=merchant@bank&pn=Merchant'));
        self::assertInstanceOf(UpiPaymentUri::class, PaymentQrFactory::fromString('UPI://pay?pa=merchant@bank&pn=Merchant'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function markedButInvalid(): iterable
    {
        yield 'EPC' => ["BCD\n003"];
        yield 'Swiss' => ["SPC\n0200"];
        yield 'GOST' => ['ST00012|Name=A'];
        yield 'SBP' => ['https://qr.nspk.ru/'];
        yield 'SBP subscription' => ['https://sub.nspk.ru/?type=03'];
        yield 'SBP B2B' => ['https://b2b.cbrpay.ru/AO10#x'];
        yield 'UPI' => ['upi://pay?pn=A'];
    }

    #[DataProvider('markedButInvalid')]
    public function testAMarkedPayloadThatItsClassRefusesIsNotTriedAsEmvco(string $payload): void
    {
        try {
            PaymentQrFactory::fromString($payload);
            self::fail('Expected an exception.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringNotContainsString('MerchantPresentedQr', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nearMarkers(): iterable
    {
        yield 'SBP host with another suffix' => ['https://qr.nspk.rux/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'SBP subscription host with another suffix' => ['https://sub.nspk.ru.example/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'SBP B2B host without its slash' => ['https://b2b.cbrpay.ru'];
        yield 'another NSPK host' => ['https://sbp.nspk.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'UPI with one slash' => ['upi:/pay?pa=a@b&pn=A'];
    }

    #[DataProvider('nearMarkers')]
    public function testANearMarkerIsTriedAsEmvco(string $payload): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('MerchantPresentedQr');

        PaymentQrFactory::fromString($payload);
    }

    public function testFromBytesTranscodesARawWindows1251GostPayload(): void
    {
        // GOST R 56042-2014, Annex D, mandatory block, as scanned bytes declared "1" (WIN1251).
        $bytes = \mb_convert_encoding('ST00011|Name=ООО "Три кита"|PersonalAcc=40702810138250123017|BankName=ОАО "БАНК"|BIC=044525225|CorrespAcc=30101810400000000225', 'Windows-1251', 'UTF-8');

        $qr = PaymentQrFactory::fromBytes($bytes);

        self::assertInstanceOf(Gost56042Payload::class, $qr);
        self::assertSame('ООО "Три кита"', $qr->name());
    }

    public function testFromBytesTranscodesAnIso88591EpcPayload(): void
    {
        // EPC069-12 v3.0, section 2.3, example V2 in ISO 8859-1.
        $bytes = \mb_convert_encoding("BCD\n002\n2\nSCT\n\nFrançois D'Alsace S.A.\nFR1420041010050500013M02606\nEUR12.3\n\n\nClient:Marie Louise La Lune", 'ISO-8859-1', 'UTF-8');

        $qr = PaymentQrFactory::fromBytes($bytes);

        self::assertInstanceOf(EpcQr::class, $qr);
        self::assertSame(1, $qr->characterSet());
    }

    public function testFromBytesTreatsOtherUtf8PayloadsAsFromString(): void
    {
        self::assertInstanceOf(UpiPaymentUri::class, PaymentQrFactory::fromBytes('upi://pay?pa=merchant@bank&pn=Merchant'));
        self::assertInstanceOf(MerchantPresentedQr::class, PaymentQrFactory::fromBytes(self::EMVCO_ANNEX_B7));
    }

    public function testFromBytesRefusesOtherNonUtf8Bytes(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('must be UTF-8');

        PaymentQrFactory::fromBytes("upi://pay?pa=a@b&pn=\xE9");
    }

    public function testTryFromStringBuildsWhatFromStringBuilds(): void
    {
        $qr = PaymentQrFactory::tryFromString(self::PIX);

        self::assertInstanceOf(PixBrCode::class, $qr);
        self::assertEquals(PaymentQrFactory::fromString(self::PIX), $qr);
    }

    public function testTryFromStringIsNullWhenTheMarkedClassRefuses(): void
    {
        self::assertNull(PaymentQrFactory::tryFromString('BCD'));
        self::assertNull(PaymentQrFactory::tryFromString('not a payment payload'));
    }

    public function testTryFromStringIsNullWhenSeveralProfilesMatch(): void
    {
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

        self::assertNull(PaymentQrFactory::tryFromString($payload));
    }

    public function testTryFromBytesBuildsWhatFromBytesBuilds(): void
    {
        $bytes = \mb_convert_encoding("BCD\n002\n2\nSCT\n\nFrançois D'Alsace S.A.\nFR1420041010050500013M02606\nEUR12.3\n\n\nClient:Marie Louise La Lune", 'ISO-8859-1', 'UTF-8');

        $qr = PaymentQrFactory::tryFromBytes($bytes);

        self::assertInstanceOf(EpcQr::class, $qr);
        self::assertEquals(PaymentQrFactory::fromBytes($bytes), $qr);
        self::assertInstanceOf(PixBrCode::class, PaymentQrFactory::tryFromBytes(self::PIX));
    }

    public function testTryFromBytesIsNullForNonUtf8BytesOutsideEpcAndGost(): void
    {
        self::assertNull(PaymentQrFactory::tryFromBytes("upi://pay?pa=a@b&pn=\xE9"));
    }

    public function testTryFromBytesIsNullWhenAnEpcPayloadIsInvalidInItsCharacterSet(): void
    {
        self::assertNull(PaymentQrFactory::tryFromBytes("BCD\n002\n2\nSCT\n"));
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
