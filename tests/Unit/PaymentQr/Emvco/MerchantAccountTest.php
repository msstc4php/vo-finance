<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(InvalidPaymentQrException::class)]
final class MerchantAccountTest extends TestCase
{
    public function testAPrimitiveAccount(): void
    {
        $account = new MerchantAccount('02', 'VISA-ACCOUNT');

        self::assertNull($account->globallyUniqueIdentifier);
        self::assertNull($account->subField('01'));
    }

    public function testATemplateAccount(): void
    {
        $account = new MerchantAccount('51', '0001A0102bb', 'A', [new DataObject('01', 'bb')]);

        self::assertSame('bb', $account->subField('01'));
    }

    public function testTheGuiMayFollowOtherSubFieldsAndOnlyTheFirstSubId00IsTheGui(): void
    {
        $account = new MerchantAccount('26', '0102bb0001A0001B', 'A', [new DataObject('01', 'bb'), new DataObject('00', 'B')]);

        self::assertSame('A', $account->globallyUniqueIdentifier);
        self::assertSame('bb', $account->subField('01'));
    }

    public function testAGuiOfThirtyTwoCharactersIsAccepted(): void
    {
        $guid = str_repeat('G', 32);

        self::assertSame($guid, new MerchantAccount('26', '0032' . $guid, $guid)->globallyUniqueIdentifier);
    }

    public function testAnEmptyGuiIsRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new ReflectionClass(MerchantAccount::class)->newInstanceArgs(['26', '0001A', '', []]);
    }

    /**
     * @return iterable<string, array{string, non-empty-string, non-empty-string|null, list<DataObject>}>
     */
    public static function invalid(): iterable
    {
        yield 'id 01' => ['01', 'x', null, []];
        yield 'id 52' => ['52', 'x', 'A', []];
        yield 'id with a letter' => ['2A', 'x', 'A', []];
        yield 'three digits' => ['026', 'x', null, []];
        yield 'primitive with a GUI' => ['25', 'x', 'A', []];
        yield 'primitive with sub-fields' => ['02', 'x', null, [new DataObject('01', 'b')]];
        yield 'GUI of 33 characters' => ['26', '0033GGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG', 'GGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG', []];
        yield 'value unrelated to the GUI' => ['26', '0001A', 'B', []];
        yield 'value lacks the sub-field' => ['26', '0001A', 'A', [new DataObject('01', 'bb')]];
        yield 'value has an extra sub-field' => ['26', '0001A0102bb', 'A', []];
        yield 'template without a GUI' => ['26', 'x', null, []];
    }

    /**
     * @param non-empty-string $value
     * @param non-empty-string|null $guid
     * @param list<DataObject> $subFields
     */
    #[DataProvider('invalid')]
    public function testInvalidCombinationsAreRefused(string $id, string $value, ?string $guid, array $subFields): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantAccount($id, $value, $guid, $subFields);
    }

    public function testSubFieldsWithTheSameValuesUnderOtherIdsDoNotMatch(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new MerchantAccount('26', '0001A0102bb', 'A', [new DataObject('02', 'bb')]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function idsWithJunk(): iterable
    {
        yield 'leading digit' => ['102'];
        yield 'trailing digit' => ['026'];
        yield 'trailing newline' => ["02\n"];
    }

    #[DataProvider('idsWithJunk')]
    public function testAnIdWithJunkIsRefused(string $id): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('MerchantAccount ID must be 02 to 51.');

        new MerchantAccount($id, 'VISA');
    }
}
