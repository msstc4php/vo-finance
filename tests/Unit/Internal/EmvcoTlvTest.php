<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmvcoTlv::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(InvalidPaymentQrException::class)]
final class EmvcoTlvTest extends TestCase
{
    public function testParsesObjectsInOrder(): void
    {
        self::assertEquals(
            [new DataObject('00', '01'), new DataObject('59', 'Café')],
            EmvcoTlv::parse('0002015904Café'),
        );
    }

    public function testEncodesWithACharacterLength(): void
    {
        self::assertSame('5904Café', EmvcoTlv::encode(new DataObject('59', 'Café')));
        self::assertSame('0102ab', EmvcoTlv::encode(new DataObject('01', 'ab')));
    }

    public function testCharacterLength(): void
    {
        self::assertSame(4, EmvcoTlv::length('Café'));
        self::assertSame(0, EmvcoTlv::length(''));
    }

    public function testStopsExactlyAtTheEnd(): void
    {
        self::assertCount(1, EmvcoTlv::parse('0101A'));
        self::assertSame([], EmvcoTlv::parse(''));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformed(): iterable
    {
        yield 'length one character too many' => ['0102A'];
        yield 'one trailing character' => ['0101A0'];
        yield 'two trailing characters' => ['0101A01'];
        yield 'three trailing characters' => ['0101A010'];
        yield 'zero length after a valid object' => ['0101A0100'];
        yield 'repeated id' => ['0101A0101B'];
        yield 'non-digit id' => ['A101A'];
        yield 'non-digit length' => ['01A1A'];
        yield 'invalid UTF-8' => ["0101\xFF"];
        yield 'length with a full-width digit' => ['01０1A'];
    }

    #[DataProvider('malformed')]
    public function testMalformedInputIsRefused(string $payload): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        EmvcoTlv::parse($payload);
    }
}
