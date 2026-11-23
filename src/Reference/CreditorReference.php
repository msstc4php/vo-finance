<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Reference;

use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentReferenceException;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso7064Mod97;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * ISO 11649 RF creditor reference: `RF`, two check digits and 1 to 21 letters or digits, checked with ISO/IEC 7064
 * MOD 97-10 after moving the first four characters to the end. Blanks are removed and letters, which the standard
 * allows in both cases, are stored in capitals.
 *
 * @see https://www.iso.org/standard/50649.html ISO 11649:2009, clauses 5 and 6
 * @see docs/sources/FINANCE.md "Creditor reference, ISO 11649"
 */
final class CreditorReference extends AbstractStringValueObject
{
    /** The longest stored value: `RF`, two check digits and 21 characters; frozen for 1.x. */
    public const int MAX_LENGTH = 25;

    /**
     * Builds `RF`, the check digits and the body; blanks are removed and letters folded to capitals.
     *
     * @throws InvalidPaymentReferenceException when the body is not 1 to 21 letters or digits
     */
    public static function fromParts(string $body): self
    {
        $body = \strtoupper(\str_replace(' ', '', $body));
        if (\preg_match('/^[A-Z0-9]{1,21}$/D', $body) !== 1) {
            throw new InvalidPaymentReferenceException('A creditor reference body must be 1 to 21 letters or digits.');
        }

        return new self('RF' . Iso7064Mod97::compute($body . 'RF') . $body);
    }

    /**
     * @return non-empty-string two digits
     */
    public function checkDigits(): string
    {
        $digits = \substr($this->value, 2, 2);
        \assert($digits !== '');

        return $digits;
    }

    /**
     * @return non-empty-string the reference after the check digits
     */
    public function body(): string
    {
        $body = \substr($this->value, 4);
        \assert($body !== '');

        return $body;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $compact = \strtoupper(\str_replace(' ', '', $value));

        if (\preg_match('/^RF\d{2}[A-Z0-9]{1,21}$/D', $compact) !== 1) {
            throw new InvalidPaymentReferenceException('A creditor reference must be RF, two check digits and 1 to 21 letters or digits.');
        }

        if (!Iso7064Mod97::isValid(\substr($compact, 4) . \substr($compact, 0, 4))) {
            throw new InvalidPaymentReferenceException('The creditor reference fails its MOD 97-10 check.');
        }

        return $compact;
    }
}
