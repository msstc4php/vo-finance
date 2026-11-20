<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Iso7064Mod97::class)]
final class Iso7064Mod97Test extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function bodies(): iterable
    {
        // ISO 17442:2012 Annex A.1 and A.2.
        yield 'ISO 17442 A.1' => ['A1234567', '02'];
        yield 'ISO 17442 A.2' => ['F50EOCWSQFAUVO9Q8Z', '97'];
        // GLEIF's own LEI, shown on gleif.org.
        yield 'GLEIF LEI' => ['506700GE1G29325QX3', '63'];
        // ISO 11649:2009 Annex B.1: the reference 2348231 with "RF" moved behind it.
        yield 'ISO 11649 B.1' => ['2348231RF', '71'];
        // SIX Swiss Implementation Guidelines for the QR-bill 2.3, Annex A, and EPC069-12 v3.0 section 2.3: RF18 5390 0754 7034.
        yield 'SIX and EPC example' => ['539007547034RF', '18'];
        // Finance Finland, "Structure of the RF Creditor Reference": RF54 1234 and RF98 REF1 234.
        yield 'Finance Finland 1' => ['1234RF', '54'];
        yield 'Finance Finland 2' => ['REF1234RF', '98'];
    }

    #[DataProvider('bodies')]
    public function testComputesTheCheckPair(string $body, string $expected): void
    {
        self::assertSame($expected, Iso7064Mod97::compute($body));
    }

    public function testAcceptsTheStandardsOwnLeis(): void
    {
        self::assertTrue(Iso7064Mod97::isValid('A123456702'));
        self::assertTrue(Iso7064Mod97::isValid('F50EOCWSQFAUVO9Q8Z97'));
        self::assertTrue(Iso7064Mod97::isValid('506700GE1G29325QX363'));
    }

    public function testAcceptsTheRotatedCreditorReferences(): void
    {
        self::assertTrue(Iso7064Mod97::isValid('2348231RF71'));
        self::assertTrue(Iso7064Mod97::isValid('539007547034RF18'));
        self::assertTrue(Iso7064Mod97::isValid('1234RF54'));
        self::assertTrue(Iso7064Mod97::isValid('REF1234RF98'));
    }

    public function testTheStandardsPrintedExampleFailsItsOwnCheck(): void
    {
        self::assertFalse(Iso7064Mod97::isValid('539007547034RF68'));
    }

    public function testRejectsAWrongPair(): void
    {
        self::assertFalse(Iso7064Mod97::isValid('F50EOCWSQFAUVO9Q8Z96'));
        self::assertFalse(Iso7064Mod97::isValid('F50EOCWSQFAUVO9Q8Z98'));
    }

    public function testRejectsCharactersOutsideTheAlphabetWithoutThrowing(): void
    {
        self::assertFalse(Iso7064Mod97::isValid('f50EOCWSQFAUVO9Q8Z97'));
        self::assertFalse(Iso7064Mod97::isValid('F50EOCWSQFAUVO9Q8Z9-'));
        self::assertFalse(Iso7064Mod97::isValid(''));
    }

    public function testPadsASingleDigitPair(): void
    {
        self::assertSame('08', Iso7064Mod97::compute('30'));
    }

    public function testRejectsAnEmptyBody(): void
    {
        $this->expectException(LogicException::class);
        Iso7064Mod97::compute('');
    }

    public function testRejectsLowercaseInTheBody(): void
    {
        $this->expectException(LogicException::class);
        Iso7064Mod97::compute('abc');
    }
}
