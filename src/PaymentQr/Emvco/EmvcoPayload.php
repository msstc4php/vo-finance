<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentPayload;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;

/**
 * An EMVCo merchant-presented payload: the generic {@see MerchantPresentedQr} and every national profile. The merchant
 * name (59) is mandatory in every profile; the category code (52) and the city (60) are not in Thai QR, so they are
 * nullable here, as is the postal code (61).
 *
 * Implemented by this package only; methods may be added in minor releases.
 */
interface EmvcoPayload extends PaymentPayload
{
    /**
     * Null when the payload does not say (data object 01 absent).
     */
    public function pointOfInitiation(): ?PointOfInitiation;

    /**
     * @return list<MerchantAccount> the data objects 02 to 51, in payload order
     */
    public function merchantAccounts(): array;

    /**
     * @return non-empty-string|null four digits
     */
    public function merchantCategoryCode(): ?string;

    /**
     * Null for a numeric code vo-base does not know.
     */
    public function currency(): ?CurrencyCode;

    /**
     * The amount as written in data object 54; null when absent (the payer enters it).
     *
     * @return non-empty-string|null
     */
    public function writtenAmount(): ?string;

    /**
     * Normalised (`10.50` gives 10.5, see {@see writtenAmount()}); null when absent or not digits with one optional
     * decimal mark.
     */
    public function amount(): ?Decimal;

    /**
     * Null for a code vo-base does not know.
     */
    public function countryCode(): ?CountryCode;

    /**
     * @return non-empty-string
     */
    public function merchantName(): string;

    /**
     * @return non-empty-string|null
     */
    public function merchantCity(): ?string;

    /**
     * @return non-empty-string|null
     */
    public function postalCode(): ?string;

    /**
     * The sub-fields of the Additional Data Field Template (ID 62) in payload order, repeats included; empty without
     * the template.
     *
     * @return list<DataObject>
     */
    public function additionalData(): array;

    /**
     * The value of a sub-field of template 62; a repeated sub-ID gives its last value.
     *
     * @return non-empty-string|null
     */
    public function additionalDataField(string $subId): ?string;

    /**
     * The value of a root data object as written; null when absent.
     *
     * @return non-empty-string|null
     */
    public function get(string $id): ?string;
}
