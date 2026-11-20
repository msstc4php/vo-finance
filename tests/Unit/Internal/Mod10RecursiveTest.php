<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function matrixCells(): iterable
    {
        // Figure 21 of the QR-bill guidelines: rows are the carry 0-9, columns the digit 0-9.
        $matrix = [
            [0, 9, 4, 6, 8, 2, 7, 1, 3, 5],
            [9, 4, 6, 8, 2, 7, 1, 3, 5, 0],
            [4, 6, 8, 2, 7, 1, 3, 5, 0, 9],
            [6, 8, 2, 7, 1, 3, 5, 0, 9, 4],
            [8, 2, 7, 1, 3, 5, 0, 9, 4, 6],
            [2, 7, 1, 3, 5, 0, 9, 4, 6, 8],
            [7, 1, 3, 5, 0, 9, 4, 6, 8, 2],
            [1, 3, 5, 0, 9, 4, 6, 8, 2, 7],
            [3, 5, 0, 9, 4, 6, 8, 2, 7, 1],
            [5, 0, 9, 4, 6, 8, 2, 7, 1, 3],
        ];
        // The first digit moves the carry from 0 to the first row's value, so the digit that reaches carry r is the
        // position of r in row 0; the second digit then reads the cell.
        foreach ($matrix as $carry => $row) {
            $first = \array_search($carry, $matrix[0], true);
            foreach ($row as $digit => $next) {
                yield \sprintf('carry %d digit %d', $carry, $digit) => [$first . $digit, (string) ((10 - $next) % 10)];
            }
        }
    }

    #[DataProvider('matrixCells')]
    public function testEveryCellOfTheMatrix(string $digits, string $expected): void
    {
        self::assertSame($expected, Mod10Recursive::compute($digits));
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
