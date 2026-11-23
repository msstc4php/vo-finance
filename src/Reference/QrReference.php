<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Reference;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentReferenceException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mod10Recursive;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Swiss QR reference: 26 digits and a recursive modulo 10 check digit, not all zeros. Spaces of the printed grouping
 * are removed; the stored form is the 27 digits.
 *
 * @see https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf SIX Swiss Implementation Guidelines for the QR-bill 2.3, section 2.12.1, Table 8 "Ref" and Annex B
 * @see docs/sources/FINANCE.md "SIX QR reference, recursive modulo 10"
 */
final class QrReference extends AbstractStringValueObject
{
    /** The length of every stored value; frozen for 1.x. */
    public const int MAX_LENGTH = 27;

    /**
     * @return numeric-string
     */
    public function checkDigit(): string
    {
        $digit = \substr($this->value, -1);
        \assert(\is_numeric($digit));

        return $digit;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $compact = \str_replace(' ', '', $value);

        if (\preg_match('/^\d{27}$/D', $compact) !== 1) {
            throw new InvalidPaymentReferenceException('A QR reference must be 27 digits.');
        }

        if (\trim($compact, '0') === '') {
            throw new InvalidPaymentReferenceException('A QR reference must not consist of zeros only.');
        }

        if (!Mod10Recursive::isValid($compact)) {
            throw new InvalidPaymentReferenceException('The QR reference fails its modulo 10 recursive check digit.');
        }

        return $compact;
    }
}
