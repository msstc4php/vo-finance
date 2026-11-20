<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Financial\Internal\Iso8859;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Iso8859::class)]
final class Iso8859Test extends TestCase
{
    /**
     * Characters at the 0xA0-0xFF positions of each set, from the Unicode Consortium's 8859-N.TXT tables.
     *
     * @return iterable<string, array{string}>
     */
    public static function oneSetEach(): iterable
    {
        yield '8859-1 0xE9, 0xFF' => ['éÿ'];
        yield '8859-2 0xF5 U+0151, 0xFF U+02D9' => ['ő˙'];
        yield '8859-4 0xA2 U+0138, 0xBF U+014B' => ['ĸŋ'];
        yield '8859-5 0xB6 U+0416, 0xF0 U+2116' => ['Ж№'];
        yield '8859-7 0xD9 U+03A9, 0xA4 U+20AC' => ['Ω€'];
        yield '8859-10 0xBD U+2015, 0xFF U+0138' => ['―ĸ'];
        yield '8859-15 0xA4 U+20AC, 0xBE U+0178' => ['€Ÿ'];
    }

    #[DataProvider('oneSetEach')]
    public function testTextOfOneSetFits(string $text): void
    {
        self::assertTrue(Iso8859::fitsOneSet('abc ' . $text . "\n"));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function noSet(): iterable
    {
        yield 'Cyrillic and Greek' => ['ЖΩ'];
        yield 'Latin-2 and Cyrillic' => ['őЖ'];
        yield 'CJK' => ['中'];
        yield '8859-7 unmapped 0xD2 would be U+03A2' => ["\u{03A2}"];
        yield 'three sets' => ['ĀőЖ'];
    }

    #[DataProvider('noSet')]
    public function testTextOfNoSingleSetDoesNotFit(string $text): void
    {
        self::assertFalse(Iso8859::fitsOneSet($text));
    }

    public function testAsciiAndC1Fit(): void
    {
        self::assertTrue(Iso8859::fitsOneSet("\x00\x7F\u{0080}\u{009F}"));
        self::assertTrue(Iso8859::fitsOneSet(''));
    }

    public function testATrailingNewlineDoesNotHideAForeignCharacter(): void
    {
        self::assertFalse(Iso8859::fitsOneSet("ЖΩ\n"));
    }
}
