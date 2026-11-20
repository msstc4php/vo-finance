<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Crc16CcittFalse::class)]
final class Crc16CcittFalseTest extends TestCase
{
    public function testComputesTheCatalogueCheckValue(): void
    {
        self::assertSame('29B1', Crc16CcittFalse::compute('123456789'));
    }

    public function testEmptyInputGivesTheInitialValue(): void
    {
        self::assertSame('FFFF', Crc16CcittFalse::compute(''));
    }

    public function testPadsToFourDigits(): void
    {
        self::assertSame('0DD9', Crc16CcittFalse::compute('11'));
    }

    public function testValidatesEitherCaseOfTheHexDigits(): void
    {
        self::assertTrue(Crc16CcittFalse::isValid('12345678929B1'));
        self::assertTrue(Crc16CcittFalse::isValid('12345678929b1'));
    }

    public function testRejectsAWrongOrMalformedChecksum(): void
    {
        self::assertFalse(Crc16CcittFalse::isValid('12345678929B2'));
        self::assertFalse(Crc16CcittFalse::isValid('29B1'));
        self::assertFalse(Crc16CcittFalse::isValid('123'));
        self::assertFalse(Crc16CcittFalse::isValid('123456789ZZZZ'));
    }
}
