<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Financial\Internal\RussianAccountKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RussianAccountKey::class)]
final class RussianAccountKeyTest extends TestCase
{
    public function testTheOrdersCorrespondentAccountExampleWithTheRkcNumber(): void
    {
        // Bank of Russia, Order 515 of 08.09.1997, Annex example 1: key 8 at BIC 049805000 (RKC number 005).
        self::assertTrue(RussianAccountKey::isValid('30101810800000000746', '049805000'));
    }

    public function testTheOrdersRkcClientAccountExample(): void
    {
        // Bank of Russia, Order 515 of 08.09.1997, Annex example 2: key 1 at BIC 040305000.
        self::assertTrue(RussianAccountKey::isValid('40102810100000010001', '040305000'));
    }

    public function testTheGostExampleAccountWithTheCreditInstitutionNumber(): void
    {
        // GOST R 56042-2014, Annex D: account 40702810138250123017 at BIC 044525225 (credit institution number 225).
        self::assertTrue(RussianAccountKey::isValid('40702810138250123017', '044525225'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function wrongKeys(): iterable
    {
        foreach (['0', '2', '4', '5', '6', '7', '9'] as $key) {
            yield 'key ' . $key => ['30101810' . $key . '00000000746'];
        }
    }

    #[DataProvider('wrongKeys')]
    public function testEveryOtherKeyFails(string $account): void
    {
        self::assertFalse(RussianAccountKey::isValid($account, '049805000'));
    }

    public function testTheCreditInstitutionNumberAlsoCounts(): void
    {
        // Synthetic: with the credit institution number 000 of BIC 049805000 the key of this account is 3.
        self::assertTrue(RussianAccountKey::isValid('30101810300000000746', '049805000'));
    }

    public function testMalformedInputFails(): void
    {
        self::assertFalse(RussianAccountKey::isValid('3010181080000000074', '049805000'));
        self::assertFalse(RussianAccountKey::isValid('30101810800000000746', '04980500'));
        self::assertFalse(RussianAccountKey::isValid('3010181080000000074A', '049805000'));
    }

    public function testTheGostExampleCorrespondentAccount(): void
    {
        // GOST R 56042-2014, Annex D: correspondent account 30101810400000000225 at BIC 044525225 (RKC number 045).
        self::assertTrue(RussianAccountKey::isValid('30101810400000000225', '044525225'));
        self::assertFalse(RussianAccountKey::isValid('30101810500000000225', '044525225'));
    }

    public function testOverlongInputFails(): void
    {
        self::assertFalse(RussianAccountKey::isValid('130101810800000000746', '049805000'));
        self::assertFalse(RussianAccountKey::isValid('30101810800000000746', '0498050001'));
        self::assertFalse(RussianAccountKey::isValid("30101810800000000746\n", '049805000'));
        self::assertFalse(RussianAccountKey::isValid('30101810800000000746', "049805000\n"));
        self::assertFalse(RussianAccountKey::isValid('30101810800000000746', '1049805000'));
    }
}
