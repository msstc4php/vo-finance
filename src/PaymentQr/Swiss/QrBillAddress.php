<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss;

use MSSTC4PHP\ValueObject\I18n\CountryCode;

/**
 * A structured address of a QR-bill (address type `S`): the creditor's or the ultimate debtor's.
 *
 * @see https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf SIX Swiss Implementation Guidelines for the QR-bill 2.3, Table 8 and section 4.3.1
 */
final readonly class QrBillAddress
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string|null $street
     * @param non-empty-string|null $buildingNumber
     * @param non-empty-string $postalCode
     * @param non-empty-string $town
     * @param non-empty-string $country two capital letters
     */
    public function __construct(
        public string $name,
        public ?string $street,
        public ?string $buildingNumber,
        public string $postalCode,
        public string $town,
        public string $country,
    ) {
    }

    /**
     * Null for a code vo-base does not know.
     */
    public function countryCode(): ?CountryCode
    {
        return CountryCode::tryFrom($this->country);
    }
}
