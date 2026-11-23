<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

/**
 * Point of Initiation Method (ID "01"): whether one QR code serves many transactions or a new one is shown for each.
 *
 * Cases may be added in a minor release, so a `match` over them needs a default arm; backed values never change in
 * 1.x.
 *
 * @see https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf HKMA Common QR Code Specification 1.0, table 4.1
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
enum PointOfInitiation: string
{
    case Static = '11';
    case Dynamic = '12';
}
