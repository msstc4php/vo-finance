<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;

/**
 * Writes root data objects with the values of the account-bearing IDs replaced by asterisks of the same character
 * count: merchant accounts 02 to 51, unreserved templates 80 to 99 and the CRC. Headers and all other objects stay
 * visible; the result is no valid payload.
 *
 * @internal
 *
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf Banco Central do Brasil, Manual do BR Code 2.0.1, sections 2.1 and 2.2
 * @see docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final class EmvcoMask
{
    /**
     * @param list<DataObject> $objects the root data objects of a payload
     *
     * @return non-empty-string
     */
    public static function apply(array $objects): string
    {
        $masked = '';
        foreach ($objects as $object) {
            $masked .= EmvcoTlv::encode(self::isSecret($object->id) ? self::asterisks($object) : $object);
        }

        \assert($masked !== '');

        return $masked;
    }

    private static function asterisks(DataObject $object): DataObject
    {
        $mask = \str_repeat('*', EmvcoTlv::length($object->value));
        \assert($mask !== '');

        return new DataObject($object->id, $mask);
    }

    private static function isSecret(string $id): bool
    {
        return ($id >= '02' && $id <= '51') || $id === '63' || ($id >= '80' && $id <= '99');
    }
}
