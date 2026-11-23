<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Swiss;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBill;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBillAddress;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBillReferenceType;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use MSSTC4PHP\ValueObject\Financial\Reference\QrReference;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QrBill::class)]
#[CoversClass(QrBillAddress::class)]
#[CoversClass(QrBillReferenceType::class)]
#[UsesClass(TextLines::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Mask::class)]
#[UsesClass(QrReference::class)]
#[UsesClass(CreditorReference::class)]
#[UsesClass(Mod10Recursive::class)]
#[UsesClass(Iso7064Mod97::class)]
final class QrBillTest extends TestCase
{
    private const array NO_ULTIMATE_CREDITOR = ['', '', '', '', '', '', ''];

    private const array NO_DEBTOR = ['', '', '', '', '', '', ''];

    public function testExampleOneParses(): void
    {
        $qr = new QrBill(\implode("\n", $this->exampleOne()));

        self::assertSame('CH6431961000004421557', $qr->iban()->value);
        self::assertTrue($qr->isQrIban());
        self::assertSame(QrBillReferenceType::QrReference, $qr->referenceType());
        self::assertInstanceOf(QrReference::class, $qr->reference());
        self::assertSame('000008207791225857421286694', $qr->reference()->value);
        self::assertSame('50.00', $qr->writtenAmount());
        self::assertSame('50', $qr->amount()?->value);
        self::assertSame(CurrencyCode::CHF, $qr->currency());
        self::assertSame('Payment of travel', $qr->unstructuredMessage());
        self::assertNull($qr->billingInformation());
        self::assertSame([], $qr->alternativeProcedures());
    }

    public function testExampleOneAddressesAreTyped(): void
    {
        $qr = new QrBill(\implode("\n", $this->exampleOne()));

        $creditor = $qr->creditor();
        self::assertSame('Max Muster & Söhne (sample company)', $creditor->name);
        self::assertSame('Musterstrasse', $creditor->street);
        self::assertSame('123', $creditor->buildingNumber);
        self::assertSame('8000', $creditor->postalCode);
        self::assertSame('Seldwyla', $creditor->town);
        self::assertSame('CH', $creditor->country);
        self::assertSame(CountryCode::CH, $creditor->countryCode());

        $debtor = $qr->ultimateDebtor();
        self::assertNotNull($debtor);
        self::assertSame('Simon Muster', $debtor->name);
        self::assertSame('1', $debtor->buildingNumber);
    }

    public function testExampleTwoWithBillingInformationAndAnAlternativeProcedureParses(): void
    {
        $qr = new QrBill(\implode("\n", $this->exampleTwo()));

        self::assertTrue($qr->isQrIban());
        self::assertSame('210000000003139471430009017', $qr->reference()?->value);
        self::assertSame('1949.75', $qr->writtenAmount());
        self::assertSame('Order from 15.10.2020', $qr->unstructuredMessage());
        self::assertSame('//S1/10/1234/11/201021/30/102673386/32/7.7/40/0:30', $qr->billingInformation());
        self::assertSame(['eBill/B/simon.muster@example.com'], $qr->alternativeProcedures());
    }

    public function testExampleThreeWithoutAmountDebtorOrReferenceParses(): void
    {
        $qr = new QrBill(\implode("\n", $this->exampleThree()));

        self::assertFalse($qr->isQrIban());
        self::assertSame(QrBillReferenceType::None, $qr->referenceType());
        self::assertNull($qr->reference());
        self::assertNull($qr->amount());
        self::assertNull($qr->writtenAmount());
        self::assertNull($qr->ultimateDebtor());
        self::assertNull($qr->creditor()->buildingNumber);
        self::assertSame('P.O. Box', $qr->creditor()->street);
        self::assertNull($qr->unstructuredMessage());
    }

    public function testExamplesFiveAndSixWithACreditorReferenceParse(): void
    {
        $five = new QrBill(\implode("\n", $this->exampleFive()));
        $six = new QrBill(\implode("\n", $this->exampleSix()));

        self::assertSame(QrBillReferenceType::CreditorReference, $five->referenceType());
        self::assertInstanceOf(CreditorReference::class, $five->reference());
        self::assertSame('RF18539007547034', $five->reference()->value);
        self::assertSame('LI', $five->creditor()->country);
        self::assertSame('DE', $six->ultimateDebtor()?->country);
        self::assertSame('78462', $six->ultimateDebtor()->postalCode);
    }

    public function testExampleFourIsRefusedForItsCreditorReference(): void
    {
        // SIX IG 2.3 Annex A example 4 prints RF720191230100405JSH0438, which fails the ISO 11649 check.
        $lines = $this->exampleFive();
        $lines[3] = 'CH5800791123000889012';
        $lines[28] = 'RF720191230100405JSH0438';

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
        $lines[28] = 'RF240191230100405JSH0438';
        self::assertTrue(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testAQrIbanWithACreditorReferenceIsRefused(): void
    {
        // SIX IG 2.3, section 4.3.2: "Use of the Creditor Reference (ISO 11649) presupposes that an IBAN has been used.
        // A QR-IBAN cannot be used."
        $lines = $this->exampleTwo();
        $lines[27] = 'SCOR';
        $lines[28] = 'RF18539007547034';

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('QR-IBAN');

        new QrBill(\implode("\n", $lines));
    }

    public function testAnIbanWithAQrReferenceIsRefused(): void
    {
        // SIX IG 2.3, section 4.3.2: "Use of the QR reference presupposes that a QR-IBAN has been used ... An IBAN
        // cannot therefore be used."
        $lines = $this->exampleFive();
        $lines[27] = 'QRR';
        $lines[28] = '210000000003139471430009017';

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('QR-IBAN');

        new QrBill(\implode("\n", $lines));
    }

    public function testAQrIbanWithoutReferenceIsRefused(): void
    {
        $lines = $this->exampleTwo();
        $lines[27] = 'NON';
        $lines[28] = '';

        $this->expectException(InvalidPaymentQrException::class);

        new QrBill(\implode("\n", $lines));
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
        // Example 2 has 33 elements; a final separator adds the empty 34th (SIX IG 2.3, section 4.1.4).
        $payload = \implode($separator, $this->exampleTwo());
        $trailing = $payload . $separator;

        self::assertSame($payload, QrBill::from($payload)->value);
        self::assertSame($trailing, QrBill::from($trailing)->value);
        self::assertSame(['eBill/B/simon.muster@example.com'], QrBill::from($trailing)->alternativeProcedures());
        self::assertFalse(QrBill::from($payload)->equals(QrBill::from($trailing)));
    }

    #[DataProvider('lineEndings')]
    public function testASeparatorAfterThe34thElementIsRefused(string $separator): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('31 to 34 elements');

        new QrBill(\implode($separator, $this->exampleOne()) . $separator);
    }

    public function testLfAndCrlfFormsAreNotEqual(): void
    {
        self::assertFalse(QrBill::from(\implode("\n", $this->exampleOne()))->equals(QrBill::from(\implode("\r\n", $this->exampleOne()))));
    }

    public function testMixedLineEndingsAreRefused(): void
    {
        // SIX IG 2.3, section 4.1.4: "The same type of carriage return must always be used within a document."
        $payload = \implode("\n", $this->exampleOne());
        $payload = \preg_replace('/\n/', "\r\n", $payload, 1);
        self::assertIsString($payload);

        $this->expectException(InvalidPaymentQrException::class);

        new QrBill($payload);
    }

    public function testTheTrailerAloneEndsTheShortestPayload(): void
    {
        self::assertTrue(QrBill::isValid(\implode("\n", \array_slice($this->exampleThree(), 0, 31))));
    }

    public function testAnAlternativeProcedureNeedsTheBillingInformationLine(): void
    {
        $lines = $this->exampleThree();
        $lines[32] = 'eBill/B/x@example.com';
        $lines[33] = 'other';

        self::assertSame(['eBill/B/x@example.com', 'other'], QrBill::from(\implode("\n", $lines))->alternativeProcedures());
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function invalidLines(): iterable
    {
        yield 'QR type' => [0, 'SPX'];
        yield 'version' => [1, '0201'];
        yield 'coding' => [2, '2'];
        yield 'German IBAN' => [3, 'DE71110220330123456789'];
        yield 'IBAN with spaces' => [3, 'CH52 0483 5012 3456 7100 0'];
        yield 'IBAN check digits' => [3, 'CH5304835012345671000'];
        yield 'address type K' => [4, 'K'];
        yield 'empty creditor name' => [5, ''];
        yield 'creditor name of 71 characters' => [5, \str_repeat('n', 71)];
        yield 'street of 71 characters' => [6, \str_repeat('s', 71)];
        yield 'building number of 17 characters' => [7, \str_repeat('1', 17)];
        yield 'empty postal code' => [8, ''];
        yield 'postal code of 17 characters' => [8, \str_repeat('1', 17)];
        yield 'empty town' => [9, ''];
        yield 'town of 36 characters' => [9, \str_repeat('t', 36)];
        yield 'country of three letters' => [10, 'CHE'];
        yield 'lowercase country' => [10, 'ch'];
        yield 'ultimate creditor filled' => [11, 'S'];
        yield 'ultimate creditor name filled' => [12, 'Name'];
        yield 'amount without decimals' => [18, '50'];
        yield 'amount with one decimal' => [18, '50.0'];
        yield 'amount with a leading zero' => [18, '050.00'];
        yield 'amount above the maximum' => [18, '1000000000.00'];
        yield 'amount with a comma' => [18, '50,00'];
        yield 'currency USD' => [19, 'USD'];
        yield 'reference type' => [27, 'ISO'];
        yield 'reference of 26 digits' => [28, '00000820779122585742128669'];
        yield 'reference with a wrong check digit' => [28, '000008207791225857421286695'];
        yield 'unstructured message of 141 characters' => [29, \str_repeat('m', 141)];
        yield 'trailer' => [30, 'EOD'];
        yield 'character outside the Latin set' => [29, 'Оплата'];
        yield 'control character' => [29, "tab\there"];
    }

    #[DataProvider('invalidLines')]
    public function testAnInvalidElementIsRefused(int $index, string $line): void
    {
        $lines = $this->exampleOne();
        $lines[$index] = $line;

        $this->expectException(InvalidPaymentQrException::class);

        new QrBill(\implode("\n", $lines));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function invalidDebtors(): iterable
    {
        yield 'address type missing' => [20, ''];
        yield 'name missing' => [21, ''];
        yield 'postal code missing' => [24, ''];
        yield 'town missing' => [25, ''];
        yield 'country missing' => [26, ''];
        yield 'name of 71 characters' => [21, \str_repeat('n', 71)];
    }

    #[DataProvider('invalidDebtors')]
    public function testAnIncompleteUltimateDebtorIsRefused(int $index, string $line): void
    {
        $lines = $this->exampleOne();
        $lines[$index] = $line;

        $this->expectException(InvalidPaymentQrException::class);

        new QrBill(\implode("\n", $lines));
    }

    public function testTheNotificationAmountZeroParses(): void
    {
        // SIX IG 2.3, section 4.4, Table 10: a "DO NOT USE FOR PAYMENT" QR-bill carries the amount 0.00.
        $lines = $this->exampleThree();
        $lines[18] = '0.00';
        $lines[29] = 'DO NOT USE FOR PAYMENT';

        self::assertSame('0', QrBill::from(\implode("\n", $lines))->amount()?->value);
    }

    public function testTheLargestAmountParses(): void
    {
        $lines = $this->exampleThree();
        $lines[18] = '999999999.99';

        self::assertTrue(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testMessageAndBillingInformationShareTheirLimit(): void
    {
        // SIX IG 2.3, section 4.3.3: "The number of characters in the two fields together must not exceed 140".
        $lines = $this->exampleThree();
        $lines[29] = \str_repeat('m', 70);
        $lines[31] = \str_repeat('b', 70);
        self::assertTrue(QrBill::isValid(\implode("\n", $lines)));

        $lines[31] = \str_repeat('b', 71);
        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testAnAlternativeProcedureOf101CharactersIsRefused(): void
    {
        $lines = $this->exampleThree();
        $lines[32] = \str_repeat('a', 101);

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testThirtyElementsAreRefused(): void
    {
        self::assertFalse(QrBill::isValid(\implode("\n", \array_slice($this->exampleThree(), 0, 30))));
    }

    public function testThirtyFiveElementsAreRefused(): void
    {
        $lines = $this->exampleThree();
        $lines[] = 'third';

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testAReferenceWithReferenceTypeNonIsRefused(): void
    {
        $lines = $this->exampleThree();
        $lines[28] = 'RF18539007547034';

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testACreditorReferenceWithSpacesIsRefused(): void
    {
        $lines = $this->exampleFive();
        $lines[28] = 'RF18 5390 0754 7034';

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testAQrReferenceWithSpacesIsRefused(): void
    {
        $lines = $this->exampleOne();
        $lines[28] = '00 00820 77912 25857 42128 66694';

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testALowercaseCreditorReferenceIsAccepted(): void
    {
        // SIX IG 2.3: "The banks draw no distinction between lower and upper case when processing."
        $lines = $this->exampleFive();
        $lines[28] = 'rf18539007547034';

        self::assertSame('RF18539007547034', QrBill::from(\implode("\n", $lines))->reference()?->value);
    }

    public function testMaskedHidesTheIban(): void
    {
        $lines = $this->exampleOne();
        $masked = QrBill::from(\implode("\r\n", $lines))->masked();

        $lines[3] = 'CH***************1557';
        [$lines[5], $lines[6], $lines[7], $lines[8], $lines[9]] = ['M***', '***', '***', '***', '***'];
        [$lines[21], $lines[22], $lines[23], $lines[24], $lines[25]] = ['S***', '***', '***', '***', '***'];
        self::assertSame(\implode("\r\n", $lines), $masked);
        self::assertStringNotContainsString('Muster', $masked);
        self::assertStringNotContainsString('Seldwyla', $masked);
    }

    public function testMaskedLeavesAnEmptyAddressElementEmpty(): void
    {
        $lines = $this->exampleThree();
        $masked = QrBill::from(\implode("\n", $lines))->masked();

        self::assertSame(['S', 'M***', '***', '', '***', '***', 'CH'], \array_slice(\explode("\n", $masked), 4, 7));
        self::assertFalse(QrBill::isValid($masked));
    }

    public function testMessagesCarryNoPartOfThePayload(): void
    {
        $lines = $this->exampleOne();
        $lines[28] = '000008207791225857421286695';

        try {
            new QrBill(\implode("\n", $lines));
            self::fail('Expected an exception.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringNotContainsString('8207791', $exception->getMessage());
            self::assertStringNotContainsString('Muster', $exception->getMessage());
        }
    }

    public function testEveryElementAtItsMaximumParses(): void
    {
        $lines = self::exampleOne();
        [$lines[5], $lines[6], $lines[7], $lines[8], $lines[9]] = [\str_repeat('n', 70), \str_repeat('s', 70), \str_repeat('1', 16), \str_repeat('2', 16), \str_repeat('t', 35)];
        [$lines[21], $lines[22], $lines[23], $lines[24], $lines[25]] = [\str_repeat('n', 70), \str_repeat('s', 70), \str_repeat('1', 16), \str_repeat('2', 16), \str_repeat('t', 35)];
        [$lines[29], $lines[31], $lines[32], $lines[33]] = [\str_repeat('m', 140), '', \str_repeat('a', 100), \str_repeat('b', 100)];

        self::assertTrue(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testASecondAlternativeProcedureOf101CharactersIsRefused(): void
    {
        $lines = self::exampleThree();
        $lines[33] = \str_repeat('a', 101);

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testTheLastUltimateCreditorElementMustBeEmpty(): void
    {
        $lines = self::exampleOne();
        $lines[17] = 'CH';

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testAnEmptyCreditorIsRefused(): void
    {
        $lines = self::exampleOne();
        for ($index = 4; $index <= 10; $index++) {
            $lines[$index] = '';
        }

        self::assertFalse(QrBill::isValid(\implode("\n", $lines)));
    }

    public function testTheQrIidRangeBounds(): void
    {
        // SIX IG 2.3, section 2.8: "QR-IIDs consist exclusively of numbers from 30000 to 31999."
        $iban = static function (string $iid): string {
            $bban = $iid . '000000000123';
            $digits = \strtr($bban . 'CH00', ['C' => '12', 'H' => '17']);
            $remainder = 0;
            foreach (\str_split($digits) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }

            return \sprintf('CH%02d%s', 98 - $remainder, $bban);
        };
        $qrr = self::exampleOne();
        $scor = self::exampleFive();

        $qrr[3] = $iban('30000');
        self::assertTrue(QrBill::from(\implode("\n", $qrr))->isQrIban());
        $qrr[3] = $iban('31999');
        self::assertTrue(QrBill::from(\implode("\n", $qrr))->isQrIban());
        $scor[3] = $iban('29999');
        self::assertFalse(QrBill::from(\implode("\n", $scor))->isQrIban());
        $scor[3] = $iban('32000');
        self::assertFalse(QrBill::from(\implode("\n", $scor))->isQrIban());
    }

    public function testALowercaseIbanIsAccepted(): void
    {
        $lines = self::exampleThree();
        $lines[3] = 'ch5204835012345671000';

        self::assertSame('CH5204835012345671000', QrBill::from(\implode("\n", $lines))->iban()->value);
    }

    public function testTheAddressValidatesItsElements(): void
    {
        $address = new QrBillAddress('Name', null, null, '8000', 'Seldwyla', 'CH');
        self::assertNull($address->street);

        foreach ([
            ['', null, null, '8000', 'Town', 'CH'],
            ['Name', '', null, '8000', 'Town', 'CH'],
            ['Name', null, '', '8000', 'Town', 'CH'],
            ['Name', null, null, '', 'Town', 'CH'],
            ['Name', null, null, '8000', '', 'CH'],
            ['Name', null, null, '8000', 'Town', 'ch'],
            ['Name', null, null, '8000', 'Town', "CH\n"],
            ['Name', null, null, '8000', 'Town', 'xCH'],
        ] as $arguments) {
            try {
                new QrBillAddress(...$arguments);
                self::fail('Expected an exception.');
            } catch (InvalidPaymentQrException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /**
     * SIX IG QR-bill 2.3, Annex A, example 1 (Table 17).
     *
     * @return non-empty-list<string>
     */
    private function exampleOne(): array
    {
        return [
            'SPC', '0200', '1', 'CH6431961000004421557',
            'S', 'Max Muster & Söhne (sample company)', 'Musterstrasse', '123', '8000', 'Seldwyla', 'CH',
            ...self::NO_ULTIMATE_CREDITOR,
            '50.00', 'CHF',
            'S', 'Simon Muster', 'Musterstrasse', '1', '8000', 'Seldwyla', 'CH',
            'QRR', '000008207791225857421286694', 'Payment of travel', 'EPD', '', '', '',
        ];
    }

    /**
     * SIX IG QR-bill 2.3, Annex A, example 2 (Table 18): ends after the first alternative procedure.
     *
     * @return non-empty-list<string>
     */
    private function exampleTwo(): array
    {
        return [
            'SPC', '0200', '1', 'CH4431999123000889012',
            'S', 'Max Muster & Söhne (sample company)', 'Musterstrasse', '123', '8000', 'Seldwyla', 'CH',
            ...self::NO_ULTIMATE_CREDITOR,
            '1949.75', 'CHF',
            'S', 'Simon Muster', 'Musterstrasse', '1', '8000', 'Seldwyla', 'CH',
            'QRR', '210000000003139471430009017', 'Order from 15.10.2020', 'EPD',
            '//S1/10/1234/11/201021/30/102673386/32/7.7/40/0:30', 'eBill/B/simon.muster@example.com',
        ];
    }

    /**
     * SIX IG QR-bill 2.3, Annex A, example 3 (Table 19).
     *
     * @return non-empty-list<string>
     */
    private function exampleThree(): array
    {
        return [
            'SPC', '0200', '1', 'CH5204835012345671000',
            'S', 'Muster Stiftung (sample foundation)', 'P.O. Box', '', '3001', 'Bern', 'CH',
            ...self::NO_ULTIMATE_CREDITOR,
            '', 'CHF',
            ...self::NO_DEBTOR,
            'NON', '', '', 'EPD', '', '', '',
        ];
    }

    /**
     * SIX IG QR-bill 2.3, Annex A, example 5 (Table 21).
     *
     * @return non-empty-list<string>
     */
    private function exampleFive(): array
    {
        return [
            'SPC', '0200', '1', 'CH5800791123000889012',
            'S', 'Max Muster & Söhne', 'Musterstrasse', '123', '9490', 'Vaduz', 'LI',
            ...self::NO_ULTIMATE_CREDITOR,
            '199.95', 'CHF',
            'S', 'Sarah Beispiel', 'Musterstrasse', '1', '8000', 'Seldwyla', 'CH',
            'SCOR', 'RF18539007547034', '', 'EPD', '', '', '',
        ];
    }

    /**
     * SIX IG QR-bill 2.3, Annex A, example 6 (Table 22).
     *
     * @return non-empty-list<string>
     */
    private function exampleSix(): array
    {
        $lines = $this->exampleFive();
        [$lines[8], $lines[9], $lines[10]] = ['8000', 'Seldwyla', 'CH'];
        [$lines[24], $lines[25], $lines[26]] = ['78462', 'Konstanz', 'DE'];

        return \array_values($lines);
    }
}
