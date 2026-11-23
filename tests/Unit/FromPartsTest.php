<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentReferenceException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\FigiCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Internal\SedolCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use MSSTC4PHP\ValueObject\Financial\Reference\QrReference;
use MSSTC4PHP\ValueObject\Financial\Security\Cusip;
use MSSTC4PHP\ValueObject\Financial\Security\Figi;
use MSSTC4PHP\ValueObject\Financial\Security\Lei;
use MSSTC4PHP\ValueObject\Financial\Security\Sedol;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * `fromParts()` computes the check digits with the algorithm the class already enforces; the expected values are the
 * sources' own examples used by each class's tests.
 */
#[CoversClass(Lei::class)]
#[CoversClass(Figi::class)]
#[CoversClass(Sedol::class)]
#[CoversClass(Cusip::class)]
#[CoversClass(CreditorReference::class)]
#[CoversClass(QrReference::class)]
#[UsesClass(SecurityRules::class)]
#[UsesClass(Iso7064Mod97::class)]
#[UsesClass(FigiCheckDigit::class)]
#[UsesClass(SedolCheckDigit::class)]
#[UsesClass(CusipCheckDigit::class)]
#[UsesClass(Mod10Recursive::class)]
final class FromPartsTest extends TestCase
{
    /**
     * @return iterable<string, array{Stringable, non-empty-string}>
     */
    public static function built(): iterable
    {
        yield 'LEI, ISO 17442 A.2' => [Lei::fromParts('F50EOCWSQFAUVO9Q8Z'), 'F50EOCWSQFAUVO9Q8Z97'];
        yield 'LEI, lowercase body' => [Lei::fromParts('506700ge1g29325qx3'), '506700GE1G29325QX363'];
        yield 'FIGI, OMG' => [Figi::fromParts('BBG000BLNQ1'), 'BBG000BLNQ16'];
        yield 'FIGI, lowercase body' => [Figi::fromParts('bbg000blnq1'), 'BBG000BLNQ16'];
        yield 'SEDOL, LSEG' => [Sedol::fromParts('B1F3M5'), 'B1F3M59'];
        yield 'SEDOL, numeric' => [Sedol::fromParts('122217'), '1222171'];
        yield 'SEDOL, lowercase body' => [Sedol::fromParts('b1f3m5'), 'B1F3M59'];
        yield 'CUSIP, all digits' => [Cusip::fromParts('03783310'), '037833100'];
        yield 'creditor reference, ISO 11649' => [CreditorReference::fromParts('539007547034'), 'RF18539007547034'];
        yield 'creditor reference, blanks' => [CreditorReference::fromParts('5390 0754 7034'), 'RF18539007547034'];
        yield 'creditor reference, lowercase letters' => [CreditorReference::fromParts('abc'), 'RF45ABC'];
        yield 'QR reference, SIX IG 2.3 Annex A' => [QrReference::fromParts('21000000000313947143000901'), '210000000003139471430009017'];
        yield 'QR reference, printed grouping' => [QrReference::fromParts('21 00000 00003 13947 14300 0901'), '210000000003139471430009017'];
    }

    #[DataProvider('built')]
    public function testComputesTheCheckDigits(Stringable $value, string $expected): void
    {
        self::assertSame($expected, (string) $value);
    }

    /**
     * @return iterable<string, array{callable(): mixed, class-string<InvalidValueObjectException>, non-empty-string}>
     */
    public static function refused(): iterable
    {
        yield 'LEI body of 17' => [static fn (): Lei => Lei::fromParts('F50EOCWSQFAUVO9Q8'), InvalidSecurityIdentifierException::class, 'LEI body must be 18 letters or digits.'];
        yield 'LEI body with a hyphen' => [static fn (): Lei => Lei::fromParts('F50EOCWSQFAUVO9Q8-'), InvalidSecurityIdentifierException::class, 'LEI body must be 18 letters or digits.'];
        yield 'FIGI body of 12' => [static fn (): Figi => Figi::fromParts('BBG000BLNQ16'), InvalidSecurityIdentifierException::class, 'FIGI body must be 11 consonants or digits.'];
        yield 'FIGI body with a vowel' => [static fn (): Figi => Figi::fromParts('BBG000BLNQA'), InvalidSecurityIdentifierException::class, 'FIGI body must be 11 consonants or digits.'];
        yield 'FIGI excluded prefix' => [static fn (): Figi => Figi::fromParts('BSG000BLNQ1'), InvalidSecurityIdentifierException::class, 'FIGI has an invalid format (two consonants outside the excluded prefixes, G, eight consonants or digits, a digit).'];
        yield 'SEDOL body of 7' => [static fn (): Sedol => Sedol::fromParts('B1F3M59'), InvalidSecurityIdentifierException::class, 'SEDOL body must be 6 digits or capital consonants.'];
        yield 'SEDOL body with a vowel' => [static fn (): Sedol => Sedol::fromParts('A1F3M5'), InvalidSecurityIdentifierException::class, 'SEDOL body must be 6 digits or capital consonants.'];
        yield 'CUSIP body with a letter' => [static fn (): Cusip => Cusip::fromParts('037833AL'), InvalidSecurityIdentifierException::class, 'CUSIP body must be 8 digits: no published check-digit values exist for letters or *, @, #.'];
        yield 'CUSIP body of 9' => [static fn (): Cusip => Cusip::fromParts('037833100'), InvalidSecurityIdentifierException::class, 'CUSIP body must be 8 digits: no published check-digit values exist for letters or *, @, #.'];
        yield 'creditor reference body of 22' => [static fn (): CreditorReference => CreditorReference::fromParts('1234567890123456789012'), InvalidPaymentReferenceException::class, 'A creditor reference body must be 1 to 21 letters or digits.'];
        yield 'creditor reference empty body' => [static fn (): CreditorReference => CreditorReference::fromParts(' '), InvalidPaymentReferenceException::class, 'A creditor reference body must be 1 to 21 letters or digits.'];
        yield 'QR reference of 27' => [static fn (): QrReference => QrReference::fromParts('210000000003139471430009017'), InvalidPaymentReferenceException::class, 'A QR reference body must be 26 digits.'];
        yield 'QR reference with a letter' => [static fn (): QrReference => QrReference::fromParts('2100000000031394714300090A'), InvalidPaymentReferenceException::class, 'A QR reference body must be 26 digits.'];
        yield 'LEI body of 19' => [static fn (): Lei => Lei::fromParts('XF50EOCWSQFAUVO9Q8Z'), InvalidSecurityIdentifierException::class, 'LEI body must be 18 letters or digits.'];
        yield 'LEI body with a trailing newline' => [static fn (): Lei => Lei::fromParts("F50EOCWSQFAUVO9Q8Z\n"), InvalidSecurityIdentifierException::class, 'LEI body must be 18 letters or digits.'];
        yield 'FIGI body with a trailing newline' => [static fn (): Figi => Figi::fromParts("BBG000BLNQ1\n"), InvalidSecurityIdentifierException::class, 'FIGI body must be 11 consonants or digits.'];
        yield 'SEDOL body with a trailing newline' => [static fn (): Sedol => Sedol::fromParts("B1F3M5\n"), InvalidSecurityIdentifierException::class, 'SEDOL body must be 6 digits or capital consonants.'];
        yield 'CUSIP body with a trailing newline' => [static fn (): Cusip => Cusip::fromParts("03783310\n"), InvalidSecurityIdentifierException::class, 'CUSIP body must be 8 digits: no published check-digit values exist for letters or *, @, #.'];
        yield 'creditor reference body with a trailing newline' => [static fn (): CreditorReference => CreditorReference::fromParts("539007547034\n"), InvalidPaymentReferenceException::class, 'A creditor reference body must be 1 to 21 letters or digits.'];
        yield 'QR reference body with a trailing newline' => [static fn (): QrReference => QrReference::fromParts("21000000000313947143000901\n"), InvalidPaymentReferenceException::class, 'A QR reference body must be 26 digits.'];
        yield 'QR reference of zeros' => [static fn (): QrReference => QrReference::fromParts('00000000000000000000000000'), InvalidPaymentReferenceException::class, 'A QR reference must not consist of zeros only.'];
    }

    /**
     * @param callable(): mixed $build
     * @param class-string<InvalidValueObjectException> $exception
     * @param non-empty-string $message
     */
    #[DataProvider('refused')]
    public function testRefusesABodyOfTheWrongShape(callable $build, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $build();
    }
}
