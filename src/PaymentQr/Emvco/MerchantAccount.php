<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * A Merchant Account Information data object (ID 02 to 51). IDs 26 to 51 are templates with a Globally Unique
 * Identifier (sub-ID 00) and payment-network-specific sub-fields; IDs 02 to 25 are primitives whose payment system the
 * ID identifies, so they have no GUI and no sub-fields.
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, tables 4.2A and 4.2B
 * @see docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final readonly class MerchantAccount
{
    /**
     * @param non-empty-string $value the whole value of the data object, template content included
     * @param non-empty-string|null $globallyUniqueIdentifier required for IDs 26 to 51, absent for 02 to 25
     * @param list<DataObject> $subFields the sub-fields of a template other than the first GUI, in payload order; empty for IDs 02 to 25
     *
     * @throws InvalidPaymentQrException when the ID is outside 02 to 51 or the GUI and sub-fields do not fit the ID
     */
    public function __construct(
        public string $id,
        public string $value,
        public ?string $globallyUniqueIdentifier = null,
        public array $subFields = [],
    ) {
        if (\preg_match('/^(0[2-9]|[1-4][0-9]|5[01])$/D', $id) !== 1) {
            throw new InvalidPaymentQrException('MerchantAccount ID must be 02 to 51.');
        }

        $isTemplate = (int) $id >= 26;
        if ($isTemplate !== ($globallyUniqueIdentifier !== null) || (!$isTemplate && $subFields !== [])) {
            throw new InvalidPaymentQrException('MerchantAccount has a GUI only for templates 26 to 51, and sub-fields only with it.');
        }
    }

    public function subField(string $id): ?string
    {
        return array_find($this->subFields, static fn (DataObject $object): bool => $object->id === $id)?->value;
    }
}
