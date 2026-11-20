<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Financial\Card\CardScheme;
use MSSTC4PHP\ValueObject\Financial\Internal\IinRange;
use MSSTC4PHP\ValueObject\Financial\Internal\IinRanges;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IinRange::class)]
#[CoversClass(IinRanges::class)]
final class IinRangesTest extends TestCase
{
    public function testRangeBoundsAreInclusive(): void
    {
        $range = new IinRange(CardScheme::Jcb, '35280000', '35899999');

        self::assertTrue($range->contains('35280000'));
        self::assertTrue($range->contains('35899999'));
        self::assertTrue($range->contains('35500000'));
        self::assertFalse($range->contains('35279999'));
        self::assertFalse($range->contains('35900000'));
    }

    public function testRangeOfOneIin(): void
    {
        $range = new IinRange(CardScheme::Jcb, '35280000', '35280000');

        self::assertTrue($range->contains('35280000'));
        self::assertFalse($range->contains('35280001'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function badBounds(): iterable
    {
        yield 'reversed' => ['35899999', '35280000'];
        yield 'start too short' => ['3528000', '35899999'];
        yield 'start too long' => ['352800000', '35899999'];
        yield 'end too short' => ['35280000', '3589999'];
        yield 'end too long' => ['35280000', '358999999'];
        yield 'start not digits' => ['3528000A', '35899999'];
        yield 'end not digits' => ['35280000', '3589999A'];
        yield 'start with newline' => ["35280000\n", '35899999'];
        yield 'end with newline' => ['35280000', "35899999\n"];
        yield 'start with leading junk' => ['A35280000', '35899999'];
        yield 'start with an extra leading digit' => ['035280000', '35899999'];
        yield 'start with trailing junk' => ['35280000A', '35899999'];
        yield 'end with leading junk' => ['35280000', 'A35899999'];
        yield 'end with trailing junk' => ['35280000', '35899999A'];
    }

    #[DataProvider('badBounds')]
    public function testRangeRejectsBadBounds(string $start, string $end): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IinRange(CardScheme::Jcb, $start, $end);
    }

    /**
     * The table is synthetic: the real one has no overlapping ranges.
     */
    public function testCoBadgedRangeGivesTwoSchemesInTableOrder(): void
    {
        $ranges = new IinRanges([
            new IinRange(CardScheme::Jcb, '35000000', '35999999'),
            new IinRange(CardScheme::UnionPay, '35500000', '35999999'),
            new IinRange(CardScheme::Amex, '36000000', '36999999'),
        ]);

        self::assertSame([CardScheme::Jcb, CardScheme::UnionPay], $ranges->schemes('35600000'));
        self::assertSame([CardScheme::Jcb], $ranges->schemes('35100000'));
        self::assertSame([], $ranges->schemes('37000000'));
    }

    public function testASchemeMatchedByTwoRangesIsListedOnce(): void
    {
        $ranges = new IinRanges([
            new IinRange(CardScheme::Jcb, '35000000', '35999999'),
            new IinRange(CardScheme::Jcb, '35500000', '35999999'),
        ]);

        self::assertSame([CardScheme::Jcb], $ranges->schemes('35600000'));
    }

    public function testEmptyTableMatchesNothing(): void
    {
        self::assertSame([], new IinRanges([])->schemes('35600000'));
    }

    /**
     * Each default range: first and last IIN inside, the neighbours outside. The bounds are the rows of the Discover
     * summary (JCB "a", UnionPay "b") and the Amex page.
     *
     * @return iterable<string, array{numeric-string, numeric-string, CardScheme}>
     */
    public static function defaultRanges(): iterable
    {
        yield 'Amex 34' => ['34000000', '34999999', CardScheme::Amex];
        yield 'Amex 37' => ['37000000', '37999999', CardScheme::Amex];
        yield 'JCB 3088-3094' => ['30880000', '30949999', CardScheme::Jcb];
        yield 'JCB 3096-3102' => ['30960000', '31029999', CardScheme::Jcb];
        yield 'JCB 3112-3120' => ['31120000', '31209999', CardScheme::Jcb];
        yield 'JCB 3158-3159' => ['31580000', '31599999', CardScheme::Jcb];
        yield 'JCB 3337-3349' => ['33370000', '33499999', CardScheme::Jcb];
        yield 'JCB 3528-3589' => ['35280000', '35899999', CardScheme::Jcb];
        yield 'UnionPay 621094' => ['62109400', '62109499', CardScheme::UnionPay];
        yield 'UnionPay 6221 26-6237 96' => ['62212600', '62379699', CardScheme::UnionPay];
        yield 'UnionPay 6240-6269' => ['62400000', '62699999', CardScheme::UnionPay];
        yield 'UnionPay 6282-6288' => ['62820000', '62889999', CardScheme::UnionPay];
        yield 'UnionPay 8100-8171' => ['81000000', '81719999', CardScheme::UnionPay];
    }

    /**
     * @param numeric-string $start
     * @param numeric-string $end
     */
    #[DataProvider('defaultRanges')]
    public function testDefaultTableRangeEdges(string $start, string $end, CardScheme $scheme): void
    {
        $ranges = IinRanges::default();

        self::assertSame([$scheme], $ranges->schemes($start));
        self::assertSame([$scheme], $ranges->schemes($end));
        self::assertSame([], $ranges->schemes((string) ((int) $start - 1)));
        self::assertSame([], $ranges->schemes((string) ((int) $end + 1)));
    }

    public function testDefaultTableIsBuiltOnce(): void
    {
        self::assertSame(IinRanges::default(), IinRanges::default());
    }

    public function testDefaultTableLeavesUnmarkedDiscoverRowsOut(): void
    {
        foreach (['30000000', '30950000', '36000000', '38000000', '60110000', '64400000', '65890000'] as $iin) {
            self::assertSame([], IinRanges::default()->schemes($iin));
        }
    }
}
