<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;

/**
 * A national profile's payload read through the generic {@see EmvcoMerchantPresented} validation. A profile may differ
 * from the generic format in the payload format indicator (DuitNow uses 02) or leave the merchant category code or the
 * city out (BOT's Thai QR makes both optional). The original objects are kept as given; the generic structure,
 * character-set and template checks run, without the length bound (checked on the original), on a modified copy where
 * the indicator is 01 and the missing objects hold a placeholder, with the CRC recomputed. Accessors answer from the
 * original objects, so no placeholder ever shows.
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

    /** @var list<MerchantAccount> */
    private array $accounts;

    /**
     * @param list<DataObject> $objects the root data objects as written
     * @param list<DataObject> $copyObjects the objects of the validated modified copy
     */
    private function __construct(
        private array $objects,
        private array $copyObjects,
    ) {
        $this->accounts = EmvcoMerchantPresented::accounts($copyObjects);
    }

    /**
     * @param non-empty-string $profile the class name used in failure messages
     * @param non-empty-string $formatIndicator the value of data object 00 the profile fixes
     *
     * @throws InvalidPaymentQrException when the payload is not a valid payload of the generic format apart from the
     *                                   relaxations
     */
    public static function parse(string $payload, string $profile, string $formatIndicator = '01', bool $categoryOptional = false, bool $cityOptional = false): self
    {
        PayloadBound::ensure($payload, $profile);

        $objects = self::objectsOf($payload, $profile);
        $last = $objects[\count($objects) - 1] ?? null;
        ProfileRule::ensure($last !== null && $last->id === '63' && EmvcoTlv::length($last->value) === 4 && Crc16CcittFalse::isValid($payload), $profile, 'the CRC as the last data object, correct');

        $present = \array_map(static fn (DataObject $object): string => $object->id, $objects);
        $copyObjects = [];
        foreach ($objects as $object) {
            if ($object->id === '63') {
                continue;
            }

            if ($object->id === '00') {
                ProfileRule::ensure($object->value === $formatIndicator, $profile, \sprintf('payload format indicator %s', $formatIndicator));
                $object = new DataObject('00', '01');
            }

            $copyObjects[] = $object;
        }

        if ($categoryOptional && !\in_array('52', $present, true)) {
            $copyObjects[] = new DataObject('52', '0000');
        }

        if ($cityOptional && !\in_array('60', $present, true)) {
            $copyObjects[] = new DataObject('60', self::PLACEHOLDER_CITY);
        }

        $body = \implode('', \array_map(EmvcoTlv::encode(...), $copyObjects)) . '6304';

        return new self($objects, self::validatedCopy($body . Crc16CcittFalse::compute($body), $profile));
    }

    /**
     * @return non-empty-string|null the value of a root data object as written
     */
    public function get(string $id): ?string
    {
        return EmvcoMerchantPresented::get($this->objects, $id);
    }

    /**
     * @return list<MerchantAccount> the objects with IDs 02 to 51 in payload order
     */
    public function accounts(): array
    {
        return $this->accounts;
    }

    /**
     * The sub-field of the Additional Data Field Template (ID 62) with the given sub-ID.
     *
     * @return non-empty-string|null
     */
    public function additional(string $subId): ?string
    {
        return EmvcoMerchantPresented::additionalData($this->copyObjects)[$subId] ?? null;
    }

    /**
     * The sub-fields of a root template (such as 64) as written, by sub-ID; empty when the template is absent. A
     * repeated sub-ID keeps its last value.
     *
     * @return array<array-key, non-empty-string> PHP turns sub-IDs "10" to "99" into integer keys
     */
    public function templateFields(string $id): array
    {
        $template = $this->get($id);
        if ($template === null) {
            return [];
        }

        $subFields = [];
        foreach (EmvcoTlv::parse($template) as $subField) {
            $subFields[$subField->id] = $subField->value;
        }

        return $subFields;
    }

    public function pointOfInitiation(): ?PointOfInitiation
    {
        return EmvcoMerchantPresented::pointOfInitiation($this->objects);
    }

    public function amount(): ?Decimal
    {
        return EmvcoMerchantPresented::amount($this->copyObjects);
    }

    public function currency(): ?CurrencyCode
    {
        return EmvcoMerchantPresented::currency($this->copyObjects);
    }

    public function countryCode(): ?CountryCode
    {
        return EmvcoMerchantPresented::countryCode($this->copyObjects);
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
     * @return list<DataObject>
     *
     * @throws InvalidPaymentQrException naming the profile instead of the generic class
     */
    private static function validatedCopy(string $payload, string $profile): array
    {
        try {
            return EmvcoMerchantPresented::validate($payload, false);
        } catch (InvalidPaymentQrException $e) {
            throw self::notValid($profile, $e);
        }
    }

    /**
     * @param non-empty-string $profile
     */
    private static function notValid(string $profile, InvalidPaymentQrException $previous): InvalidPaymentQrException
    {
        return new InvalidPaymentQrException(\sprintf('%s is not a valid EMVCo merchant-presented payload.', $profile), 0, $previous);
    }
}
