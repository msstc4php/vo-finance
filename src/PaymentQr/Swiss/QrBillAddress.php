<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss;

use InvalidArgumentException;
use MSSTC4PHP\ValueObject\I18n\CountryCode;

/**
 * A structured address of a QR-bill (address type `S`): the creditor's or the ultimate debtor's. The constructor checks
 * the filled elements and the country form; the element lengths are the QR-bill's.
 *
 * @see https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf SIX Swiss Implementation Guidelines for the QR-bill 2.3, Table 8 and section 4.3.1
 */
final readonly class QrBillAddress
{
    /** @var non-empty-string */
    public string $name;

    /** @var non-empty-string|null */
    public ?string $street;

    /** @var non-empty-string|null */
    public ?string $buildingNumber;

    /** @var non-empty-string */
    public string $postalCode;

    /** @var non-empty-string */
    public string $town;

    /** @var non-empty-string two capital letters */
    public string $country;

    /**
     * @throws InvalidArgumentException for an empty name, postal code or town, an empty (not null) street or building
     *                                  number, or a country that is not two capital letters
     */
    public function __construct(string $name, ?string $street, ?string $buildingNumber, string $postalCode, string $town, string $country)
    {
        if ($name === '' || $postalCode === '' || $town === '') {
            throw new InvalidArgumentException('A QR-bill address requires a name, a postal code and a town.');
        }

        if ($street === '' || $buildingNumber === '') {
            throw new InvalidArgumentException('A QR-bill address gives an absent street or building number as null.');
        }

        if (\preg_match('/^[A-Z]{2}$/D', $country) !== 1) {
            throw new InvalidArgumentException('A QR-bill address requires a country of two capital letters.');
        }

        $this->name = $name;
        $this->street = $street;
        $this->buildingNumber = $buildingNumber;
        $this->postalCode = $postalCode;
        $this->town = $town;
        $this->country = $country;
    }

    /**
     * Null for a code vo-base does not know.
     */
    public function countryCode(): ?CountryCode
    {
        return CountryCode::tryFrom($this->country);
    }
}
