<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Ru;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpPaymentLink;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpQrType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SbpPaymentLink::class)]
#[UsesClass(QueryString::class)]
#[UsesClass(Mask::class)]
#[UsesClass(AmbiguousPaymentQrFieldException::class)]
#[UsesClass(PayloadBound::class)]
#[UsesClass(SbpQrType::class)]
final class SbpPaymentLinkTest extends TestCase
{
    // NSPK, "СБП API", section "Формат Функциональной ссылки СБП / URL-based QR" (schema example of the C2B link).
    private const string LINK = 'https://qr.nspk.ru/AD1000670LSS7DN18SJQDNP4B05KLJL2?type=01&bank=100000000001&sum=10000&cur=RUB&crc=C08B';

    /**
     * @return iterable<string, array{string, SbpQrType|null}>
     */
    public static function officialExamples(): iterable
    {
        // NSPK, "СБП API", the URL-based payload examples.
        yield 'C2B with query' => ['https://qr.nspk.ru/AS10003P3RH0LJ2A9ROO038L6NT5RU1M?type=01&bank=000000000001&sum=10000&cur=RUB&crc=F3D0', SbpQrType::Static];
        yield 'C2B without query' => ['https://qr.nspk.ru/AS10003P3RH0LJ2A9ROO038L6NT5RU1M', null];
        yield 'subscription with query' => ['https://sub.nspk.ru/AR50803R2RH0LJ2A9RTU038L6NT5RU1M?type=03&bank=000000000001&crc=F3D0', SbpQrType::Subscription];
        yield 'subscription without query' => ['https://sub.nspk.ru/AR50803R2RH0LJ2A9RTU038L6NT5RU1M', null];
        yield 'B2B' => ['https://b2b.cbrpay.ru/AO1000670LSS7DN18SJQDNP4B05KLJL2', null];
        yield 'schema example' => [self::LINK, SbpQrType::Static];
    }

    #[DataProvider('officialExamples')]
    public function testTheOfficialExamplesParseWithAValidStructure(string $value, ?SbpQrType $type): void
    {
        $link = new SbpPaymentLink($value);

        self::assertSame($value, $link->value);
        self::assertSame($type, $link->qrType());
        self::assertTrue($link->hasValidFunctionalLinkStructure());
    }

    public function testALinkParses(): void
    {
        $link = new SbpPaymentLink(self::LINK);

        self::assertSame('AD1000670LSS7DN18SJQDNP4B05KLJL2', $link->id());
        self::assertSame('01', $link->get('type'));
        self::assertSame('100000000001', $link->get('bank'));
        self::assertSame('10000', $link->get('sum'));
        self::assertNull($link->get('note'));
        self::assertSame(['type', 'bank', 'sum', 'cur', 'crc'], $link->parameterNames());
    }

    public function testALinkWithoutQueryParses(): void
    {
        $link = new SbpPaymentLink('https://qr.nspk.ru/AD1000670LSS7DN18SJQDNP4B05KLJL2');

        self::assertSame([], $link->parameterNames());
        self::assertNull($link->get('type'));
    }

    /**
     * @return iterable<string, array{string, SbpQrType|null}>
     */
    public static function qrTypes(): iterable
    {
        yield 'dynamic' => ['type=02', SbpQrType::Dynamic];
        yield 'unknown code' => ['type=04', null];
        yield 'empty' => ['type=', null];
        yield 'absent' => ['bank=100000000001', null];
        yield 'repeated with one value' => ['type=03&type=03', SbpQrType::Subscription];
    }

    #[DataProvider('qrTypes')]
    public function testQrTypeDecodesTheTypeParameter(string $query, ?SbpQrType $expected): void
    {
        self::assertSame($expected, SbpPaymentLink::from('https://qr.nspk.ru/AD10006M?' . $query)->qrType());
    }

    public function testQrTypeOfARepeatedTypeWithDistinctValuesIsAmbiguous(): void
    {
        $link = SbpPaymentLink::from(self::LINK . '&type=02');

        $this->expectException(AmbiguousPaymentQrFieldException::class);

        $link->qrType();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function linksOutsideTheStructure(): iterable
    {
        $id = 'AD1000670LSS7DN18SJQDNP4B05KLJL2';
        yield 'identifier of 31 characters' => ['https://qr.nspk.ru/AD1000670LSS7DN18SJQDNP4B05KLJL'];
        yield 'identifier of 33 characters' => ['https://qr.nspk.ru/AD1000670LSS7DN18SJQDNP4B05KLJL22'];
        yield 'B2B with a query' => ['https://b2b.cbrpay.ru/' . $id . '?type=01&bank=100000000001&crc=C08B'];
        yield 'empty query' => ['https://qr.nspk.ru/' . $id . '?'];
        yield 'no type' => ['https://qr.nspk.ru/' . $id . '?bank=100000000001&crc=C08B'];
        yield 'type 04' => ['https://qr.nspk.ru/' . $id . '?type=04&bank=100000000001&crc=C08B'];
        yield 'type 03 on qr.nspk.ru' => ['https://qr.nspk.ru/' . $id . '?type=03&bank=100000000001&crc=C08B'];
        yield 'type 01 on sub.nspk.ru' => ['https://sub.nspk.ru/' . $id . '?type=01&bank=100000000001&crc=C08B'];
        yield 'no bank' => ['https://qr.nspk.ru/' . $id . '?type=01&crc=C08B'];
        yield 'bank of 11 characters' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=10000000000&crc=C08B'];
        yield 'bank of 13 characters' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=1000000000011&crc=C08B'];
        yield 'no crc' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=100000000001'];
        yield 'crc of 3 characters' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=100000000001&crc=C08'];
        yield 'crc of 5 characters' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=100000000001&crc=C08BA'];
        yield 'dynamic without sum' => ['https://qr.nspk.ru/' . $id . '?type=02&bank=100000000001&crc=C08B'];
        yield 'dynamic with an empty sum' => ['https://qr.nspk.ru/' . $id . '?type=02&bank=100000000001&sum=&cur=RUB&crc=C08B'];
        yield 'subscription with sum' => ['https://sub.nspk.ru/' . $id . '?type=03&bank=100000000001&sum=100&cur=RUB&crc=C08B'];
        yield 'sum without cur' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=100000000001&sum=100&crc=C08B'];
        yield 'sum with decimals' => ['https://qr.nspk.ru/' . $id . '?type=02&bank=100000000001&sum=1.00&cur=RUB&crc=C08B'];
        yield 'sum of 13 digits' => ['https://qr.nspk.ru/' . $id . '?type=02&bank=100000000001&sum=1234567890123&cur=RUB&crc=C08B'];
        yield 'cur of 2 characters' => ['https://qr.nspk.ru/' . $id . '?type=02&bank=100000000001&sum=100&cur=RU&crc=C08B'];
        yield 'cur of 4 characters' => ['https://qr.nspk.ru/' . $id . '?type=02&bank=100000000001&sum=100&cur=RUBL&crc=C08B'];
        yield 'type with distinct values' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=100000000001&crc=C08B&type=02'];
        yield 'bank with distinct values' => ['https://qr.nspk.ru/' . $id . '?type=01&bank=100000000001&crc=C08B&bank=100000000002'];
    }

    #[DataProvider('linksOutsideTheStructure')]
    public function testALinkOutsideNspksStructureIsAcceptedAndReported(string $value): void
    {
        self::assertFalse(SbpPaymentLink::from($value)->hasValidFunctionalLinkStructure());
    }

    public function testTheStructureAllowsAStaticLinkWithACurrencyAndNoSumAndOtherParameters(): void
    {
        self::assertTrue(SbpPaymentLink::from('https://qr.nspk.ru/AD1000670LSS7DN18SJQDNP4B05KLJL2?type=01&bank=LF0000000001&cur=RUB&crc=ZZZZ&note=x')->hasValidFunctionalLinkStructure());
        self::assertTrue(SbpPaymentLink::from('HTTPS://SUB.NSPK.RU/AR50803R2RH0LJ2A9RTU038L6NT5RU1M?type=03&bank=000000000001&crc=F3D0&type=03')->hasValidFunctionalLinkStructure());
        self::assertTrue(SbpPaymentLink::from('https://qr.nspk.ru/AD1000670LSS7DN18SJQDNP4B05KLJL2?type=02&bank=100000000001&sum=123456789012&cur=RUB&crc=C08B')->hasValidFunctionalLinkStructure());
    }

    public function testSchemeAndHostAreCaseInsensitive(): void
    {
        // RFC 3986, sections 3.1 and 3.2.2: scheme and host are case-insensitive.
        self::assertTrue(SbpPaymentLink::isValid('HTTPS://QR.NSPK.RU/AS1000670LSS7DN18SJQDNP4B05KLJL2'));
        self::assertTrue(SbpPaymentLink::isValid('https://Sub.Nspk.Ru/AS1000670LSS7DN18SJQDNP4B05KLJL2'));
        self::assertTrue(SbpPaymentLink::isValid('https://B2B.CBRPAY.RU/AS1000670LSS7DN18SJQDNP4B05KLJL2'));
    }

    public function testAnEmptyParameterIsReadAsAbsent(): void
    {
        $link = SbpPaymentLink::from('https://qr.nspk.ru/AD10006M?type=01&sum=&cur');

        self::assertNull($link->get('sum'));
        self::assertNull($link->get('cur'));
        self::assertContains('sum', $link->parameterNames());
    }

    public function testARepeatedParameterWithOneValueIsRead(): void
    {
        self::assertSame('01', SbpPaymentLink::from(self::LINK . '&type=01')->get('type'));
    }

    public function testARepeatedParameterWithDistinctValuesIsAmbiguous(): void
    {
        $link = SbpPaymentLink::from(self::LINK . '&type=02');

        $this->expectException(AmbiguousPaymentQrFieldException::class);

        $link->get('type');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'http' => ['http://qr.nspk.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'other host' => ['https://qr.nspk.ru.example/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'other NSPK host' => ['https://sbp.nspk.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'B2B host on nspk.ru' => ['https://b2b.nspk.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'subdomain of a B2B host' => ['https://x.b2b.cbrpay.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2'];
        yield 'no id' => ['https://qr.nspk.ru/'];
        yield 'no id before the query' => ['https://qr.nspk.ru/?type=01'];
        yield 'nested path' => ['https://qr.nspk.ru/AS10/00670'];
        yield 'fragment' => [self::LINK . '#top'];
        yield 'space' => [self::LINK . ' x'];
        yield 'non-ASCII id' => ['https://qr.nspk.ru/АБВ'];
    }

    #[DataProvider('invalid')]
    public function testInvalidLinksAreRefused(string $value): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new SbpPaymentLink($value);
    }

    public function testMaskedHidesTheId(): void
    {
        $masked = SbpPaymentLink::from(self::LINK)->masked();

        self::assertSame('https://qr.nspk.ru/AD' . \str_repeat('*', 26) . 'LJL2?type=01&bank=100000000001&sum=10000&cur=RUB&crc=C08B', $masked);
        self::assertStringNotContainsString('AD1000670LSS7DN18SJQDNP4B05KLJL2', $masked);
    }

    public function testMaskedKeepsEachHostAsWritten(): void
    {
        self::assertSame('https://Sub.Nspk.Ru/AR' . \str_repeat('*', 26) . 'RU1M?type=03&bank=000000000001&crc=F3D0', SbpPaymentLink::from('https://Sub.Nspk.Ru/AR50803R2RH0LJ2A9RTU038L6NT5RU1M?type=03&bank=000000000001&crc=F3D0')->masked());
        self::assertSame('https://b2b.cbrpay.ru/AO' . \str_repeat('*', 26) . 'LJL2', SbpPaymentLink::from('https://b2b.cbrpay.ru/AO1000670LSS7DN18SJQDNP4B05KLJL2')->masked());
    }

    public function testMaskedHidesEveryQueryValueOutsideTheKnownCodes(): void
    {
        $masked = SbpPaymentLink::from('https://qr.nspk.ru/AD10006M?type=02&purpose=Anna%20Ivanova&sum=10000&cur=RUB&Bank=x&note=&flag&crc=AB12')->masked();

        self::assertSame('https://qr.nspk.ru/******6M?type=02&***=***&sum=10000&cur=RUB&***=***&***=&***&crc=AB12', $masked);
    }

    public function testMaskedHidesBareParametersAndNamesThatAreText(): void
    {
        self::assertSame('https://qr.nspk.ru/******6M?***&***&&***=***', SbpPaymentLink::from('https://qr.nspk.ru/AD10006M?79101234567&anna@x.com&&anna@x.com=1')->masked());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function allowlistedValuesWithoutACodeShape(): iterable
    {
        yield 'sum as text' => ['sum=Anna-Ivanova', 'sum=***'];
        yield 'bank as e-mail' => ['bank=anna@x.com', 'bank=***'];
        yield 'type of three digits' => ['type=123', 'type=***'];
        yield 'type other than 01 to 03' => ['type=04', 'type=***'];
        yield 'type of one digit' => ['type=1', 'type=***'];
        yield 'bank of 13 digits' => ['bank=1000000000011', 'bank=***'];
        yield 'bank as a phone number' => ['bank=79101234567', 'bank=***'];
        yield 'sum with decimals' => ['sum=1.00', 'sum=***'];
        yield 'sum with a bare point' => ['sum=1.', 'sum=***'];
        yield 'sum of 13 digits' => ['sum=1234567890123', 'sum=***'];
        yield 'lowercase currency' => ['cur=rub', 'cur=***'];
        yield 'crc of three characters' => ['crc=ABC', 'crc=***'];
        yield 'crc of five characters' => ['crc=ABCDE', 'crc=***'];
        yield 'crc with a non-hex letter' => ['crc=ABCG', 'crc=***'];
        yield 'bank of 12 letters and digits' => ['bank=LF0000000001', 'bank=***'];
        yield 'bare allowlisted name' => ['sum', '***'];
        yield 'empty allowlisted value' => ['sum=', 'sum='];
        yield 'all shapes at their bounds' => ['type=01&bank=123456789012&sum=123456789012&cur=EUR&crc=abcd', 'type=01&bank=123456789012&sum=123456789012&cur=EUR&crc=abcd'];
        yield 'type 02 and a one-digit sum' => ['type=02&sum=0', 'type=02&sum=0'];
        yield 'type 03' => ['type=03', 'type=03'];
    }

    #[DataProvider('allowlistedValuesWithoutACodeShape')]
    public function testMaskedKeepsAnAllowlistedValueOnlyInItsCodeShape(string $query, string $expected): void
    {
        self::assertSame($expected, \substr(SbpPaymentLink::from('https://qr.nspk.ru/AD10006M?' . $query)->masked(), 28));
    }

    public function testMaskedKeepsALinkWithoutAQueryOrWithAnEmptyOne(): void
    {
        self::assertSame('https://qr.nspk.ru/******6M', SbpPaymentLink::from('https://qr.nspk.ru/AD10006M')->masked());
        self::assertSame('https://qr.nspk.ru/******6M?', SbpPaymentLink::from('https://qr.nspk.ru/AD10006M?')->masked());
    }

    public function testMessagesCarryNoPartOfTheLink(): void
    {
        try {
            new SbpPaymentLink('http://qr.nspk.ru/SECRETID');
            self::fail('Expected an exception.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringNotContainsString('SECRETID', $exception->getMessage());
        }
    }

    public function testTheLinkIsBoundedAtTheLargestQrContent(): void
    {
        $link = 'https://qr.nspk.ru/' . \str_repeat('A', 7089 - 19);
        self::assertSame($link, (string) SbpPaymentLink::from($link));

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('SbpPaymentLink must not exceed 7089 characters.');

        SbpPaymentLink::from($link . 'A');
    }
}
