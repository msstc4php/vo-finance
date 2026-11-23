<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Brazil;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixKeyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\BuildsPayloads;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PixBrCode::class)]
#[UsesClass(ProfilePayload::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoMask::class)]
#[UsesClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(InvalidPaymentQrException::class)]
#[UsesClass(AmbiguousPaymentQrFieldException::class)]
#[UsesClass(PixKeyType::class)]
#[UsesClass(PayloadBound::class)]
final class PixBrCodeTest extends TestCase
{
    use BuildsPayloads;

    // BCB, Manual de Padroes para Iniciacao do Pix 2.10.0, section 2.6.3: the static QR Code.
    private const string BCB_STATIC = '00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-4266554400005204000053039865802BR5913Fulano de Tal6008BRASILIA62070503***63041D3D';

    // BCB, Manual de Padroes para Iniciacao do Pix 2.10.0, section 2.7: the dynamic QR Code.
    private const string BCB_DYNAMIC = '00020101021226700014br.gov.bcb.pix2548pix.example.com/8b3da2f39a4140d1a91abd93113bd4415204000053039865802BR5913Fulano de Tal6008BRASILIA62070503***630464E4';

    // BCB, Manual do BR Code 2.0.1, section 2.2: a Pix account next to another arrangement and a card account.
    private const string BCB_BR_CODE = '00020104141234567890123426580014BR.GOV.BCB.PIX0136123e4567-e12b-12d1-a456-426655440000'
        . '27300012BR.COM.OUTRO011001234567895204000053039865406123.455802BR5917NOME DO RECEBEDOR6008BRASILIA'
        . '61087007490062190515RP12345678-201980390012BR.COM.OUTRO01190123.ABCD.3456.WXYZ6304AD38';

    public function testTheOfficialStaticQrCodeParses(): void
    {
        $pix = new PixBrCode(self::BCB_STATIC);

        self::assertSame(self::BCB_STATIC, $pix->value);
        self::assertSame('123e4567-e12b-12d1-a456-426655440000', $pix->pixKey());
        self::assertNull($pix->paymentUrl());
        self::assertNull($pix->recurrenceUrl());
        self::assertNull($pix->additionalInfo());
        self::assertNull($pix->withdrawalProvider());
        self::assertNull($pix->txid());
        self::assertNull($pix->pointOfInitiation());
        self::assertNull($pix->amount());
        self::assertSame('Fulano de Tal', $pix->merchantName());
        self::assertSame('BRASILIA', $pix->merchantCity());
        self::assertSame('0000', $pix->merchantCategoryCode());
        self::assertSame('986', $pix->currency()?->numeric());
        self::assertSame('BR', $pix->countryCode()?->value);
    }

    public function testTheKeyTypeIsDecodedFromTheDictFormats(): void
    {
        self::assertSame(PixKeyType::Evp, new PixBrCode(self::BCB_STATIC)->pixKeyType());
        self::assertSame(PixKeyType::Email, new PixBrCode(self::sealed(...$this->baseline('pix@bcb.gov.br')))->pixKeyType());
        self::assertSame(PixKeyType::Phone, new PixBrCode(self::sealed(...$this->baseline('+5510998765432')))->pixKeyType());
        self::assertSame(PixKeyType::Cpf, new PixBrCode(self::sealed(...$this->baseline('12345678901')))->pixKeyType());
        self::assertSame(PixKeyType::Cnpj, new PixBrCode(self::sealed(...$this->baseline('12345678901234')))->pixKeyType());
    }

    public function testAKeyOfNoDictFormatIsAcceptedWithoutAType(): void
    {
        $pix = new PixBrCode(self::sealed(...$this->baseline('Fulano@Example.com')));

        self::assertSame('Fulano@Example.com', $pix->pixKey());
        self::assertNull($pix->pixKeyType());
        self::assertNull(new PixBrCode(self::BCB_DYNAMIC)->pixKeyType());
    }

    public function testTheKeyTypeIsNeverPickedSilently(): void
    {
        $pix = new PixBrCode(self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'one@example.com')),
            self::tlv('27', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', '12345678901')),
            ...$this->tail(),
        ));

        $this->expectException(AmbiguousPaymentQrFieldException::class);
        $pix->pixKeyType();
    }

    public function testTheOfficialDynamicQrCodeParses(): void
    {
        $pix = new PixBrCode(self::BCB_DYNAMIC);

        self::assertNull($pix->pixKey());
        self::assertSame('pix.example.com/8b3da2f39a4140d1a91abd93113bd441', $pix->paymentUrl());
        self::assertSame(PointOfInitiation::Dynamic, $pix->pointOfInitiation());
    }

    public function testTheBrCodeManualPayloadIsAPixPayloadWithAnAmountAndATxid(): void
    {
        $pix = new PixBrCode(self::BCB_BR_CODE);

        self::assertSame('123e4567-e12b-12d1-a456-426655440000', $pix->pixKey());
        self::assertSame('RP12345678-2019', $pix->txid());
        self::assertSame('123.45', $pix->amount()?->value);
        self::assertSame('NOME DO RECEBEDOR', $pix->merchantName());
        self::assertSame(CurrencyCode::fromNumeric('986'), $pix->currency());
        self::assertSame(CountryCode::from('BR'), $pix->countryCode());
    }

    public function testTheGuiIsCaseInsensitive(): void
    {
        $lower = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'BR.GOV.BCB.PIX') . self::tlv('01', 'fulano@example.com')),
            ...$this->tail(),
        );

        self::assertSame('fulano@example.com', new PixBrCode($lower)->pixKey());
    }

    public function testTheLongestKeyAndTheLongestAdditionalInfoThatFitTheTemplate(): void
    {
        $longKey = self::sealed(self::tlv('00', '01'), self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', \str_repeat('k', 77))), ...$this->tail());
        $longInfo = self::sealed(self::tlv('00', '01'), self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'k') . self::tlv('02', \str_repeat('i', 72))), ...$this->tail());

        self::assertSame(77, \strlen((string) new PixBrCode($longKey)->pixKey()));
        self::assertSame(72, \strlen((string) new PixBrCode($longInfo)->additionalInfo()));
    }

    public function testTheTxidIsNotRestrictedToLettersAndDigits(): void
    {
        $payload = self::sealed(...$this->baseline());

        self::assertSame('RP12345678-2019', new PixBrCode(self::BCB_BR_CODE)->txid());
        self::assertNull(new PixBrCode($payload)->txid());
    }

    public function testAKeyAndAUrlTogetherAreBothKept(): void
    {
        $payload = self::sealed(self::tlv('00', '01'), self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'a@b.co') . self::tlv('25', 'pix.example.com/abc')), ...$this->tail());

        $pix = new PixBrCode($payload);

        self::assertSame('a@b.co', $pix->pixKey());
        self::assertSame('pix.example.com/abc', $pix->paymentUrl());
    }

    public function testSeveralPixTemplatesAreAcceptedAndNeverPickedSilently(): void
    {
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'one@example.com')),
            self::tlv('27', self::tlv('00', 'BR.GOV.BCB.PIX') . self::tlv('01', 'two@example.com') . self::tlv('02', 'info')),
            ...$this->tail(),
        );

        $pix = new PixBrCode($payload);

        self::assertSame(['one@example.com', 'two@example.com'], $pix->pixKeys());
        self::assertSame([], $pix->paymentUrls());

        try {
            $pix->pixKey();
            self::fail('Expected an ambiguity.');
        } catch (AmbiguousPaymentQrFieldException $e) {
            self::assertSame('PixBrCode cannot give a single value for the Pix key: several distinct values.', $e->getMessage());
        }

        $this->expectException(AmbiguousPaymentQrFieldException::class);
        $pix->additionalInfo();
    }

    public function testTheWithdrawalProviderMustBeInEveryTemplateOrNone(): void
    {
        $with = self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'one@example.com') . self::tlv('03', '12345678');
        $without = self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'one@example.com');

        $both = new PixBrCode(self::sealed(self::tlv('00', '01'), self::tlv('26', $with), self::tlv('27', $with), ...$this->tail()));
        $mixed = new PixBrCode(self::sealed(self::tlv('00', '01'), self::tlv('26', $with), self::tlv('27', $without), ...$this->tail()));

        self::assertSame('12345678', $both->withdrawalProvider());
        $this->expectException(AmbiguousPaymentQrFieldException::class);
        $this->expectExceptionMessage('PixBrCode cannot give a single value for the withdrawal provider: present in only some Pix templates.');
        $mixed->withdrawalProvider();
    }

    public function testTheSameKeyInTwoTemplatesIsOneValue(): void
    {
        $template = self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'same@example.com');
        $pix = new PixBrCode(self::sealed(self::tlv('00', '01'), self::tlv('26', $template), self::tlv('27', $template), ...$this->tail()));

        self::assertSame('same@example.com', $pix->pixKey());
        self::assertSame(['same@example.com'], $pix->pixKeys());
    }

    public function testTheListAccessorsGiveDistinctValuesInPayloadOrder(): void
    {
        $gui = self::tlv('00', 'br.gov.bcb.pix');
        $url = self::tlv('25', 'pix.example.com/a');
        $pix = new PixBrCode(self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', $gui . self::tlv('01', 'b@example.com') . $url),
            self::tlv('27', $gui . self::tlv('01', 'b@example.com') . $url),
            self::tlv('28', $gui . self::tlv('01', 'a@example.com')),
            ...$this->tail(),
            ...[self::tlv('80', $gui . self::tlv('25', 'r.example.com/1')), self::tlv('81', $gui . self::tlv('25', 'r.example.com/1'))],
        ));

        self::assertSame(['b@example.com', 'a@example.com'], $pix->pixKeys());
        self::assertSame(['pix.example.com/a'], $pix->paymentUrls());
        self::assertSame('pix.example.com/a', $pix->paymentUrl());
        self::assertSame(['r.example.com/1'], $pix->recurrenceUrls());
        self::assertSame('r.example.com/1', $pix->recurrenceUrl());
    }

    public function testSeveralRecurrenceUrlsAreListed(): void
    {
        $gui = self::tlv('00', 'br.gov.bcb.pix');
        $pix = new PixBrCode(self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', $gui),
            ...$this->tail(),
            ...[self::tlv('80', $gui . self::tlv('25', 'a.example.com/rec/1')), self::tlv('81', $gui . self::tlv('25', 'a.example.com/rec/2'))],
        ));

        self::assertSame(['a.example.com/rec/1', 'a.example.com/rec/2'], $pix->recurrenceUrls());
        $this->expectException(AmbiguousPaymentQrFieldException::class);
        $pix->recurrenceUrl();
    }

    public function testAnUnreservedTemplateOfAnotherArrangementWithoutSubId25IsIgnored(): void
    {
        $payload = self::sealed(self::tlv('00', '01'), self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'k@b.co')), ...$this->tail(), ...[self::tlv('80', self::tlv('00', 'br.com.outro') . self::tlv('01', 'x'))]);

        self::assertSame([], new PixBrCode($payload)->recurrenceUrls());
    }

    public function testAnUnreservedTemplateThatIsNotDataObjectsIsSkipped(): void
    {
        $payload = self::sealed(self::tlv('00', '01'), self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'k@b.co')), ...$this->tail(), ...[self::tlv('80', 'free text, not TLV')]);

        self::assertSame('k@b.co', new PixBrCode($payload)->pixKey());
        self::assertSame([], new PixBrCode($payload)->recurrenceUrls());
    }

    public function testTheLongestNameCityTxidAndWithdrawalProviderAreAccepted(): void
    {
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', 'k@b.co') . self::tlv('03', '12345678')),
            self::tlv('52', '0000'),
            self::tlv('53', '986'),
            self::tlv('58', 'BR'),
            self::tlv('59', \str_repeat('n', 25)),
            self::tlv('60', \str_repeat('c', 15)),
            self::tlv('62', self::tlv('05', \str_repeat('t', 25))),
        );

        $pix = new PixBrCode($payload);

        self::assertSame(25, \strlen($pix->merchantName()));
        self::assertSame(15, \strlen((string) $pix->merchantCity()));
        self::assertSame(25, \strlen((string) $pix->txid()));
        self::assertSame('12345678', $pix->withdrawalProvider());
    }

    public function testTheOptionalTemplateFields(): void
    {
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', '+5561912345678') . self::tlv('02', 'Pagamento do pedido') . self::tlv('03', '12345678')),
            ...$this->tail('RP12345678'),
        );

        $pix = new PixBrCode($payload);

        self::assertSame('+5561912345678', $pix->pixKey());
        self::assertSame('Pagamento do pedido', $pix->additionalInfo());
        self::assertSame('12345678', $pix->withdrawalProvider());
        self::assertSame('RP12345678', $pix->txid());
    }

    public function testACompositeQrCodeCarriesTheRecurrenceUrlInTheUnreservedRange(): void
    {
        $payload = self::sealed(
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix')),
            ...$this->tail(),
            ...[self::tlv('80', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('25', 'pix.example.com/rec/2353c790eefb11ea'))],
        );

        $pix = new PixBrCode($payload);

        self::assertSame('pix.example.com/rec/2353c790eefb11ea', $pix->recurrenceUrl());
        self::assertNull($pix->pixKey());
        self::assertNull($pix->paymentUrl());
    }

    public function testMaskingHidesTheKeyAndTheCrc(): void
    {
        $masked = new PixBrCode(self::BCB_STATIC)->masked();

        self::assertStringNotContainsString('123e4567', $masked);
        self::assertStringNotContainsString('1D3D', $masked);
        self::assertStringContainsString('2658' . \str_repeat('*', 58), $masked);
        self::assertStringEndsWith('6304****', $masked);
        self::assertStringContainsString('Fulano de Tal', $masked);
    }

    public function testMaskingHidesThePaymentUrlAndTheRecurrenceUrl(): void
    {
        self::assertStringNotContainsString('pix.example.com', new PixBrCode(self::BCB_DYNAMIC)->masked());
        self::assertStringNotContainsString('pix.example.com/8b3da2f39a4140d1a91abd93113bd441', new PixBrCode(self::BCB_DYNAMIC)->masked());
    }

    /**
     * @param list<string> $objects
     */
    #[DataProvider('refusedPayloads')]
    public function testRefusesPayloadsThatBreakTheSpecification(array $objects): void
    {
        $payload = self::sealed(...$objects);

        self::assertFalse(PixBrCode::isValid($payload));
        $this->expectException(InvalidPaymentQrException::class);
        new PixBrCode($payload);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function refusedPayloads(): iterable
    {
        $gui = self::tlv('00', 'br.gov.bcb.pix');
        $key = self::tlv('01', 'fulano@example.com');
        $pix = self::tlv('26', $gui . $key);
        $marker = self::tlv('00', '01');
        $category = self::tlv('52', '0000');
        $currency = self::tlv('53', '986');
        $country = self::tlv('58', 'BR');
        $name = self::tlv('59', 'Fulano');
        $city = self::tlv('60', 'BRASILIA');
        $txid = self::tlv('62', self::tlv('05', '***'));

        yield 'no Pix template' => [[$marker, self::tlv('26', self::tlv('00', 'br.com.outro') . $key), $category, $currency, $country, $name, $city, $txid]];
        yield 'GUI of another arrangement in range 80' => [[$marker, self::tlv('26', self::tlv('00', 'br.com.outro') . $key), $category, $currency, $country, $name, $city, $txid, self::tlv('80', $gui . $key)]];
        yield 'neither key nor URL' => [[$marker, self::tlv('26', $gui), $category, $currency, $country, $name, $city, $txid]];
        yield 'recurrence URL without a Pix template in 26 to 51' => [[$marker, self::tlv('26', self::tlv('00', 'br.com.outro') . self::tlv('01', 'abc')), $category, $currency, $country, $name, $city, $txid, self::tlv('80', $gui . self::tlv('25', 'pix.example.com/rec/abc'))]];
        yield 'withdrawal provider of 7 digits' => [[$marker, self::tlv('26', $gui . $key . self::tlv('03', '1234567')), $category, $currency, $country, $name, $city, $txid]];
        yield 'URL with a protocol prefix' => [[$marker, self::tlv('26', $gui . self::tlv('25', 'https://pix.example.com/abc')), $category, $currency, $country, $name, $city, $txid]];
        yield 'Pix template in range 80 without the URL' => [[$marker, $pix, $category, $currency, $country, $name, $city, $txid, self::tlv('80', $gui . self::tlv('01', 'x'))]];
        yield 'currency other than 986' => [[$marker, $pix, $category, self::tlv('53', '840'), $country, $name, $city, $txid]];
        yield 'country other than BR' => [[$marker, $pix, $category, $currency, self::tlv('58', 'PT'), $name, $city, $txid]];
        yield 'merchant name of 26 characters' => [[$marker, $pix, $category, $currency, $country, self::tlv('59', \str_repeat('n', 26)), $city, $txid]];
        yield 'merchant city of 16 characters' => [[$marker, $pix, $category, $currency, $country, $name, self::tlv('60', \str_repeat('c', 16)), $txid]];
        yield 'no transaction identifier template' => [[$marker, $pix, $category, $currency, $country, $name, $city]];
        yield 'template 62 without the reference label' => [[$marker, $pix, $category, $currency, $country, $name, $city, self::tlv('62', self::tlv('01', 'bill'))]];
        yield 'txid of 26 characters' => [[$marker, $pix, $category, $currency, $country, $name, $city, self::tlv('62', self::tlv('05', \str_repeat('t', 26)))]];
    }

    public function testTheBaselineCaseIsAccepted(): void
    {
        $payload = self::sealed(...$this->baseline());

        self::assertTrue(PixBrCode::isValid($payload));
    }

    public function testRefusesAWrongCrcAndAGenericPayloadWithoutPix(): void
    {
        self::assertFalse(PixBrCode::isValid(\substr(self::BCB_STATIC, 0, -4) . '0000'));
        self::assertFalse(PixBrCode::isValid(''));
        self::assertFalse(PixBrCode::isValid('not a payload'));
    }

    public function testAMessageNeverContainsThePayload(): void
    {
        $payload = self::sealed(...$this->baseline(key: 'secret-key@example.com', category: self::tlv('52', 'abcd')));

        try {
            new PixBrCode($payload);
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertStringNotContainsString('secret-key', $e->getMessage());
            self::assertStringNotContainsString($payload, $e->getMessage());
        }
    }

    public function testEqualityAndSerialisation(): void
    {
        $pix = new PixBrCode(self::BCB_STATIC);

        self::assertTrue($pix->equals(PixBrCode::from(self::BCB_STATIC)));
        self::assertFalse($pix->equals(new PixBrCode(self::BCB_DYNAMIC)));
        self::assertSame(self::BCB_STATIC, (string) $pix);
        self::assertSame('"' . self::BCB_STATIC . '"', \json_encode($pix));
    }

    /**
     * @return list<string>
     */
    private function tail(string $txid = '***'): array
    {
        return [
            self::tlv('52', '0000'),
            self::tlv('53', '986'),
            self::tlv('58', 'BR'),
            self::tlv('59', 'Fulano de Tal'),
            self::tlv('60', 'BRASILIA'),
            self::tlv('62', self::tlv('05', $txid)),
        ];
    }

    /**
     * @return list<string>
     */
    private function baseline(string $key = 'fulano@example.com', ?string $category = null): array
    {
        return [
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', $key)),
            $category ?? self::tlv('52', '0000'),
            self::tlv('53', '986'),
            self::tlv('58', 'BR'),
            self::tlv('59', 'Fulano'),
            self::tlv('60', 'BRASILIA'),
            self::tlv('62', self::tlv('05', '***')),
        ];
    }
}
