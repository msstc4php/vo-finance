<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\Luhn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Luhn::class)]
final class LuhnTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function bodies(): iterable
    {
        // The Stock Exchange of Thailand, "ISIN calculation": the expanded digits of its three worked examples.
        yield 'TH0013057B0 expanded' => ['29170013057110', '5'];
        yield 'TH064601000 expanded' => ['2917064601000', '7'];
        yield 'TH0268010Z1 expanded' => ['29170268010351', '1'];
        yield 'single digit' => ['0', '0'];
        yield 'doubling carries' => ['5', '9'];
        yield 'doubled nine' => ['9', '1'];
        yield 'two digits' => ['18', '2'];
    }

    #[DataProvider('bodies')]
    public function testComputesTheCheckDigit(string $body, string $expected): void
    {
        self::assertSame($expected, Luhn::compute($body));
    }

    #[DataProvider('bodies')]
    public function testAcceptsTheBodyWithItsCheckDigit(string $body, string $expected): void
    {
        self::assertTrue(Luhn::isValid($body . $expected));
    }

    public function testRejectsAWrongCheckDigit(): void
    {
        self::assertFalse(Luhn::isValid('29170646010006'));
        self::assertFalse(Luhn::isValid('29170646010008'));
    }

    public function testRejectsTooShortAndNonDigitValues(): void
    {
        self::assertFalse(Luhn::isValid(''));
        self::assertFalse(Luhn::isValid('5'));
    }

    public function testRejectsNonDigitsBeforeTheCheckDigit(): void
    {
        $this->expectException(LogicException::class);
        Luhn::compute('12A4');
    }

    public function testRejectsAnEmptyBody(): void
    {
        $this->expectException(LogicException::class);
        Luhn::compute('');
    }

    public function testIsValidDoesNotThrowOnLetters(): void
    {
        self::assertFalse(Luhn::isValid('12A45'));
    }
}
