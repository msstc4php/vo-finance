<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Tests\Unit\PaymentQr;

use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;

trait BuildsPayloads
{
    private static function tlv(string $id, string $value): string
    {
        \assert($value !== '');

        return EmvcoTlv::encode(new DataObject($id, $value));
    }

    private static function sealed(string ...$objects): string
    {
        $body = \implode('', $objects) . '6304';

        return $body . Crc16CcittFalse::compute($body);
    }
}
