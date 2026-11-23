<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ExposesRootFields;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\Internal\RussianAccountKey;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Epc\EpcQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\India\UpiPaymentUri;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\Gost56042Payload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpPaymentLink;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBill;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayProxyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(DuitNowQr::class)]
#[UsesClass(EmvcoMask::class)]
#[UsesClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(EpcQr::class)]
#[UsesClass(Gost56042Payload::class)]
#[UsesClass(JpqrCode::class)]
#[UsesClass(KhqrCode::class)]
#[UsesClass(Mask::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(PayloadBound::class)]
#[UsesClass(PixBrCode::class)]
#[UsesClass(ProfilePayload::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(PromptPayProxyType::class)]
#[UsesClass(PromptPayQr::class)]
#[UsesClass(QrBill::class)]
#[UsesClass(QueryString::class)]
#[UsesClass(RussianAccountKey::class)]
#[UsesClass(SbpPaymentLink::class)]
#[UsesClass(TextLines::class)]
#[UsesClass(UpiPaymentUri::class)]
#[UsesTrait(ExposesRootFields::class)]
final class SerializationTest extends TestCase
{
    use BuildsPayloads;

    /**
     * @return iterable<string, array{class-string<AbstractStringValueObject&Maskable>, string}>
     */
    public static function payloads(): iterable
    {
        yield 'EMVCo Annex B.7' => [MerchantPresentedQr::class, '00020101021229300012D156000000000510A93FO3230Q31280012D15600000001030812345678520441115802CN'
            . '5914BEST TRANSPORT6007BEIJING64200002ZH0104最佳运输0202北京540523.7253031565502016233030412340603***0708A60086670902ME'
            . '91320016A0112233449988770708123456786304A13A'];
        yield 'Pix' => [PixBrCode::class, '00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-4266554400005204000053039865802BR5913Fulano de Tal6008BRASILIA62070503***63041D3D'];
        yield 'PromptPay' => [PromptPayQr::class, self::sealed(self::tlv('00', '01'), self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')), self::tlv('53', '764'), self::tlv('58', 'TH'), self::tlv('59', 'Shop'))];
        yield 'DuitNow' => [DuitNowQr::class, '00020201021126410014A000000615000101065016640209123456789520499995303458540510.005802MY5909QRCSDNBHD6005BANGI6304343F'];
        yield 'KHQR' => [KhqrCode::class, '00020101021129200016test_bakong@devb5204599953031165802KH5917Lucky Supermarket6010Phnom Penh9917001316339210247786304102F'];
        yield 'JPQR' => [JpqrCode::class, self::sealed(
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
        )];
        yield 'EPC' => [EpcQr::class, "BCD\n001\n1\nSCT\nBHBLDEHHXXX\nFranz Mustermänn\nDE71110220330123456789\nEUR12.3\nGDDS\nRF18539007547034"];
        yield 'QR-bill' => [QrBill::class, \implode("\r\n", [
            'SPC', '0200', '1', 'CH5204835012345671000', 'S', 'Muster Stiftung (sample foundation)', 'P.O. Box', '', '3001',
            'Bern', 'CH', '', '', '', '', '', '', '', '', 'CHF', '', '', '', '', '', '', '', 'NON', '', '', 'EPD',
        ])];
        yield 'GOST' => [Gost56042Payload::class, 'ST00012|Name=ООО "Три кита"|PersonalAcc=40702810138250123017|BankName=ОАО "БАНК"|BIC=044525225|CorrespAcc=30101810400000000225'];
        yield 'SBP' => [SbpPaymentLink::class, 'https://qr.nspk.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2?type=01'];
        yield 'UPI' => [UpiPaymentUri::class, 'upi://pay?pa=merchant@bank&pn=Merchant'];
    }

    /**
     * @param class-string<AbstractStringValueObject&Maskable> $class
     */
    #[DataProvider('payloads')]
    public function testARoundTripKeepsTheValueAndTheDerivedState(string $class, string $payload): void
    {
        $original = new $class($payload);
        $serialized = \serialize($original);

        $restored = \unserialize($serialized);

        self::assertInstanceOf($class, $restored);
        self::assertTrue($original->equals($restored));
        self::assertSame($original->masked(), $restored->masked());
        self::assertSame(\sprintf('O:%d:"%s":1:{s:5:"value";s:%d:"%s";}', \strlen($class), $class, \strlen($payload), $payload), $serialized);
    }

    /**
     * @param class-string<AbstractStringValueObject&Maskable> $class
     */
    #[DataProvider('payloads')]
    public function testACraftedValueIsValidatedAgain(string $class, string $payload): void
    {
        $crafted = \sprintf('O:%d:"%s":1:{s:5:"value";s:5:"hello";}', \strlen($class), $class);

        $this->expectException(InvalidPaymentQrException::class);

        \unserialize($crafted);
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function malformed(): iterable
    {
        foreach (self::payloads() as $label => [$class]) {
            $prefix = \sprintf('O:%d:"%s":', \strlen($class), $class);

            yield $label . ', no value' => [$class, $prefix . '0:{}'];
            yield $label . ', a value that is no string' => [$class, $prefix . '1:{s:5:"value";i:1;}'];
        }
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('malformed')]
    public function testAPayloadWithoutAStringValueIsRefused(string $class, string $serialized): void
    {
        $this->expectExceptionObject(new InvalidValueObjectException('A serialized ' . $class . ' must hold its value as a string.'));

        \unserialize($serialized);
    }

    public function testDerivedStateBesideTheValueIsIgnoredAndRebuilt(): void
    {
        $class = UpiPaymentUri::class;
        $serialized = \sprintf('O:%d:"%s":2:{s:5:"value";s:38:"upi://pay?pa=merchant@bank&pn=Merchant";s:5:"query";N;}', \strlen($class), $class);

        $restored = \unserialize($serialized);

        self::assertInstanceOf(UpiPaymentUri::class, $restored);
        self::assertTrue(UpiPaymentUri::from('upi://pay?pa=merchant@bank&pn=Merchant')->equals($restored));
        self::assertSame('merchant@bank', $restored->payeeAddress());
        self::assertSame(['pa', 'pn'], $restored->parameterNames());
    }

    /**
     * @param class-string<AbstractStringValueObject&Maskable> $class
     */
    #[DataProvider('payloads')]
    public function testABuiltObjectRefusesUnserializeAndKeepsItsState(string $class, string $payload): void
    {
        $object = new $class($payload);
        $masked = $object->masked();
        self::assertTrue(\method_exists($object, '__unserialize'));

        try {
            $object->__unserialize(['value' => 'hello']);
            self::fail('__unserialize() on a built object must throw.');
        } catch (LogicException $e) {
            self::assertSame('A value object is immutable; __unserialize() runs only on a new object.', $e->getMessage());
        }

        self::assertSame($payload, (string) $object);
        self::assertSame($masked, $object->masked());
    }
}
