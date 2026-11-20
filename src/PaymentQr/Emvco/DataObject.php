<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * One ID, length, value data object: a two-digit ID and a value of 1 to 99 characters. The length is not stored; it is
 * the number of characters of the value. A public type added beyond the plan's API (approved deviation): the builder and
 * the accessors of {@see MerchantAccount} exchange sub-fields as data objects.
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, section 2
 * @see docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final readonly class DataObject
{
    /**
     * @param non-empty-string $value
     */
    public function __construct(
        public string $id,
        public string $value,
    ) {
        if (\preg_match('/^[0-9]{2}$/D', $id) !== 1) {
            throw new InvalidPaymentQrException('EMVCo data object ID must be two digits.');
        }

        $length = \preg_match_all('/./su', $value);
        if ($length === false || $length < 1 || $length > 99) {
            throw new InvalidPaymentQrException('EMVCo data object value must be 1 to 99 characters of valid UTF-8.');
        }
    }
}
