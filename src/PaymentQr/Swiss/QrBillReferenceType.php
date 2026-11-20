<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss;

/**
 * The reference type of a QR-bill (element `Tp`).
 *
 * @see https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf SIX Swiss Implementation Guidelines for the QR-bill 2.3, Table 8 "Tp"
 */
enum QrBillReferenceType: string
{
    case QrReference = 'QRR';
    case CreditorReference = 'SCOR';
    case None = 'NON';
}
