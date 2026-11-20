<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQrBuilder;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MerchantPresentedQrBuilder::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(PointOfInitiation::class)]
#[UsesClass(InvalidPaymentQrException::class)]
#[UsesClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(PayloadBound::class)]
final class MerchantPresentedQrBuilderTest extends TestCase
{
    public function testReproducesTheBcbPayloadByteExact(): void
    {
        $qr = new MerchantPresentedQrBuilder()
            ->with('04', '12345678901234')
            ->withMerchantAccount('26', 'BR.GOV.BCB.PIX', [new DataObject('01', '123e4567-e12b-12d1-a456-426655440000')])
            ->withMerchantAccount('27', 'BR.COM.OUTRO', [new DataObject('01', '0123456789')])
            ->with('52', '0000')
            ->with('53', '986')
            ->with('54', '123.45')
            ->with('58', 'BR')
            ->with('59', 'NOME DO RECEBEDOR')
            ->with('60', 'BRASILIA')
            ->with('61', '70074900')
            ->withAdditionalData([new DataObject('05', 'RP12345678-2019')])
            ->with('80', '0012BR.COM.OUTRO01190123.ABCD.3456.WXYZ')
            ->build()
        ;

        self::assertSame(MerchantPresentedQrTest::BCB_PAYLOAD, $qr->value);
    }

    public function testReproducesTheEmvcoAnnexB7PayloadByteExact(): void
    {
        $qr = new MerchantPresentedQrBuilder()
            ->withPointOfInitiation(PointOfInitiation::Dynamic)
            ->withMerchantAccount('29', 'D15600000000', [new DataObject('05', 'A93FO3230Q')])
            ->withMerchantAccount('31', 'D15600000001', [new DataObject('03', '12345678')])
            ->with('52', '4111')
            ->with('58', 'CN')
            ->with('59', 'BEST TRANSPORT')
            ->with('60', 'BEIJING')
            ->with('64', '0002ZH0104最佳运输0202北京')
            ->with('54', '23.72')
            ->with('53', '156')
            ->with('55', '01')
            ->withAdditionalData([
                new DataObject('03', '1234'),
                new DataObject('06', '***'),
                new DataObject('07', 'A6008667'),
                new DataObject('09', 'ME'),
            ])
            ->with('91', '0016A011223344998877070812345678')
            ->build()
        ;

        self::assertSame(MerchantPresentedQrTest::EMVCO_ANNEX_B7, $qr->value);
    }

    public function testKeepsTheInsertionOrderWithTheMarkerFirstAndTheCrcLast(): void
    {
        $value = new MerchantPresentedQrBuilder()
            ->with('60', 'Rome')
            ->with('59', 'Shop')
            ->with('58', 'BR')
            ->with('53', '986')
            ->with('52', '5411')
            ->with('04', 'CARDACCOUNT1')
            ->build()
            ->value
        ;

        self::assertStringStartsWith('0002016004Rome5904Shop5802BR5303986520454110412CARDACCOUNT16304', $value);
    }

    public function testPointOfInitiationAndTheMarkerDefault(): void
    {
        $qr = $this->minimal()->withPointOfInitiation(PointOfInitiation::Dynamic)->build();

        self::assertStringEndsWith('0102126304' . substr($qr->value, -4), $qr->value);
        self::assertStringStartsWith('000201', $qr->value);
        self::assertSame(PointOfInitiation::Dynamic, $qr->pointOfInitiation());
        self::assertSame('01', $qr->get('00'));
    }

    public function testAMarkerSetExplicitlyStaysFirst(): void
    {
        $qr = $this->minimal()->with('00', '01')->build();

        self::assertStringStartsWith('0002010412CARDACCOUNT1', $qr->value);
    }

    public function testAnExistingIdIsReplaced(): void
    {
        $qr = $this->minimal()->with('59', 'First')->with('59', 'Second')->build();

        self::assertSame('Second', $qr->merchantName());
    }

    public function testReplacingKeepsThePosition(): void
    {
        $value = $this->minimal()->with('52', '0000')->build()->value;

        self::assertStringContainsString('0412CARDACCOUNT1520400005303986', $value);
    }

    public function testIsImmutable(): void
    {
        $base = $this->minimal();
        $changed = $base->with('59', 'Other');

        self::assertNotSame($base, $changed);
        self::assertSame('Shop', $base->build()->merchantName());
        self::assertSame('Other', $changed->build()->merchantName());
    }

    public function testBuiltPayloadsUseAnUppercaseCrc(): void
    {
        $value = $this->minimal()->build()->value;

        self::assertSame(strtoupper(substr($value, -4)), substr($value, -4));
        self::assertSame('6304', substr($value, -8, 4));
    }

    public function testBuildRefusesAMissingMandatoryObject(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantPresentedQrBuilder()->with('59', 'Shop')->build();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidObjects(): iterable
    {
        yield 'id of one digit' => ['5', 'x'];
        yield 'id with a letter' => ['5A', 'x'];
        yield 'id of three digits' => ['590', 'x'];
        yield 'empty value' => ['59', ''];
        yield 'value of 100 characters' => ['59', str_repeat('a', 100)];
        yield 'the CRC' => ['63', 'ABCD'];
    }

    #[DataProvider('invalidObjects')]
    public function testWithRefusesAnInvalidObject(string $id, string $value): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantPresentedQrBuilder()->with($id, $value);
    }

    public function testAValueOfNinetyNineCharactersIsAllowed(): void
    {
        $qr = $this->minimal()->with('80', str_repeat('a', 99))->build();

        self::assertSame(str_repeat('a', 99), $qr->get('80'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidAccounts(): iterable
    {
        yield 'id 01' => ['01', 'A.B'];
        yield 'id 25' => ['25', 'A.B'];
        yield 'id 260' => ['260', 'A.B'];
        yield 'id 52' => ['52', 'A.B'];
        yield 'id with a letter' => ['2A', 'A.B'];
        yield 'empty GUI' => ['26', ''];
        yield 'GUI of 33 characters' => ['26', str_repeat('G', 33)];
    }

    #[DataProvider('invalidAccounts')]
    public function testWithMerchantAccountRefusesInvalidInput(string $id, string $guid): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantPresentedQrBuilder()->withMerchantAccount($id, $guid);
    }

    public function testTemplateIds26And51AreAllowed(): void
    {
        $qr = $this->minimal()->withMerchantAccount('26', 'A.B')->withMerchantAccount('51', 'C.D')->build();

        self::assertSame(['04', '26', '51'], array_map(static fn (MerchantAccount $account): string => $account->id, $qr->merchantAccounts()));
    }

    public function testAnEmptyValueIsRefusedByWith(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        $this->minimal()->with('61', '');
    }

    public function testReplacingKeepsOtherObjects(): void
    {
        $qr = $this->minimal()->with('61', 'A')->with('59', 'B')->build();

        self::assertSame('A', $qr->postalCode());
        self::assertSame('CARDACCOUNT1', $qr->get('04'));
    }

    public function testAGuiOfThirtyTwoCharactersIsAllowed(): void
    {
        $guid = str_repeat('G', 32);
        $qr = $this->minimal()->withMerchantAccount('26', $guid)->build();

        self::assertSame($guid, $qr->merchantAccounts()[1]->globallyUniqueIdentifier);
    }

    public function testATemplateOverNinetyNineCharactersIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantPresentedQrBuilder()->withMerchantAccount('26', 'A.B', [new DataObject('01', str_repeat('x', 90))]);
    }

    public function testASubFieldWithTheGuiIdIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantPresentedQrBuilder()->withMerchantAccount('26', 'A.B', [new DataObject('00', 'x')]);
    }

    public function testEmptyAdditionalDataIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantPresentedQrBuilder()->withAdditionalData([]);
    }

    private function minimal(): MerchantPresentedQrBuilder
    {
        return new MerchantPresentedQrBuilder()
            ->with('04', 'CARDACCOUNT1')
            ->with('52', '5411')
            ->with('53', '986')
            ->with('58', 'BR')
            ->with('59', 'Shop')
            ->with('60', 'Rome')
        ;
    }
}
