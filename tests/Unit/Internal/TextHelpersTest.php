<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\Internal\Transcoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mask::class)]
#[CoversClass(TextLines::class)]
#[CoversClass(QueryString::class)]
#[CoversClass(Transcoder::class)]
#[UsesClass(AmbiguousPaymentQrFieldException::class)]
final class TextHelpersTest extends TestCase
{
    public function testMaskKeepsTwoAndFourCharacters(): void
    {
        self::assertSame('DE****************6789', Mask::keepEnds('DE71110220330123456789'));
        self::assertSame('ab*ghij', Mask::keepEnds('abcghij'));
        self::assertSame('ии*нннн', Mask::keepEnds('иивнннн'));
        self::assertSame('******', Mask::keepEnds('abcdef'));
        self::assertSame('*', Mask::keepEnds(''));
    }

    public function testLinesKeepEverySeparator(): void
    {
        $lines = TextLines::parse("a\r\nb\nc\n", 'Test');

        self::assertSame(['a', 'b', 'c', ''], $lines->lines);
        self::assertFalse($lines->isUniform());
        self::assertSame("a\r\nX\nc\n", $lines->withLine(1, 'X'));
        self::assertSame('', $lines->line(4));
    }

    public function testUniformLines(): void
    {
        self::assertTrue(TextLines::parse("a\r\nb\r\n", 'Test')->isUniform());
        self::assertTrue(TextLines::parse('a', 'Test')->isUniform());
        self::assertSame([''], TextLines::parse('', 'Test')->lines);
        self::assertSame(['', '', ''], TextLines::parse("\n\n", 'Test')->lines);
    }

    public function testABareCarriageReturnIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        TextLines::parse("a\rb", 'Test');
    }

    public function testInvalidUtf8IsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Test must be valid UTF-8.');

        TextLines::parse("\xFF", 'Test');
    }

    public function testQueryStringPairs(): void
    {
        $query = QueryString::parse('a=1&&b&a=1&c=x=y');

        self::assertSame(['a', 'b', 'c'], $query->names());
        self::assertSame(['1', '1'], $query->all('a'));
        self::assertSame('1', $query->single('a', 'Test'));
        self::assertSame('', $query->single('b', 'Test'));
        self::assertSame('x=y', $query->single('c', 'Test'));
        self::assertNull($query->single('d', 'Test'));
        self::assertSame('a=*&&b&a=*&c=x=y', $query->withMasked('a', static fn (string $value): string => '*'));
    }

    public function testQueryStringAmbiguityNamesOnlyAPlainParameter(): void
    {
        $query = QueryString::parse('a=1&a=2&b%20c=1&b%20c=2');

        try {
            $query->single('a', 'Test');
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousPaymentQrFieldException $exception) {
            self::assertSame('Test cannot give a single value for the parameter a: several distinct values.', $exception->getMessage());
        }

        $this->expectExceptionMessage('the requested parameter');

        $query->single('b%20c', 'Test');
    }

    public function testTranscoderConvertsAndRefusesUndefinedBytes(): void
    {
        self::assertSame('Ж', Transcoder::toUtf8("\xC6", 'Windows-1251', 'Test'));

        $this->expectException(InvalidPaymentQrException::class);

        Transcoder::toUtf8("\x98", 'Windows-1251', 'Test');
    }

    public function testTranscoderNeedsMbstring(): void
    {
        self::assertFalse(Transcoder::fitsOneOf('abc', ['ISO-8859-1'], false));
        self::assertTrue(Transcoder::fitsOneOf('abc', ['ISO-8859-1']));
        self::assertTrue(Transcoder::fitsOneOf('Ж', ['ISO-8859-1', 'ISO-8859-5']));
        self::assertFalse(Transcoder::fitsOneOf('Жé', ['ISO-8859-1', 'ISO-8859-5']));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Test::fromBytes() requires ext-mbstring.');

        Transcoder::toUtf8('abc', 'Windows-1251', 'Test', false);
    }
}
