<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Security;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Security\Cfi;
use MSSTC4PHP\ValueObject\Financial\Security\CfiCategory;
use MSSTC4PHP\ValueObject\Financial\Security\CfiGroups;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cfi::class)]
#[CoversClass(CfiCategory::class)]
#[UsesClass(SecurityRules::class)]
#[CoversClass(CfiGroups::class)]
final class CfiTest extends TestCase
{
    public function testDecodes(): void
    {
        $cfi = new Cfi('ESVUFR');

        self::assertSame(CfiCategory::Equities, $cfi->category());
        self::assertSame('Common/ordinary shares', $cfi->group());
    }

    public function testLowercaseIsStoredUppercase(): void
    {
        self::assertSame('ESVUFR', (string) new Cfi('esvufr'));
    }

    public function testUnknownCodeIsAcceptedUndecoded(): void
    {
        $cfi = new Cfi('AAAAAA');

        self::assertNull($cfi->category());
        self::assertNull($cfi->group());
    }

    public function testKnownCategoryWithUnknownGroup(): void
    {
        $cfi = new Cfi('EZXXXX');

        self::assertSame(CfiCategory::Equities, $cfi->category());
        self::assertNull($cfi->group());
    }

    public function testNotApplicableGroupIsUndecoded(): void
    {
        self::assertNull(new Cfi('EXXXXX')->group());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'short' => ['ESVUF'];
        yield 'long' => ['ESVUFRX'];
        yield 'digit' => ['ESVUF1'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalid')]
    public function testRefuses(string $value): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        new Cfi($value);
    }

    public function testEveryCategoryLetterIsDecoded(): void
    {
        foreach (CfiCategory::cases() as $category) {
            self::assertSame($category, new Cfi($category->value . 'XXXXX')->category());
        }
    }
}
