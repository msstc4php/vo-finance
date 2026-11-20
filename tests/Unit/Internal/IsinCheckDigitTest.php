<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\IsinCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\Luhn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsinCheckDigit::class)]
#[UsesClass(Luhn::class)]
final class IsinCheckDigitTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function isins(): iterable
    {
        yield 'Apple (CUSIP 037833100)' => ['US037833100', '5'];
        yield 'Treasury Corporation of Victoria' => ['AU0000XVGZA', '3'];
    }

    #[DataProvider('isins')]
    public function testComputes(string $body, string $expected): void
    {
        self::assertSame($expected, IsinCheckDigit::compute($body));
    }

    #[DataProvider('isins')]
    public function testValidates(string $body, string $check): void
    {
        self::assertTrue(IsinCheckDigit::isValid($body . $check));
    }

    public function testRejectsAWrongDigitAndAWrongLength(): void
    {
        self::assertFalse(IsinCheckDigit::isValid('US0378331004'));
        self::assertFalse(IsinCheckDigit::isValid('US037833100'));
        self::assertFalse(IsinCheckDigit::isValid('US03783310050'));
        self::assertFalse(IsinCheckDigit::isValid('us0378331005'));
    }

    public function testRejectsABodyOutsideTheAlphabet(): void
    {
        $this->expectException(LogicException::class);
        IsinCheckDigit::compute('us037833100');
    }
}
