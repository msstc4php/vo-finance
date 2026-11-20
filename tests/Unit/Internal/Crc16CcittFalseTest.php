<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Crc16CcittFalse::class)]
final class Crc16CcittFalseTest extends TestCase
{
    // Banco Central do Brasil, Manual do BR Code 2.0.1, section 2.2: the complete dynamic payload (270 characters with
    // the CRC) and its printed checksum 6304AD38; the text up to and including `6304` is what the CRC covers.
    private const string BCB_PAYLOAD_WITHOUT_CRC = '00020104141234567890123426580014BR.GOV.BCB.PIX0136123e4567-e12b-12d1-a456-426655440000'
        . '27300012BR.COM.OUTRO011001234567895204000053039865406123.455802BR5917NOME DO RECEBEDOR6008BRASILIA'
        . '61087007490062190515RP12345678-201980390012BR.COM.OUTRO01190123.ABCD.3456.WXYZ6304';

    public function testComputesTheCatalogueCheckValue(): void
    {
        self::assertSame('29B1', Crc16CcittFalse::compute('123456789'));
    }

    public function testComputesThePrintedCrcOfAnOfficialPayload(): void
    {
        self::assertSame('AD38', Crc16CcittFalse::compute(self::BCB_PAYLOAD_WITHOUT_CRC));
        self::assertTrue(Crc16CcittFalse::isValid(self::BCB_PAYLOAD_WITHOUT_CRC . 'AD38'));
        self::assertTrue(Crc16CcittFalse::isValid(self::BCB_PAYLOAD_WITHOUT_CRC . 'ad38'));
        self::assertFalse(Crc16CcittFalse::isValid(self::BCB_PAYLOAD_WITHOUT_CRC . 'AD39'));
    }

    public function testEmptyInputGivesTheInitialValue(): void
    {
        self::assertSame('FFFF', Crc16CcittFalse::compute(''));
    }

    public function testPadsToFourDigits(): void
    {
        self::assertSame('0DD9', Crc16CcittFalse::compute('11'));
    }

    public function testValidatesEitherCaseOfTheHexDigits(): void
    {
        self::assertTrue(Crc16CcittFalse::isValid('12345678929B1'));
        self::assertTrue(Crc16CcittFalse::isValid('12345678929b1'));
    }

    public function testRejectsAWrongOrMalformedChecksum(): void
    {
        self::assertFalse(Crc16CcittFalse::isValid('12345678929B2'));
        self::assertFalse(Crc16CcittFalse::isValid('29B1'));
        self::assertFalse(Crc16CcittFalse::isValid('123'));
        self::assertFalse(Crc16CcittFalse::isValid('123456789ZZZZ'));
    }
}
