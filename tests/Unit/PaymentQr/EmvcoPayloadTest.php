<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr;

use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\EmvcoPayload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayProxyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The accessors every EMVCo class shares through {@see EmvcoPayload}, the generic class and each national profile alike.
 */
#[CoversClass(MerchantPresentedQr::class)]
#[CoversClass(PixBrCode::class)]
#[CoversClass(PromptPayQr::class)]
#[CoversClass(DuitNowQr::class)]
#[CoversClass(KhqrCode::class)]
#[CoversClass(JpqrCode::class)]
#[CoversClass(ProfilePayload::class)]
#[CoversClass(EmvcoMerchantPresented::class)]
#[UsesClass(PromptPayProxyType::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoMask::class)]
#[UsesClass(Mask::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(PayloadBound::class)]
final class EmvcoPayloadTest extends TestCase
{
    use BuildsPayloads;

    /**
     * Synthetic payloads, each the smallest its class accepts plus a postal code, an amount and template 62.
     *
     * @return iterable<string, array{class-string<EmvcoPayload>, string}>
     */
    public static function payloads(): iterable
    {
        $tail = [self::tlv('61', '12345'), self::tlv('62', self::tlv('05', 'REF-1') . self::tlv('10', 'TEN') . self::tlv('05', 'REF-2'))];

        yield 'generic' => [MerchantPresentedQr::class, self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'com.example') . self::tlv('01', 'acct')),
            self::tlv('52', '5999'),
            self::tlv('53', '840'),
            self::tlv('54', '10.50'),
            self::tlv('58', 'US'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'City'),
            ...$tail,
        )];
        yield 'Pix' => [PixBrCode::class, self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'fulano@example.com')),
            self::tlv('52', '5999'),
            self::tlv('53', '986'),
            self::tlv('54', '10.50'),
            self::tlv('58', 'BR'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'City'),
            ...$tail,
        )];
        yield 'PromptPay' => [PromptPayQr::class, self::sealed(
            self::tlv('00', '01'),
            self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')),
            self::tlv('52', '5999'),
            self::tlv('53', '764'),
            self::tlv('54', '10.50'),
            self::tlv('58', 'TH'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'City'),
            ...$tail,
        )];
        yield 'DuitNow' => [DuitNowQr::class, self::sealed(
            self::tlv('00', '02'),
            self::tlv('01', '11'),
            self::tlv('26', self::tlv('00', 'A0000006150001') . self::tlv('01', '501664') . self::tlv('02', '123456789')),
            self::tlv('52', '5999'),
            self::tlv('53', '458'),
            self::tlv('54', '10.50'),
            self::tlv('58', 'MY'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'City'),
            ...$tail,
        )];
        yield 'KHQR' => [KhqrCode::class, self::sealed(
            self::tlv('00', '01'),
            self::tlv('29', self::tlv('00', 'shop@bank')),
            self::tlv('52', '5999'),
            self::tlv('53', '116'),
            self::tlv('54', '10.50'),
            self::tlv('58', 'KH'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'City'),
            ...$tail,
        )];
        yield 'JPQR' => [JpqrCode::class, self::sealed(
            self::tlv('00', '01'),
            self::tlv('01', '11'),
            self::tlv('26', self::tlv('00', 'jp.or.paymentsjapan') . self::tlv('01', '1234567890123') . self::tlv('02', '0001') . self::tlv('03', '000002') . self::tlv('04', '000003')),
            self::tlv('52', '5999'),
            self::tlv('53', '392'),
            self::tlv('54', '10.50'),
            self::tlv('58', 'JP'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'City'),
            self::tlv('64', self::tlv('00', 'JA') . self::tlv('01', 'Mise')),
            ...$tail,
        )];
    }

    /**
     * @param class-string<EmvcoPayload> $class
     */
    #[DataProvider('payloads')]
    public function testSharesTheEmvcoAccessors(string $class, string $payload): void
    {
        $qr = $class::from($payload);

        self::assertInstanceOf(EmvcoPayload::class, $qr);
        self::assertSame('5999', $qr->merchantCategoryCode());
        self::assertSame('Shop', $qr->merchantName());
        self::assertSame('City', $qr->merchantCity());
        self::assertSame('12345', $qr->postalCode());
        self::assertSame('10.50', $qr->writtenAmount());
        self::assertSame('10.5', $qr->amount()?->value);
        self::assertSame('Shop', $qr->get('59'));
        self::assertNull($qr->get('55'));
        self::assertEquals([new DataObject('05', 'REF-1'), new DataObject('10', 'TEN'), new DataObject('05', 'REF-2')], $qr->additionalData());
        self::assertSame('REF-2', $qr->additionalDataField('05'));
        self::assertSame('TEN', $qr->additionalDataField('10'));
        self::assertNull($qr->additionalDataField('07'));
        self::assertCount(1, $qr->merchantAccounts());
    }

    public function testAProfileWithoutCityOrPostalCodeGivesNull(): void
    {
        $qr = new PromptPayQr(self::sealed(
            self::tlv('00', '01'),
            self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')),
            self::tlv('53', '764'),
            self::tlv('58', 'TH'),
            self::tlv('59', 'Shop'),
        ));

        self::assertNull($qr->merchantCategoryCode());
        self::assertNull($qr->merchantCity());
        self::assertNull($qr->postalCode());
        self::assertNull($qr->writtenAmount());
        self::assertSame([], $qr->additionalData());
        self::assertNull($qr->additionalDataField('05'));
        self::assertSame('29', $qr->merchantAccounts()[0]->id);
    }
}
