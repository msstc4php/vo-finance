<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Thailand;

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
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayProxyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;
use MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\BuildsPayloads;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

// The Bank of Thailand guideline (Standardized Thai QR Code for Payment Transactions, 2019) prints the tag structure
// but no complete payload; every payload here is synthetic, built from the tag table of its section 2.3.
#[CoversClass(PromptPayQr::class)]
#[CoversClass(PromptPayProxyType::class)]
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
final class PromptPayQrTest extends TestCase
{
    use BuildsPayloads;

    private const string CREDIT_AID = 'A000000677010111';

    public function testACreditTransferToAMobileNumber(): void
    {
        $payload = self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678')), amount: '100.50'));

        $qr = new PromptPayQr($payload);

        self::assertSame($payload, $qr->value);
        self::assertSame(PromptPayProxyType::MobileNumber, $qr->proxyType());
        self::assertSame('0066812345678', $qr->proxy());
        self::assertNull($qr->billerId());
        self::assertNull($qr->reference1());
        self::assertNull($qr->reference2());
        self::assertSame('100.5', $qr->amount()?->value);
        self::assertSame(CurrencyCode::fromNumeric('764'), $qr->currency());
        self::assertSame(CountryCode::from('TH'), $qr->countryCode());
        self::assertNull($qr->merchantCategoryCode());
        self::assertNull($qr->merchantCity());
        self::assertSame('Shop', $qr->merchantName());
    }

    /**
     * @param value-of<PromptPayProxyType> $subId
     */
    #[DataProvider('proxies')]
    public function testEveryProxyType(string $subId, string $proxy, PromptPayProxyType $type): void
    {
        $qr = new PromptPayQr(self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv($subId, $proxy)))));

        self::assertSame($type, $qr->proxyType());
        self::assertSame($proxy, $qr->proxy());
    }

    /**
     * @return iterable<string, array{string, string, PromptPayProxyType}>
     */
    public static function proxies(): iterable
    {
        yield 'mobile number' => ['01', '0066812345678', PromptPayProxyType::MobileNumber];
        yield 'national ID or tax ID' => ['02', '1234567890123', PromptPayProxyType::NationalOrTaxId];
        yield 'e-wallet ID' => ['03', '123456789012345', PromptPayProxyType::EWalletId];
        yield 'bank account, bank code plus number' => ['04', '0141234567890', PromptPayProxyType::BankAccount];
        yield 'bank account at the 43 digit limit' => ['04', \str_repeat('7', 43), PromptPayProxyType::BankAccount];
    }

    public function testTheAidIsComparedCaseInsensitively(): void
    {
        $credit = new PromptPayQr(self::sealed(...$this->frame(self::tlv('29', self::tlv('00', 'a000000677010111') . self::tlv('01', '0066812345678')))));
        $bill = new PromptPayQr(self::sealed(...$this->frame(self::tlv('30', self::tlv('00', 'a000000677012006') . self::tlv('01', '010753600031507') . self::tlv('02', 'R')))));

        self::assertSame('0066812345678', $credit->proxy());
        self::assertSame('R', $bill->reference1());
    }

    public function testSeveralProxiesAreKeptAndNeverPickedSilently(): void
    {
        $qr = new PromptPayQr(self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678') . self::tlv('02', '1234567890123')))));

        self::assertSame([PromptPayProxyType::MobileNumber, PromptPayProxyType::NationalOrTaxId], $qr->proxyTypes());
        self::assertSame('1234567890123', $qr->proxyOf(PromptPayProxyType::NationalOrTaxId));
        self::assertNull($qr->proxyOf(PromptPayProxyType::EWalletId));

        try {
            $qr->proxyType();
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousPaymentQrFieldException $e) {
            self::assertSame('PromptPayQr cannot give a single value for the proxy: several distinct values.', $e->getMessage());
        }

        $this->expectException(AmbiguousPaymentQrFieldException::class);
        $qr->proxy();
    }

    public function testNoProxyTypesWithoutACreditTransfer(): void
    {
        $qr = new PromptPayQr(self::sealed(...$this->frame(self::tlv('30', self::tlv('00', 'A000000677010112') . self::tlv('01', '010753600031507') . self::tlv('02', 'R')))));

        self::assertSame([], $qr->proxyTypes());
        self::assertNull($qr->proxyOf(PromptPayProxyType::MobileNumber));
        self::assertNull($qr->proxyType());
    }

    public function testAPayloadLackingCategoryAndCityIsAcceptedAtExactlyTheLengthBound(): void
    {
        $payload = self::sealed(...$this->fillTo(7089));

        self::assertSame(7089, \strlen($payload));
        self::assertNull(new PromptPayQr($payload)->merchantCategoryCode());
    }

    public function testAPayloadOfOneCharacterMoreIsRefused(): void
    {
        $payload = self::sealed(...$this->fillTo(7090));

        self::assertSame(7090, \strlen($payload));
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('PromptPayQr must not exceed 7089 characters.');
        new PromptPayQr($payload);
    }

    public function testTheLongestReferencesAreAccepted(): void
    {
        $bill = self::tlv('30', self::tlv('00', 'A000000677010112') . self::tlv('01', '010753600031507') . self::tlv('02', \str_repeat('a', 20)) . self::tlv('03', \str_repeat('b', 20)));
        $qr = new PromptPayQr(self::sealed(...$this->frame($bill)));

        self::assertSame(\str_repeat('a', 20), $qr->reference1());
        self::assertSame(\str_repeat('b', 20), $qr->reference2());
    }

    public function testABillPaymentWithBillerIdAndReferences(): void
    {
        $bill = self::tlv('30', self::tlv('00', 'A000000677010112') . self::tlv('01', '010753600031507') . self::tlv('02', 'INV0000001') . self::tlv('03', 'CUSTOMER-77'));

        $qr = new PromptPayQr(self::sealed(...$this->frame($bill)));

        self::assertNull($qr->proxyType());
        self::assertNull($qr->proxy());
        self::assertSame('010753600031507', $qr->billerId());
        self::assertSame('INV0000001', $qr->reference1());
        self::assertSame('CUSTOMER-77', $qr->reference2());
    }

    public function testACrossBorderBillerAndTheOptionalCategoryAndCity(): void
    {
        $bill = self::tlv('30', self::tlv('00', 'A000000677012006') . self::tlv('01', '010753600031507') . self::tlv('02', \str_repeat('r', 20)));
        $payload = self::sealed(self::tlv('00', '01'), self::tlv('01', '12'), $bill, self::tlv('52', '5999'), self::tlv('53', '764'), self::tlv('58', 'JP'), self::tlv('59', 'Shop'), self::tlv('60', 'Tokyo'));

        $qr = new PromptPayQr($payload);

        self::assertSame(20, \strlen((string) $qr->reference1()));
        self::assertSame('5999', $qr->merchantCategoryCode());
        self::assertSame('Tokyo', $qr->merchantCity());
        self::assertSame(CountryCode::from('JP'), $qr->countryCode());
    }

    public function testBothTemplatesMayBePresent(): void
    {
        $credit = self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678'));
        $bill = self::tlv('30', self::tlv('00', 'A000000677010112') . self::tlv('01', '010753600031507') . self::tlv('02', 'REF'));

        $qr = new PromptPayQr(self::sealed(...$this->frame($credit . $bill)));

        self::assertSame('0066812345678', $qr->proxy());
        self::assertSame('REF', $qr->reference1());
    }

    public function testMaskingHidesTheProxyAndTheCrc(): void
    {
        $payload = self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678'))));

        $masked = new PromptPayQr($payload)->masked();

        self::assertStringNotContainsString('0066812345678', $masked);
        self::assertStringNotContainsString('A000000677010111', $masked);
        self::assertStringContainsString('2937' . \str_repeat('*', 37), $masked);
        self::assertStringEndsWith('6304****', $masked);
        self::assertStringContainsString('Shop', $masked);
    }

    public function testMaskingHidesTheBillerId(): void
    {
        $bill = self::tlv('30', self::tlv('00', 'A000000677010112') . self::tlv('01', '010753600031507') . self::tlv('02', 'INV1'));

        self::assertStringNotContainsString('010753600031507', new PromptPayQr(self::sealed(...$this->frame($bill)))->masked());
    }

    /**
     * @param list<string> $objects
     */
    #[DataProvider('refusedPayloads')]
    public function testRefusesPayloadsThatBreakTheSpecification(array $objects): void
    {
        $payload = self::sealed(...$objects);

        self::assertFalse(PromptPayQr::isValid($payload));
        $this->expectException(InvalidPaymentQrException::class);
        new PromptPayQr($payload);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function refusedPayloads(): iterable
    {
        $credit = self::credit(...);
        $bill = self::bill(...);
        $frame = self::refusalFrame(...);

        yield 'no PromptPay template' => [$frame(self::tlv('26', self::tlv('00', 'com.example') . self::tlv('01', 'x')))];
        yield 'tag 29 with another AID' => [$frame(self::tlv('29', self::tlv('00', 'A000000677010114') . self::tlv('01', '0066812345678')))];
        yield 'tag 30 with another AID' => [$frame(self::tlv('30', self::tlv('00', 'A000000677010111') . self::tlv('01', '010753600031507') . self::tlv('02', 'R')))];
        yield 'credit transfer without a proxy' => [$frame($credit(self::tlv('05', '1234567890')))];
        yield 'mobile number of 12 digits' => [$frame($credit(self::tlv('01', '006681234567')))];
        yield 'mobile number with a letter' => [$frame($credit(self::tlv('01', '006681234567A')))];
        yield 'national ID of 12 digits' => [$frame($credit(self::tlv('02', '123456789012')))];
        yield 'e-wallet ID of 14 digits' => [$frame($credit(self::tlv('03', '12345678901234')))];
        yield 'bank account of letters' => [$frame($credit(self::tlv('04', 'ABC123')))];
        yield 'biller without a biller ID' => [$frame($bill(self::tlv('02', 'REF')))];
        yield 'biller ID of 14 digits' => [$frame($bill(self::tlv('01', '01075360003150'), self::tlv('02', 'REF')))];
        yield 'biller without reference 1' => [$frame($bill(self::tlv('01', '010753600031507')))];
        yield 'reference 1 of 21 characters' => [$frame($bill(self::tlv('01', '010753600031507'), self::tlv('02', \str_repeat('r', 21))))];
        yield 'reference 2 of 21 characters' => [$frame($bill(self::tlv('01', '010753600031507'), self::tlv('02', 'R'), self::tlv('03', \str_repeat('r', 21))))];
        yield 'tip indicator' => [$frame($credit(self::tlv('01', '0066812345678')), self::tlv('55', '01'))];
        yield 'convenience fee fixed' => [$frame($credit(self::tlv('01', '0066812345678')), self::tlv('55', '02'), self::tlv('56', '1.00'))];
        yield 'convenience fee percentage' => [$frame($credit(self::tlv('01', '0066812345678')), self::tlv('57', '5.00'))];
        yield 'no merchant name' => [[self::tlv('00', '01'), $credit(self::tlv('01', '0066812345678')), self::tlv('53', '764'), self::tlv('58', 'TH')]];
    }

    public function testTheBaselineFrameIsAcceptedSoEachRefusalChangesOneThing(): void
    {
        self::assertTrue(PromptPayQr::isValid(self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678'))))));
    }

    public function testRefusesAWrongCrcAndNonPayloads(): void
    {
        $payload = self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678'))));

        self::assertFalse(PromptPayQr::isValid(\substr($payload, 0, -4) . '0000'));
        self::assertFalse(PromptPayQr::isValid(''));
        self::assertFalse(PromptPayQr::isValid('hello'));
    }

    public function testAMessageNeverContainsThePayload(): void
    {
        $payload = self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678') . self::tlv('02', '12345678901234'))));

        try {
            new PromptPayQr($payload);
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertStringNotContainsString('0066812345678', $e->getMessage());
            self::assertStringNotContainsString('12345678901234', $e->getMessage());
        }
    }

    public function testEqualityAndSerialisation(): void
    {
        $payload = self::sealed(...$this->frame(self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678'))));
        $qr = new PromptPayQr($payload);

        self::assertTrue($qr->equals(PromptPayQr::from($payload)));
        self::assertSame($payload, (string) $qr);
        self::assertSame(\json_encode($payload), \json_encode($qr));
    }

    private static function credit(string ...$subFields): string
    {
        return self::tlv('29', self::tlv('00', self::CREDIT_AID) . \implode('', $subFields));
    }

    private static function bill(string ...$subFields): string
    {
        return self::tlv('30', self::tlv('00', 'A000000677010112') . \implode('', $subFields));
    }

    /**
     * @return list<string>
     */
    private static function refusalFrame(string $account, string ...$extra): array
    {
        return [self::tlv('00', '01'), $account, self::tlv('53', '764'), self::tlv('58', 'TH'), self::tlv('59', 'Shop'), ...\array_values($extra)];
    }

    /**
     * Objects without 52 and 60 that, sealed, make a payload of exactly `$total` characters.
     *
     * @param positive-int $total
     *
     * @return list<string>
     */
    private function fillTo(int $total): array
    {
        $objects = [
            self::tlv('00', '01'),
            self::tlv('29', self::tlv('00', self::CREDIT_AID) . self::tlv('01', '0066812345678')),
            self::tlv('53', '764'),
            self::tlv('58', 'TH'),
            self::tlv('59', 'Shop'),
        ];
        $length = \array_sum(\array_map(strlen(...), $objects)) + 8;
        foreach ([...\range(2, 25), ...\range(31, 38), ...\range(40, 51), ...\range(64, 79), ...\range(80, 99)] as $id) {
            $remaining = $total - $length;
            if ($remaining === 0) {
                break;
            }

            $size = $remaining <= 103 ? $remaining - 4 : ($remaining - 103 >= 5 ? 99 : $remaining - 9);
            $template = $id >= 26 && $id <= 51;
            self::assertGreaterThan($template ? 16 : 0, $size, \sprintf('fillTo(%d) cannot fill ID %02d.', $total, $id));
            $value = $template ? self::tlv('00', 'com.fill') . self::tlv('01', \str_repeat('x', $size - 16)) : \str_repeat('x', $size);
            $objects[] = self::tlv(\sprintf('%02d', $id), $value);
            $length += $size + 4;
        }

        return $objects;
    }

    /**
     * @return list<string>
     */
    private function frame(string $accounts, ?string $amount = null): array
    {
        return [
            self::tlv('00', '01'),
            self::tlv('01', '11'),
            $accounts,
            self::tlv('53', '764'),
            ...($amount === null ? [] : [self::tlv('54', $amount)]),
            self::tlv('58', 'TH'),
            self::tlv('59', 'Shop'),
        ];
    }
}
