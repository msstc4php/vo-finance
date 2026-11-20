<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mod10Recursive::class)]
final class Mod10RecursiveTest extends TestCase
{
    public function testComputesTheGuidesOwnExample(): void
    {
        // Swiss Implementation Guidelines for the QR-bill 2.3, Annex B, Figure 22.
        self::assertSame('7', Mod10Recursive::compute('21000000000313947143000901'));
    }

    public function testValidatesTheGuidesOwnExample(): void
    {
        self::assertTrue(Mod10Recursive::isValid('210000000003139471430009017'));
    }

    public function testEveryDigitAndCarryFollowsTheMatrix(): void
    {
        self::assertSame('0', Mod10Recursive::compute('0'));
        self::assertSame('1', Mod10Recursive::compute('1'));
        self::assertSame('6', Mod10Recursive::compute('2'));
        self::assertSame('4', Mod10Recursive::compute('0123456789'));
        self::assertSame('3', Mod10Recursive::compute('9876543210'));
    }

    public function testRejectsAWrongDigitAndNonDigits(): void
    {
        self::assertFalse(Mod10Recursive::isValid('210000000003139471430009018'));
        self::assertFalse(Mod10Recursive::isValid('7'));
        self::assertFalse(Mod10Recursive::isValid('21000000000313947143000901X'));
    }

    public function testRejectsAnEmptyBody(): void
    {
        $this->expectException(LogicException::class);
        Mod10Recursive::compute('');
    }

    public function testRejectsNonDigitsInTheBody(): void
    {
        $this->expectException(LogicException::class);
        Mod10Recursive::compute('12a');
    }
}
