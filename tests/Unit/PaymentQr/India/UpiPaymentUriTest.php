<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\India;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\India\UpiPaymentUri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UpiPaymentUri::class)]
#[UsesClass(QueryString::class)]
#[UsesClass(ProfileRule::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Mask::class)]
#[UsesClass(AmbiguousPaymentQrFieldException::class)]
#[UsesClass(PayloadBound::class)]
final class UpiPaymentUriTest extends TestCase
{
    // Synthetic, with the parameters of NPCI UPI OC 18 (2017), Table 1, and its "tn=test%transaction" spacing example.
    private const string URI = 'upi://pay?pa=merchant@bank&pn=Test%Merchant&tr=ORDER123&am=10.50&mam=5&cu=INR&tn=test%transaction';

    public function testAUriParses(): void
    {
        $uri = new UpiPaymentUri(self::URI);

        self::assertSame(self::URI, $uri->value);
        self::assertSame('merchant@bank', $uri->payeeAddress());
        self::assertSame('Test%Merchant', $uri->payeeName());
        self::assertSame('ORDER123', $uri->transactionReference());
        self::assertSame('10.50', $uri->writtenAmount());
        self::assertSame('10.5', $uri->amount()?->value);
        self::assertSame('5', $uri->get('mam'));
        self::assertSame('INR', $uri->get('cu'));
        self::assertSame(['pa', 'pn', 'tr', 'am', 'mam', 'cu', 'tn'], $uri->parameterNames());
    }

    public function testTheMinimalUriParses(): void
    {
        $uri = UpiPaymentUri::from('upi://pay?pa=a@b&pn=A');

        self::assertNull($uri->transactionReference());
        self::assertNull($uri->amount());
        self::assertNull($uri->writtenAmount());
    }

    public function testANonDecimalAmountGivesNoDecimal(): void
    {
        $uri = UpiPaymentUri::from('upi://pay?pa=a@b&pn=A&am=ten');

        self::assertSame('ten', $uri->writtenAmount());
        self::assertNull($uri->amount());
    }

    public function testAnEmptyParameterIsReadAsAbsent(): void
    {
        $uri = UpiPaymentUri::from('upi://pay?pa=merchant@bank&pn=Shop&am=&tn');

        self::assertNull($uri->writtenAmount());
        self::assertNull($uri->amount());
        self::assertNull($uri->get('am'));
        self::assertNull($uri->get('tn'));
    }

    public function testAnEmptyRepeatBesideAFilledValueIsAmbiguous(): void
    {
        // A first-wins parser reads no amount here; reading 10 would be a parser differential.
        $uri = UpiPaymentUri::from('upi://pay?pa=merchant@bank&pn=Shop&am=&am=10');

        $this->expectException(AmbiguousPaymentQrFieldException::class);

        $uri->writtenAmount();
    }

    public function testARepeatOfTheSameAmountIsThatAmount(): void
    {
        self::assertSame('10', UpiPaymentUri::from('upi://pay?pa=merchant@bank&pn=Shop&am=10&am=10')->writtenAmount());
    }

    public function testSchemeIsCaseInsensitive(): void
    {
        self::assertTrue(UpiPaymentUri::isValid('UPI://PAY?pa=a@b&pn=A'));
    }

    public function testATransactionReferenceOf35CharactersParses(): void
    {
        self::assertTrue(UpiPaymentUri::isValid('upi://pay?pa=a@b&pn=A&tr=' . \str_repeat('1', 35)));
    }

    public function testARepeatedParameterWithDistinctValuesIsAmbiguous(): void
    {
        $uri = UpiPaymentUri::from('upi://pay?pa=a@b&pn=A&pa=c@d');

        $this->expectException(AmbiguousPaymentQrFieldException::class);

        $uri->payeeAddress();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'other action' => ['upi://mandate?pa=a@b&pn=A'];
        yield 'no query' => ['upi://pay'];
        yield 'payee address missing' => ['upi://pay?pn=A'];
        yield 'payee address empty' => ['upi://pay?pa=&pn=A'];
        yield 'payee name missing' => ['upi://pay?pa=a@b'];
        yield 'transaction reference of 36 characters' => ['upi://pay?pa=a@b&pn=A&tr=' . \str_repeat('1', 36)];
        yield 'empty transaction reference' => ['upi://pay?pa=a@b&pn=A&tr='];
        yield 'fragment' => ['upi://pay?pa=a@b&pn=A#x'];
        yield 'space' => ['upi://pay?pa=a@b&pn=A B'];
        yield 'invalid UTF-8' => ["upi://pay?pa=a@b&pn=\xC0"];
    }

    #[DataProvider('invalid')]
    public function testInvalidUrisAreRefused(string $value): void
    {
        $this->expectException(InvalidPaymentQrException::class);

        new UpiPaymentUri($value);
    }

    public function testMaskedHidesThePayeeAddress(): void
    {
        $masked = UpiPaymentUri::from(self::URI)->masked();

        self::assertSame('upi://pay?pa=*********bank&pn=T***&tr=ORDER123&am=10.50&mam=5&cu=INR&tn=***', $masked);
        self::assertStringNotContainsString('merchant@bank', $masked);
    }

    public function testMessagesCarryNoPartOfTheUri(): void
    {
        try {
            new UpiPaymentUri('upi://pay?pn=SecretName');
            self::fail('Expected an exception.');
        } catch (InvalidPaymentQrException $exception) {
            self::assertStringNotContainsString('SecretName', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonDecimalAmounts(): iterable
    {
        yield 'leading letter' => ['x10'];
        yield 'trailing letter' => ['10x'];
        yield 'two points' => ['1.0.0'];
    }

    #[DataProvider('nonDecimalAmounts')]
    public function testAmountsThatAreNotDecimalsGiveNull(string $amount): void
    {
        self::assertNull(UpiPaymentUri::from('upi://pay?pa=a@b&pn=A&am=' . $amount)->amount());
    }

    public function testATrailingNewlineIsRefused(): void
    {
        self::assertFalse(UpiPaymentUri::isValid("upi://pay?pa=a@b&pn=A\n"));
    }

    public function testMaskedKeepsTheUriAsWritten(): void
    {
        self::assertSame('UPI://PAY?pa=*********bank&&flag&pn=A***', UpiPaymentUri::from('UPI://PAY?pa=merchant@bank&&flag&pn=A')->masked());
        self::assertSame('upi://pay?pa=*********bank&pn=A***&tn=&tn=***&tn', UpiPaymentUri::from('upi://pay?pa=merchant@bank&pn=A&tn=&tn=Rent%for%Anna&tn')->masked());
    }

    public function testTheUriIsBoundedAtTheLargestQrContent(): void
    {
        $head = 'upi://pay?pa=merchant@bank&pn=Merchant&tn=';
        $uri = $head . \str_repeat('a', 7089 - \strlen($head));
        self::assertSame($uri, (string) UpiPaymentUri::from($uri));

        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('UpiPaymentUri must not exceed 7089 characters.');

        UpiPaymentUri::from($uri . 'a');
    }
}
