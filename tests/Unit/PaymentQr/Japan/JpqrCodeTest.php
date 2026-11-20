<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Japan;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\BuildsPayloads;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The Payments Japan MPM guideline prints the template layout but no complete payload: every payload here is synthetic,
 * built from table 2.1-1 of the guideline.
 */
#[CoversClass(JpqrCode::class)]
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
#[UsesClass(AmbiguousPaymentQrFieldException::class)]
final class JpqrCodeTest extends TestCase
{
    use BuildsPayloads;

    private const string STORE_ID = '12345678901230001000002000003';

    public function testAStaticCodeParsesFieldByField(): void
    {
        $qr = new JpqrCode(self::sealed(...self::frame()));

        self::assertSame(PointOfInitiation::Static, $qr->pointOfInitiation());
        self::assertSame([self::STORE_ID], $qr->storeIds());
        self::assertSame(self::STORE_ID, $qr->storeId());
        self::assertSame('5812', $qr->merchantCategoryCode());
        self::assertSame(CurrencyCode::fromNumeric('392'), $qr->currency());
        self::assertNull($qr->amount());
        self::assertSame(CountryCode::from('JP'), $qr->countryCode());
        self::assertSame('CASHLESS CAFE', $qr->merchantName());
        self::assertSame('MINATO-KU', $qr->merchantCity());
        self::assertSame('1050001', $qr->postalCode());
        self::assertSame('キャッシュレス推進協議会', $qr->alternateMerchantName());
    }

    public function testADynamicCodeCarriesAnAmount(): void
    {
        $qr = new JpqrCode(self::sealed(...self::frame(self::tlv('01', '12'), self::tlv('54', '1500'))));

        self::assertSame(PointOfInitiation::Dynamic, $qr->pointOfInitiation());
        self::assertSame('1500', $qr->amount()?->value);
    }

    public function testTheTemplateMayUseAnyIdFrom26To51AndTheGuiIsCaseInsensitive(): void
    {
        $qr = new JpqrCode(self::sealed(...self::frame(
            self::tlv('26', self::tlv('00', 'jp.example.wallet') . self::tlv('01', 'M-1')),
            self::tlv('27', self::account('JP.OR.PAYMENTSJAPAN')),
        )));

        self::assertSame(self::STORE_ID, $qr->storeId());
    }

    public function testSeveralTemplatesAreAcceptedAndNeverPickedSilently(): void
    {
        $qr = new JpqrCode(self::sealed(...self::frame(
            self::tlv('27', self::account(levels: ['1111111111111', '0001', '000001', '000001'])),
        )));

        self::assertSame([self::STORE_ID, '11111111111110001000001000001'], $qr->storeIds());

        try {
            $qr->storeId();
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousPaymentQrFieldException $e) {
            self::assertSame('JpqrCode cannot give a single value for the unified store ID: several distinct values.', $e->getMessage());
        }
    }

    public function testTheSameStoreIdInTwoTemplatesIsOneValue(): void
    {
        $qr = new JpqrCode(self::sealed(...self::frame(self::tlv('27', self::account()))));

        self::assertSame([self::STORE_ID], $qr->storeIds());
    }

    public function testMaskingHidesTheStoreIdAndTheCrc(): void
    {
        $masked = new JpqrCode(self::sealed(...self::frame()))->masked();

        self::assertStringNotContainsString('1234567890123', $masked);
        self::assertStringNotContainsString('paymentsjapan', $masked);
        self::assertStringContainsString('2668' . \str_repeat('*', 68), $masked);
        self::assertStringEndsWith('6304****', $masked);
        self::assertStringContainsString('CASHLESS CAFE', $masked);
    }

    /**
     * @param list<string> $objects
     */
    #[DataProvider('refusedPayloads')]
    public function testRefusesPayloadsThatBreakTheGuideline(array $objects): void
    {
        $payload = self::sealed(...$objects);

        self::assertFalse(JpqrCode::isValid($payload));
        $this->expectException(InvalidPaymentQrException::class);
        new JpqrCode($payload);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function refusedPayloads(): iterable
    {
        $frame = self::frame(...);
        $without = static fn (string $id): array => \array_values(\array_filter($frame(), static fn (string $object): bool => !\str_starts_with($object, $id)));
        $levels = static fn (string ...$levels): array => $frame(self::tlv('26', self::account(levels: \array_values($levels))));

        yield 'no JPQR template' => [$frame(self::tlv('26', self::tlv('00', 'jp.example.wallet') . self::tlv('01', 'M-1')))];
        yield 'no payload format indicator' => [$without('00')];
        yield 'no point of initiation' => [$without('01')];
        yield 'no merchant category code' => [$without('52')];
        yield 'no currency' => [$without('53')];
        yield 'no country code' => [$without('58')];
        yield 'no merchant name' => [$without('59')];
        yield 'no merchant city' => [$without('60')];
        yield 'no postal code' => [$without('61')];
        yield 'no language template' => [$without('64')];
        yield 'language template without the language' => [$frame(self::tlv('64', self::tlv('01', '協議会')))];
        yield 'language template without the merchant name' => [$frame(self::tlv('64', self::tlv('00', 'JA')))];
        yield 'dynamic code without an amount' => [$frame(self::tlv('01', '12'))];
        yield 'level 1 missing' => [$frame(self::tlv('26', self::tlv('00', 'jp.or.paymentsjapan') . self::tlv('02', '0001') . self::tlv('03', '000002') . self::tlv('04', '000003')))];
        yield 'level 4 missing' => [$frame(self::tlv('26', self::tlv('00', 'jp.or.paymentsjapan') . self::tlv('01', '1234567890123') . self::tlv('02', '0001') . self::tlv('03', '000002')))];
        yield 'level 1 of 12 digits' => [$levels('123456789012', '0001', '000002', '000003')];
        yield 'level 1 of 14 digits' => [$levels('12345678901234', '0001', '000002', '000003')];
        yield 'level 2 of 3 digits' => [$levels('1234567890123', '001', '000002', '000003')];
        yield 'level 3 of 7 digits' => [$levels('1234567890123', '0001', '0000002', '000003')];
        yield 'level 4 of 5 digits' => [$levels('1234567890123', '0001', '000002', '00003')];
        yield 'level 2 with a letter' => [$levels('1234567890123', '000A', '000002', '000003')];
    }

    public function testTheFrameIsAcceptedSoEachRefusalChangesOneThing(): void
    {
        self::assertTrue(JpqrCode::isValid(self::sealed(...self::frame())));
        self::assertTrue(JpqrCode::isValid(self::sealed(...self::frame(self::tlv('01', '12'), self::tlv('54', '1')))));
    }

    public function testAPayloadOf512BytesIsAcceptedAndOneByteMoreIsRefused(): void
    {
        $atBound = self::sealed(...$this->paddedTo(512));
        $over = self::sealed(...$this->paddedTo(513));

        self::assertSame(512, \strlen($atBound));
        self::assertTrue(JpqrCode::isValid($atBound));
        self::assertSame(513, \strlen($over));
        self::assertFalse(JpqrCode::isValid($over));
    }

    public function testTheBoundCountsUtf8Bytes(): void
    {
        $templates = \array_map(static fn (string $id): string => self::tlv($id, self::tlv('00', \str_repeat('日', 30))), ['80', '81', '82', '83']);
        $payload = self::sealed(...self::frame(...$templates));

        self::assertLessThan(512, \mb_strlen($payload));
        self::assertGreaterThan(512, \strlen($payload));
        self::assertFalse(JpqrCode::isValid($payload));

        try {
            new JpqrCode($payload);
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertSame('JpqrCode requires a payload of at most 512 bytes.', $e->getMessage());
        }
    }

    public function testRefusesAWrongCrcAndNonPayloads(): void
    {
        self::assertFalse(JpqrCode::isValid(\substr(self::sealed(...self::frame()), 0, -4) . '0000'));
        self::assertFalse(JpqrCode::isValid('hello'));
        self::assertFalse(JpqrCode::isValid(\implode('', self::frame())));
    }

    public function testAMessageNeverContainsThePayload(): void
    {
        try {
            new JpqrCode(self::sealed(...self::frame(self::tlv('26', self::account(levels: ['9876543210987', '0001', '000002', '00003'])))));
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertStringNotContainsString('9876543210987', $e->getMessage());
        }
    }

    public function testEqualityAndSerialisation(): void
    {
        $payload = self::sealed(...self::frame());
        $qr = new JpqrCode($payload);

        self::assertTrue($qr->equals(JpqrCode::from($payload)));
        self::assertSame(\json_encode($payload), \json_encode($qr));
    }

    /**
     * The static frame followed by unreserved templates 80, 81, ... of ASCII filler, so the sealed payload has exactly
     * `$bytes` bytes.
     *
     * @return list<string>
     */
    private function paddedTo(int $bytes): array
    {
        $remaining = $bytes - \strlen(self::sealed(...self::frame()));
        $templates = [];
        for ($id = 80; $remaining > 0; $id++) {
            $length = $remaining - 8 > 95 ? \min(95, $remaining - 17) : $remaining - 8;
            $templates[] = self::tlv((string) $id, self::tlv('00', \str_repeat('x', $length)));
            $remaining -= 8 + $length;
        }

        return self::frame(...$templates);
    }

    /**
     * @param list<string> $levels
     */
    private static function account(string $gui = 'jp.or.paymentsjapan', array $levels = ['1234567890123', '0001', '000002', '000003']): string
    {
        return self::tlv('00', $gui) . self::tlv('01', $levels[0]) . self::tlv('02', $levels[1]) . self::tlv('03', $levels[2]) . self::tlv('04', $levels[3]);
    }

    /**
     * The objects of a synthetic static code in ID order; a given object replaces the frame object with the same ID.
     *
     * @return list<string>
     */
    private static function frame(string ...$extra): array
    {
        $objects = [];
        $base = [
            self::tlv('00', '01'), self::tlv('01', '11'), self::tlv('26', self::account()), self::tlv('52', '5812'),
            self::tlv('53', '392'), self::tlv('58', 'JP'), self::tlv('59', 'CASHLESS CAFE'), self::tlv('60', 'MINATO-KU'),
            self::tlv('61', '1050001'), self::tlv('64', self::tlv('00', 'JA') . self::tlv('01', 'キャッシュレス推進協議会')),
        ];
        foreach ([...$base, ...$extra] as $object) {
            $objects[\substr($object, 0, 2)] = $object;
        }

        \ksort($objects, \SORT_STRING);

        return \array_values($objects);
    }
}
