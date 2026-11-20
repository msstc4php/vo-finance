<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Reference;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentReferenceException;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreditorReference::class)]
#[UsesClass(Iso7064Mod97::class)]
final class CreditorReferenceTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function official(): iterable
    {
        // ISO 11649:2009, Annex B.
        yield 'ISO 11649 Annex B' => ['RF712348231', 'RF712348231'];
        // SIX IG QR-bill 2.3 Annex A examples 5 and 6; EPC069-12 v3.0 section 2.3, V1.
        yield 'SIX and EPC' => ['RF18539007547034', 'RF18539007547034'];
        // ISO 11649:2009 Annex A.3 print layout, with the check pair of SIX and EPC.
        yield 'printed in groups' => ['RF18 5390 0754 7034', 'RF18539007547034'];
        // Finance Finland, "Structure of the RF Creditor Reference".
        yield 'Finance Finland 1' => ['RF54 1234', 'RF541234'];
        yield 'Finance Finland 2' => ['RF98 REF1 234', 'RF98REF1234'];
    }

    #[DataProvider('official')]
    public function testOfficialReferencesParse(string $value, string $canonical): void
    {
        self::assertSame($canonical, CreditorReference::from($value)->value);
    }

    public function testLowercaseIsAcceptedAndFoldedToUppercase(): void
    {
        // ISO 11649:2009 clause 5 allows "upper and lower case letters (A to Z and a to z)".
        $reference = new CreditorReference('rf98 ref1 234');

        self::assertSame('RF98REF1234', $reference->value);
        self::assertSame('98', $reference->checkDigits());
        self::assertSame('REF1234', $reference->body());
    }

    public function testTheStandardsOwnLayoutExampleFailsItsCheck(): void
    {
        // ISO 11649:2009 Annex A.3 prints RF68 for the body whose check pair is 18 (see FINANCE.md).
        self::assertFalse(CreditorReference::isValid('RF68539007547034'));
    }

    public function testSixExampleFourFailsTheIsoCheck(): void
    {
        // SIX IG QR-bill 2.3 Annex A example 4 prints RF72 for a body whose ISO 11649 check pair is 24 (see FINANCE.md).
        self::assertFalse(CreditorReference::isValid('RF720191230100405JSH0438'));
        self::assertTrue(CreditorReference::isValid('RF240191230100405JSH0438'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'wrong check pair' => ['RF19539007547034'];
        yield 'no body' => ['RF18'];
        yield 'body of 22 characters' => ['RF00' . \str_repeat('1', 22)];
        yield 'other prefix' => ['RX18539007547034'];
        yield 'letters as check pair' => ['RFAB539007547034'];
        yield 'special character' => ['RF18539007547-34'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalid')]
    public function testInvalidReferencesAreRefused(string $value): void
    {
        $this->expectException(InvalidPaymentReferenceException::class);

        new CreditorReference($value);
    }

    public function testAMaximalBodyOf21CharactersParses(): void
    {
        $body = \str_repeat('A', 21);
        $pair = Iso7064Mod97::compute($body . 'RF');

        self::assertSame(25, \strlen(CreditorReference::from('RF' . $pair . $body)->value));
    }

    public function testTheMessageCarriesNoInput(): void
    {
        try {
            new CreditorReference('RF19539007547034');
            self::fail('Expected an exception.');
        } catch (InvalidPaymentReferenceException $exception) {
            self::assertStringNotContainsString('5390', $exception->getMessage());
        }
    }

    public function testAValidCheckOverABodyOf22CharactersIsStillRefused(): void
    {
        $body = \str_repeat('A', 22);

        $this->expectException(InvalidPaymentReferenceException::class);
        $this->expectExceptionMessage('1 to 21');

        new CreditorReference('RF' . Iso7064Mod97::compute($body . 'RF') . $body);
    }
}
