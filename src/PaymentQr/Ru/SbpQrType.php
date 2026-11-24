<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru;

/**
 * QR_TYPE of an SBP functional link (query parameter `type`): QR-Static, QR-Dynamic or QR-Subscription.
 *
 * Cases may be added in a minor release, so a `match` over them needs a default arm; backed values never change in
 * 1.x.
 *
 * @see https://sbp.nspk.ru/api/ NSPK, "СБП API", section "Формат Функциональной ссылки СБП / URL-based QR"
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "NSPK SBP"
 */
enum SbpQrType: string
{
    case Static = '01';
    case Dynamic = '02';
    case Subscription = '03';
}
