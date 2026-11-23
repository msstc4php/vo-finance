<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Emvco;

use LogicException;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MerchantPresentedQr::class)]
#[CoversClass(MerchantAccount::class)]
#[CoversClass(DataObject::class)]
#[CoversClass(PointOfInitiation::class)]
#[CoversClass(InvalidPaymentQrException::class)]
#[UsesClass(EmvcoMask::class)]
#[CoversClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(PayloadBound::class)]
final class MerchantPresentedQrTest extends TestCase
{
    // Banco Central do Brasil, Manual do BR Code 2.0.1, section 2.2: the complete dynamic payload (270 characters).
    public const string BCB_PAYLOAD = '00020104141234567890123426580014BR.GOV.BCB.PIX0136123e4567-e12b-12d1-a456-426655440000'
        . '27300012BR.COM.OUTRO011001234567895204000053039865406123.455802BR5917NOME DO RECEBEDOR6008BRASILIA'
        . '61087007490062190515RP12345678-201980390012BR.COM.OUTRO01190123.ABCD.3456.WXYZ6304AD38';

    // EMVCo QRCPS-MPM 1.1, Annex B.7 "Representation" (read via a third-party copy): not synthetic; the printed text wraps
    // lines inside values, the characters here are decoded from the hexadecimal representation printed under it.
    public const string EMVCO_ANNEX_B7 = '00020101021229300012D156000000000510A93FO3230Q31280012D15600000001030812345678520441115802CN'
        . '5914BEST TRANSPORT6007BEIJING64200002ZH0104最佳运输0202北京540523.7253031565502016233030412340603***0708A60086670902ME'
        . '91320016A0112233449988770708123456786304A13A';

    // Synthetic body, not from a publication: the mandatory data objects without the CRC.
    private const string MINIMAL_BODY = '0002010412CARDACCOUNT15204541153039865802BR5904Shop6004Rome';

    public function testBcbPayloadParsesFieldByField(): void
    {
        $qr = new MerchantPresentedQr(self::BCB_PAYLOAD);

        self::assertSame(self::BCB_PAYLOAD, $qr->value);
        self::assertSame(self::BCB_PAYLOAD, (string) $qr);
        self::assertNull($qr->pointOfInitiation());
        self::assertSame('0000', $qr->merchantCategoryCode());
        self::assertSame(CurrencyCode::BRL, $qr->currency());
        self::assertSame('123.45', $qr->amount()?->value);
        self::assertSame(CountryCode::BR, $qr->countryCode());
        self::assertSame('NOME DO RECEBEDOR', $qr->merchantName());
        self::assertSame('BRASILIA', $qr->merchantCity());
        self::assertSame('70074900', $qr->postalCode());
        self::assertEquals([new DataObject('05', 'RP12345678-2019')], $qr->additionalData());
        self::assertSame('AD38', $qr->get('63'));
        self::assertSame('01', $qr->get('00'));
        self::assertSame('0012BR.COM.OUTRO01190123.ABCD.3456.WXYZ', $qr->get('80'));
        self::assertNull($qr->get('01'));
        self::assertNull($qr->get('99'));
    }

    public function testBcbMerchantAccounts(): void
    {
        $accounts = new MerchantPresentedQr(self::BCB_PAYLOAD)->merchantAccounts();

        self::assertCount(3, $accounts);

        self::assertSame('04', $accounts[0]->id);
        self::assertSame('12345678901234', $accounts[0]->value);
        self::assertNull($accounts[0]->globallyUniqueIdentifier);
        self::assertSame([], $accounts[0]->subFields);

        self::assertSame('26', $accounts[1]->id);
        self::assertSame('BR.GOV.BCB.PIX', $accounts[1]->globallyUniqueIdentifier);
        self::assertEquals([new DataObject('01', '123e4567-e12b-12d1-a456-426655440000')], $accounts[1]->subFields);
        self::assertSame('123e4567-e12b-12d1-a456-426655440000', $accounts[1]->subField('01'));
        self::assertNull($accounts[1]->subField('02'));

        self::assertSame('27', $accounts[2]->id);
        self::assertSame('BR.COM.OUTRO', $accounts[2]->globallyUniqueIdentifier);
        self::assertSame('0123456789', $accounts[2]->subField('01'));
    }

    public function testLowercaseCrcWithTheRightValueIsAccepted(): void
    {
        $payload = substr(self::BCB_PAYLOAD, 0, -4) . 'ad38';
        $qr = new MerchantPresentedQr($payload);

        self::assertSame($payload, $qr->value);
        self::assertSame('ad38', $qr->get('63'));
    }

    public function testMinimalPayload(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY));

        self::assertSame('5411', $qr->merchantCategoryCode());
        self::assertNull($qr->amount());
        self::assertNull($qr->postalCode());
        self::assertSame([], $qr->additionalData());
        self::assertNull($qr->pointOfInitiation());
    }

    public function testAnnexB7ParsesFieldByField(): void
    {
        $qr = new MerchantPresentedQr(self::EMVCO_ANNEX_B7);

        self::assertSame(PointOfInitiation::Dynamic, $qr->pointOfInitiation());
        self::assertSame('4111', $qr->merchantCategoryCode());
        self::assertSame(CurrencyCode::CNY, $qr->currency());
        self::assertSame('23.72', $qr->amount()?->value);
        self::assertSame('01', $qr->get('55'));
        self::assertSame(CountryCode::CN, $qr->countryCode());
        self::assertSame('BEST TRANSPORT', $qr->merchantName());
        self::assertSame('BEIJING', $qr->merchantCity());
        self::assertNull($qr->postalCode());
        self::assertSame('0002ZH0104最佳运输0202北京', $qr->get('64'));
        self::assertEquals([new DataObject('03', '1234'), new DataObject('06', '***'), new DataObject('07', 'A6008667'), new DataObject('09', 'ME')], $qr->additionalData());
        self::assertSame('0016A011223344998877070812345678', $qr->get('91'));
        self::assertSame('A13A', $qr->get('63'));

        $accounts = $qr->merchantAccounts();
        self::assertCount(2, $accounts);
        self::assertSame('29', $accounts[0]->id);
        self::assertSame('D15600000000', $accounts[0]->globallyUniqueIdentifier);
        self::assertSame('A93FO3230Q', $accounts[0]->subField('05'));
        self::assertSame('31', $accounts[1]->id);
        self::assertSame('D15600000001', $accounts[1]->globallyUniqueIdentifier);
        self::assertSame('12345678', $accounts[1]->subField('03'));
    }

    public function testAnnexB7MaskedHidesTheAccountsTheUnreservedTemplateAndTheCrc(): void
    {
        $masked = new MerchantPresentedQr(self::EMVCO_ANNEX_B7)->masked();

        self::assertStringContainsString('A93FO3230Q', self::EMVCO_ANNEX_B7);
        foreach (['D15600000000', 'A93FO3230Q', '12345678', 'A011223344998877', 'A13A'] as $secret) {
            self::assertStringNotContainsString($secret, $masked);
        }

        self::assertStringContainsString('BEST TRANSPORT', $masked);
        self::assertStringContainsString('最佳运输', $masked);
        self::assertStringContainsString('9132' . str_repeat('*', 32), $masked);
        self::assertStringEndsWith('6304****', $masked);
    }

    public function testMaskedHidesTheMobileNumberAndTheCustomerLabelOfTheAdditionalData(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '62440105BILL10212+601234567890503REF0608Jane Doe'));

        self::assertSame('+60123456789', $qr->additionalDataField('02'));
        self::assertStringContainsString('62440105BILL10212' . \str_repeat('*', 12) . '0503REF0608' . \str_repeat('*', 8) . '6304', $qr->masked());
    }

    public function testStringFormatObjectsMayHoldUtf8AndAreCountedInCharacters(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '64140002ZH0104Café26190001A0110Ünïcödé-ok62171213ÄÖÜäöüßéèàùç!'));

        self::assertSame('0002ZH0104Café', $qr->get('64'));
        self::assertSame('Ünïcödé-ok', $qr->merchantAccounts()[1]->subField('01'));
        self::assertEquals([new DataObject('12', 'ÄÖÜäöüßéèàùç!')], $qr->additionalData());
    }

    public function testMerchantAccountIdsRunFrom02To51(): void
    {
        $body = '0002010102110204AAAA26060002AB51060002CD5204541153039865802BR5904Shop6004Rome';
        $qr = new MerchantPresentedQr(self::sealed($body));

        self::assertSame(['02', '26', '51'], array_map(static fn (MerchantAccount $account): string => $account->id, $qr->merchantAccounts()));
        self::assertStringContainsString('010211', $qr->masked());
        self::assertStringContainsString('52045411', $qr->masked());
        self::assertStringContainsString('0204****', $qr->masked());
        self::assertStringContainsString('5106****', $qr->masked());
    }

    public function testOnlyIdsOutsideTheMerchantAccountRangeAreNotAccounts(): void
    {
        $this->assertRefused(self::sealed('0002010102115204541153039865802BR5904Shop6004Rome'));
    }

    public function testAGuiOfThirtyTwoCharactersIsAccepted(): void
    {
        $ok = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '26360032' . str_repeat('G', 32)));

        self::assertSame(str_repeat('G', 32), $ok->merchantAccounts()[1]->globallyUniqueIdentifier);
    }

    public function testPointOfInitiation(): void
    {
        $static = new MerchantPresentedQr(self::sealed(str_replace('000201', '000201010211', self::MINIMAL_BODY)));
        $dynamic = new MerchantPresentedQr(self::sealed(str_replace('000201', '000201010212', self::MINIMAL_BODY)));

        self::assertSame(PointOfInitiation::Static, $static->pointOfInitiation());
        self::assertSame(PointOfInitiation::Dynamic, $dynamic->pointOfInitiation());
        self::assertSame('11', PointOfInitiation::Static->value);
        self::assertSame('12', PointOfInitiation::Dynamic->value);
    }

    public function testAmountIsNormalisedAndTheWrittenFormStaysInGet(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '540510.50'));

        self::assertSame('10.50', $qr->get('54'));
        self::assertInstanceOf(Decimal::class, $qr->amount());
        self::assertSame('10.5', $qr->amount()->value);
    }

    public function testAmountWithATrailingDecimalMark(): void
    {
        // EMVCo 4.7.4.1 lists "98." as a valid amount.
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '540398.'));

        self::assertSame('98', $qr->amount()?->value);
    }

    public function testAmountThatIsNotADecimalGivesNull(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '54043,50'));

        self::assertNull($qr->amount());
    }

    public function testUnknownCurrencyAndCountryGiveNull(): void
    {
        $body = str_replace(['5303986', '5802BR'], ['5303001', '5802ZZ'], self::MINIMAL_BODY);
        $qr = new MerchantPresentedQr(self::sealed($body));

        self::assertNull($qr->currency());
        self::assertNull($qr->countryCode());
    }

    public function testAdditionalDataTemplate(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '62240507INV-0010709TERM-0001'));

        self::assertEquals([new DataObject('05', 'INV-001'), new DataObject('07', 'TERM-0001')], $qr->additionalData());
    }

    public function testMaskedHidesTheMerchantAccountsAndKeepsTheRest(): void
    {
        $qr = new MerchantPresentedQr(self::BCB_PAYLOAD);
        $masked = $qr->masked();

        self::assertSame(\strlen(self::BCB_PAYLOAD), \strlen($masked));
        self::assertStringNotContainsString('12345678901234', $masked);
        self::assertStringNotContainsString('BR.GOV.BCB.PIX', $masked);
        self::assertStringNotContainsString('123e4567', $masked);
        self::assertStringNotContainsString('0123456789', $masked);
        self::assertStringContainsString('NOME DO RECEBEDOR', $masked);
        self::assertStringContainsString('BRASILIA', $masked);
        self::assertStringContainsString('RP12345678-2019', $masked);
        self::assertStringStartsWith('0002010414**************2658' . str_repeat('*', 58) . '2730', $masked);
        self::assertSame(self::BCB_PAYLOAD, $qr->value);
    }

    public function testBcbMaskedHidesTheUnreservedTemplateAndTheCrc(): void
    {
        $masked = new MerchantPresentedQr(self::BCB_PAYLOAD)->masked();

        self::assertStringEndsWith('6304****', $masked);
        self::assertStringNotContainsString('AD38', $masked);
        self::assertStringNotContainsString('0123.ABCD.3456.WXYZ', $masked);
        self::assertStringNotContainsString('BR.COM.OUTRO', $masked);
        self::assertStringContainsString('8039' . str_repeat('*', 39), $masked);
        self::assertStringContainsString('2730' . str_repeat('*', 30), $masked);
    }

    public function testMaskedCountsCharacters(): void
    {
        $masked = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '8006Café12'))->masked();

        self::assertStringContainsString('8006******', $masked);
    }

    public function testJsonSerializesTheFullPayload(): void
    {
        self::assertSame(json_encode(self::BCB_PAYLOAD), json_encode(new MerchantPresentedQr(self::BCB_PAYLOAD)));
    }

    public function testAPayloadOverFiveHundredTwelveCharactersIsAccepted(): void
    {
        self::assertSame(513, \strlen(new MerchantPresentedQr($this->padded(513))->value));
    }

    public function testAPayloadOfTheAbuseBoundIsAccepted(): void
    {
        self::assertSame(7089, \strlen(new MerchantPresentedQr($this->padded(7089))->value));
    }

    public function testALongerPayloadIsRefused(): void
    {
        $this->assertRefused($this->padded(7089) . str_repeat('0', 1));
        $this->assertRefused(str_repeat('0', 40000));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedPayloads(): iterable
    {
        $body = self::MINIMAL_BODY;

        yield 'empty' => [''];
        yield 'invalid UTF-8' => ["\xFF\xFE"];
        yield 'only a marker' => ['000201'];
        yield 'header cut after the id' => [self::sealed($body) . '59'];
        yield 'header cut inside the length' => ['0002016'];
        yield 'non-digit id' => [self::sealed($body . 'AB03abc')];
        yield 'non-digit length' => [self::sealed($body . '59XYabc')];
        yield 'zero length' => [self::sealed($body . '6100')];
        yield 'length runs past the end' => ['0002015903A'];
        yield 'length 99 on a short payload' => [self::sealed($body . '6199Rome')];
        yield 'nested 62 runs past its parent' => [self::sealed($body . '62070510ABC6104ZIP1')];
        yield 'nested 62 runs past the payload' => [self::sealed($body . '62070510ABC')];
        yield 'nested 26 runs past its parent' => [self::sealed($body . '26140004AAAA0110BBBB6104ZIP1')];
        yield 'nested 62 with a cut header' => [self::sealed($body . '620305A')];
        yield 'wrong CRC' => [substr(self::BCB_PAYLOAD, 0, -4) . 'AD39'];
        yield 'wrong lowercase CRC' => [substr(self::BCB_PAYLOAD, 0, -4) . 'ad39'];
        yield 'CRC not hexadecimal' => [substr(self::BCB_PAYLOAD, 0, -4) . 'ZZZZ'];
        yield 'changed body with the old CRC' => [str_replace('BRASILIA', 'BRASILIB', self::BCB_PAYLOAD)];
        yield 'no CRC' => [$body];
        yield 'CRC of the wrong length' => [$body . '630300A'];
        yield 'CRC not last' => [self::sealed($body) . '6104ZIP1'];
        yield 'no marker' => [self::sealed(str_replace('000201', '', $body))];
        yield 'marker value 02' => [self::sealed(str_replace('000201', '000202', $body))];
        yield 'marker of the wrong length' => [self::sealed(str_replace('000201', '00030011', $body))];
        yield 'point of initiation 13' => [self::sealed(str_replace('000201', '000201010213', $body))];
        yield 'no merchant account' => [self::sealed(str_replace('0412CARDACCOUNT1', '', $body))];
        yield 'no category code' => [self::sealed(str_replace('52045411', '', $body))];
        yield 'category code of the wrong length' => [self::sealed(str_replace('52045411', '5203541', $body))];
        yield 'category code not numeric' => [self::sealed(str_replace('52045411', '5204541X', $body))];
        yield 'no currency' => [self::sealed(str_replace('5303986', '', $body))];
        yield 'currency of the wrong length' => [self::sealed(str_replace('5303986', '53049860', $body))];
        yield 'currency not numeric' => [self::sealed(str_replace('5303986', '5303A86', $body))];
        yield 'no country' => [self::sealed(str_replace('5802BR', '', $body))];
        yield 'country of the wrong length' => [self::sealed(str_replace('5802BR', '5803BRA', $body))];
        yield 'no merchant name' => [self::sealed(str_replace('5904Shop', '', $body))];
        yield 'no merchant city' => [self::sealed(str_replace('6004Rome', '', $body))];
        yield 'duplicate id under the root' => [self::sealed($body . '5904Shop')];
        yield 'template without a GUI' => [self::sealed($body . '26080104ABCD')];
        yield 'GUI longer than 32 characters' => [self::sealed($body . '26390033' . str_repeat('G', 33))];
        yield 'multi-byte merchant name' => [self::sealed(str_replace('5904Shop', '5904Café', $body))];
        yield 'control character in the merchant city' => [self::sealed(str_replace('6004Rome', "6004Ro\x01e", $body))];
        yield 'multi-byte card account' => [self::sealed(str_replace('0412CARDACCOUNT1', '0404Café', $body))];
        yield 'multi-byte GUI' => [self::sealed($body . '26060002é1')];
        yield 'multi-byte store label' => [self::sealed($body . '62060302é1')];
        yield 'letters in the tip indicator' => [self::sealed($body . '5502AB')];
        yield 'multi-byte amount' => [self::sealed($body . '5403٣٣٣')];
    }

    #[DataProvider('malformedPayloads')]
    public function testMalformedPayloadsAreRefused(string $payload): void
    {
        $this->assertRefused($payload);
    }

    public function testARepeatedSubIdInsideATemplateIsAccepted(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '26240006A.B.C.0103xyz0103abc'));
        $account = $qr->merchantAccounts()[1];

        self::assertSame('A.B.C.', $account->globallyUniqueIdentifier);
        self::assertCount(2, $account->subFields);
        self::assertSame('xyz', $account->subField('01'));
    }

    public function testTemplateSubIdsMayRepeat(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '62140503abc0503def'));

        self::assertEquals([new DataObject('05', 'abc'), new DataObject('05', 'def')], $qr->additionalData());
        self::assertSame('def', $qr->additionalDataField('05'));
        self::assertNull($qr->additionalDataField('06'));
    }

    public function testAdditionalDataIsAListEvenForTwoDigitSubIdsAboveNine(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '62140503abc1003xyz'));

        self::assertTrue(\array_is_list($qr->additionalData()));
        self::assertSame('10', $qr->additionalData()[1]->id);
        self::assertSame('xyz', $qr->additionalDataField('10'));
    }

    public function testWrittenAmount(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '540510.50'));

        self::assertSame('10.50', $qr->writtenAmount());
        self::assertNull(new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY))->writtenAmount());
    }

    public function testAmountAcceptsALeadingDecimalMark(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '5403.50'));

        self::assertSame('0.5', $qr->amount()?->value);
    }

    public function testMessagesNeverContainThePayload(): void
    {
        try {
            new MerchantPresentedQr(substr(self::BCB_PAYLOAD, 0, -4) . 'AD39');
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringNotContainsString('NOME', $exception->getMessage());
            self::assertStringNotContainsString('AD39', $exception->getMessage());
        }
    }

    public function testOneCharacterOverTheBoundIsRefusedForTheBound(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('MerchantPresentedQr must not exceed 7089 characters.');

        new MerchantPresentedQr($this->padded(7090));
    }

    public function testACrcObjectOfFiveCharactersIsRefusedEvenWhenItsLastFourMatch(): void
    {
        $body = self::MINIMAL_BODY . '6305Z';

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('MerchantPresentedQr must end with a correct CRC.');

        new MerchantPresentedQr($body . Crc16CcittFalse::compute($body));
    }

    public function testAGuiAfterAnotherSubFieldIsTheGui(): void
    {
        $account = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '26140103xyz0003A.B'))->merchantAccounts()[1];

        self::assertSame('A.B', $account->globallyUniqueIdentifier);
        self::assertSame('xyz', $account->subField('01'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function characterSetBoundaries(): iterable
    {
        $body = self::MINIMAL_BODY;

        yield 'multi-byte account 02' => [self::sealed($body . '0204Café')];
        yield 'multi-byte account 25' => [self::sealed($body . '2504Café')];
        yield 'multi-byte bill number 62-01' => [self::sealed($body . '62080104Café')];
        yield 'multi-byte purpose 62-11' => [self::sealed($body . '62081104Café')];
        yield 'trailing newline in the category code' => [self::sealed(str_replace('52045411', '5204541
', $body))];
        yield 'trailing newline in the merchant name' => [self::sealed(str_replace('5904Shop', '5904Sho
', $body))];
    }

    #[DataProvider('characterSetBoundaries')]
    public function testCharacterSetBoundariesAreRefused(string $payload): void
    {
        $this->assertRefused($payload);
    }

    public function testMultiByteTextIsAcceptedInAccount26And62SubId12(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '26130001G0104Café62081204Café'));

        self::assertSame('Café', $qr->additionalDataField('12'));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function amountsThatAreNoDecimal(): iterable
    {
        yield 'leading junk' => ['x1.5'];
        yield 'trailing junk' => ['1.5x'];
        yield 'trailing newline' => ["1.5\n"];
    }

    /**
     * @param non-empty-string $amount
     */
    #[DataProvider('amountsThatAreNoDecimal')]
    public function testAnAmountThatIsNoDecimalGivesNull(string $amount): void
    {
        self::assertNull(EmvcoMerchantPresented::amount([new DataObject('54', $amount)]));
    }

    public function testRequiredOnAnUnvalidatedListThrows(): void
    {
        $this->expectException(LogicException::class);

        EmvcoMerchantPresented::required([], '59');
    }

    public function testTryFromAndIsValid(): void
    {
        self::assertNull(MerchantPresentedQr::tryFrom('0002015903A'));
        self::assertTrue(MerchantPresentedQr::isValid(self::BCB_PAYLOAD));
    }

    /**
     * A valid payload of exactly $length characters: the minimal body plus filler objects.
     */
    private function padded(int $length): string
    {
        $ids = [...range(26, 51), ...range(80, 99), ...range(64, 79), ...array_diff(range(2, 25), [4])];
        $body = self::MINIMAL_BODY;
        $remaining = $length - \strlen($body) - 8;

        foreach ($ids as $id) {
            if ($remaining <= 0) {
                break;
            }

            $id = \sprintf('%02d', $id);
            $size = $remaining > 103 ? \min(99, $remaining - 9) : $remaining - 4;
            $value = $id >= '26' && $id <= '51' ? '0001G01' . \sprintf('%02d', $size - 9) . str_repeat('x', $size - 9) : str_repeat('A', $size);
            $body .= $id . \sprintf('%02d', $size) . $value;
            $remaining -= $size + 4;
        }

        self::assertSame(0, $remaining);

        return self::sealed($body);
    }

    private static function sealed(string $body): string
    {
        return $body . '6304' . Crc16CcittFalse::compute($body . '6304');
    }

    private function assertRefused(string $payload): void
    {
        try {
            new MerchantPresentedQr($payload);
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringContainsString('MerchantPresentedQr', $exception->getMessage());
            self::assertStringNotContainsString('Shop', $exception->getMessage());
            self::assertStringNotContainsString('Rome', $exception->getMessage());
        }
    }
}
