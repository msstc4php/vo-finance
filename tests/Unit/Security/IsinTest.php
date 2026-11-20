<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Security;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\IsinCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\Luhn;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Internal\SedolCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Security\Cusip;
use MSSTC4PHP\ValueObject\Financial\Security\Isin;
use MSSTC4PHP\ValueObject\Financial\Security\Sedol;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Isin::class)]
#[UsesClass(SecurityRules::class)]
#[UsesClass(IsinCheckDigit::class)]
#[UsesClass(Luhn::class)]
#[UsesClass(CusipCheckDigit::class)]
#[UsesClass(SedolCheckDigit::class)]
#[UsesClass(Cusip::class)]
#[UsesClass(Sedol::class)]
final class IsinTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function valid(): iterable
    {
        yield 'SET example 1' => ['TH0013057B05'];
        yield 'SET example 2' => ['TH0646010007'];
        yield 'SET example 3' => ['TH0268010Z11'];
        yield 'US' => ['US0378331005'];
        yield 'CGS Amazon' => ['US0231351067'];
        yield 'CGS Air Canada' => ['CA0089117034'];
    }

    #[DataProvider('valid')]
    public function testAccepts(string $value): void
    {
        self::assertSame($value, (new Isin($value))->value);
    }

    public function testLowercaseIsUppercased(): void
    {
        self::assertSame('US0378331005', (string) new Isin('us0378331005'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'wrong check digit' => ['US0378331006'];
        yield 'short' => ['US037833100'];
        yield 'long' => ['US03783310050'];
        yield 'digit prefix' => ['1S0378331005'];
        yield 'bad character' => ['US037833100-'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalid')]
    public function testRefuses(string $value): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        new Isin($value);
    }

    public function testMessageNamesOnlyTheType(): void
    {
        try {
            new Isin('US0378331006');
            self::fail('Expected an exception.');
        } catch (InvalidSecurityIdentifierException $e) {
            self::assertSame('ISIN has a wrong check digit.', $e->getMessage());
        }
    }

    public function testParts(): void
    {
        $isin = new Isin('US0378331005');

        self::assertSame('US', $isin->prefix());
        self::assertSame('037833100', $isin->nsin());
        self::assertSame(CountryCode::US, $isin->countryCode());
    }

    public function testFromNsin(): void
    {
        $isin = Isin::fromNsin('US', '037833100');

        self::assertSame('US0378331005', $isin->value);
        self::assertSame('037833100', $isin->cusip()?->value);
        self::assertNull($isin->sedol());
    }

    public function testFromNsinPadsWithZeros(): void
    {
        self::assertSame('00000000B', \substr((string) Isin::fromNsin('XS', 'B'), 2, 9));
    }

    public function testFromNsinRefusesLongNsin(): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        Isin::fromNsin('US', '0378331005');
    }

    public function testFromNsinRefusesEmptyNsin(): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        Isin::fromNsin('US', '');
    }

    public function testXsPrefixHasNoCountry(): void
    {
        $isin = Isin::fromNsin('XS', '000000001');

        self::assertSame('XS', $isin->prefix());
        self::assertNull($isin->countryCode());
        self::assertNull($isin->cusip());
        self::assertNull($isin->sedol());
    }

    public function testCanadaCusip(): void
    {
        self::assertInstanceOf(Cusip::class, Isin::fromNsin('CA', '037833100')->cusip());
    }

    public function testSedolOfGreatBritainAndIreland(): void
    {
        $gb = Isin::fromNsin('GB', '000263494');
        $ie = Isin::fromNsin('IE', '000263494');

        self::assertInstanceOf(Sedol::class, $gb->sedol());
        self::assertSame('0263494', $gb->sedol()->value);
        self::assertSame('0263494', $ie->sedol()?->value);
        self::assertNull(Isin::fromNsin('GB', '123456789')->sedol());
        self::assertNull($gb->cusip());
    }

    public function testCusipIsNullWhenNsinIsNotACusip(): void
    {
        self::assertNull(Isin::fromNsin('US', '037833101')->cusip());
    }
}
