<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr;

use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;

/**
 * A payment QR payload of this package; {@see PaymentQrFactory} returns one. `__toString()` and `jsonSerialize()` give
 * the full payload, {@see Maskable::masked()} a form for logs and screens.
 *
 * Implemented by this package only; methods may be added in minor releases.
 */
interface PaymentPayload extends ValueObject, StringConvertible, Maskable
{
}
