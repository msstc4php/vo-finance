<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Reference;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentReferenceException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use MSSTC4PHP\ValueObject\Financial\Reference\QrReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QrReference::class)]
#[UsesClass(Mod10Recursive::class)]
final class QrReferenceTest extends TestCase
{
    public function testTheGuidesReferenceParses(): void
    {
        // SIX Swiss Implementation Guidelines for the QR-bill 2.3, Annex A, example 2, and Annex B, Figure 22.
        $reference = new QrReference('210000000003139471430009017');

        self::assertSame('210000000003139471430009017', $reference->value);
        self::assertSame('7', $reference->checkDigit());
    }

    public function testTheGuidesPrintedGroupingIsCompacted(): void
    {
        // SIX IG 2.3, Annex B: "21 00000 00003 13947 14300 0901" plus its check digit 7.
        self::assertSame('210000000003139471430009017', QrReference::from('21 00000 00003 13947 14300 09017')->value);
    }

    public function testExampleOneOfTheGuideParses(): void
    {
        // SIX IG 2.3, Annex A, example 1.
        self::assertTrue(QrReference::isValid('000008207791225857421286694'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'wrong check digit' => ['210000000003139471430009018'];
        yield '26 digits' => ['21000000000313947143000901'];
        yield '28 digits' => ['2100000000031394714300090170'];
        yield 'a letter' => ['21000000000313947143000901A'];
        yield 'all zeros' => ['000000000000000000000000000'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalid')]
    public function testInvalidReferencesAreRefused(string $value): void
    {
        $this->expectException(InvalidPaymentReferenceException::class);

        new QrReference($value);
    }

    public function testTheMessageCarriesNoInput(): void
    {
        try {
            new QrReference('210000000003139471430009018');
            self::fail('Expected an exception.');
        } catch (InvalidPaymentReferenceException $exception) {
            self::assertStringNotContainsString('2100000', $exception->getMessage());
            self::assertStringNotContainsString('9018', $exception->getMessage());
        }
    }
}
