<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Malaysia;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\BuildsPayloads;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DuitNowQr::class)]
#[UsesClass(ProfilePayload::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoMask::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(InvalidPaymentQrException::class)]
final class DuitNowQrTest extends TestCase
{
    use BuildsPayloads;

    // PayNet, DuitNow QR merchant-presented mode 1.5, "Examples" 1 to 4 (payload format indicator 02).
    private const string EXAMPLE_POS = '00020201021126410014A000000615000101065016640209123456789520499995303458540510.005802MY5909QRCSDNBHD6005BANGI6304343F';

    private const string EXAMPLE_P2P = '00020201021126410014A000000615000101065016640209123456789520400005303458540510.005802MY5909AUSERNAME6005BANGI63043A23';

    private const string EXAMPLE_INTEGRITY = '00020201021126410014A000000615000101065016640209123456789520499995303458540510.005802MY5909QRCSDNBHD6005BANGI8264f879ac6266d8d6da081fc1c17160021119747b190615c21076b3fc0fce56c6eb6304B623';

    private const string EXAMPLE_JOMPAY = '00020201021126360014A00000061500010106898989020412345204111153034585802MY5903TNB6002KL610559200621790130124007552406823231d4db4506dc9852b1c21c74be148b8363045DFB';

    public function testThePosExampleParsesFieldByField(): void
    {
        $qr = new DuitNowQr(self::EXAMPLE_POS);

        self::assertSame(self::EXAMPLE_POS, $qr->value);
        self::assertSame(PointOfInitiation::Static, $qr->pointOfInitiation());
        self::assertSame('501664', $qr->acquirerId());
        self::assertSame('123456789', $qr->qrId());
        self::assertNull($qr->merchantDescriptor());
        self::assertNull($qr->mobileNumber());
        self::assertSame('9999', $qr->merchantCategoryCode());
        self::assertSame(CurrencyCode::fromNumeric('458'), $qr->currency());
        self::assertSame('10', $qr->amount()?->value);
        self::assertSame(CountryCode::from('MY'), $qr->countryCode());
        self::assertSame('QRCSDNBHD', $qr->merchantName());
        self::assertSame('BANGI', $qr->merchantCity());
        self::assertNull($qr->postalCode());
        self::assertFalse($qr->isJomPay());
    }

    public function testTheP2pExampleUsesMerchantCategoryCode0000(): void
    {
        $qr = new DuitNowQr(self::EXAMPLE_P2P);

        self::assertSame('0000', $qr->merchantCategoryCode());
        self::assertSame('AUSERNAME', $qr->merchantName());
    }

    public function testTheExampleWithADataIntegrityCheckParses(): void
    {
        self::assertSame('501664', new DuitNowQr(self::EXAMPLE_INTEGRITY)->acquirerId());
    }

    public function testTheJomPayExample(): void
    {
        $qr = new DuitNowQr(self::EXAMPLE_JOMPAY);

        self::assertTrue($qr->isJomPay());
        self::assertSame('898989', $qr->acquirerId());
        self::assertSame('1234', $qr->qrId());
        self::assertSame('59200', $qr->postalCode());
        self::assertNull($qr->amount());
        self::assertSame(PointOfInitiation::Static, $qr->pointOfInitiation());
    }

    public function testTheAidIsComparedCaseInsensitively(): void
    {
        $account = self::tlv('26', self::tlv('00', 'a0000006150001') . self::tlv('01', '501664') . self::tlv('02', '123456789'));

        self::assertSame('501664', new DuitNowQr(self::sealed(...$this->frame($account)))->acquirerId());
    }

    public function testTheOptionalAccountFields(): void
    {
        $account = self::tlv('26', self::tlv('00', 'A0000006150001') . self::tlv('01', '501664') . self::tlv('02', 'MERCHANT-77') . self::tlv('03', 'Interesting Wares') . self::tlv('04', '60123456789'));

        $qr = new DuitNowQr(self::sealed(...$this->frame($account, point: '12')));

        self::assertSame(PointOfInitiation::Dynamic, $qr->pointOfInitiation());
        self::assertSame('MERCHANT-77', $qr->qrId());
        self::assertSame('Interesting Wares', $qr->merchantDescriptor());
        self::assertSame('60123456789', $qr->mobileNumber());
    }

    public function testTheLongestAccountFieldsNameAndCityAreAccepted(): void
    {
        $long = new DuitNowQr(self::sealed(...$this->longFrame(self::tlv('26', self::tlv('00', 'A0000006150001') . self::tlv('01', '123456') . self::tlv('02', \str_repeat('q', 28))))));
        $optional = new DuitNowQr(self::sealed(...$this->longFrame(self::tlv('26', self::tlv('00', 'A0000006150001') . self::tlv('01', '1') . self::tlv('02', '1') . self::tlv('03', \str_repeat('d', 20)) . self::tlv('04', \str_repeat('9', 15))))));

        self::assertSame('123456', $long->acquirerId());
        self::assertSame(28, \strlen($long->qrId()));
        self::assertSame(20, \strlen((string) $optional->merchantDescriptor()));
        self::assertSame(15, \strlen((string) $optional->mobileNumber()));
        self::assertSame(25, \strlen((string) $long->merchantName()));
        self::assertSame(15, \strlen((string) $long->merchantCity()));
    }

    public function testMaskingHidesTheAccountAndTheCrcAndKeepsTheFormatIndicator(): void
    {
        $masked = new DuitNowQr(self::EXAMPLE_POS)->masked();

        self::assertStringStartsWith('000202', $masked);
        self::assertStringNotContainsString('501664', $masked);
        self::assertStringNotContainsString('123456789', $masked);
        self::assertStringNotContainsString('A0000006150001', $masked);
        self::assertStringContainsString('2641' . \str_repeat('*', 41), $masked);
        self::assertStringEndsWith('6304****', $masked);
        self::assertStringNotContainsString('343F', $masked);
        self::assertStringContainsString('QRCSDNBHD', $masked);
    }

    public function testMaskingHidesTheDataIntegrityCheck(): void
    {
        self::assertStringNotContainsString('64f879ac', new DuitNowQr(self::EXAMPLE_INTEGRITY)->masked());
    }

    /**
     * @param list<string> $objects
     */
    #[DataProvider('refusedPayloads')]
    public function testRefusesPayloadsThatBreakTheSpecification(array $objects): void
    {
        $payload = self::sealed(...$objects);

        self::assertFalse(DuitNowQr::isValid($payload));
        $this->expectException(InvalidPaymentQrException::class);
        new DuitNowQr($payload);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function refusedPayloads(): iterable
    {
        $account = self::account(...);
        $valid = $account(self::tlv('01', '501664'), self::tlv('02', '123456789'));
        $frame = self::refusalFrame(...);
        $without = static fn (string $id): array => \array_values(\array_filter($frame($valid), static fn (string $object): bool => !\str_starts_with($object, $id)));

        yield 'payload format indicator 01' => [[self::tlv('00', '01'), ...\array_slice($frame($valid), 1)]];
        yield 'no payload format indicator' => [\array_slice($frame($valid), 1)];
        yield 'no point of initiation' => [$without('01')];
        yield 'point of initiation 13' => [[self::tlv('00', '02'), self::tlv('01', '13'), ...\array_slice($frame($valid), 2)]];
        yield 'no merchant account' => [$without('26')];
        yield 'merchant account with another AID' => [$frame(self::tlv('26', self::tlv('00', 'A0000006150002') . self::tlv('01', '501664') . self::tlv('02', '1')))];
        yield 'AID in ID 27 only' => [$frame(self::tlv('27', self::tlv('00', 'A0000006150001') . self::tlv('01', '501664') . self::tlv('02', '1')))];
        yield 'no acquirer ID' => [$frame($account(self::tlv('02', '1')))];
        yield 'acquirer ID of 7 characters' => [$frame($account(self::tlv('01', '5016640'), self::tlv('02', '1')))];
        yield 'no QR ID' => [$frame($account(self::tlv('01', '501664')))];
        yield 'QR ID of 29 characters' => [$frame($account(self::tlv('01', '501664'), self::tlv('02', \str_repeat('q', 29))))];
        yield 'merchant descriptor of 21 characters' => [$frame($account(self::tlv('01', '501664'), self::tlv('02', '1'), self::tlv('03', \str_repeat('d', 21))))];
        yield 'mobile number of 16 characters' => [$frame($account(self::tlv('01', '501664'), self::tlv('02', '1'), self::tlv('04', \str_repeat('9', 16))))];
        yield 'no merchant category code' => [$without('52')];
        yield 'no currency' => [$without('53')];
        yield 'currency other than 458' => [[self::tlv('00', '02'), self::tlv('01', '11'), $valid, self::tlv('52', '9999'), self::tlv('53', '840'), self::tlv('58', 'MY'), self::tlv('59', 'Shop'), self::tlv('60', 'Bangi')]];
        yield 'country other than MY' => [[self::tlv('00', '02'), self::tlv('01', '11'), $valid, self::tlv('52', '9999'), self::tlv('53', '458'), self::tlv('58', 'SG'), self::tlv('59', 'Shop'), self::tlv('60', 'Bangi')]];
        yield 'merchant name of 26 characters' => [[self::tlv('00', '02'), self::tlv('01', '11'), $valid, self::tlv('52', '9999'), self::tlv('53', '458'), self::tlv('58', 'MY'), self::tlv('59', \str_repeat('n', 26)), self::tlv('60', 'Bangi')]];
        yield 'merchant city of 16 characters' => [[self::tlv('00', '02'), self::tlv('01', '11'), $valid, self::tlv('52', '9999'), self::tlv('53', '458'), self::tlv('58', 'MY'), self::tlv('59', 'Shop'), self::tlv('60', \str_repeat('c', 16))]];
        yield 'postal code of 4 digits' => [$frame($valid, self::tlv('61', '5920'))];
        yield 'postal code with a letter' => [$frame($valid, self::tlv('61', '5920A'))];
        yield 'zero amount' => [$frame($valid, self::tlv('54', '0.00'))];
        yield 'zero amount without decimals' => [$frame($valid, self::tlv('54', '0'))];
        yield 'convenience fee fixed without the indicator' => [$frame($valid, self::tlv('56', '1.00'))];
        yield 'indicator 02 without the fixed fee' => [$frame($valid, self::tlv('55', '02'))];
        yield 'indicator 02 with a zero fixed fee' => [$frame($valid, self::tlv('55', '02'), self::tlv('56', '0.00'))];
        yield 'convenience fee percentage without the indicator' => [$frame($valid, self::tlv('57', '0.06'))];
        yield 'indicator 03 without the percentage' => [$frame($valid, self::tlv('55', '03'))];
        yield 'indicator 01 with a fixed fee' => [$frame($valid, self::tlv('55', '01'), self::tlv('56', '1.00'))];
        yield 'language template without the merchant name' => [$frame($valid, self::tlv('64', self::tlv('00', 'ms')))];
        yield 'language template without the language' => [$frame($valid, self::tlv('64', self::tlv('01', 'Kedai')))];
        yield 'JomPAY without the additional data template' => [$frame($account(self::tlv('01', '898989'), self::tlv('02', '1234')))];
        yield 'JomPAY without the RRN' => [$frame($account(self::tlv('01', '898989'), self::tlv('02', '1234')), self::tlv('62', self::tlv('01', 'bill')))];
    }

    public function testTheFrameWithEveryOptionalPartIsAcceptedSoEachRefusalChangesOneThing(): void
    {
        $account = self::tlv('26', self::tlv('00', 'A0000006150001') . self::tlv('01', '501664') . self::tlv('02', '123456789'));
        $payload = self::sealed(
            self::tlv('00', '02'),
            self::tlv('01', '11'),
            $account,
            self::tlv('52', '9999'),
            self::tlv('53', '458'),
            self::tlv('54', '12.50'),
            self::tlv('55', '02'),
            self::tlv('56', '1.00'),
            self::tlv('58', 'MY'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'Bangi'),
            self::tlv('61', '59200'),
            self::tlv('64', self::tlv('00', 'ms') . self::tlv('01', 'Kedai')),
        );

        self::assertTrue(DuitNowQr::isValid($payload));
        self::assertTrue(DuitNowQr::isValid(self::sealed(
            self::tlv('00', '02'),
            self::tlv('01', '11'),
            $account,
            self::tlv('52', '9999'),
            self::tlv('53', '458'),
            self::tlv('55', '03'),
            self::tlv('57', '0.06'),
            self::tlv('58', 'MY'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'Bangi'),
        )));
    }

    public function testRefusesAWrongCrcAndNonPayloads(): void
    {
        self::assertFalse(DuitNowQr::isValid(\substr(self::EXAMPLE_POS, 0, -4) . '0000'));
        self::assertFalse(DuitNowQr::isValid(''));
        self::assertFalse(DuitNowQr::isValid('hello'));
    }

    public function testAMessageNeverContainsThePayload(): void
    {
        $payload = self::sealed(...$this->frame(self::tlv('26', self::tlv('00', 'A0000006150001') . self::tlv('01', '501664') . self::tlv('02', 'SECRET-QR-ID') . self::tlv('03', \str_repeat('d', 21)))));

        try {
            new DuitNowQr($payload);
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertStringNotContainsString('SECRET-QR-ID', $e->getMessage());
            self::assertStringNotContainsString('501664', $e->getMessage());
        }
    }

    public function testEqualityAndSerialisation(): void
    {
        $qr = new DuitNowQr(self::EXAMPLE_POS);

        self::assertTrue($qr->equals(DuitNowQr::from(self::EXAMPLE_POS)));
        self::assertFalse($qr->equals(new DuitNowQr(self::EXAMPLE_P2P)));
        self::assertSame(\json_encode(self::EXAMPLE_POS), \json_encode($qr));
    }

    private static function account(string ...$subFields): string
    {
        return self::tlv('26', self::tlv('00', 'A0000006150001') . \implode('', $subFields));
    }

    /**
     * @return list<string>
     */
    private static function refusalFrame(string $accountObject, string ...$extra): array
    {
        return [
            self::tlv('00', '02'), self::tlv('01', '11'), $accountObject, self::tlv('52', '9999'), self::tlv('53', '458'),
            self::tlv('58', 'MY'), self::tlv('59', 'Shop'), self::tlv('60', 'Bangi'), ...\array_values($extra),
        ];
    }

    /**
     * @return list<string>
     */
    private function longFrame(string $account): array
    {
        return [
            self::tlv('00', '02'), self::tlv('01', '11'), $account, self::tlv('52', '9999'), self::tlv('53', '458'),
            self::tlv('58', 'MY'), self::tlv('59', \str_repeat('n', 25)), self::tlv('60', \str_repeat('c', 15)),
        ];
    }

    /**
     * @return list<string>
     */
    private function frame(string $account, string $point = '11'): array
    {
        return [
            self::tlv('00', '02'),
            self::tlv('01', $point),
            $account,
            self::tlv('52', '9999'),
            self::tlv('53', '458'),
            self::tlv('58', 'MY'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'Bangi'),
        ];
    }
}
