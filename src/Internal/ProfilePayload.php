<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantPresentedQr;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;

/**
 * A national profile's payload read through {@see MerchantPresentedQr}. A profile may differ from the generic format in
 * the payload format indicator (DuitNow uses 02) or leave the merchant category code or the city out (BOT's Thai QR
 * makes both optional). The original objects are kept as given; the generic structure, character-set and template
 * checks run on a twin where the indicator is 01 and the missing objects hold a placeholder, with the CRC recomputed.
 * Accessors answer from the original objects, so no placeholder ever shows.
 *
 * @internal
 *
 * @see https://docs.developer.paynet.my/docs/duitNow-QR/integration/QR-generation-specification/merchant-presented-mode/qr-data-object PayNet DuitNow QR merchant-presented mode 1.5, "DuitNow QR Data Object"
 * @see https://www.bot.or.th/content/dam/bot/fipcs/documents/FPG/2562/EngPDF/25620084.pdf Bank of Thailand, Standardized Thai QR Code for Payment Transactions, section 2.3
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
final readonly class ProfilePayload
{
    private const string PLACEHOLDER_CITY = 'NA';

    /**
     * @param list<DataObject> $objects the root data objects as written
     */
    private function __construct(
        private array $objects,
        private MerchantPresentedQr $twin,
    ) {
    }

    /**
     * @param non-empty-string $profile the class name used in failure messages
     * @param non-empty-string $formatIndicator the value of data object 00 the profile fixes
     *
     * @throws InvalidPaymentQrException when the payload is not a valid payload of the generic format apart from the relaxations
     */
    public static function parse(string $payload, string $profile, string $formatIndicator = '01', bool $categoryOptional = false, bool $cityOptional = false): self
    {
        if (\strlen($payload) > EmvcoTlv::MAX_PAYLOAD_LENGTH * 4 || EmvcoTlv::length($payload) > EmvcoTlv::MAX_PAYLOAD_LENGTH) {
            throw new InvalidPaymentQrException(\sprintf('%s must not exceed %d characters.', $profile, EmvcoTlv::MAX_PAYLOAD_LENGTH));
        }

        $objects = self::objectsOf($payload, $profile);
        $last = $objects[\count($objects) - 1] ?? null;
        ProfileRule::ensure($last !== null && $last->id === '63' && EmvcoTlv::length($last->value) === 4 && Crc16CcittFalse::isValid($payload), $profile, 'the CRC as the last data object, correct');

        $present = \array_map(static fn (DataObject $object): string => $object->id, $objects);
        $twinObjects = [];
        foreach ($objects as $object) {
            if ($object->id === '63') {
                continue;
            }

            if ($object->id === '00') {
                ProfileRule::ensure($object->value === $formatIndicator, $profile, \sprintf('payload format indicator %s', $formatIndicator));
                $object = new DataObject('00', '01');
            }

            $twinObjects[] = $object;
        }

        if ($categoryOptional && !\in_array('52', $present, true)) {
            $twinObjects[] = new DataObject('52', '0000');
        }

        if ($cityOptional && !\in_array('60', $present, true)) {
            $twinObjects[] = new DataObject('60', self::PLACEHOLDER_CITY);
        }

        $body = \implode('', \array_map(EmvcoTlv::encode(...), $twinObjects)) . '6304';

        return new self($objects, self::twinOf($body . Crc16CcittFalse::compute($body), $profile));
    }

    /**
     * @return non-empty-string|null the value of a root data object as written
     */
    public function get(string $id): ?string
    {
        return array_find($this->objects, static fn (DataObject $object): bool => $object->id === $id)?->value;
    }

    /**
     * @return list<MerchantAccount> the objects with IDs 02 to 51 in payload order
     */
    public function accounts(): array
    {
        return $this->twin->merchantAccounts();
    }

    /**
     * The sub-field of the Additional Data Field Template (ID 62) with the given sub-ID.
     *
     * @return non-empty-string|null
     */
    public function additional(string $subId): ?string
    {
        return $this->twin->additionalData()[$subId] ?? null;
    }

    public function pointOfInitiation(): ?PointOfInitiation
    {
        $value = $this->get('01');

        return $value === null ? null : PointOfInitiation::tryFrom($value);
    }

    public function amount(): ?Decimal
    {
        return $this->twin->amount();
    }

    public function currency(): ?CurrencyCode
    {
        return $this->twin->currency();
    }

    public function countryCode(): ?CountryCode
    {
        return $this->twin->countryCode();
    }

    /**
     * @return non-empty-string
     */
    public function masked(): string
    {
        return EmvcoMask::apply($this->objects);
    }

    /**
     * @param non-empty-string $profile
     *
     * @return list<DataObject>
     *
     * @throws InvalidPaymentQrException naming the profile instead of the generic class
     */
    private static function objectsOf(string $payload, string $profile): array
    {
        try {
            return EmvcoTlv::parse($payload);
        } catch (InvalidPaymentQrException $e) {
            throw self::notValid($profile, $e);
        }
    }

    /**
     * @param non-empty-string $profile
     *
     * @throws InvalidPaymentQrException naming the profile instead of the generic class
     */
    private static function twinOf(string $payload, string $profile): MerchantPresentedQr
    {
        try {
            return MerchantPresentedQr::withoutLengthBound($payload);
        } catch (InvalidPaymentQrException $e) {
            throw self::notValid($profile, $e);
        }
    }

    private static function notValid(string $profile, InvalidPaymentQrException $previous): InvalidPaymentQrException
    {
        return new InvalidPaymentQrException(\sprintf('%s is not a valid EMVCo merchant-presented payload.', $profile), 0, $previous);
    }
}
