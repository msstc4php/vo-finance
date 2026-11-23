<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMask;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoMerchantPresented;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * EMVCo merchant-presented QR payload (EMV QRCPS, merchant-presented mode 1.1): a sequence of ID, length, value data
 * objects, templates 26 to 51 and 62 nested, ending in a CRC-16/CCITT-FALSE. The payload is kept as given, a lowercase
 * CRC included. Lengths count characters.
 *
 * Enforced: the TLV shape; `00` with value `01`; the CRC as the last object and correct; no repeated root ID; at least
 * one of IDs 02 to 51; `52`, `53`, `58`, `59`, `60` present with the fixed lengths of 52, 53, 58; point of initiation
 * `11` or `12`; a GUI of at most 32 characters in every template 26 to 51; the Common Character Set (printable ASCII,
 * 0x20 to 0x7E, the minimum of EMV Book 4's set) in numeric and alphanumeric-special objects, digits in numeric ones;
 * at most 7089 characters, the largest QR content and only a bound against abusive input. UTF-8 is accepted only
 * where a String-format object allows it: sub-fields 01 to 99 of templates 26 to 51, 62 sub-IDs 12 to 99, and the
 * templates 64 to 99. Not enforced (see docs/sources/FINANCE.md): the "var. up to" maxima, the 512-character
 * recommendation, the content of IDs 02 to 25, and the amount beyond its character set; {@see amount()} gives null for
 * a non-decimal.
 *
 * {@see masked()} replaces with asterisks the values of IDs 02 to 51, 80 to 99 and the CRC; the CRC is not recomputed.
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, sections 2 and 4
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf Banco Central do Brasil, Manual do BR Code 2.0.1, sections 2.1 and 2.2
 * @see https://mvallim.github.io/emv-qrcode/docs/EMVCo-Merchant-Presented-QR-Specification-v1.1.pdf EMV QRCPS Merchant-Presented Mode 1.1 (third-party copy), sections 3.2, 4.2 to 4.8, Annex B
 * @see docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final class MerchantPresentedQr extends AbstractStringValueObject implements EmvcoPayload
{
    /** The longest stored payload, in characters: the abuse bound of 7089; frozen for 1.x. */
    public const int MAX_LENGTH = PayloadBound::MAX_CHARACTERS;

    /** @var list<DataObject> */
    private array $objects;

    /** @var list<MerchantAccount> */
    private array $accounts;

    #[Override]
    public function pointOfInitiation(): ?PointOfInitiation
    {
        return EmvcoMerchantPresented::pointOfInitiation($this->objects);
    }

    /**
     * @return list<MerchantAccount> the objects with IDs 02 to 51, in payload order
     */
    #[Override]
    public function merchantAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * Never null here: the generic format makes it mandatory.
     *
     * @return non-empty-string four digits
     */
    #[Override]
    public function merchantCategoryCode(): string
    {
        return $this->required('52');
    }

    /**
     * Null for a numeric code vo-base does not know.
     */
    #[Override]
    public function currency(): ?CurrencyCode
    {
        return EmvcoMerchantPresented::currency($this->objects);
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function writtenAmount(): ?string
    {
        return $this->get('54');
    }

    /**
     * Normalised ("10.50" gives 10.5; the written form is {@see writtenAmount()}). Null when the amount is absent (the
     * payer enters it) or is not digits with one optional decimal mark.
     */
    #[Override]
    public function amount(): ?Decimal
    {
        return EmvcoMerchantPresented::amount($this->objects);
    }

    /**
     * Null for a code vo-base does not know.
     */
    #[Override]
    public function countryCode(): ?CountryCode
    {
        return EmvcoMerchantPresented::countryCode($this->objects);
    }

    /**
     * @return non-empty-string
     */
    #[Override]
    public function merchantName(): string
    {
        return $this->required('59');
    }

    /**
     * Never null here: the generic format makes it mandatory.
     *
     * @return non-empty-string
     */
    #[Override]
    public function merchantCity(): string
    {
        return $this->required('60');
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function postalCode(): ?string
    {
        return $this->get('61');
    }

    /**
     * @return list<DataObject>
     */
    #[Override]
    public function additionalData(): array
    {
        return EmvcoMerchantPresented::additionalData($this->objects);
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function additionalDataField(string $subId): ?string
    {
        return EmvcoMerchantPresented::additionalDataField($this->objects, $subId);
    }

    /**
     * The value of a data object under the root as written, or null when it is absent.
     *
     * @return non-empty-string|null
     */
    #[Override]
    public function get(string $id): ?string
    {
        return EmvcoMerchantPresented::get($this->objects, $id);
    }

    /**
     * A builder holding every data object of this payload but the CRC, in payload order; `build()` computes the CRC
     * again.
     */
    public function toBuilder(): MerchantPresentedQrBuilder
    {
        $builder = new MerchantPresentedQrBuilder();
        foreach ($this->objects as $object) {
            if ($object->id !== '63') {
                $builder = $builder->with($object->id, $object->value);
            }
        }

        return $builder;
    }

    #[Override]
    public function masked(): string
    {
        return EmvcoMask::apply($this->objects);
    }

    #[Override]
    protected function parse(string $value): string
    {
        $this->objects = EmvcoMerchantPresented::validate($value, true);
        $this->accounts = EmvcoMerchantPresented::accounts($this->objects);

        return $value;
    }

    /**
     * @return non-empty-string
     */
    private function required(string $id): string
    {
        return EmvcoMerchantPresented::required($this->objects, $id);
    }
}
