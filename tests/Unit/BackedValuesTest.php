<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit;

use BackedEnum;
use MSSTC4PHP\ValueObject\Financial\Card\CardScheme;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil\PixKeyType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss\QrBillReferenceType;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand\PromptPayProxyType;
use MSSTC4PHP\ValueObject\Financial\Security\CfiCategory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Backed values are a storage format (Doctrine `enumType`) and never change in 1.x; cases may only be added.
 */
#[CoversNothing]
final class BackedValuesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<BackedEnum>, array<string, string>}>
     */
    public static function enums(): iterable
    {
        yield 'CardScheme' => [CardScheme::class, ['Amex' => 'amex', 'Jcb' => 'jcb', 'UnionPay' => 'unionpay']];
        yield 'PixKeyType' => [PixKeyType::class, ['Cpf' => 'CPF', 'Cnpj' => 'CNPJ', 'Phone' => 'PHONE', 'Email' => 'EMAIL', 'Evp' => 'EVP']];
        yield 'PromptPayProxyType' => [PromptPayProxyType::class, ['MobileNumber' => '01', 'NationalOrTaxId' => '02', 'EWalletId' => '03', 'BankAccount' => '04']];
        yield 'QrBillReferenceType' => [QrBillReferenceType::class, ['QrReference' => 'QRR', 'CreditorReference' => 'SCOR', 'None' => 'NON']];
        yield 'PointOfInitiation' => [PointOfInitiation::class, ['Static' => '11', 'Dynamic' => '12']];
        yield 'CfiCategory' => [CfiCategory::class, [
            'Equities' => 'E', 'CollectiveInvestmentVehicles' => 'C', 'DebtInstruments' => 'D', 'Entitlements' => 'R',
            'ListedOptions' => 'O', 'Futures' => 'F', 'Swaps' => 'S', 'NonListedAndComplexListedOptions' => 'H',
            'Spot' => 'I', 'Forwards' => 'J', 'Strategies' => 'K', 'Financing' => 'L', 'ReferentialInstruments' => 'T',
            'Others' => 'M',
        ]];
    }

    /**
     * @param class-string<BackedEnum> $enum
     * @param array<string, string> $expected
     */
    #[DataProvider('enums')]
    public function testTheReleasedCasesKeepTheirNamesAndValues(string $enum, array $expected): void
    {
        $actual = [];
        foreach ($enum::cases() as $case) {
            $actual[$case->name] = $case->value;
        }

        self::assertSame($expected, $actual);
    }
}
