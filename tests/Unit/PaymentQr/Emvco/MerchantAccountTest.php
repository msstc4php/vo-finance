<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
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
}
