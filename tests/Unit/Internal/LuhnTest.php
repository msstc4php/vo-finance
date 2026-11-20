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
        // The ISIN of the CUSIP Global Services guide's Apple example, letters expanded to digits (U=30, S=28).
        yield 'ISIN US037833100 expanded' => ['3028037833100', '5'];
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
        self::assertFalse(Luhn::isValid('30280378331004'));
        self::assertFalse(Luhn::isValid('30280378331006'));
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
