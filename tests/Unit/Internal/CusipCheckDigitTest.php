<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CusipCheckDigit::class)]
final class CusipCheckDigitTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function cusips(): iterable
    {
        // CUSIP Global Services, "CUSIP: A Common Language for Efficient Markets": 037833 10 0.
        yield 'Apple common stock' => ['03783310', '0'];
        // CUSIP Global Services' identifiers page: FactSet Research Systems, 303075 10 5.
        yield 'FactSet common stock' => ['30307510', '5'];
    }

    #[DataProvider('cusips')]
    public function testComputes(string $body, string $expected): void
    {
        self::assertSame($expected, CusipCheckDigit::compute($body));
    }

    #[DataProvider('cusips')]
    public function testValidates(string $body, string $check): void
    {
        self::assertTrue(CusipCheckDigit::isValid($body . $check));
    }

    public function testRejectsAWrongDigitAndAWrongLength(): void
    {
        self::assertFalse(CusipCheckDigit::isValid('037833101'));
        self::assertFalse(CusipCheckDigit::isValid('03783310'));
        self::assertFalse(CusipCheckDigit::isValid('0378331000'));
        self::assertFalse(CusipCheckDigit::isValid('03783310x'));
        self::assertFalse(CusipCheckDigit::isValid('037833AL4'));
    }

    public function testRejectsABodyOutsideTheAlphabet(): void
    {
        $this->expectException(LogicException::class);
        CusipCheckDigit::compute('037833AL');
    }

    public function testRejectsPrivatePlacementCharacters(): void
    {
        $this->expectException(LogicException::class);
        CusipCheckDigit::compute('00000*00');
    }

    public function testRejectsAShortBody(): void
    {
        $this->expectException(LogicException::class);
        CusipCheckDigit::compute('0378331');
    }
}
