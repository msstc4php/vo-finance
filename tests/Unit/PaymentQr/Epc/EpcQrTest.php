<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Epc;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso8859;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\Internal\Transcoder;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Epc\EpcQr;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EpcQr::class)]
#[UsesClass(TextLines::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Transcoder::class)]
#[UsesClass(Mask::class)]
#[UsesClass(Iso8859::class)]
#[UsesClass(CreditorReference::class)]
#[UsesClass(Iso7064Mod97::class)]
final class EpcQrTest extends TestCase
{
    // EPC069-12 v3.0, section 2.3, example V1.
    private const array V1 = ['BCD', '001', '1', 'SCT', 'BHBLDEHHXXX', 'Franz Mustermänn', 'DE71110220330123456789', 'EUR12.3', 'GDDS', 'RF18539007547034'];

    // EPC069-12 v3.0, section 2.3, example V2 (declared ISO 8859-1), written here as UTF-8 text.
    private const array V2 = ['BCD', '002', '2', 'SCT', '', "François D'Alsace S.A.", 'FR1420041010050500013M02606', 'EUR12.3', '', '', 'Client:Marie Louise La Lune'];

    public function testTheV1ExampleParses(): void
    {
        $payload = \implode("\n", self::V1);
        $qr = new EpcQr($payload);

        // The EPC counts "95 characters including spaces (1) and line feeds (9), 96 byte UTF-8 code payload".
        self::assertSame(96, \strlen($payload));
        self::assertSame($payload, $qr->value);
        self::assertSame('001', $qr->version());
        self::assertSame(1, $qr->characterSet());
        self::assertSame('BHBLDEHHXXX', $qr->bic()?->value);
        self::assertSame('Franz Mustermänn', $qr->beneficiaryName());
        self::assertSame('DE71110220330123456789', $qr->iban()->value);
        self::assertSame('12.3', $qr->amount()?->value);
        self::assertSame('12.3', $qr->writtenAmount());
        self::assertSame('GDDS', $qr->purpose());
        self::assertSame('RF18539007547034', $qr->reference());
        self::assertSame('RF18539007547034', $qr->creditorReference()?->value);
        self::assertNull($qr->remittanceText());
        self::assertNull($qr->beneficiaryToOriginatorInformation());
    }

    public function testTheV2ExampleComesThroughFromBytesAsUtf8(): void
    {
        $bytes = \mb_convert_encoding(\implode("\n", self::V2), 'ISO-8859-1', 'UTF-8');
        // "103 characters including spaces (5) and line feeds (10), 103 byte ISO 8859-1 code payload".
        self::assertSame(103, \strlen($bytes));

        $qr = EpcQr::fromBytes($bytes);

        $expected = self::V2;
        $expected[2] = '1';
        self::assertSame(\implode("\n", $expected), $qr->value);
        self::assertSame(1, $qr->characterSet());
        self::assertSame('002', $qr->version());
        self::assertNull($qr->bic());
        self::assertSame("François D'Alsace S.A.", $qr->beneficiaryName());
        self::assertNull($qr->purpose());
        self::assertNull($qr->reference());
        self::assertNull($qr->creditorReference());
        self::assertSame('Client:Marie Louise La Lune', $qr->remittanceText());
    }

    public function testFromStringRefusesNonAsciiTextDeclaredInAnotherCharacterSet(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('fromBytes()');

        new EpcQr(\implode("\n", self::V2));
    }

    public function testFromStringAcceptsAsciiTextDeclaredInAnotherCharacterSet(): void
    {
        $lines = self::V2;
        $lines[5] = 'Francois';
        $payload = \implode("\n", $lines);

        self::assertSame($payload, EpcQr::from($payload)->value);
        self::assertSame(2, EpcQr::from($payload)->characterSet());
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function characterSets(): iterable
    {
        // EPC069-12 v3.0, section 2.1.
        yield '2 ISO 8859-1' => ['2', 'ISO-8859-1'];
        yield '3 ISO 8859-2' => ['3', 'ISO-8859-2'];
        yield '4 ISO 8859-4' => ['4', 'ISO-8859-4'];
        yield '5 ISO 8859-5' => ['5', 'ISO-8859-5'];
        yield '6 ISO 8859-7' => ['6', 'ISO-8859-7'];
        yield '7 ISO 8859-10' => ['7', 'ISO-8859-10'];
        yield '8 ISO 8859-15' => ['8', 'ISO-8859-15'];
    }

    #[DataProvider('characterSets')]
    public function testFromBytesTranscodesEveryDeclaredCharacterSet(string $code, string $encoding): void
    {
        $name = match ($code) {
            '5' => 'Иван',
            '6' => 'Ιωάννης',
            '8' => 'Jean €',
            default => 'Müller',
        };
        $lines = self::V1;
        $lines[2] = $code;
        $lines[5] = $name;

        $qr = EpcQr::fromBytes(\mb_convert_encoding(\implode("\n", $lines), $encoding, 'UTF-8'));

        $lines[2] = '1';
        self::assertSame(\implode("\n", $lines), $qr->value);
        self::assertSame($name, $qr->beneficiaryName());
    }

    public function testFromBytesKeepsAUtf8Payload(): void
    {
        $payload = \implode("\n", self::V1);

        self::assertTrue(EpcQr::fromBytes($payload)->equals(new EpcQr($payload)));
    }

    public function testFromBytesRefusesAnUnknownCharacterSet(): void
    {
        $lines = self::V1;
        $lines[2] = '9';

        $this->expectException(InvalidPaymentQrException::class);

        EpcQr::fromBytes(\implode("\n", $lines));
    }

    public function testFromBytesRefusesAPayloadWithoutTheHeader(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        EpcQr::fromBytes('BCD');
    }

    public function testFromBytesRefusesMoreThan331Bytes(): void
    {
        $lines = self::V1;
        $lines[2] = '2';
        $lines[9] = '';
        $lines[] = \str_repeat('a', 140);
        $lines[] = \str_repeat('b', 70);
        $lines[5] = \str_repeat('c', 70);
        $bytes = \implode("\n", $lines);
        self::assertGreaterThan(331, \strlen($bytes));

        $this->expectException(InvalidPaymentQrException::class);

        EpcQr::fromBytes($bytes);
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function lineEndings(): iterable
    {
        yield 'LF' => ["\n"];
        yield 'CRLF' => ["\r\n"];
    }

    #[DataProvider('lineEndings')]
    public function testLineEndingsRoundTripByteExact(string $separator): void
    {
        $payload = \implode($separator, self::V1);

        self::assertSame($payload, EpcQr::from($payload)->value);
    }

    #[DataProvider('lineEndings')]
    public function testATrailingSeparatorIsRefused(string $separator): void
    {
        // EPC069-12 v3.0, section 2.2: "The last populated element is not followed by any character or element
        // separator." Review Focus 3's trailing-newline round trip does not hold for EPC.
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('populated last element');

        new EpcQr(\implode($separator, self::V1) . $separator);
    }

    public function testATrailingEmptyElementIsRefused(): void
    {
        $lines = self::V2;
        [$lines[2], $lines[5], $lines[10]] = ['1', 'Francois', ''];

        self::assertFalse(EpcQr::isValid(\implode("\n", $lines)));
    }

    public function testLfAndCrlfFormsAreNotEqual(): void
    {
        self::assertFalse(EpcQr::from(\implode("\n", self::V1))->equals(EpcQr::from(\implode("\r\n", self::V1))));
    }

    public function testAStrayCarriageReturnIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\r", self::V1));
    }

    public function testTheBeneficiaryToOriginatorInformationIsRead(): void
    {
        $lines = self::V1;
        $lines[] = '';
        $lines[] = 'Thank you';

        self::assertSame('Thank you', EpcQr::from(\implode("\n", $lines))->beneficiaryToOriginatorInformation());
    }

    public function testANonRfReferenceGivesNoCreditorReference(): void
    {
        $lines = self::V1;
        $lines[9] = 'INVOICE 42';

        $qr = EpcQr::from(\implode("\n", $lines));

        self::assertSame('INVOICE 42', $qr->reference());
        self::assertNull($qr->creditorReference());
    }

    public function testWithoutAmountTheAmountIsNull(): void
    {
        $qr = EpcQr::from(\implode("\n", \array_slice(self::V1, 0, 7)));

        self::assertNull($qr->amount());
        self::assertNull($qr->writtenAmount());
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function validAmounts(): iterable
    {
        yield 'minimum' => ['EUR0.01'];
        yield 'maximum' => ['EUR999999999.99'];
        yield 'integer' => ['EUR5'];
    }

    #[DataProvider('validAmounts')]
    public function testAmountsWithinTheBoundsParse(string $amount): void
    {
        $lines = self::V1;
        $lines[7] = $amount;

        self::assertTrue(EpcQr::isValid(\implode("\n", $lines)));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function invalidLines(): iterable
    {
        yield 'service tag' => [0, 'bcd'];
        yield 'version' => [1, '003'];
        yield 'character set' => [2, '9'];
        yield 'identification' => [3, 'SCX'];
        yield 'V1 without BIC' => [4, ''];
        yield 'malformed BIC' => [4, 'BHBL'];
        yield 'empty name' => [5, ''];
        yield 'name of 71 characters' => [5, \str_repeat('ä', 71)];
        yield 'invalid IBAN' => [6, 'DE71110220330123456780'];
        yield 'empty IBAN' => [6, ''];
        yield 'amount below 0.01' => [7, 'EUR0.00'];
        yield 'amount above the maximum' => [7, 'EUR1000000000'];
        yield 'amount of 13 characters' => [7, 'EUR0000000012.30'];
        yield 'amount with a comma' => [7, 'EUR12,3'];
        yield 'amount in another currency' => [7, 'CHF12.3'];
        yield 'amount without digits' => [7, 'EUR'];
        yield 'amount with two marks' => [7, 'EUR1.2.3'];
        yield 'purpose of 5 characters' => [8, 'GDDSX'];
        yield 'reference of 36 characters' => [9, \str_repeat('1', 36)];
    }

    #[DataProvider('invalidLines')]
    public function testAnInvalidLineIsRefused(int $index, string $line): void
    {
        $lines = self::V1;
        $lines[$index] = $line;

        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", $lines));
    }

    public function testAReferenceAndATextTogetherAreRefused(): void
    {
        $lines = self::V1;
        $lines[] = 'text';

        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", $lines));
    }

    public function testATextOf141CharactersIsRefused(): void
    {
        $lines = self::V2;
        $lines[2] = '1';
        $lines[10] = \str_repeat('x', 141);

        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", $lines));
    }

    public function testInformationOf71CharactersIsRefused(): void
    {
        $lines = self::V1;
        $lines[] = '';
        $lines[] = \str_repeat('x', 71);

        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", $lines));
    }

    public function testSixLinesAreRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", \array_slice(self::V1, 0, 6)));
    }

    public function testThirteenLinesAreRefused(): void
    {
        $lines = self::V1;
        $lines[9] = '';
        $lines[] = '';
        $lines[] = 'info';
        $lines[] = 'extra';

        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", $lines));
    }

    public function testMoreThan331CharactersAreRefused(): void
    {
        $lines = self::V2;
        $lines[2] = '1';
        $lines[5] = \str_repeat('n', 70);
        $lines[10] = \str_repeat('t', 140);
        $lines[] = \str_repeat('i', 70);
        self::assertGreaterThan(331, \strlen(\implode("\n", $lines)));

        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", $lines));
    }

    public function testInvalidUtf8IsRefused(): void
    {
        $lines = self::V1;
        $lines[5] = "Franz M\xFCstermann";

        $this->expectException(InvalidPaymentQrException::class);

        new EpcQr(\implode("\n", $lines));
    }

    public function testMaskedHidesTheIban(): void
    {
        $lines = self::V1;
        $masked = EpcQr::from(\implode("\r\n", $lines))->masked();

        $lines[5] = 'F***';
        $lines[6] = 'DE****************6789';
        self::assertSame(\implode("\r\n", $lines), $masked);
        self::assertStringNotContainsString('Mustermänn', $masked);
        self::assertStringNotContainsString('DE71110220330123456789', $masked);
        self::assertFalse(EpcQr::isValid($masked));
    }

    public function testMaskedHidesTheRemittanceTextAndTheBeneficiaryToOriginatorInformation(): void
    {
        $lines = [...self::V2, 'Thanks, Marie'];
        $lines[2] = '1';
        $masked = EpcQr::from(\implode("\n", $lines))->masked();

        self::assertSame(['F***', 'FR*********************2606', 'EUR12.3', '', '', '***', '***'], \array_slice(\explode("\n", $masked), 5));
        self::assertStringNotContainsString('Marie', $masked);
    }

    public function testMaskedKeepsTheStructuredReference(): void
    {
        self::assertStringEndsWith("\nRF18539007547034", EpcQr::from(\implode("\n", self::V1))->masked());
    }

    public function testMessagesCarryNoPartOfThePayload(): void
    {
        $lines = self::V1;
        $lines[6] = 'DE71110220330123456780';

        try {
            new EpcQr(\implode("\n", $lines));
            self::fail('Expected an exception.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringNotContainsString('6780', $exception->getMessage());
            self::assertStringNotContainsString('Franz', $exception->getMessage());
        }
    }

    public function testEveryElementAtItsMaximumParses(): void
    {
        $lines = self::V1;
        [$lines[5], $lines[8], $lines[9]] = [\str_repeat('n', 70), 'ABCD', \str_repeat('r', 35)];
        $lines[] = '';
        $lines[] = \str_repeat('i', 70);

        self::assertTrue(EpcQr::isValid(\implode("\n", $lines)));

        $text = self::V2;
        [$text[2], $text[10]] = ['1', \str_repeat('t', 140)];
        self::assertTrue(EpcQr::isValid(\implode("\n", $text)));
    }

    public function testExactly331CharactersParse(): void
    {
        $lines = self::V2;
        [$lines[2], $lines[5], $lines[10]] = ['1', \str_repeat('n', 70), \str_repeat('t', 140)];
        $lines[] = \str_repeat('i', 66);
        $payload = \implode("\n", $lines);
        self::assertSame(331, \strlen($payload));

        self::assertTrue(EpcQr::isValid($payload));
        self::assertSame($payload, EpcQr::fromBytes($payload)->value);
    }

    public function testFromBytesCountsUtf8Bytes(): void
    {
        $lines = self::V2;
        [$lines[2], $lines[5], $lines[10]] = ['1', \str_repeat('ä', 70), \str_repeat('t', 140)];
        $payload = \implode("\n", $lines);
        self::assertLessThanOrEqual(331, \preg_match_all('/./su', $payload));
        self::assertGreaterThan(331, \strlen($payload));

        self::assertTrue(EpcQr::isValid($payload));
        $this->expectException(InvalidPaymentQrException::class);

        EpcQr::fromBytes($payload);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function asciiCharacterSets(): iterable
    {
        yield 'nine' => ['9'];
        yield 'zero' => ['0'];
        yield 'two digits' => ['12'];
        yield 'letter' => ['x1'];
    }

    #[DataProvider('asciiCharacterSets')]
    public function testAnUnknownCharacterSetIsRefusedForAsciiText(string $code): void
    {
        $lines = self::V1;
        [$lines[2], $lines[5]] = [$code, 'Franz'];

        self::assertFalse(EpcQr::isValid(\implode("\n", $lines)));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function fractions(): iterable
    {
        yield 'ten cents' => ['EUR0.10', true];
        yield 'one cent' => ['EUR0.01', true];
        yield 'one digit' => ['EUR0.1', true];
        yield 'a tenth of a cent' => ['EUR0.001', false];
        yield 'nine tenths of a cent' => ['EUR0.009', false];
        yield 'leading zeros' => ['EUR000000001', true];
    }

    #[DataProvider('fractions')]
    public function testSmallAmounts(string $amount, bool $valid): void
    {
        $lines = self::V1;
        $lines[7] = $amount;

        self::assertSame($valid, EpcQr::isValid(\implode("\n", $lines)));
    }

    public function testFromBytesRefusesLeadingBytesBeforeTheHeader(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('header');

        EpcQr::fromBytes('x' . \implode("\n", self::V1));
    }

    public function testA332ByteUtf8PayloadOutsideTheIsoSetsIsRefused(): void
    {
        // EPC069-12 v3.0, section 2.2: "The total payload is limited to 331 bytes." Cyrillic and Greek together fit no
        // single ISO 8859 set of section 2.1.
        $lines = self::V2;
        [$lines[2], $lines[5], $lines[10]] = ['1', 'Иван Ιωάννης', \str_repeat('я', 140)];
        $payload = \implode("\n", $lines);
        self::assertGreaterThan(331, \strlen($payload));
        self::assertLessThanOrEqual(331, \preg_match_all('/./su', $payload));

        self::assertFalse(EpcQr::isValid($payload));
    }

    public function testATranscodedPayloadOf331IsoCharactersParses(): void
    {
        $lines = self::V2;
        [$lines[5], $lines[10]] = [\str_repeat('ç', 70), \str_repeat('é', 140)];
        $lines[] = \str_repeat('à', 66);
        $bytes = \mb_convert_encoding(\implode("\n", $lines), 'ISO-8859-1', 'UTF-8');
        self::assertSame(331, \strlen($bytes));

        $qr = EpcQr::fromBytes($bytes);

        self::assertGreaterThan(331, \strlen($qr->value));
        self::assertTrue($qr->equals(EpcQr::from($qr->value)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function isoCharacters(): iterable
    {
        // A character with one byte in each set of EPC069-12 v3.0 section 2.1 (Unicode Consortium 8859-N.TXT) and two or
        // three in UTF-8.
        yield '8859-1' => ['é'];
        yield '8859-2' => ['ő'];
        yield '8859-4' => ['ĸ'];
        yield '8859-5' => ['Ж'];
        yield '8859-7' => ['Ω'];
        yield '8859-10' => ['―'];
        yield '8859-15' => ['Ÿ'];
    }

    #[DataProvider('isoCharacters')]
    public function testA331CharacterPayloadOfOneIsoSetParsesWithoutAnExtension(string $character): void
    {
        $lines = self::V2;
        [$lines[2], $lines[5], $lines[10]] = ['1', \str_repeat($character, 70), \str_repeat($character, 140)];
        $lines[] = \str_repeat($character, 66);
        $payload = \implode("\n", $lines);
        self::assertSame(331, \preg_match_all('/./su', $payload));
        self::assertGreaterThan(331, \strlen($payload));

        self::assertSame($payload, EpcQr::from($payload)->value);
    }

    public function testA332CharacterPayloadOfOneIsoSetIsRefused(): void
    {
        $lines = self::V2;
        [$lines[2], $lines[5], $lines[10]] = ['1', \str_repeat('Ж', 70), \str_repeat('Ж', 140)];
        $lines[] = \str_repeat('Ж', 67);

        self::assertFalse(EpcQr::isValid(\implode("\n", $lines)));
    }
}
