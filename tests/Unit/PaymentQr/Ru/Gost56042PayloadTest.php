<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\Ru;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\RussianAccountKey;
use MSSTC4PHP\ValueObject\Financial\Internal\Transcoder;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru\Gost56042Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gost56042Payload::class)]
#[UsesClass(RussianAccountKey::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Transcoder::class)]
#[UsesClass(Mask::class)]
#[UsesClass(PayloadBound::class)]
final class Gost56042PayloadTest extends TestCase
{
    // GOST R 56042-2014, Annex D (the mirror reads the payee INN alias as "PayeelNN"; the Annex V alias is PayeeINN).
    private const string MANDATORY = 'Name=ООО "Три кита"|PersonalAcc=40702810138250123017|BankName=ОАО "БАНК"|BIC=044525225|CorrespAcc=30101810400000000225';

    private const string ADDITIONAL = '|PayeeINN=6200098765|LastName=Иванов|FirstName=Иван|MiddleName=Иванович|Purpose=Оплата членского взноса|PayerAddress=г.Рязань ул.Ленина д.10 кв.15|Sum=100000';

    // Synthetic from the Federal Tax Service's published Moscow requisites (see testATreasuryAccountIsOutsideTheKeyRule).
    private const string BUDGET = 'ST00012|Name=UFK|PersonalAcc=03100643000000017300|BankName=GU|BIC=004525988|CorrespAcc=40102810545370000003'
        . '|PayeeINN=7727406020|KPP=770801001|CBC=18210102010011000110|OKTMO=45000000|DrawerStatus=01|PaytReason=0|TaxPeriod=0';

    public function testTheAnnexExampleParsesInUtf8(): void
    {
        $payload = 'ST00012|' . self::MANDATORY . self::ADDITIONAL;
        $gost = new Gost56042Payload($payload);

        self::assertSame($payload, $gost->value);
        self::assertSame(2, $gost->characterSet());
        self::assertSame('|', $gost->separator());
        self::assertSame('ООО "Три кита"', $gost->name());
        self::assertSame('40702810138250123017', $gost->personalAccount());
        self::assertSame('ОАО "БАНК"', $gost->bankName());
        self::assertSame('044525225', $gost->bik());
        self::assertSame('30101810400000000225', $gost->correspondentAccount());
        self::assertSame('100000', $gost->get('Sum'));
        self::assertSame('6200098765', $gost->get('payeeinn'));
        self::assertSame('г.Рязань ул.Ленина д.10 кв.15', $gost->get('PayerAddress'));
        self::assertNull($gost->get('Phone'));
    }

    public function testFromBytesOnAWindows1251PayloadGivesTheUtf8Form(): void
    {
        // GOST R 56042-2014, Annex D: the example is declared "1" (WIN1251).
        $bytes = \mb_convert_encoding('ST00011|' . self::MANDATORY . self::ADDITIONAL, 'Windows-1251', 'UTF-8');

        $gost = Gost56042Payload::fromBytes($bytes);

        self::assertSame('ST00012|' . self::MANDATORY . self::ADDITIONAL, $gost->value);
        self::assertSame('Иванов', $gost->get('LastName'));
    }

    public function testFromBytesOnAKoi8RPayloadGivesTheUtf8Form(): void
    {
        $bytes = \mb_convert_encoding('ST00013|' . self::MANDATORY . self::ADDITIONAL, 'KOI8-R', 'UTF-8');

        self::assertSame('ST00012|' . self::MANDATORY . self::ADDITIONAL, Gost56042Payload::fromBytes($bytes)->value);
    }

    public function testFromBytesKeepsAUtf8Payload(): void
    {
        $payload = 'ST00012|' . self::MANDATORY;

        self::assertSame($payload, Gost56042Payload::fromBytes($payload)->value);
    }

    public function testFromBytesRefusesAnUnknownCharacterSet(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        Gost56042Payload::fromBytes('ST00014|' . self::MANDATORY);
    }

    public function testFromBytesRefusesAShortHeader(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        Gost56042Payload::fromBytes('ST000');
    }

    public function testFromStringRefusesNonAsciiTextDeclaredInAnotherCharacterSet(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('fromBytes()');

        new Gost56042Payload('ST00011|' . self::MANDATORY);
    }

    public function testFromStringAcceptsAsciiTextDeclaredInAnotherCharacterSet(): void
    {
        $payload = 'ST00011|Name=OOO Tri kita|PersonalAcc=40702810138250123017|BankName=BANK|BIC=044525225|CorrespAcc=30101810400000000225';

        self::assertSame(1, Gost56042Payload::from($payload)->characterSet());
    }

    public function testAnotherSeparatorIsHonoured(): void
    {
        // GOST R 56042-2014, 5.2.2: the provider chooses another separator when "|" occurs in a value.
        $payload = 'ST00012#Name=A|B#PersonalAcc=40702810138250123017#BankName=BANK#BIC=044525225#CorrespAcc=0';
        $gost = Gost56042Payload::from($payload);

        self::assertSame('#', $gost->separator());
        self::assertSame('A|B', $gost->name());
        self::assertSame('0', $gost->correspondentAccount());
    }

    public function testAliasesAreCaseInsensitive(): void
    {
        $payload = 'ST00012|name=A|personalacc=40702810138250123017|BANKNAME=B|bic=044525225|correspacc=30101810400000000225|SUM=5';

        self::assertSame('5', Gost56042Payload::from($payload)->get('Sum'));
    }

    public function testTheLastOfRepeatedAliasesWins(): void
    {
        // GOST R 56042-2014, 5.2.4 and Table 4 step 6: only the last occurrence of an alias is taken.
        $gost = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|Sum=1|sum=2');

        self::assertSame('2', $gost->get('Sum'));
    }

    public function testAValueKeepsEverythingAfterTheFirstEqualsSign(): void
    {
        self::assertSame('a=b', Gost56042Payload::from('ST00012|' . self::MANDATORY . '|Purpose=a=b')->get('Purpose'));
    }

    public function testAnEmptyAdditionalValueIsKeptAndReadAsAbsent(): void
    {
        $gost = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|Phone=');

        self::assertNull($gost->get('Phone'));
        self::assertStringEndsWith('|Phone=', (string) $gost);
    }

    public function testTheBankOfRussiaKeyExampleOfAnRkcClientAccountParses(): void
    {
        // Bank of Russia, Order 515 of 08.09.1997, Annex example 2: account 40102810100000010001 at BIC 040305000.
        $payload = 'ST00012|Name=A|PersonalAcc=40102810100000010001|BankName=B|BIC=040305000|CorrespAcc=0';

        self::assertTrue(Gost56042Payload::isValid($payload));
    }

    public function testTheBankOfRussiaKeyExampleOfACorrespondentAccountParses(): void
    {
        // Bank of Russia, Order 515 of 08.09.1997, Annex example 1: account 30101810800000000746 at BIC 049805000.
        $payload = 'ST00012|Name=A|PersonalAcc=40102810100000010001|BankName=B|BIC=049805000|CorrespAcc=30101810800000000746';

        self::assertTrue(Gost56042Payload::isValid($payload));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'other format' => ['SX00012|' . self::MANDATORY];
        yield 'other version' => ['ST00022|' . self::MANDATORY];
        yield 'character set 4' => ['ST00014|' . self::MANDATORY];
        yield 'no separator' => ['ST00012'];
        yield 'space as separator' => ['ST00012 ' . \str_replace('|', ' ', self::MANDATORY)];
        yield 'mandatory block only partly present' => ['ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225'];
        yield 'mandatory block out of order' => ['ST00012|PersonalAcc=40702810138250123017|Name=A|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'empty name' => ['ST00012|Name=|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'name of 161 characters' => ['ST00012|Name=' . \str_repeat('Я', 161) . '|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'account of 19 digits' => ['ST00012|Name=A|PersonalAcc=4070281013825012301|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'account with a letter in the last place' => ['ST00012|Name=A|PersonalAcc=4070281013825012301A|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'account with a wrong key' => ['ST00012|Name=A|PersonalAcc=40702810238250123017|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'empty bank name' => ['ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=|BIC=044525225|CorrespAcc=0'];
        yield 'bank name of 46 characters' => ['ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=' . \str_repeat('Б', 46) . '|BIC=044525225|CorrespAcc=0'];
        yield 'BIC of 8 digits' => ['ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=B|BIC=04452522|CorrespAcc=0'];
        yield 'empty correspondent account' => ['ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc='];
        yield 'correspondent account of 21 digits' => ['ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc=301018104000000002250'];
        yield 'correspondent account with a wrong key' => ['ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc=30101810500000000225'];
        yield 'pair without equals sign' => ['ST00012|' . self::MANDATORY . '|Sum'];
        yield 'trailing separator' => ['ST00012|' . self::MANDATORY . '|'];
        yield 'Cyrillic alias' => ['ST00012|' . self::MANDATORY . '|Сумма=1'];
        yield 'empty alias' => ['ST00012|' . self::MANDATORY . '|=1'];
        yield 'repeated name with a bad value' => ['ST00012|' . self::MANDATORY . '|Name='];
        yield 'invalid UTF-8' => ["ST00012|Name=\xC0|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc=0"];
    }

    #[DataProvider('invalid')]
    public function testInvalidPayloadsAreRefused(string $payload): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new Gost56042Payload($payload);
    }

    public function testMaskedHidesNamesAndTheAddress(): void
    {
        $masked = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|LastName=Иванов|FirstName=Иван|MiddleName=Иванович|childFio=Петров Петр|PayerAddress=г.Рязань ул.Ленина д.10 кв.15|Purpose=Взнос')->masked();

        self::assertStringEndsWith('|LastName=И***|FirstName=И***|MiddleName=И***|childFio=П***|PayerAddress=***|Purpose=***', $masked);
        self::assertStringNotContainsString('Взнос', $masked);
        self::assertStringNotContainsString('Рязань', $masked);
        self::assertStringEndsWith('|LastName=|PersAcc=', Gost56042Payload::from('ST00012|' . self::MANDATORY . '|LastName=|PersAcc=')->masked());
        self::assertStringNotContainsString('Три кита', $masked);
    }

    public function testMaskedHidesThePhoneFlatBirthDateAndSpecialistButKeepsTheReferences(): void
    {
        $masked = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|Phone=79101234567|Flat=15|BirthDate=01.02.2010|SpecFio=Сидорова Анна|InstNum=1234|ClassNum=5А|QuittId=987654|UIN=18810177220110012345|Sum=100000')->masked();

        self::assertStringEndsWith('|Phone=*********67|Flat=***|BirthDate=***|SpecFio=С***|InstNum=***|ClassNum=***|QuittId=9***4|UIN=1***5|Sum=100000', $masked);
        self::assertStringEndsWith('|Purpose=|Phone=', Gost56042Payload::from('ST00012|' . self::MANDATORY . '|Purpose=|Phone=')->masked());
    }

    public function testMaskedHidesTheReferencesThatIdentifyThePayer(): void
    {
        $masked = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|Contract=Д-17/Иванов|CounterId=123456789|ExecId=12345/24/77001-ИП|RuleId=18810177220110012345')->masked();

        self::assertStringEndsWith('|Contract=***|CounterId=*******89|ExecId=*************1-ИП|RuleId=18**************2345', $masked);
        self::assertStringNotContainsString('Иванов', $masked);
    }

    public function testMaskedHidesTheValueOfEveryAliasOutsideAnnexV(): void
    {
        $masked = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|Comment=Анна Иванова, тел 79101234567|SomeNewReq=100|Empty=|Anna=1|DocNo=12|TechCode=02|Sum=100')->masked();

        self::assertStringEndsWith('|***=***|***=***|***=|***=***|DocNo=***|TechCode=02|Sum=100', $masked);
    }

    public function testMaskedKeepsTheCodeAliasesInTheirShape(): void
    {
        $visible = 'BIC=044525225|CorrespAcc=0|CBC=18210102010011000110|Sum=123456789012345678|AddAmount=1|PayeeINN=7707083893|DrawerStatus=01|PayerIdType=01|TechCode=02|KPP=7736AB001|OKTMO=45000000|PaytReason=ПК|TaxPaytKind=0|TaxPeriod=МС.02.2014|DocDate=01.02.2014|PaymTerm=0|QuittDate=31.12.2024|PaymPeriod=12.2024|CounterVal=1234,5|RegType=12';

        self::assertStringEndsWith('|' . $visible, Gost56042Payload::from('ST00012|' . self::MANDATORY . '|' . $visible)->masked());
        self::assertStringEndsWith('|DrawerStatus=34|PaytReason=0|PaytReason=00|TaxPeriod=01.02.2014|PaymPeriod=122024|CounterVal=7', Gost56042Payload::from('ST00012|' . self::MANDATORY . '|DrawerStatus=34|PaytReason=0|PaytReason=00|TaxPeriod=01.02.2014|PaymPeriod=122024|CounterVal=7')->masked());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function codeAliasesOfAnotherShape(): iterable
    {
        foreach (['CorrespAcc=79101234567', 'CBC=7910123456', 'Sum=1.5', 'Sum=1234567890123456789', 'AddAmount=Анна', 'PayeeINN=79101234567', 'PayeeINN=770708389312', 'DrawerStatus=1', 'DrawerStatus=02', 'DrawerStatus=35', 'OKTMO=45000000001', 'PaytReason=ТП', 'TaxPeriod=10702000', 'PayerIdType=Анна', 'TechCode=123', 'KPP=7736ab001', 'KPP=77360100', 'OKTMO=7910123', 'PaytReason=Тп', 'PaytReason=ТПП', 'TaxPaytKind=a', 'TaxPeriod=МС.02.201', 'TaxPeriod=Мс.02.2014', 'DocDate=1.02.2014', 'PaymTerm=01-02-2014', 'QuittDate=Анна', 'PaymPeriod=12.202', 'PaymPeriod=12..2024', 'CounterVal=1.', 'CounterVal=1234567890123456', 'CounterVal=1.1234567', 'RegType=12345', 'RegType=Анна'] as $pair) {
            yield $pair => [$pair];
        }
    }

    #[DataProvider('codeAliasesOfAnotherShape')]
    public function testMaskedHidesACodeAliasOfAnotherShape(string $pair): void
    {
        $alias = \explode('=', $pair)[0];

        self::assertStringEndsWith('|' . $alias . '=***', Gost56042Payload::from('ST00012|' . self::MANDATORY . '|' . $pair)->masked());
    }

    public function testMaskedHidesTheFreeTextAliasesAndShortensNamesAndReferences(): void
    {
        $masked = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|BankName=Анна|Category=Анна|ServiceName=Анна|DocNo=Анна Иванова|DocIdx=79101234567|QuittId=12|UIN=anna@x.com|Contract=|DocNo=')->masked();

        self::assertStringEndsWith('|BankName=А***|Category=***|ServiceName=***|DocNo=А***а|DocIdx=7***7|QuittId=***|UIN=a***m|Contract=|DocNo=', $masked);
    }

    public function testMaskedHidesThePayeeAndPayerAccounts(): void
    {
        $gost = Gost56042Payload::from('ST00012|' . self::MANDATORY . '|PersAcc=1234567890|PersonalAccount=04123456789|PensAcc=11223344595|payerIdNum=4510123456|PayerINN=500100732259|Sum=100');

        $masked = $gost->masked();

        self::assertSame(
            'ST00012|Name=О***|PersonalAcc=40**************3017|BankName=О***|BIC=044525225|CorrespAcc=30101810400000000225'
            . '|PersAcc=********90|PersonalAccount=*********89|PensAcc=*********95|payerIdNum=********56|PayerINN=********2259|Sum=100',
            $masked,
        );
        self::assertFalse(Gost56042Payload::isValid($masked));
    }

    public function testMessagesCarryNoPartOfThePayload(): void
    {
        try {
            new Gost56042Payload('ST00012|Name=Secret|PersonalAcc=40702810238250123017|BankName=B|BIC=044525225|CorrespAcc=0');
            self::fail('Expected an exception.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringNotContainsString('Secret', $exception->getMessage());
            self::assertStringNotContainsString('3017', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAliases(): iterable
    {
        yield 'Cyrillic before Latin' => ['Сумма_1'];
        yield 'dash at the end' => ['Sum-'];
        yield 'trailing newline' => ["Sum\n"];
    }

    #[DataProvider('invalidAliases')]
    public function testAnAliasOutsideTheAlphabetIsRefused(string $alias): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new Gost56042Payload('ST00012|' . self::MANDATORY . '|' . $alias . '=1');
    }

    public function testLeadingBytesBeforeTheServiceBlockAreRefused(): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new Gost56042Payload('XST00012|' . self::MANDATORY);
    }

    public function testInvalidUtf8IsNamedAsSuch(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('valid UTF-8');

        new Gost56042Payload('ST00012|' . self::MANDATORY . "|Purpose=\xC0");
    }

    public function testATreasuryAccountIsOutsideTheKeyRule(): void
    {
        // Synthetic from the Federal Tax Service's published Moscow requisites: treasury account 03100643000000017300 at
        // the treasury BIC 004525988 fails the Order 515 key, which the order's scope (credit institution and RKC
        // accounts) does not cover; the single treasury account 40102810545370000003 passes it.
        $payload = 'ST00012|Name=Kaznachejstvo Rossii (FNS Rossii)|PersonalAcc=03100643000000017300|BankName=GU Banka Rossii po CFO'
            . '|BIC=004525988|CorrespAcc=40102810545370000003';

        self::assertSame('03100643000000017300', Gost56042Payload::from($payload)->personalAccount());
    }

    public function testAnAccountWithALetterOfTheAlbumInTheSixthPlaceIsAcceptedWithoutItsKey(): void
    {
        // УФЭБС 2026.09 leaf type of an account: \d{5}[0-9ABCEHKMPTX]\d{14}. The key of such an account needs the
        // letter table of 579-П, which was not reached, so it is not checked.
        $payload = 'ST00012|Name=A|PersonalAcc=40702C10238250123017|BankName=B|BIC=044525225|CorrespAcc=30101X10400000000225';
        $gost = Gost56042Payload::from($payload);

        self::assertSame('40702C10238250123017', $gost->personalAccount());
        self::assertSame('30101X10400000000225', $gost->correspondentAccount());
        self::assertStringStartsWith('ST00012|Name=A***|PersonalAcc=40**************3017|BankName=B***|BIC=044525225|CorrespAcc=***', $gost->masked());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function accountsWithALetterOutsideTheAlbum(): iterable
    {
        yield 'letter outside the album list' => ['PersonalAcc=40702D10238250123017|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'lowercase letter' => ['PersonalAcc=40702c10238250123017|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'letter in the fifth place' => ['PersonalAcc=4070C810238250123017|BankName=B|BIC=044525225|CorrespAcc=0'];
        yield 'letter in a correspondent account under 20 characters' => ['PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc=30101X'];
    }

    #[DataProvider('accountsWithALetterOutsideTheAlbum')]
    public function testAnAccountWithALetterElsewhereIsRefused(string $tail): void
    {
        self::assertFalse(Gost56042Payload::isValid('ST00012|Name=A|' . $tail));
    }

    public function testBudgetPaymentDetailsMeetingTheUfebsChecksAreReported(): void
    {
        self::assertTrue(Gost56042Payload::from(self::BUDGET)->hasValidBudgetPaymentDetails());
        self::assertTrue(Gost56042Payload::from(self::BUDGET . '|DocNo=0|PayerINN=500100732259|TaxPeriod=10702000|PaytReason=ТД|OKTMO=0|CBC=0|KPP=7708AB001|DrawerStatus=34')->hasValidBudgetPaymentDetails());
        self::assertTrue(Gost56042Payload::from(self::BUDGET . '|DocNo=' . \str_repeat('Я', 15) . '|PayerINN=0')->hasValidBudgetPaymentDetails());
        self::assertTrue(Gost56042Payload::from(self::BUDGET . '|PayerINN=12345|PayerINN=7707083893')->hasValidBudgetPaymentDetails());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function budgetDetailsFailingTheUfebsChecks(): iterable
    {
        foreach (['DrawerStatus', 'PayeeINN', 'KPP', 'CBC', 'OKTMO', 'PaytReason', 'TaxPeriod'] as $alias) {
            yield 'empty ' . $alias => ['|' . $alias . '='];
        }

        foreach ([
            'DrawerStatus=02', 'DrawerStatus=1', 'PayeeINN=0027406020', 'PayeeINN=772740602', 'PayeeINN=772740602012',
            'KPP=007708010', 'KPP=77080100', 'KPP=7708010011', 'CBC=00000000000000000000', 'CBC=1821010201001100011',
            'CBC=1821010201001100011A', 'OKTMO=00000000', 'OKTMO=45000000001', 'OKTMO=4500000', 'PaytReason=ТП',
            'PaytReason=пк', 'TaxPeriod=МС.02.2014', 'TaxPeriod=1070200', 'TaxPeriod=107020001', 'DocNo=' . \str_repeat('Я', 16),
            'PayerINN=00000', 'PayerINN=0012345678', 'PayerINN=001234567890', 'PayerINN=1234', 'PayerINN=12345678901',
            'PayerINN=1234567', 'PayerINN=00',
        ] as $pair) {
            yield $pair => ['|' . $pair];
        }
    }

    #[DataProvider('budgetDetailsFailingTheUfebsChecks')]
    public function testBudgetPaymentDetailsFailingTheUfebsChecksAreReportedButAccepted(string $pair): void
    {
        self::assertFalse(Gost56042Payload::from(self::BUDGET . $pair)->hasValidBudgetPaymentDetails());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function missingBudgetRequisites(): iterable
    {
        foreach (['PayeeINN=7727406020', 'KPP=770801001', 'CBC=18210102010011000110', 'OKTMO=45000000', 'DrawerStatus=01', 'PaytReason=0', 'TaxPeriod=0'] as $pair) {
            yield $pair => [$pair];
        }
    }

    #[DataProvider('missingBudgetRequisites')]
    public function testAMissingBudgetRequisiteIsReported(string $pair): void
    {
        self::assertFalse(Gost56042Payload::from(\str_replace('|' . $pair, '', self::BUDGET))->hasValidBudgetPaymentDetails());
    }

    public function testStatus27IsWaivedFromTheBudgetChecksExceptThePaymentReason(): void
    {
        // УФЭБС budget checks 1.1: no item 1 check for status 27 except 1.11, which then requires 106 = "0".
        $payload = 'ST00012|Name=A|PersonalAcc=40702810138250123017|BankName=B|BIC=044525225|CorrespAcc=0|DrawerStatus=27';

        self::assertTrue(Gost56042Payload::from($payload . '|PaytReason=0')->hasValidBudgetPaymentDetails());
        self::assertTrue(Gost56042Payload::from($payload . '|PaytReason=0|PayeeINN=1|KPP=1|OKTMO=1|TaxPeriod=МС.02.2014|PayerINN=1')->hasValidBudgetPaymentDetails());
        self::assertFalse(Gost56042Payload::from($payload . '|PaytReason=ПК')->hasValidBudgetPaymentDetails());
        self::assertFalse(Gost56042Payload::from($payload)->hasValidBudgetPaymentDetails());
        self::assertFalse(Gost56042Payload::from(self::BUDGET . '|DrawerStatus=28|PayeeINN=1')->hasValidBudgetPaymentDetails());
    }

    public function testAnOlderTaxPeriodFormIsReportedAgainstThe2026Checks(): void
    {
        // Field 107 holds the customs authority code in the УФЭБС 2026.09 checks (1.12); GOST's 2014 period form fails.
        self::assertFalse(Gost56042Payload::from(\str_replace('TaxPeriod=0', 'TaxPeriod=МС.02.2014', self::BUDGET))->hasValidBudgetPaymentDetails());
    }

    public function testALetterAccountIsAcceptedBesideACorrespondentAccountThatStaysKeyChecked(): void
    {
        $head = 'ST00012|Name=A|PersonalAcc=40702A10238250123017|BankName=B|BIC=044525225|CorrespAcc=';

        self::assertTrue(Gost56042Payload::isValid($head . '30101810400000000225'));
        self::assertFalse(Gost56042Payload::isValid($head . '30101810500000000225'));
    }

    public function testEveryOccurrenceOfAnAccountMustCarryAValidKey(): void
    {
        // The last PersonalAcc counts; an earlier one with a wrong key is still refused.
        $payload = 'ST00012|Name=A|PersonalAcc=40702810238250123017|BankName=B|BIC=044525225|CorrespAcc=0|PersonalAcc=40702810138250123017';

        self::assertFalse(Gost56042Payload::isValid($payload));
        self::assertTrue(Gost56042Payload::isValid(\str_replace('40702810238250123017', '40702810138250123017', $payload)));
    }

    public function testThePayloadIsBoundedAtTheLargestQrContent(): void
    {
        $head = 'ST00012|' . self::MANDATORY . '|Purpose=';
        $payload = $head . \str_repeat('я', 7089 - \mb_strlen($head));
        self::assertSame($payload, (string) Gost56042Payload::from($payload));

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Gost56042Payload must not exceed 7089 characters.');

        Gost56042Payload::from($payload . 'я');
    }

    public function testFromBytesBoundsASingleByteCharacterSetBeforeTranscoding(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Gost56042Payload must not exceed 7089 characters.');

        // 0x98 is undefined in Windows-1251: transcoding first would report the character set, not the bound.
        Gost56042Payload::fromBytes('ST00011|' . \str_repeat("\x98", 7082));
    }

    public function testTheServiceBlockMustLead(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Gost56042Payload requires the service block ST0001, a character set 1 to 3 and an ASCII graphic separator.');

        Gost56042Payload::from('XST00012|' . self::MANDATORY);
    }

    public function testFromBytesRequiresTheServiceBlockToLead(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Gost56042Payload requires the service block ST0001 and a character set 1 to 3.');

        Gost56042Payload::fromBytes('XST00011|Name=A');
    }
}
