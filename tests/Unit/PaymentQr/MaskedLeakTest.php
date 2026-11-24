<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso8859;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\Internal\RussianAccountKey;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixBrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia\KhqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Epc\EpcQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\India\UpiPaymentUri;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan\JpqrCode;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia\DuitNowQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentPayload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\Gost56042Payload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\SbpPaymentLink;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBill;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayProxyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayQr;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use MSSTC4PHP\ValueObject\Financial\Reference\QrReference;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Puts a name, an e-mail address and a phone number into every slot of every payment profile that parse() accepts
 * them in, and checks that masked() shows none of them. A phone number in an amount or a numeric code slot (EMVCo 54
 * and 56, GOST `Sum`, `AddAmount`, `OKTMO`, `CounterVal`, SBP `sum`, UPI `am` and `mam`) is a valid amount or code and
 * stays visible by design, so those pairs are not probed.
 */
#[CoversClass(EmvcoMask::class)]
#[CoversClass(EpcQr::class)]
#[CoversClass(QrBill::class)]
#[CoversClass(Gost56042Payload::class)]
#[CoversClass(SbpPaymentLink::class)]
#[CoversClass(UpiPaymentUri::class)]
#[UsesClass(Mask::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(PixBrCode::class)]
#[UsesClass(PromptPayQr::class)]
#[UsesClass(PromptPayProxyType::class)]
#[UsesClass(DuitNowQr::class)]
#[UsesClass(KhqrCode::class)]
#[UsesClass(JpqrCode::class)]
#[UsesClass(ProfilePayload::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(InvalidPaymentQrException::class)]
#[UsesClass(PayloadBound::class)]
#[UsesClass(TextLines::class)]
#[UsesClass(QueryString::class)]
#[UsesClass(RussianAccountKey::class)]
#[UsesClass(Iso8859::class)]
#[UsesClass(Iso7064Mod97::class)]
#[UsesClass(Mod10Recursive::class)]
#[UsesClass(QrReference::class)]
#[UsesClass(CreditorReference::class)]
final class MaskedLeakTest extends TestCase
{
    use BuildsPayloads;

    private const string NAME = 'Anna Ivanova';

    private const string EMAIL = 'anna@x.com';

    private const string PHONE = '79101234567';

    private const array PERSONAL = [self::NAME, self::EMAIL, self::PHONE];

    /** EMVCo slots: a root ID, or a template ID and a sub-ID. */
    private const array EMVCO_SLOTS = [
        ['54'], ['55'], ['56'], ['57'], ['58'], ['59'], ['60'], ['61'], ['65'], ['72'], ['79'], ['80'], ['99'],
        ['62', '01'], ['62', '02'], ['62', '03'], ['62', '04'], ['62', '05'], ['62', '06'], ['62', '07'], ['62', '08'],
        ['62', '09'], ['62', '10'], ['62', '11'], ['62', '12'], ['62', '49'], ['62', '50'], ['62', '99'],
        ['64', '00'], ['64', '01'], ['64', '02'], ['64', '03'], ['64', '99'],
    ];

    /**
     * @return iterable<string, array{class-string<AbstractStringValueObject&PaymentPayload>, string, positive-int}>
     */
    public static function emvcoProfiles(): iterable
    {
        $pix = self::tlv('26', self::tlv('00', 'br.gov.bcb.pix') . self::tlv('01', '123e4567-e12b-12d1-a456-426655440000'));
        $jpqrAccount = self::tlv('26', self::tlv('00', 'jp.or.paymentsjapan') . self::tlv('01', '1234567890123') . self::tlv('02', '0001') . self::tlv('03', '000002') . self::tlv('04', '000003'));

        yield 'MerchantPresentedQr' => [MerchantPresentedQr::class, self::sealed(self::tlv('00', '01'), $pix, self::tlv('52', '0000'), self::tlv('53', '986'), self::tlv('58', 'BR'), self::tlv('59', 'Shop'), self::tlv('60', 'City')), 92];
        yield 'PixBrCode' => [PixBrCode::class, self::sealed(self::tlv('00', '01'), $pix, self::tlv('52', '0000'), self::tlv('53', '986'), self::tlv('58', 'BR'), self::tlv('59', 'Shop'), self::tlv('60', 'City'), self::tlv('62', self::tlv('05', '***'))), 92];
        yield 'PromptPayQr' => [PromptPayQr::class, self::sealed(self::tlv('00', '01'), self::tlv('01', '11'), self::tlv('29', self::tlv('00', 'A000000677010111') . self::tlv('01', '0066812345678')), self::tlv('53', '764'), self::tlv('58', 'TH'), self::tlv('59', 'Shop')), 86];
        yield 'DuitNowQr' => [DuitNowQr::class, '00020201021126410014A000000615000101065016640209123456789520400005303458540510.005802MY5909AUSERNAME6005BANGI63043A23', 69];
        yield 'KhqrCode' => [KhqrCode::class, '00020101021129200016test_bakong@devb5204599953031165802KH5917Lucky Supermarket6010Phnom Penh9917001316339210247786304102F', 71];
        yield 'JpqrCode' => [JpqrCode::class, self::sealed(self::tlv('00', '01'), self::tlv('01', '11'), $jpqrAccount, self::tlv('52', '5812'), self::tlv('53', '392'), self::tlv('58', 'JP'), self::tlv('59', 'CAFE'), self::tlv('60', 'MINATO'), self::tlv('61', '1050001'), self::tlv('64', self::tlv('00', 'JA') . self::tlv('01', 'CAFE'))), 92];
    }

    /**
     * @param class-string<AbstractStringValueObject&PaymentPayload> $class
     * @param positive-int $expected the probes parse() accepts, pinned so that the test cannot pass empty
     */
    #[DataProvider('emvcoProfiles')]
    public function testEmvcoMaskedShowsNoPersonalData(string $class, string $base, int $expected): void
    {
        $accepted = 0;
        foreach (self::EMVCO_SLOTS as $slot) {
            foreach (self::PERSONAL as $personal) {
                if ($personal === self::PHONE && \in_array($slot, [['54'], ['56']], true)) {
                    continue;
                }

                $payload = $this->withEmvcoSlot($base, $slot, $personal);
                if ($payload !== null && $class::isValid($payload)) {
                    $this->assertNoLeak($class::from($payload)->masked(), \implode('-', $slot));
                    $accepted++;
                }
            }
        }

        self::assertSame($expected, $accepted);
    }

    public function testEpcMaskedShowsNoPersonalData(): void
    {
        $base = ['BCD', '002', '1', 'SCT', '', 'Franz', 'DE71110220330123456789', 'EUR12.3', '', '', '', 'Thanks'];
        $accepted = 0;
        foreach ([4, 5, 8, 9, 10, 11] as $index) {
            foreach (self::PERSONAL as $personal) {
                $lines = $base;
                $lines[$index] = $personal;
                if (EpcQr::isValid(\implode("\n", $lines))) {
                    $this->assertNoLeak(EpcQr::from(\implode("\n", $lines))->masked(), 'EPC line ' . $index);
                    $accepted++;
                }
            }
        }

        self::assertSame(12, $accepted);
    }

    public function testQrBillMaskedShowsNoPersonalData(): void
    {
        $base = [
            'SPC', '0200', '1', 'CH5204835012345671000',
            'S', 'Muster Stiftung', 'Box', '1', '3001', 'Bern', 'CH',
            '', '', '', '', '', '', '',
            '', 'CHF',
            'S', 'Max', 'Weg', '2', '8000', 'Zurich', 'CH',
            'NON', '', '', 'EPD', '', '', '',
        ];
        $accepted = 0;
        foreach ([5, 6, 7, 8, 9, 21, 22, 23, 24, 25, 29, 31, 32, 33] as $index) {
            foreach (self::PERSONAL as $personal) {
                $values = $index === 31 ? [$personal, '//' . $personal, '//S1/10/' . $personal, '//S1/20/' . $personal, '//S1/32/' . $personal, '//S1/40/0:' . $personal] : [$personal];
                foreach ($values as $value) {
                    $lines = $base;
                    $lines[$index] = $value;
                    if (QrBill::isValid(\implode("\n", $lines))) {
                        $this->assertNoLeak(QrBill::from(\implode("\n", $lines))->masked(), 'QR-bill line ' . $index);
                        $accepted++;
                    }
                }
            }
        }

        self::assertSame(57, $accepted);
    }

    public function testGostMaskedShowsNoPersonalData(): void
    {
        $mandatory = 'Name=Shop|PersonalAcc=40702810138250123017|BankName=Bank|BIC=044525225|CorrespAcc=30101810400000000225';
        $aliases = [
            'Name', 'BankName', 'CorrespAcc', 'Sum', 'Purpose', 'PayeeINN', 'PayerINN', 'DrawerStatus', 'KPP', 'CBC', 'OKTMO',
            'PaytReason', 'TaxPeriod', 'DocNo', 'DocDate', 'TaxPaytKind', 'LastName', 'FirstName', 'MiddleName',
            'PayerAddress', 'PersonalAccount', 'DocIdx', 'PensAcc', 'Contract', 'PersAcc', 'Flat', 'Phone', 'PayerIdType',
            'PayerIdNum', 'ChildFio', 'BirthDate', 'PaymTerm', 'PaymPeriod', 'Category', 'ServiceName', 'CounterId',
            'CounterVal', 'QuittId', 'QuittDate', 'InstNum', 'ClassNum', 'SpecFio', 'AddAmount', 'RuleId', 'ExecId',
            'RegType', 'UIN', 'TechCode', 'Comment', self::PHONE,
        ];
        $accepted = 0;
        foreach ($aliases as $alias) {
            foreach (self::PERSONAL as $personal) {
                if ($personal === self::PHONE && \in_array($alias, ['Sum', 'AddAmount', 'OKTMO', 'CounterVal'], true)) {
                    continue;
                }

                $payload = 'ST00012|' . $mandatory . '|' . $alias . '=' . $personal;
                if (Gost56042Payload::isValid($payload)) {
                    $this->assertNoLeak(Gost56042Payload::from($payload)->masked(), 'GOST ' . $alias);
                    $accepted++;
                }
            }
        }

        self::assertSame(144, $accepted);
    }

    public function testSbpMaskedShowsNoPersonalData(): void
    {
        $accepted = 0;
        foreach (self::PERSONAL as $personal) {
            $queries = [$personal, $personal . '=1', 'note=' . $personal];
            foreach (['type', 'bank', 'sum', 'cur', 'crc'] as $name) {
                if ($personal !== self::PHONE || $name !== 'sum') {
                    $queries[] = $name . '=' . $personal;
                }
            }

            foreach (['https://qr.nspk.ru/', 'https://sub.nspk.ru/', 'https://b2b.cbrpay.ru/'] as $host) {
                foreach ([$host . $personal, ...\array_map(static fn (string $query): string => $host . 'AD10006M?' . $query, $queries)] as $link) {
                    if (SbpPaymentLink::isValid($link)) {
                        $this->assertNoLeak(SbpPaymentLink::from($link)->masked(), $link);
                        $accepted++;
                    }
                }
            }
        }

        // 16 per host: 1.2.0 added the hosts sub.nspk.ru and b2b.cbrpay.ru.
        self::assertSame(48, $accepted);
    }

    public function testUpiMaskedShowsNoPersonalData(): void
    {
        $accepted = 0;
        foreach (self::PERSONAL as $personal) {
            $queries = ['pa=' . $personal . '&pn=A', 'pa=a@b&pn=' . $personal, 'pa=a@b&pn=A&' . $personal, 'pa=a@b&pn=A&' . $personal . '=1'];
            foreach (['tr', 'tn', 'tid', 'url', 'sign', 'am', 'mam', 'cu', 'mc', 'mode', 'purpose', 'qrMedium', 'orgid', 'email', 'PN', 'TN'] as $name) {
                if ($personal !== self::PHONE || !\in_array($name, ['am', 'mam'], true)) {
                    $queries[] = 'pa=a@b&pn=A&' . $name . '=' . $personal;
                }
            }

            foreach ($queries as $query) {
                if (UpiPaymentUri::isValid('upi://pay?' . $query)) {
                    $this->assertNoLeak(UpiPaymentUri::from('upi://pay?' . $query)->masked(), $query);
                    $accepted++;
                }
            }
        }

        self::assertSame(38, $accepted);
    }

    private function assertNoLeak(string $masked, string $slot): void
    {
        foreach ([...self::PERSONAL, 'Ivanova'] as $personal) {
            self::assertStringNotContainsString($personal, $masked, $slot);
        }
    }

    /**
     * @param array{0: string, 1?: string} $slot
     */
    private function withEmvcoSlot(string $base, array $slot, string $value): ?string
    {
        $objects = [];
        foreach (EmvcoTlv::parse(\substr($base, 0, -8)) as $object) {
            $objects[$object->id] = $object->value;
        }

        if (isset($slot[1])) {
            $subFields = [];
            foreach (isset($objects[$slot[0]]) ? EmvcoTlv::parse($objects[$slot[0]]) : [] as $subField) {
                $subFields[$subField->id] = self::tlv($subField->id, $subField->value);
            }

            $subFields[$slot[1]] = self::tlv($slot[1], $value);
            \ksort($subFields, \SORT_STRING);
            $value = \implode('', $subFields);
            if (\strlen($value) > 99) {
                return null;
            }
        }

        $objects[$slot[0]] = $value;
        \ksort($objects, \SORT_STRING);

        return self::sealed(...\array_map(self::tlv(...), \array_map(strval(...), \array_keys($objects)), $objects));
    }
}
