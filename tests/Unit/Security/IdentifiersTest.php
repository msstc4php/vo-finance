<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Security;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidSecurityIdentifierException;
use MSSTC4PHP\ValueObject\Financial\Internal\CusipCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\FigiCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Financial\Internal\SedolCheckDigit;
use MSSTC4PHP\ValueObject\Financial\Security\Cusip;
use MSSTC4PHP\ValueObject\Financial\Security\Figi;
use MSSTC4PHP\ValueObject\Financial\Security\Lei;
use MSSTC4PHP\ValueObject\Financial\Security\Mic;
use MSSTC4PHP\ValueObject\Financial\Security\Sedol;
use MSSTC4PHP\ValueObject\Financial\Security\Upi;
use MSSTC4PHP\ValueObject\Financial\Security\Valor;
use MSSTC4PHP\ValueObject\Financial\Security\Wkn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Lei::class)]
#[CoversClass(Upi::class)]
#[CoversClass(Mic::class)]
#[CoversClass(Figi::class)]
#[CoversClass(Cusip::class)]
#[CoversClass(Sedol::class)]
#[CoversClass(Wkn::class)]
#[CoversClass(Valor::class)]
#[CoversClass(SecurityRules::class)]
#[UsesClass(Iso7064Mod97::class)]
#[UsesClass(FigiCheckDigit::class)]
#[UsesClass(CusipCheckDigit::class)]
#[UsesClass(SedolCheckDigit::class)]
final class IdentifiersTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<ValueObject>, string}>
     */
    public static function valid(): iterable
    {
        yield 'LEI ISO 17442 A.2' => [Lei::class, 'F50EOCWSQFAUVO9Q8Z97'];
        yield 'LEI GLEIF' => [Lei::class, '506700GE1G29325QX363'];
        // Synthetic: shaped by the ROC's UPI format, not an issued UPI.
        yield 'UPI synthetic' => [Upi::class, 'QZ4TM8GJN9XW'];
        // Synthetic: four alphanumerics.
        yield 'MIC synthetic' => [Mic::class, 'XNAS'];
        yield 'MIC digits synthetic' => [Mic::class, '1234'];
        yield 'FIGI OMG 1' => [Figi::class, 'BBG000BLNQ16'];
        yield 'FIGI OMG 2' => [Figi::class, 'NRG92C84SB39'];
        yield 'CUSIP digits' => [Cusip::class, '037833100'];
        yield 'CUSIP of the CGS Amazon ISIN' => [Cusip::class, '023135106'];
        yield 'CUSIP of the CGS Air Canada ISIN' => [Cusip::class, '008911703'];
        yield 'CUSIP FactSet' => [Cusip::class, '303075105'];
        yield 'CUSIP with letters, format only' => [Cusip::class, '037833AL4'];
        // Synthetic: the PPN characters sit in positions 6 to 8; the check digit is not verified.
        yield 'CUSIP star synthetic' => [Cusip::class, '12345*AB7'];
        yield 'CUSIP at synthetic' => [Cusip::class, '12345@AB7'];
        yield 'CUSIP hash synthetic' => [Cusip::class, '12345#AB7'];
        yield 'SEDOL guide' => [Sedol::class, 'B1F3M59'];
        yield 'SEDOL legacy numeric' => [Sedol::class, '0263494'];
        // Synthetic: format only, no check digit exists.
        yield 'WKN synthetic' => [Wkn::class, '519000'];
        yield 'WKN alphanumeric synthetic' => [Wkn::class, 'A0D9PT'];
        yield 'Valor synthetic' => [Valor::class, '1222171'];
        yield 'Valor 9 synthetic' => [Valor::class, '123456789'];
        yield 'Valor 1 synthetic' => [Valor::class, '7'];
        yield 'Valor alphanumeric synthetic' => [Valor::class, 'AB12'];
    }

    /**
     * @param class-string<StringConvertible> $class
     */
    #[DataProvider('valid')]
    public function testAccepts(string $class, string $value): void
    {
        self::assertSame($value, (string) new $class($value));
    }

    /**
     * @return iterable<string, array{class-string<ValueObject>, string}>
     */
    public static function invalid(): iterable
    {
        yield 'LEI wrong check pair' => [Lei::class, 'F50EOCWSQFAUVO9Q8Z98'];
        yield 'LEI short' => [Lei::class, 'F50EOCWSQFAUVO9Q8Z9'];
        yield 'LEI letter in check pair' => [Lei::class, 'F50EOCWSQFAUVO9Q8ZA7'];
        yield 'UPI short' => [Upi::class, 'QZ4TM8GJN9X'];
        yield 'UPI other prefix' => [Upi::class, 'XX4TM8GJN9XW'];
        yield 'UPI vowel' => [Upi::class, 'QZ4TM8GJN9XA'];
        yield 'UPI letter Y' => [Upi::class, 'QZ4TM8GJN9XY'];
        yield 'MIC short' => [Mic::class, 'XNA'];
        yield 'FIGI wrong check digit' => [Figi::class, 'BBG000BLNQ17'];
        yield 'FIGI third not G' => [Figi::class, 'BBH000BLNQ15'];
        yield 'FIGI vowel' => [Figi::class, 'BBG000BLNA16'];
        yield 'FIGI excluded BS' => [Figi::class, 'BSG000BLNQ19'];
        yield 'FIGI excluded BM' => [Figi::class, 'BMG000BLNQ12'];
        yield 'FIGI excluded GG' => [Figi::class, 'GGG000BLNQ10'];
        yield 'FIGI excluded GB' => [Figi::class, 'GBG000BLNQ11'];
        yield 'FIGI excluded GH' => [Figi::class, 'GHG000BLNQ18'];
        yield 'FIGI excluded KY' => [Figi::class, 'KYG000BLNQ16'];
        yield 'FIGI excluded VG' => [Figi::class, 'VGG000BLNQ13'];
        yield 'FIGI letter check' => [Figi::class, 'BBG000BLNQ1A'];
        yield 'CUSIP wrong digit check' => [Cusip::class, '037833101'];
        yield 'CUSIP short' => [Cusip::class, '03783310'];
        yield 'CUSIP letter check character' => [Cusip::class, '037833ALX'];
        yield 'CUSIP star check character' => [Cusip::class, '037833A**'];
        yield 'SEDOL wrong check' => [Sedol::class, 'B1H54P6'];
        yield 'SEDOL vowel' => [Sedol::class, 'A1F3M59'];
        yield 'SEDOL short' => [Sedol::class, 'B1F3M5'];
        yield 'WKN short' => [Wkn::class, '51900'];
        yield 'WKN letter I' => [Wkn::class, '5I9000'];
        yield 'WKN letter O' => [Wkn::class, '5O9000'];
        yield 'Valor empty' => [Valor::class, ''];
        yield 'Valor ten' => [Valor::class, '1234567890'];
        yield 'Valor punctuation' => [Valor::class, '12-45'];
    }

    /**
     * @param class-string<StringConvertible> $class
     */
    #[DataProvider('invalid')]
    public function testRefuses(string $class, string $value): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        new $class($value);
    }

    /**
     * @return iterable<string, array{class-string<ValueObject>, string, string}>
     */
    public static function lowercase(): iterable
    {
        yield 'LEI' => [Lei::class, 'f50eocwsqfauvo9q8z97', 'F50EOCWSQFAUVO9Q8Z97'];
        yield 'UPI' => [Upi::class, 'qz4tm8gjn9xw', 'QZ4TM8GJN9XW'];
        yield 'MIC' => [Mic::class, 'xnas', 'XNAS'];
        yield 'FIGI' => [Figi::class, 'bbg000blnq16', 'BBG000BLNQ16'];
        yield 'CUSIP' => [Cusip::class, '037833al4', '037833AL4'];
        yield 'SEDOL' => [Sedol::class, 'b1f3m59', 'B1F3M59'];
        yield 'WKN' => [Wkn::class, 'a0d9pt', 'A0D9PT'];
        yield 'Valor' => [Valor::class, 'ab12', 'AB12'];
    }

    /**
     * @param class-string<StringConvertible> $class
     */
    #[DataProvider('lowercase')]
    public function testLowercaseIsStoredUppercase(string $class, string $input, string $expected): void
    {
        self::assertSame($expected, (string) new $class($input));
    }

    /**
     * @return iterable<string, array{class-string<ValueObject>, string, string}>
     */
    public static function wrongCheck(): iterable
    {
        yield 'LEI' => [Lei::class, 'F50EOCWSQFAUVO9Q8Z98', 'LEI has a wrong check pair.'];
        yield 'FIGI' => [Figi::class, 'BBG000BLNQ17', 'FIGI has a wrong check digit.'];
        yield 'CUSIP' => [Cusip::class, '037833101', 'CUSIP has a wrong check digit.'];
        yield 'SEDOL' => [Sedol::class, 'B1H54P6', 'SEDOL has a wrong check digit.'];
    }

    /**
     * @param class-string<StringConvertible> $class
     */
    #[DataProvider('wrongCheck')]
    public function testWrongCheckMessageNamesOnlyTheType(string $class, string $value, string $message): void
    {
        try {
            new $class($value);
            self::fail('Expected an exception.');
        } catch (InvalidSecurityIdentifierException $e) {
            self::assertSame($message, $e->getMessage());
        }
    }

    public function testLouPrefix(): void
    {
        self::assertSame('F50E', new Lei('F50EOCWSQFAUVO9Q8Z97')->louPrefix());
    }

    public function testAWrongLengthIsReportedAsTheLengthRule(): void
    {
        $this->expectException(InvalidSecurityIdentifierException::class);
        $this->expectExceptionMessage('MIC must have 4 characters.');

        new Mic('XNASA');
    }

    /**
     * @return iterable<string, array{class-string<ValueObject>, string, non-empty-string}>
     */
    public static function overLong(): iterable
    {
        yield 'LEI of 23' => [Lei::class, 'F50EOCWSQFAUVO9Q8Z97123', 'LEI must have 20 characters.'];
        yield 'MIC of 13' => [Mic::class, 'XNASXNASXNASX', 'MIC must have 4 characters.'];
        yield 'CUSIP of 13' => [Cusip::class, '0378331001234', 'CUSIP must have 9 characters.'];
        yield 'SEDOL of 13' => [Sedol::class, 'B1F3M59B1F3M5', 'SEDOL must have 7 characters.'];
    }

    /**
     * @param class-string<ValueObject> $class
     * @param non-empty-string $message
     */
    #[DataProvider('overLong')]
    public function testTheMessageHoldsNoLengthOfTheInput(string $class, string $value, string $message): void
    {
        try {
            new $class($value);
            self::fail('Expected an exception.');
        } catch (InvalidSecurityIdentifierException $e) {
            self::assertSame($message, $e->getMessage());
            self::assertStringNotContainsString((string) \strlen($value), $e->getMessage());
        }
    }
}
