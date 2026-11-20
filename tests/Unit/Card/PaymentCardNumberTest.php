<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Card;

use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;
use MSSTC4PHP\ValueObject\Financial\Card\CardScheme;
use MSSTC4PHP\ValueObject\Financial\Card\PaymentCardNumber;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentCardNumberException;
use MSSTC4PHP\ValueObject\Financial\Internal\IinRange;
use MSSTC4PHP\ValueObject\Financial\Internal\IinRanges;
use MSSTC4PHP\ValueObject\Financial\Internal\Luhn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentCardNumber::class)]
#[CoversClass(CardScheme::class)]
#[CoversClass(InvalidPaymentCardNumberException::class)]
#[UsesClass(IinRanges::class)]
#[UsesClass(IinRange::class)]
#[UsesClass(Luhn::class)]
final class PaymentCardNumberTest extends TestCase
{
    /**
     * @return iterable<string, array{string, CardScheme}>
     */
    public static function schemeNumbers(): iterable
    {
        yield 'Amex 34' => [self::withCheckDigit('34000000000000'), CardScheme::Amex];
        yield 'Amex 37' => [self::withCheckDigit('37123456789012'), CardScheme::Amex];
        yield 'JCB 3528' => [self::withCheckDigit('352800000000000'), CardScheme::Jcb];
        yield 'JCB 3589 end' => [self::withCheckDigit('358999990000000'), CardScheme::Jcb];
        yield 'JCB 3337' => [self::withCheckDigit('333700000000000'), CardScheme::Jcb];
        yield 'UnionPay 6221 26' => [self::withCheckDigit('622126000000000'), CardScheme::UnionPay];
        yield 'UnionPay 8100' => [self::withCheckDigit('810000000000000'), CardScheme::UnionPay];
        yield 'UnionPay 19 digits' => [self::withCheckDigit('624000000000000000'), CardScheme::UnionPay];
    }

    #[DataProvider('schemeNumbers')]
    public function testSchemeIsDecoded(string $number, CardScheme $scheme): void
    {
        self::assertSame([$scheme], new PaymentCardNumber($number)->schemes());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownNumbers(): iterable
    {
        yield 'Visa style, no reached publication' => [self::withCheckDigit('411111111111111')];
        yield 'Mastercard 2221 edge, no reached publication' => [self::withCheckDigit('222100000000000')];
        yield 'Mastercard 2720 edge, no reached publication' => [self::withCheckDigit('272099990000000')];
        yield 'Mir 2200, no reached publication' => [self::withCheckDigit('220000000000000')];
        yield 'Mir 2204, no reached publication' => [self::withCheckDigit('220499990000000')];
        yield 'range edge below JCB 3528' => [self::withCheckDigit('352799990000000')];
        yield 'range edge above JCB 3589' => [self::withCheckDigit('359000000000000')];
        yield 'range edge below UnionPay 6221 26' => [self::withCheckDigit('622125990000000')];
    }

    #[DataProvider('unknownNumbers')]
    public function testNumberOutsideEveryRangeHasNoScheme(string $number): void
    {
        $card = new PaymentCardNumber($number);

        self::assertSame([], $card->schemes());
        self::assertFalse($card->matchesSchemeLength());
    }

    public function testSpacesAndHyphensAreStripped(): void
    {
        $plain = self::withCheckDigit('352800000000000');
        $card = new PaymentCardNumber(\substr($plain, 0, 4) . ' ' . \substr($plain, 4, 4) . '-' . \substr($plain, 8));

        self::assertSame($plain, $card->value);
        self::assertSame($plain, (string) $card);
        self::assertSame($plain, $card->jsonSerialize());
    }

    public function testAccessors(): void
    {
        $number = self::withCheckDigit('622126000000000');
        $card = new PaymentCardNumber($number);

        self::assertSame(\substr($number, 0, 8), $card->iin());
        self::assertSame(\substr($number, -4), $card->last4());
        self::assertTrue($card->matchesSchemeLength());
    }

    public function testLengthOutsideThePublishedOnesIsAcceptedButFlagged(): void
    {
        $card = new PaymentCardNumber(self::withCheckDigit('34000000000000000'));

        self::assertSame([CardScheme::Amex], $card->schemes());
        self::assertFalse($card->matchesSchemeLength());
    }

    public function testSchemeLengths(): void
    {
        self::assertSame([15], CardScheme::Amex->lengths());
        self::assertSame([16, 17, 18, 19], CardScheme::Jcb->lengths());
        self::assertSame([16, 17, 18, 19], CardScheme::UnionPay->lengths());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function masks(): iterable
    {
        yield '16 digits' => ['4111111111111111', '411111******1111'];
        yield '19 digits' => ['6240000000000000006', '624000*********0006'];
        yield '11 digits' => ['40000000006', '400000*0006'];
        yield '10 digits' => ['4000000002', '40000*0002'];
    }

    #[DataProvider('masks')]
    public function testMasked(string $number, string $expected): void
    {
        self::assertSame($expected, new PaymentCardNumber($number)->masked());
    }

    public function testMaskedHidesAtLeastOneDigitOfEveryLength(): void
    {
        for ($length = 10; $length <= 19; $length++) {
            $card = new PaymentCardNumber(self::withCheckDigit(\str_repeat('4', $length - 1)));
            $masked = $card->masked();

            self::assertSame($length, \strlen($masked));
            self::assertStringContainsString('*', $masked);
            self::assertLessThanOrEqual(10, \strlen(\str_replace('*', '', $masked)));
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalid(): iterable
    {
        yield 'empty' => [''];
        yield 'nine digits' => ['000000018'];
        yield 'twenty digits' => ['00000000000000000018'];
        yield 'letters' => ['4111 1111 1111 111X'];
        yield 'wrong Luhn digit' => ['4111111111111112'];
        yield 'CVV typed after the number' => ['4111 1111 1111 1111 123'];
        yield 'slash separator' => ['4111/1111/1111/1111'];
        yield 'non-ASCII digits' => ["\u{0664}111111111111111"];
        yield 'trailing newline' => ["4111111111111111\n"];
    }

    #[DataProvider('invalid')]
    public function testRejects(string $value): void
    {
        $this->expectException(InvalidPaymentCardNumberException::class);

        new PaymentCardNumber($value);
    }

    #[DataProvider('invalid')]
    public function testMessageHasNoDigitAtAll(string $value): void
    {
        try {
            new PaymentCardNumber($value);
            self::fail('Expected an exception.');
        } catch (InvalidPaymentCardNumberException $e) {
            self::assertStringContainsString('PaymentCardNumber', $e->getMessage());
            self::assertTrue($value === '' || !\str_contains($e->getMessage(), $value));
            self::assertDoesNotMatchRegularExpression('/\d/', $e->getMessage());
        }
    }

    public function testTryFromAndIsValid(): void
    {
        self::assertNull(PaymentCardNumber::tryFrom('4111111111111112'));
        self::assertTrue(PaymentCardNumber::isValid('4111 1111 1111 1111'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function agreementBodies(): iterable
    {
        for ($length = 12; $length <= 19; $length++) {
            yield "length $length" => [\str_repeat('7', $length - 1)];
            yield "length $length mixed" => [\substr(\str_repeat('4929123456', 2), 0, $length - 1)];
        }
    }

    #[DataProvider('agreementBodies')]
    public function testAgreesWithTheDeprecatedCreditCardNumber(string $body): void
    {
        $valid = self::withCheckDigit($body);
        $wrong = $body . ((((int) Luhn::compute($body)) + 1) % 10);

        foreach ([$valid, $wrong, \chunk_split($valid, 4, ' '), \implode('-', \str_split($valid, 4))] as $candidate) {
            $old = CreditCardNumber::isValid($candidate);
            self::assertSame($old, PaymentCardNumber::isValid($candidate), 'length ' . \strlen($candidate));
            if ($old) {
                self::assertSame((string) CreditCardNumber::from($candidate), (string) PaymentCardNumber::from($candidate));
            }
        }
    }

    public function testTenAndElevenDigitsAreAcceptedOnlyByTheNewClass(): void
    {
        foreach ([10, 11] as $length) {
            $number = self::withCheckDigit(\str_repeat('4', $length - 1));

            self::assertFalse(CreditCardNumber::isValid($number));
            self::assertTrue(PaymentCardNumber::isValid($number));
        }
    }

    /**
     * All test numbers are synthetic: a prefix inside a cited range plus a computed Luhn digit. No official card-number
     * vector was reachable (docs/sources/FINANCE.md, "Luhn").
     */
    private static function withCheckDigit(string $body): string
    {
        return $body . Luhn::compute($body);
    }
}
