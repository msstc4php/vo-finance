<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

/**
 * Point of Initiation Method (ID "01"): whether one QR code serves many transactions or a new one is shown for each.
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, table 4.1
 * @see docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
enum PointOfInitiation: string
{
    case Static = '11';
    case Dynamic = '12';
}
