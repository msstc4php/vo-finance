<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
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
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
final class MerchantPresentedQrTest extends TestCase
{
    // Banco Central do Brasil, Manual do BR Code 2.0.1, section 2.2: the complete dynamic payload (270 characters).
    public const string BCB_PAYLOAD = '00020104141234567890123426580014BR.GOV.BCB.PIX0136123e4567-e12b-12d1-a456-426655440000'
        . '27300012BR.COM.OUTRO011001234567895204000053039865406123.455802BR5917NOME DO RECEBEDOR6008BRASILIA'
        . '61087007490062190515RP12345678-201980390012BR.COM.OUTRO01190123.ABCD.3456.WXYZ6304AD38';

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
        self::assertSame(['05' => 'RP12345678-2019'], $qr->additionalData());
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

    public function testLengthsAreCountedInCharactersNotBytes(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(str_replace('5904Shop', '5904Café', self::MINIMAL_BODY)));

        self::assertSame('Café', $qr->merchantName());
    }

    public function testTheGuiMayBeAnywhereInItsTemplate(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '26160104AAAA0004BBBB'));

        self::assertSame('BBBB', $qr->merchantAccounts()[1]->globallyUniqueIdentifier);
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

    public function testATemplateWithAGuiOfThirtyThreeCharactersIsRefusedButThirtyTwoIsAccepted(): void
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

    public function testAmountKeepsItsValue(): void
    {
        $qr = new MerchantPresentedQr(self::sealed(self::MINIMAL_BODY . '540510.50'));

        self::assertSame('10.50', $qr->get('54'));
        self::assertInstanceOf(Decimal::class, $qr->amount());
        self::assertTrue($qr->amount()->equals(new Decimal('10.50')));
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

        self::assertSame(['05' => 'INV-001', '07' => 'TERM-0001'], $qr->additionalData());
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

    public function testMaskedCountsCharacters(): void
    {
        $body = str_replace('0412CARDACCOUNT1', '0406Café12', self::MINIMAL_BODY);
        $masked = new MerchantPresentedQr(self::sealed($body))->masked();

        self::assertStringContainsString('0406******', $masked);
    }

    public function testJsonSerializesTheFullPayload(): void
    {
        self::assertSame(json_encode(self::BCB_PAYLOAD), json_encode(new MerchantPresentedQr(self::BCB_PAYLOAD)));
    }

    public function testAPayloadOfTheMaximumLengthIsAccepted(): void
    {
        self::assertSame(512, \strlen(new MerchantPresentedQr($this->padded(512))->value));
    }

    public function testALongerPayloadIsRefused(): void
    {
        $this->assertRefused($this->padded(513));
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
        yield 'marker not first' => [self::sealed('0412CARDACCOUNT10002015204541153039865802BR5904Shop6004Rome')];
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
        yield 'duplicate id inside a template' => [self::sealed($body . '26220006A.B.C.0103xyz0103abc')];
        yield 'duplicate id inside additional data' => [self::sealed($body . '62140503abc0503def')];
    }

    #[DataProvider('malformedPayloads')]
    public function testMalformedPayloadsAreRefused(string $payload): void
    {
        $this->assertRefused($payload);
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

    public function testTryFromAndIsValid(): void
    {
        self::assertNull(MerchantPresentedQr::tryFrom('0002015903A'));
        self::assertTrue(MerchantPresentedQr::isValid(self::BCB_PAYLOAD));
    }

    private static function sealed(string $body): string
    {
        return $body . '6304' . Crc16CcittFalse::compute($body . '6304');
    }

    /**
     * A valid payload of exactly $length characters: the minimal body plus filler objects 80, 81 and so on.
     */
    private function padded(int $length): string
    {
        $body = self::MINIMAL_BODY;
        $remaining = $length - \strlen($body) - 8;

        for ($id = 80; $remaining > 0; $id++) {
            $size = $remaining > 103 ? \min(99, $remaining - 9) : $remaining - 4;
            $body .= $id . \sprintf('%02d', $size) . str_repeat('A', $size);
            $remaining -= $size + 4;
        }

        return self::sealed($body);
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
