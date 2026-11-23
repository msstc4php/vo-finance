<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Ru;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpPaymentLink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SbpPaymentLink::class)]
#[UsesClass(QueryString::class)]
#[UsesClass(Mask::class)]
#[UsesClass(AmbiguousPaymentQrFieldException::class)]
#[UsesClass(PayloadBound::class)]
final class SbpPaymentLinkTest extends TestCase
{
    // Synthetic: NSPK publishes no link layout (see FINANCE.md "NSPK SBP"); the shape follows the plan's format-only rule.
    private const string LINK = 'https://qr.nspk.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2?type=01&bank=100000000001&crc=C08B';

    public function testALinkParses(): void
    {
        $link = new SbpPaymentLink(self::LINK);

        self::assertSame(self::LINK, $link->value);
        self::assertSame('AS1000670LSS7DN18SJQDNP4B05KLJL2', $link->id());
        self::assertSame('01', $link->get('type'));
        self::assertSame('100000000001', $link->get('bank'));
        self::assertNull($link->get('sum'));
        self::assertSame(['type', 'bank', 'crc'], $link->parameterNames());
    }

    public function testALinkWithoutQueryParses(): void
    {
        $link = new SbpPaymentLink('https://qr.nspk.ru/AS1000670LSS7DN18SJQDNP4B05KLJL2');

        self::assertSame([], $link->parameterNames());
        self::assertNull($link->get('type'));
    }

    public function testSchemeAndHostAreCaseInsensitive(): void
    {
        // RFC 3986, sections 3.1 and 3.2.2: scheme and host are case-insensitive.
        self::assertTrue(SbpPaymentLink::isValid('HTTPS://QR.NSPK.RU/AS1000670LSS7DN18SJQDNP4B05KLJL2'));
    }

    public function testAnEmptyParameterIsReadAsAbsent(): void
    {
        $link = SbpPaymentLink::from(self::LINK . '&sum=&cur');

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

        self::assertSame('https://qr.nspk.ru/AS' . \str_repeat('*', 26) . 'LJL2?type=01&bank=100000000001&crc=C08B', $masked);
        self::assertStringNotContainsString('AS1000670LSS7DN18SJQDNP4B05KLJL2', $masked);
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
        yield 'type other than 01 and 02' => ['type=03', 'type=***'];
        yield 'type of one digit' => ['type=1', 'type=***'];
        yield 'bank of 13 digits' => ['bank=1000000000011', 'bank=***'];
        yield 'bank as a phone number' => ['bank=79101234567', 'bank=***'];
        yield 'sum with three decimals' => ['sum=1.234', 'sum=***'];
        yield 'sum with a bare point' => ['sum=1.', 'sum=***'];
        yield 'sum of 19 digits' => ['sum=1234567890123456789', 'sum=***'];
        yield 'lowercase currency' => ['cur=rub', 'cur=***'];
        yield 'crc of nine characters' => ['crc=ABCDEF012', 'crc=***'];
        yield 'crc with a non-hex letter' => ['crc=AG', 'crc=***'];
        yield 'bare allowlisted name' => ['sum', '***'];
        yield 'empty allowlisted value' => ['sum=', 'sum='];
        yield 'all shapes at their bounds' => ['type=01&bank=123456789012&sum=123456789012345678.99&cur=EUR&crc=abcdef01', 'type=01&bank=123456789012&sum=123456789012345678.99&cur=EUR&crc=abcdef01'];
        yield 'type 02 and a one-decimal sum' => ['type=02&sum=0.5', 'type=02&sum=0.5'];
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
