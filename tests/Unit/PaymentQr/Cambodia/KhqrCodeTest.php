<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Cambodia;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\BuildsPayloads;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KhqrCode::class)]
#[UsesClass(ProfilePayload::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoMask::class)]
#[UsesClass(Mask::class)]
#[UsesClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(InvalidPaymentQrException::class)]
#[UsesClass(AmbiguousPaymentQrFieldException::class)]
#[UsesClass(PayloadBound::class)]
final class KhqrCodeTest extends TestCase
{
    use BuildsPayloads;

    // NBC Bakong, KHQR Content Guideline 1.3: tag 29 solo merchant, static (page 04) and dynamic (page 06).
    private const string GUIDELINE_SOLO_STATIC = '00020101021129200016test_bakong@devb5204599953031165802KH5917Lucky Supermarket6010Phnom Penh9917001316339210247786304102F';

    private const string GUIDELINE_SOLO_DYNAMIC = '00020101021229200016test_bakong@devb520459995303116540410005802KH5917Lucky Supermarket6010Phnom Penh9917001316339234555826304D1EB';

    // NBC Bakong, KHQR Content Guideline 1.3: tag 29 remittance (page 07) and tag 30 corporate merchant (page 08).
    private const string GUIDELINE_REMITTANCE = '00020101021129460016devbkhppxxx@devb011000000011120208Dev Bank5204599953031165802KH5910Jonh Smith6010Phnom Penh99170013163392463782863042ACD';

    private const string GUIDELINE_CORPORATE = '00020101021230450016devbkhppxxx@devb01090133451680208Dev bank520459995303840540410.05802KH5914Coffee Khlaing6010Phnom Penh62600115Invoice#06903140211855776795510310NBC SenSok0708Number 2991700131630574656249630402F7';

    // NBC Bakong, KHQR SDK Documentation 2.7, "Generate KHQR for Individual" and "for Merchant" results (UnionPay ID 15, String template 64).
    private const string SDK_INDIVIDUAL = '00020101021215231234567812345678901234529460015john_smith@devb0111855122334550208Dev bank5204599953038405406100.005802KH5910John Smith6010Phnom Penh62710106#123450211855122334550311Coffee Shop0709Cashier_10814Morning coffee64280002km0108ចន ស្មីន0206សៀមរាប99170013168800699761963042B5B';

    private const string SDK_MERCHANT = '00020101021215231234567812345678901234530350009khqr@devb01061234560208Dev Bank5204599953038405406100.005802KH5910John Smith6010Phnom Penh62710106#123450211855122334550311Coffee Shop0709Cashier_10814Morning coffee64280002km0108ចន ស្មីន0206សៀមរាប99170013168800766071263049740';

    public function testTheStaticSoloMerchantExampleParsesFieldByField(): void
    {
        $qr = new KhqrCode(self::GUIDELINE_SOLO_STATIC);

        self::assertSame(self::GUIDELINE_SOLO_STATIC, $qr->value);
        self::assertSame(PointOfInitiation::Static, $qr->pointOfInitiation());
        self::assertSame(['test_bakong@devb'], $qr->bakongAccountIds());
        self::assertSame('test_bakong@devb', $qr->bakongAccountId());
        self::assertNull($qr->accountInformation());
        self::assertNull($qr->merchantId());
        self::assertNull($qr->acquiringBank());
        self::assertSame('5999', $qr->merchantCategoryCode());
        self::assertSame(CurrencyCode::fromNumeric('116'), $qr->currency());
        self::assertNull($qr->amount());
        self::assertSame(CountryCode::from('KH'), $qr->countryCode());
        self::assertSame('Lucky Supermarket', $qr->merchantName());
        self::assertSame('Phnom Penh', $qr->merchantCity());
        self::assertNull($qr->postalCode());
    }

    public function testTheDynamicSoloMerchantExampleCarriesAnAmount(): void
    {
        $qr = new KhqrCode(self::GUIDELINE_SOLO_DYNAMIC);

        self::assertSame(PointOfInitiation::Dynamic, $qr->pointOfInitiation());
        self::assertSame('1000', $qr->amount()?->value);
    }

    public function testTheRemittanceExampleCarriesTheAccountInformationAndTheAcquiringBank(): void
    {
        $qr = new KhqrCode(self::GUIDELINE_REMITTANCE);

        self::assertSame('devbkhppxxx@devb', $qr->bakongAccountId());
        self::assertSame('0000001112', $qr->accountInformation());
        self::assertNull($qr->merchantId());
        self::assertSame('Dev Bank', $qr->acquiringBank());
        self::assertSame('Jonh Smith', $qr->merchantName());
    }

    public function testTheCorporateMerchantExampleUsesTag30(): void
    {
        $qr = new KhqrCode(self::GUIDELINE_CORPORATE);

        self::assertSame('devbkhppxxx@devb', $qr->bakongAccountId());
        self::assertSame('013345168', $qr->merchantId());
        self::assertNull($qr->accountInformation());
        self::assertSame('Dev bank', $qr->acquiringBank());
        self::assertSame(CurrencyCode::fromNumeric('840'), $qr->currency());
        self::assertSame('10', $qr->amount()?->value);
        self::assertSame('Coffee Khlaing', $qr->merchantName());
    }

    public function testTheSdkExamplesWithAnotherSchemeAndALanguageTemplateParse(): void
    {
        $individual = new KhqrCode(self::SDK_INDIVIDUAL);
        $merchant = new KhqrCode(self::SDK_MERCHANT);

        self::assertSame('john_smith@devb', $individual->bakongAccountId());
        self::assertSame('85512233455', $individual->accountInformation());
        self::assertSame('100', $individual->amount()?->value);
        self::assertSame('khqr@devb', $merchant->bakongAccountId());
        self::assertSame('123456', $merchant->merchantId());
        self::assertSame('Dev Bank', $merchant->acquiringBank());
    }

    public function testTheLongestNameCityPostalCodeAndAmountsAreAccepted(): void
    {
        $qr = new KhqrCode(self::sealed(
            self::tlv('00', '01'),
            self::tlv('01', '12'),
            self::solo(),
            self::tlv('52', '5999'),
            self::tlv('53', '116'),
            self::tlv('54', '1234567890.12'),
            self::tlv('55', '02'),
            self::tlv('56', '123456789.123'),
            self::tlv('58', 'KH'),
            self::tlv('59', \str_repeat('n', 25)),
            self::tlv('60', \str_repeat('c', 15)),
            self::tlv('61', \str_repeat('9', 10)),
            self::tlv('62', self::tlv('01', \str_repeat('b', 25)) . self::tlv('09', \str_repeat('d', 25))),
            self::tlv('64', self::tlv('00', 'km') . self::tlv('01', \str_repeat('ន', 25)) . self::tlv('02', \str_repeat('ក', 15))),
        ));

        self::assertSame(25, \strlen($qr->merchantName()));
        self::assertSame(15, \strlen((string) $qr->merchantCity()));
        self::assertSame(\str_repeat('9', 10), $qr->postalCode());
        self::assertSame('1234567890.12', $qr->amount()?->value);
        self::assertTrue(KhqrCode::isValid(self::sealed(...self::frame(self::solo(), self::tlv('55', '03'), self::tlv('57', '12.50')))));
        self::assertSame('0.5', new KhqrCode(self::sealed(...self::frame(self::solo(), self::tlv('54', '0.50'))))->amount()?->value);
    }

    #[DataProvider('additionalDataSubIds')]
    public function testEveryAdditionalDataSubFieldOf25CharactersIsAccepted(string $subId): void
    {
        self::assertTrue(KhqrCode::isValid(self::sealed(...self::frame(self::solo(), self::tlv('62', self::tlv($subId, \str_repeat('d', 25)))))));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function additionalDataSubIds(): iterable
    {
        foreach (['01', '02', '03', '04', '05', '06', '07', '08', '09'] as $subId) {
            yield 'sub-ID ' . $subId => [$subId];
        }
    }

    public function testThePointOfInitiationIsOptional(): void
    {
        $objects = \array_values(\array_filter(self::frame(self::solo()), static fn (string $object): bool => !\str_starts_with($object, '01')));

        self::assertNull(new KhqrCode(self::sealed(...$objects))->pointOfInitiation());
    }

    public function testTag29AndTag30TogetherAreAcceptedAndNeverPickedSilently(): void
    {
        $qr = new KhqrCode(self::sealed(...self::frame(
            self::tlv('29', self::tlv('00', 'alice@bank') . self::tlv('02', 'Bank A')),
            self::tlv('30', self::tlv('00', 'shop@bank') . self::tlv('01', '42') . self::tlv('02', 'Bank B')),
        )));

        self::assertSame(['alice@bank', 'shop@bank'], $qr->bakongAccountIds());
        self::assertSame('42', $qr->merchantId());

        try {
            $qr->bakongAccountId();
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousPaymentQrFieldException $e) {
            self::assertSame('KhqrCode cannot give a single value for the Bakong account ID: several distinct values.', $e->getMessage());
        }

        $this->expectException(AmbiguousPaymentQrFieldException::class);
        $qr->acquiringBank();
    }

    public function testTheSameAccountAndBankInBothTemplatesAreOneValue(): void
    {
        $qr = new KhqrCode(self::sealed(...self::frame(
            self::tlv('29', self::tlv('00', 'shop@bank') . self::tlv('02', 'Bank A')),
            self::tlv('30', self::tlv('00', 'shop@bank') . self::tlv('01', '42') . self::tlv('02', 'Bank A')),
        )));

        self::assertSame(['shop@bank'], $qr->bakongAccountIds());
        self::assertSame('shop@bank', $qr->bakongAccountId());
        self::assertSame('Bank A', $qr->acquiringBank());
    }

    public function testMaskingHidesTheBakongAccountTheOtherSchemeAndTheCrc(): void
    {
        $masked = new KhqrCode(self::SDK_INDIVIDUAL)->masked();

        self::assertStringNotContainsString('john_smith@devb', $masked);
        self::assertStringNotContainsString('85512233455020', $masked);
        self::assertStringNotContainsString('1234567812345678901234', $masked);
        self::assertStringContainsString('2946' . \str_repeat('*', 46), $masked);
        self::assertStringEndsWith('6304****', $masked);
        self::assertStringNotContainsString('John Smith', $masked);
        self::assertStringNotContainsString('Phnom Penh', $masked);
        self::assertStringContainsString('5904J***6003***', $masked);
    }

    /**
     * @param list<string> $objects
     */
    #[DataProvider('refusedPayloads')]
    public function testRefusesPayloadsThatBreakTheSpecification(array $objects): void
    {
        $payload = self::sealed(...$objects);

        self::assertFalse(KhqrCode::isValid($payload));
        $this->expectException(InvalidPaymentQrException::class);
        new KhqrCode($payload);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function refusedPayloads(): iterable
    {
        $solo = self::solo();
        $frame = self::frame(...);
        $without = static fn (string $id): array => \array_values(\array_filter($frame($solo), static fn (string $object): bool => !\str_starts_with($object, $id)));
        $replaced = static fn (string $id, string $value): array => \array_map(static fn (string $object): string => \str_starts_with($object, $id) ? self::tlv($id, $value) : $object, $frame($solo));

        yield 'no payload format indicator' => [$without('00')];
        yield 'no Bakong template' => [$frame(self::tlv('31', self::tlv('00', 'shop@bank')))];
        yield 'country other than KH' => [$replaced('58', 'TH')];
        yield 'no merchant category code' => [$without('52')];
        yield 'no currency' => [$without('53')];
        yield 'no country code' => [$without('58')];
        yield 'no merchant name' => [$without('59')];
        yield 'no merchant city' => [$without('60')];
        yield 'merchant name of 26 characters' => [$replaced('59', \str_repeat('n', 26))];
        yield 'merchant city of 16 characters' => [$replaced('60', \str_repeat('c', 16))];
        yield 'postal code of 11 characters' => [$frame($solo, self::tlv('61', \str_repeat('9', 11)))];
        yield 'amount of 14 characters' => [$frame($solo, self::tlv('54', '12345678901.12'))];
        yield 'zero amount' => [$frame($solo, self::tlv('54', '0.00'))];
        yield 'zero amount without decimals' => [$frame($solo, self::tlv('54', '0'))];
        yield 'tip indicator of 1 digit' => [$frame($solo, self::tlv('55', '2'))];
        yield 'fixed fee of 14 characters' => [$frame($solo, self::tlv('55', '02'), self::tlv('56', '12345678901.12'))];
        yield 'percentage fee of 6 characters' => [$frame($solo, self::tlv('55', '03'), self::tlv('57', '12.505'))];
        foreach (['01', '02', '03', '04', '05', '06', '07', '08', '09'] as $subId) {
            yield \sprintf('additional data sub-ID %s of 26 characters', $subId) => [$frame($solo, self::tlv('62', self::tlv($subId, \str_repeat('d', 26))))];
        }
        yield 'language template without the merchant name' => [$frame($solo, self::tlv('64', self::tlv('00', 'km')))];
        yield 'language template without the language' => [$frame($solo, self::tlv('64', self::tlv('01', 'Hang')))];
        yield 'language of 3 letters' => [$frame($solo, self::tlv('64', self::tlv('00', 'khm') . self::tlv('01', 'Hang')))];
        yield 'language with a trailing newline' => [$frame($solo, self::tlv('64', self::tlv('00', "km\n") . self::tlv('01', 'Hang')))];
        yield 'language with a digit' => [$frame($solo, self::tlv('64', self::tlv('00', 'k1') . self::tlv('01', 'Hang')))];
        yield 'alternate merchant name of 26 characters' => [$frame($solo, self::tlv('64', self::tlv('00', 'km') . self::tlv('01', \str_repeat('ន', 26))))];
        yield 'alternate merchant city of 16 characters' => [$frame($solo, self::tlv('64', self::tlv('00', 'km') . self::tlv('01', 'Hang') . self::tlv('02', \str_repeat('ក', 16))))];
    }

    public function testTheFrameIsAcceptedSoEachRefusalChangesOneThing(): void
    {
        self::assertTrue(KhqrCode::isValid(self::sealed(...self::frame(self::solo()))));
        self::assertTrue(KhqrCode::isValid(self::sealed(...self::frame(
            self::solo(),
            self::tlv('54', '10.00'),
            self::tlv('55', '01'),
            self::tlv('61', '120101'),
            self::tlv('62', self::tlv('01', 'bill') . self::tlv('09', 'ME')),
            self::tlv('64', self::tlv('00', 'km') . self::tlv('01', 'Hang') . self::tlv('02', 'Phnom Penh')),
        ))));
    }

    public function testRefusesAWrongCrcAndNonPayloads(): void
    {
        self::assertFalse(KhqrCode::isValid(\substr(self::GUIDELINE_SOLO_STATIC, 0, -4) . '0000'));
        self::assertFalse(KhqrCode::isValid(''));
        self::assertFalse(KhqrCode::isValid('hello'));
    }

    public function testAMessageNeverContainsThePayload(): void
    {
        $payload = self::sealed(...self::frame(self::tlv('29', self::tlv('00', 'secret@bank')), self::tlv('59', \str_repeat('S', 26))));

        try {
            new KhqrCode($payload);
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertStringNotContainsString('secret@bank', $e->getMessage());
            self::assertStringNotContainsString('SSS', $e->getMessage());
        }
    }

    public function testEqualityAndSerialisation(): void
    {
        $qr = new KhqrCode(self::GUIDELINE_SOLO_STATIC);

        self::assertTrue($qr->equals(KhqrCode::from(self::GUIDELINE_SOLO_STATIC)));
        self::assertFalse($qr->equals(new KhqrCode(self::GUIDELINE_SOLO_DYNAMIC)));
        self::assertSame(\json_encode(self::GUIDELINE_SOLO_STATIC), \json_encode($qr));
    }

    private static function solo(): string
    {
        return self::tlv('29', self::tlv('00', 'shop@bank'));
    }

    /**
     * The objects of a synthetic payload, in ID order; a later object with the same ID as a frame object replaces it.
     *
     * @return list<string>
     */
    private static function frame(string ...$extra): array
    {
        $objects = [];
        foreach ([self::tlv('00', '01'), self::tlv('01', '11'), self::tlv('52', '5999'), self::tlv('53', '116'), self::tlv('58', 'KH'), self::tlv('59', 'Shop'), self::tlv('60', 'Phnom Penh'), ...$extra] as $object) {
            $objects[\substr($object, 0, 2)] = $object;
        }

        \ksort($objects, \SORT_STRING);

        return \array_values($objects);
    }
}
