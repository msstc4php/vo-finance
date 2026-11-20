<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Crc16CcittFalse;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;

/**
 * Immutable builder of {@see MerchantPresentedQr}: `build()` writes the payload format indicator first (value 01 unless
 * set), the other objects in the order they were added and the computed CRC last. Setting an ID twice replaces its
 * value in place; ID 63 cannot be set.
 *
 * @see https://mvallim.github.io/emv-qrcode/docs/EMVCo-Merchant-Presented-QR-Specification-v1.1.pdf EMV QRCPS Merchant-Presented Mode 1.1 (third-party copy), sections 4.6 and 4.7.3
 * @see docs/sources/FINANCE.md "EMVCo QRCPS merchant-presented mode"
 */
final readonly class MerchantPresentedQrBuilder
{
    private const int MAX_GUI_LENGTH = 32;

    /**
     * Usually started empty. Objects given here skip the checks of {@see with()}; {@see build()} validates the whole
     * payload either way.
     *
     * @param list<DataObject> $objects the objects to start from, in payload order
     */
    public function __construct(private array $objects = [])
    {
    }

    /**
     * @throws InvalidPaymentQrException when the ID is not two digits, is 63, or the value is not 1 to 99 characters
     */
    public function with(string $id, string $value): self
    {
        if ($id === '63') {
            throw new InvalidPaymentQrException('MerchantPresentedQr computes the CRC (ID 63); it cannot be set.');
        }

        if ($value === '') {
            throw new InvalidPaymentQrException('MerchantPresentedQr data object value must not be empty.');
        }

        return $this->replace(new DataObject($id, $value));
    }

    public function withPointOfInitiation(PointOfInitiation $pointOfInitiation): self
    {
        return $this->with('01', $pointOfInitiation->value);
    }

    /**
     * A template merchant account (ID 26 to 51) with its GUI first.
     *
     * @param list<DataObject> $subFields
     *
     * @throws InvalidPaymentQrException when the ID is outside 26 to 51, the GUI is empty or over 32 characters, a
     *                                   sub-field has ID 00, or the template exceeds 99 characters
     */
    public function withMerchantAccount(string $id, string $globallyUniqueIdentifier, array $subFields = []): self
    {
        if (\preg_match('/^[0-9]{2}$/D', $id) !== 1 || $id < '26' || $id > '51') {
            throw new InvalidPaymentQrException('MerchantPresentedQr merchant account templates use IDs 26 to 51.');
        }

        if ($globallyUniqueIdentifier === '' || EmvcoTlv::length($globallyUniqueIdentifier) > self::MAX_GUI_LENGTH) {
            throw new InvalidPaymentQrException('MerchantPresentedQr GUI must be 1 to 32 characters.');
        }

        if (array_any($subFields, static fn (DataObject $subField): bool => $subField->id === '00')) {
            throw new InvalidPaymentQrException('MerchantPresentedQr sub-fields must not use ID 00, which is the GUI.');
        }

        return $this->replace(new DataObject($id, $this->template([new DataObject('00', $globallyUniqueIdentifier), ...$subFields])));
    }

    /**
     * The Additional Data Field Template (ID 62).
     *
     * @param list<DataObject> $subFields
     *
     * @throws InvalidPaymentQrException when there is no sub-field or the template exceeds 99 characters
     */
    public function withAdditionalData(array $subFields): self
    {
        return $this->replace(new DataObject('62', $this->template($subFields)));
    }

    /**
     * @throws InvalidPaymentQrException when the result is not a valid payload
     */
    public function build(): MerchantPresentedQr
    {
        $marker = array_find($this->objects, static fn (DataObject $object): bool => $object->id === '00') ?? new DataObject('00', '01');
        $objects = [$marker, ...\array_filter($this->objects, static fn (DataObject $object): bool => $object->id !== '00')];

        $body = \implode('', \array_map(EmvcoTlv::encode(...), $objects)) . '6304';

        return new MerchantPresentedQr($body . Crc16CcittFalse::compute($body));
    }

    private function replace(DataObject $object): self
    {
        $replaced = false;
        $objects = [];
        foreach ($this->objects as $existing) {
            if ($existing->id === $object->id) {
                $objects[] = $object;
                $replaced = true;
            } else {
                $objects[] = $existing;
            }
        }

        if (!$replaced) {
            $objects[] = $object;
        }

        return new self($objects);
    }

    /**
     * @param list<DataObject> $subFields
     *
     * @return non-empty-string
     */
    private function template(array $subFields): string
    {
        $template = '';
        foreach ($subFields as $subField) {
            $template .= EmvcoTlv::encode($subField);
        }

        return $template === '' ? throw new InvalidPaymentQrException('MerchantPresentedQr template needs at least one sub-field.') : $template;
    }
}
