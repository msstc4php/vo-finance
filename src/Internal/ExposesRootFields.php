<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\EmvcoPayload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use Override;

/**
 * The {@see EmvcoPayload} accessors every national profile answers the same way, read from {@see ProfilePayload}. A
 * using class sets `$payload` in its `parse()` and implements `EmvcoPayload`.
 *
 * @internal
 */
trait ExposesRootFields
{
    private ProfilePayload $payload;

    /**
     * Null when the payload does not say (a static code).
     */
    #[Override]
    public function pointOfInitiation(): ?PointOfInitiation
    {
        return $this->payload->pointOfInitiation();
    }

    /**
     * @return list<MerchantAccount>
     */
    #[Override]
    public function merchantAccounts(): array
    {
        return $this->payload->accounts();
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function writtenAmount(): ?string
    {
        return $this->payload->get('54');
    }

    /**
     * Null when absent (the payer enters it); `10.50` is normalised to `10.5`, the written form is
     * {@see writtenAmount()}.
     */
    #[Override]
    public function amount(): ?Decimal
    {
        return $this->payload->amount();
    }

    /**
     * Null for a numeric code vo-base does not know.
     */
    #[Override]
    public function currency(): ?CurrencyCode
    {
        return $this->payload->currency();
    }

    /**
     * Null for a code vo-base does not know.
     */
    #[Override]
    public function countryCode(): ?CountryCode
    {
        return $this->payload->countryCode();
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function merchantName(): string
    {
        return $this->payload->required('59');
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function merchantCity(): ?string
    {
        return $this->payload->get('60');
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function merchantCategoryCode(): ?string
    {
        return $this->payload->get('52');
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function postalCode(): ?string
    {
        return $this->payload->get('61');
    }

    /**
     * @return list<DataObject>
     */
    #[Override]
    public function additionalData(): array
    {
        return $this->payload->additionalData();
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function additionalDataField(string $subId): ?string
    {
        return $this->payload->additional($subId);
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function get(string $id): ?string
    {
        return $this->payload->get($id);
    }

    /**
     * The values of merchant accounts 02 to 51, templates 80 to 99 and the CRC are replaced by asterisks of the same
     * length; the result is no valid payload.
     */
    #[Override]
    public function masked(): string
    {
        return $this->payload->masked();
    }
}
