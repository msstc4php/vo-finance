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
#[UsesClass(SecurityRules::class)]
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
        yield 'UPI' => [Upi::class, 'QZ4TM8GJN9XW'];
        yield 'MIC' => [Mic::class, 'XNAS'];
        yield 'MIC digits' => [Mic::class, '1234'];
        yield 'FIGI OMG 1' => [Figi::class, 'BBG000BLNQ16'];
        yield 'FIGI OMG 2' => [Figi::class, 'NRG92C84SB39'];
        yield 'CUSIP digits' => [Cusip::class, '037833100'];
        yield 'CUSIP FactSet' => [Cusip::class, '303075105'];
        yield 'CUSIP with letters, format only' => [Cusip::class, '037833AL4'];
        yield 'CUSIP star' => [Cusip::class, '12345*AB7'];
        yield 'CUSIP at' => [Cusip::class, '12345@AB7'];
        yield 'CUSIP hash' => [Cusip::class, '12345#AB7'];
        yield 'SEDOL guide' => [Sedol::class, 'B1F3M59'];
        yield 'SEDOL legacy numeric' => [Sedol::class, '0263494'];
        yield 'WKN' => [Wkn::class, '519000'];
        yield 'WKN alphanumeric' => [Wkn::class, 'A0D9PT'];
        yield 'Valor' => [Valor::class, '1222171'];
        yield 'Valor 9' => [Valor::class, '123456789'];
        yield 'Valor 1' => [Valor::class, '7'];
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
        yield 'LEI lowercase' => [Lei::class, 'f50eocwsqfauvo9q8z97'];
        yield 'LEI letter in check pair' => [Lei::class, 'F50EOCWSQFAUVO9Q8ZA7'];
        yield 'UPI short' => [Upi::class, 'QZ4TM8GJN9X'];
        yield 'UPI lowercase' => [Upi::class, 'qz4tm8gjn9xw'];
        yield 'MIC short' => [Mic::class, 'XNA'];
        yield 'MIC lowercase' => [Mic::class, 'xnas'];
        yield 'FIGI wrong check digit' => [Figi::class, 'BBG000BLNQ17'];
        yield 'FIGI third not G' => [Figi::class, 'BBH000BLNQ16'];
        yield 'FIGI vowel' => [Figi::class, 'BBG000BLNA16'];
        yield 'FIGI excluded BS' => [Figi::class, 'BSG000BLNQ10'];
        yield 'FIGI excluded KY' => [Figi::class, 'KYG000BLNQ10'];
        yield 'FIGI excluded VG' => [Figi::class, 'VGG000BLNQ10'];
        yield 'FIGI letter check' => [Figi::class, 'BBG000BLNQ1A'];
        yield 'CUSIP wrong digit check' => [Cusip::class, '037833101'];
        yield 'CUSIP short' => [Cusip::class, '03783310'];
        yield 'CUSIP letter check character' => [Cusip::class, '037833ALX'];
        yield 'CUSIP lowercase' => [Cusip::class, '037833al4'];
        yield 'CUSIP star check character' => [Cusip::class, '037833A**'];
        yield 'SEDOL wrong check' => [Sedol::class, 'B1H54P6'];
        yield 'SEDOL vowel' => [Sedol::class, 'A1F3M59'];
        yield 'SEDOL short' => [Sedol::class, 'B1F3M5'];
        yield 'WKN short' => [Wkn::class, '51900'];
        yield 'WKN letter I' => [Wkn::class, '5I9000'];
        yield 'WKN letter O' => [Wkn::class, '5O9000'];
        yield 'WKN lowercase' => [Wkn::class, 'a0d9pt'];
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

    public function testLouPrefix(): void
    {
        self::assertSame('F50E', new Lei('F50EOCWSQFAUVO9Q8Z97')->louPrefix());
    }

    public function testMessageNamesOnlyTheType(): void
    {
        try {
            new Lei('F50EOCWSQFAUVO9Q8Z98');
            self::fail('Expected an exception.');
        } catch (InvalidSecurityIdentifierException $e) {
            self::assertSame('LEI has a wrong check pair.', $e->getMessage());
        }
    }
}
