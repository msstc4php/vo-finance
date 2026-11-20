<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr\BuildsPayloads;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfilePayload::class)]
#[CoversClass(ProfileRule::class)]
#[CoversClass(EmvcoMask::class)]
#[UsesClass(EmvcoMerchantPresented::class)]
#[UsesClass(EmvcoTlv::class)]
#[UsesClass(Crc16CcittFalse::class)]
#[UsesClass(MerchantPresentedQr::class)]
#[UsesClass(MerchantAccount::class)]
#[UsesClass(DataObject::class)]
#[UsesClass(InvalidPaymentQrException::class)]
final class ProfilePayloadTest extends TestCase
{
    use BuildsPayloads;

    // Synthetic payloads.
    public function testAGenericPayloadIsReadAsGiven(): void
    {
        $payload = ProfilePayload::parse(self::sealed(...$this->objects()), 'Profile');

        self::assertSame('0000', $payload->get('52'));
        self::assertNull($payload->get('99'));
        self::assertSame('Rome', $payload->get('60'));
        self::assertSame('SALE-1', $payload->additional('05'));
        self::assertNull($payload->additional('06'));
        self::assertNull($payload->pointOfInitiation());
        self::assertSame('986', $payload->currency()?->numeric());
        self::assertSame('BR', $payload->countryCode()?->value);
        self::assertNull($payload->amount());
        self::assertCount(1, $payload->accounts());
    }

    public function testThePointOfInitiationAndTheAmountAreRead(): void
    {
        $payload = ProfilePayload::parse(self::sealed(...$this->objects(), ...[self::tlv('01', '12'), self::tlv('54', '5.50')]), 'Profile');

        self::assertSame(PointOfInitiation::Dynamic, $payload->pointOfInitiation());
        self::assertSame('5.5', $payload->amount()?->value);
    }

    public function testAFixedFormatIndicatorIsRequiredAndRestoredOnlyInTheModifiedCopy(): void
    {
        $objects = $this->objects();
        $objects[0] = self::tlv('00', '02');
        $payload = ProfilePayload::parse(self::sealed(...$objects), 'Profile', formatIndicator: '02');

        self::assertSame('02', $payload->get('00'));
        self::assertStringStartsWith('000202', $payload->masked());
    }

    public function testTheCategoryAndTheCityMayBeLeftOutOnlyWhenAllowed(): void
    {
        $objects = \array_values(\array_filter($this->objects(), static fn (string $object): bool => !\str_starts_with($object, '52') && !\str_starts_with($object, '60')));
        $payload = ProfilePayload::parse(self::sealed(...$objects), 'Profile', categoryOptional: true, cityOptional: true);

        self::assertNull($payload->get('52'));
        self::assertNull($payload->get('60'));
        self::assertStringNotContainsString('NA', $payload->masked());

        $this->expectException(InvalidPaymentQrException::class);
        ProfilePayload::parse(self::sealed(...$objects), 'Profile');
    }

    public function testACategoryAndACityThatArePresentAreKept(): void
    {
        $payload = ProfilePayload::parse(self::sealed(...$this->objects()), 'Profile', categoryOptional: true, cityOptional: true);

        self::assertSame('0000', $payload->get('52'));
        self::assertSame('Rome', $payload->get('60'));
    }

    public function testRefusesAPayloadWithoutTheFixedIndicator(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        ProfilePayload::parse(self::sealed(...$this->objects()), 'Profile', formatIndicator: '02');
    }

    public function testRefusesAnotherIndicatorThanTheFixedOne(): void
    {
        $objects = $this->objects();
        $objects[0] = self::tlv('00', '03');

        $this->expectException(InvalidPaymentQrException::class);
        ProfilePayload::parse(self::sealed(...$objects), 'Profile', formatIndicator: '02');
    }

    public function testRefusesAMissingIndicator(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        ProfilePayload::parse(self::sealed(...\array_slice($this->objects(), 1)), 'Profile', categoryOptional: true);
    }

    public function testRefusesAWrongCrcAndAMisplacedCrc(): void
    {
        $good = self::sealed(...$this->objects());

        $refused = 0;
        foreach ([\substr($good, 0, -4) . '0000', '6304ABCD' . \substr($good, 0, -8), \substr($good, 0, -8) . '6305ABCDE'] as $payload) {
            try {
                ProfilePayload::parse($payload, 'Profile');
            } catch (InvalidPaymentQrException) {
                $refused++;
            }
        }

        self::assertSame(3, $refused);
    }

    public function testAFailureInsideTheGenericFormatNamesTheProfile(): void
    {
        $objects = $this->objects();
        $objects[1] = self::tlv('26', self::tlv('01', 'no-gui'));

        try {
            ProfilePayload::parse(self::sealed(...$objects), 'Profile');
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertSame('Profile is not a valid EMVCo merchant-presented payload.', $e->getMessage());
            self::assertInstanceOf(InvalidPaymentQrException::class, $e->getPrevious());
        }

        $this->expectExceptionMessage('Profile is not a valid EMVCo merchant-presented payload.');
        ProfilePayload::parse('00', 'Profile');
    }

    public function testRefusesAnOversizedPayloadWithoutParsingIt(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Profile must not exceed 7089 characters.');
        ProfilePayload::parse(\str_repeat('0', 7090), 'Profile');
    }

    public function testRefusesAByteHeavyPayloadBeforeCountingCharacters(): void
    {
        $this->expectException(InvalidPaymentQrException::class);
        $this->expectExceptionMessage('Profile must not exceed 7089 characters.');
        ProfilePayload::parse(\str_repeat('é', 14200), 'Profile');
    }

    public function testTheSubFieldsOfARootTemplateAreReadBySubId(): void
    {
        $payload = ProfilePayload::parse(self::sealed(...[...$this->objects(), self::tlv('64', self::tlv('00', 'JA') . self::tlv('01', '協議会') . self::tlv('10', 'x'))]), 'Profile');

        self::assertSame(['00' => 'JA', '01' => '協議会', 10 => 'x'], $payload->templateFields('64'));
        self::assertSame([], $payload->templateFields('65'));
        self::assertSame('SALE-1', $payload->templateFields('62')['05'] ?? null);
    }

    public function testMaskHidesAccountsTemplatesAndTheCrcOnly(): void
    {
        $masked = EmvcoMask::apply([
            new DataObject('00', '01'),
            new DataObject('01', '12'),
            new DataObject('02', 'card'),
            new DataObject('26', 'abc'),
            new DataObject('51', 'x'),
            new DataObject('52', '0000'),
            new DataObject('62', 'keep'),
            new DataObject('79', 'rfu'),
            new DataObject('80', 'tmpl'),
            new DataObject('99', 'last'),
            new DataObject('63', 'ABCD'),
        ]);

        self::assertSame('0002010102120204****2603***5101*520400006204keep7903rfu8004****9904****6304****', $masked);
    }

    public function testEnsureNamesTheProfileAndTheRuleOnly(): void
    {
        ProfileRule::ensure(true, 'Profile', 'a thing');

        try {
            ProfileRule::ensure(false, 'Profile', 'a thing');
            self::fail('Expected a refusal.');
        } catch (InvalidPaymentQrException $e) {
            self::assertSame('Profile requires a thing.', $e->getMessage());
        }
    }

    public function testAtMostCountsCharactersAndPassesAnAbsentValue(): void
    {
        self::assertTrue(ProfileRule::atMost(null, 5));
        self::assertTrue(ProfileRule::atMost('ab', 2));
        self::assertTrue(ProfileRule::atMost('日本', 2));
        self::assertFalse(ProfileRule::atMost('abc', 2));
        self::assertFalse(ProfileRule::atMost('日本語', 2));
    }

    /**
     * @return list<string>
     */
    private function objects(): array
    {
        return [
            self::tlv('00', '01'),
            self::tlv('26', self::tlv('00', 'com.example') . self::tlv('01', 'acct')),
            self::tlv('52', '0000'),
            self::tlv('53', '986'),
            self::tlv('58', 'BR'),
            self::tlv('59', 'Shop'),
            self::tlv('60', 'Rome'),
            self::tlv('62', self::tlv('05', 'SALE-1')),
        ];
    }
}
