<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;

/**
 * Serializes a payment payload as its value alone and runs `parse()` again on unserialize, so the derived state is
 * never restored apart from the value it was validated with.
 *
 * @internal
 */
trait ReparsesOnUnserialize
{
    /**
     * @return array{value: string}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value];
    }

    /**
     * @param array<array-key, mixed> $data untrusted: whatever the serialized string holds
     *
     * @throws InvalidPaymentQrException when the data holds no string value or the value is not valid
     */
    public function __unserialize(array $data): void
    {
        $value = $data['value'] ?? null;
        if (!\is_string($value) || \count($data) !== 1) {
            throw new InvalidPaymentQrException('A serialized payment payload must hold only its value, as a string.');
        }

        $this->value = $this->parse($value);
    }
}
