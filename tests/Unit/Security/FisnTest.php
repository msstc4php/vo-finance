<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Security;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Security\Fisn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Fisn::class)]
#[UsesClass(SecurityRules::class)]
final class FisnTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function valid(): iterable
    {
        yield 'short' => ['APPLE INC/SH'];
        yield 'issuer of 15' => ['ABCDEFGHIJKLMNO/PREF SHS'];
        yield 'description of 19' => ['ISSUER/ABCDEFGHIJKLMNOPQRS'];
        yield 'total of 35' => ['ABCDEFGHIJKLMNOPQRSTUVWXYZ/ABCDEFGH'];
        yield 'digits' => ['BOND 5/2030 NTS'];
        yield 'ISO 8859-1 capitals' => ["CAF\u{C9} \u{DE}/SH"];
        yield 'leading blank' => [' APPLE/SH'];
        yield 'trailing blank' => ['APPLE/SH '];
        yield 'blank next to delimiter' => ['APPLE /SH'];
        yield '35 multibyte characters' => ["\u{C9}BCDEFGHIJKLMNOPQRSTUVWXYZ/ABCDEFGH"];
    }

    #[DataProvider('valid')]
    public function testAccepts(string $value): void
    {
        self::assertSame($value, new Fisn($value)->value);
    }

    public function testLowercaseIsStoredUppercase(): void
    {
        self::assertSame('APPLE INC/SH', (string) new Fisn('Apple Inc/sh'));
    }

    public function testLowercaseLatin1IsStoredUppercase(): void
    {
        self::assertSame("CAF\u{C9} \u{DE}/SH", (string) new Fisn("caf\u{E9} \u{FE}/sh"));
        self::assertSame("A\u{D8}/SH", (string) new Fisn("a\u{F8}/sh"));
    }

    public function testParts(): void
    {
        $fisn = new Fisn('APPLE INC/SH');

        self::assertSame('APPLE INC', $fisn->issuerName());
        self::assertSame('SH', $fisn->description());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'over 35' => ['ABCDEFGHIJKLMNOPQRSTUVWXYZAB/ABCDEFG'];
        yield 'description over 19' => ['ISSUER/ABCDEFGHIJKLMNOPQRST'];
        yield '36 multibyte characters' => ["\u{C9}BCDEFGHIJKLMNOPQRSTUVWXYZ/ABCDEFGHI"];
        yield 'no delimiter' => ['APPLE INC SH'];
        yield 'two delimiters' => ['APPLE/INC/SH'];
        yield 'empty issuer' => ['/SH'];
        yield 'empty description' => ['APPLE/'];
        yield 'double blank' => ['APPLE  INC/SH'];
        yield 'division sign' => ["A\u{F7}B/SH"];
        yield 'multiplication sign' => ["A\u{D7}B/SH"];
        yield 'invalid UTF-8' => ["APPLE\xC9/SH"];
        yield 'control character' => ["APPLE\n/SH"];
        yield 'empty' => [''];
    }

    #[DataProvider('invalid')]
    public function testRefuses(string $value): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        new Fisn($value);
    }

    public function testMessageDoesNotQuoteTheInput(): void
    {
        try {
            new Fisn('SECRETSECRETSECRETSECRETSECRETSECRET/X');
            self::fail('Expected an exception.');
        } catch (InvalidSecurityIdentifierException $e) {
            self::assertStringNotContainsString('SECRET', $e->getMessage());
        }
    }

    public function testTheMessageHoldsNoLengthOfTheInput(): void
    {
        $value = 'ISSUERISSUERISSUERISSUERISS/DESCRIPTIONS';

        try {
            new Fisn($value);
            self::fail('Expected an exception.');
        } catch (InvalidSecurityIdentifierException $e) {
            self::assertSame('FISN must have at most 35 characters.', $e->getMessage());
            self::assertStringNotContainsString((string) \mb_strlen($value), $e->getMessage());
        }
    }
}
