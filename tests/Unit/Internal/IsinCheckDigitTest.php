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
        // The Stock Exchange of Thailand, "ISIN calculation": the three worked examples.
        yield 'Industrial Finance Corporation of Thailand warrant' => ['TH0013057B0', '5'];
        yield 'PTT ordinary share (local)' => ['TH064601000', '7'];
        yield 'Advanced Info Service ordinary share (foreign)' => ['TH0268010Z1', '1'];
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
        self::assertFalse(IsinCheckDigit::isValid('TH0646010006'));
        self::assertFalse(IsinCheckDigit::isValid('TH064601000'));
        self::assertFalse(IsinCheckDigit::isValid('TH06460100070'));
        self::assertFalse(IsinCheckDigit::isValid('th0646010007'));
    }

    public function testRejectsABodyOutsideTheAlphabet(): void
    {
        $this->expectException(LogicException::class);
        IsinCheckDigit::compute('th064601000');
    }
}
