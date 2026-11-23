<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand;

/**
 * The kind of PromptPay proxy a credit-transfer template (tag 29) carries; the value is its sub-tag ID.
 *
 * Cases may be added in a minor release, so a `match` over them needs a default arm; backed values never change in
 * 1.x.
 *
 * @see https://www.bot.or.th/content/dam/bot/fipcs/documents/FPG/2562/EngPDF/25620084.pdf Bank of Thailand, Standardized Thai QR Code for Payment Transactions, section 2.3, tag 29
 */
enum PromptPayProxyType: string
{
    case MobileNumber = '01';
    case NationalOrTaxId = '02';
    case EWalletId = '03';
    case BankAccount = '04';

    /**
     * Whether the proxy has the digits and length the guideline gives for its type.
     */
    public function accepts(string $proxy): bool
    {
        return match ($this) {
            self::MobileNumber, self::NationalOrTaxId => \preg_match('/^[0-9]{13}$/D', $proxy) === 1,
            self::EWalletId => \preg_match('/^[0-9]{15}$/D', $proxy) === 1,
            self::BankAccount => \preg_match('/^[0-9]{1,43}$/D', $proxy) === 1,
        };
    }
}
