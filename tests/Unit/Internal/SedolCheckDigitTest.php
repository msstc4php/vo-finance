<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\SedolCheckDigit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SedolCheckDigit::class)]
final class SedolCheckDigitTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function sedols(): iterable
    {
        // SEDOL Service and Technical Guide v8.8, sections 8.3 and 8.4.
        yield 'guide 8.3 example 1' => ['B1F3M5', '9'];
        yield 'guide 8.4 example' => ['B12345', '6'];
        // The numeric legacy SEDOL named in the task brief.
        yield 'numeric' => ['026349', '4'];
        yield 'every letter value' => ['BCDFGH', '4'];
        yield 'upper letters' => ['JKLMNP', '2'];
        yield 'last letters' => ['QRSTVW', '1'];
        yield 'XYZ' => ['XYZ000', '0'];
    }

    #[DataProvider('sedols')]
    public function testComputes(string $body, string $expected): void
    {
        self::assertSame($expected, SedolCheckDigit::compute($body));
    }

    #[DataProvider('sedols')]
    public function testValidates(string $body, string $check): void
    {
        self::assertTrue(SedolCheckDigit::isValid($body . $check));
    }

    public function testRejectsTheGuidesIncorrectlyQuotedNumber(): void
    {
        self::assertFalse(SedolCheckDigit::isValid('B1H54P6'));
    }

    public function testRejectsAWrongLengthAndNonDigitCheck(): void
    {
        self::assertFalse(SedolCheckDigit::isValid('B1F3M5'));
        self::assertFalse(SedolCheckDigit::isValid('B1F3M590'));
        self::assertFalse(SedolCheckDigit::isValid('B1F3M5X'));
    }

    public function testRejectsAVowel(): void
    {
        $this->expectException(LogicException::class);
        SedolCheckDigit::compute('A1F3M5');
    }

    public function testRejectsAShortBody(): void
    {
        $this->expectException(LogicException::class);
        SedolCheckDigit::compute('B1F3M');
    }
}
