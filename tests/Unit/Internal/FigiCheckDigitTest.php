<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\FigiCheckDigit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FigiCheckDigit::class)]
final class FigiCheckDigitTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function figis(): iterable
    {
        // OMG FIGI 1.0 (formal/2015-12-01), section 6.1 worked examples.
        yield 'BBG000BLNQ16' => ['BBG000BLNQ1', '6'];
        yield 'NRG92C84SB39' => ['NRG92C84SB3', '9'];
        yield 'all zero digits' => ['BBG00000000', '7'];
        yield 'every letter value' => ['BCDFGHJKLMN', '1'];
        yield 'last letters' => ['PQRSTVWXYZ0', '6'];
    }

    #[DataProvider('figis')]
    public function testComputes(string $body, string $expected): void
    {
        self::assertSame($expected, FigiCheckDigit::compute($body));
    }

    #[DataProvider('figis')]
    public function testValidates(string $body, string $check): void
    {
        self::assertTrue(FigiCheckDigit::isValid($body . $check));
    }

    public function testRejectsAWrongDigitAndAWrongLength(): void
    {
        self::assertFalse(FigiCheckDigit::isValid('BBG000BLNQ17'));
        self::assertFalse(FigiCheckDigit::isValid('BBG000BLNQ1'));
        self::assertFalse(FigiCheckDigit::isValid('BBG000BLNQ16A'));
    }

    public function testRejectsAVowel(): void
    {
        $this->expectException(LogicException::class);
        FigiCheckDigit::compute('BBG000BLNA1');
    }

    public function testRejectsAShortBody(): void
    {
        $this->expectException(LogicException::class);
        FigiCheckDigit::compute('BBG000BLNQ');
    }
}
