<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\PointOfInitiation;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use Override;

/**
 * The root data objects every national profile exposes the same way, read from {@see ProfilePayload}. A using class
 * sets `$payload` in its `parse()` and implements `Maskable`.
 *
 * @internal
 */
trait ExposesRootFields
{
    private ProfilePayload $payload;

    /**
     * Null when the payload does not say (a static code).
     */
    public function pointOfInitiation(): ?PointOfInitiation
    {
        return $this->payload->pointOfInitiation();
    }

    /**
     * Null when absent (the payer enters it); `10.50` is normalised to `10.5`, the written form is in the payload.
     */
    public function amount(): ?Decimal
    {
        return $this->payload->amount();
    }

    /**
     * Null for a numeric code vo-base does not know.
     */
    public function currency(): ?CurrencyCode
    {
        return $this->payload->currency();
    }

    /**
     * Null for a code vo-base does not know.
     */
    public function countryCode(): ?CountryCode
    {
        return $this->payload->countryCode();
    }

    /**
     * @return non-empty-string|null
     */
    public function merchantName(): ?string
    {
        return $this->payload->get('59');
    }

    /**
     * @return non-empty-string|null
     */
    public function merchantCity(): ?string
    {
        return $this->payload->get('60');
    }

    /**
     * @return non-empty-string|null
     */
    public function merchantCategoryCode(): ?string
    {
        return $this->payload->get('52');
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
