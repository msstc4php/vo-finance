<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Card;

use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentCardNumberException;
use MSSTC4PHP\ValueObject\Financial\Internal\IinRanges;
use MSSTC4PHP\ValueObject\Financial\Internal\Luhn;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Primary account number (ISO/IEC 7812-1:2017): 10 to 19 digits ending in a Luhn check digit. Spaces and hyphens are
 * stripped. A length outside the published lengths of the decoded scheme is not refused, because schemes add lengths;
 * {@see matchesSchemeLength()} reports it. {@see masked()} keeps at most the first six and the last four digits, the
 * most PCI DSS 4.0 requirement 3.4.1 allows, and always hides at least one digit.
 *
 * @see https://cdn.standards.iteh.ai/samples/70484/1b747b382337445fbbdae5584d268bcc/ISO-IEC-7812-1-2017.pdf ISO/IEC 7812-1:2017, IIN of 8 digits, PAN of 10 to 19 digits (preview)
 * @see https://www.pcisecuritystandards.org/faqs/1492/ PCI SSC FAQ 1492, requirement 3.4.1
 * @see docs/sources/FINANCE.md "ISO/IEC 7812-1, IIN and PAN", "Card IIN publications"
 */
final class PaymentCardNumber extends AbstractStringValueObject implements Maskable
{
    private const string TYPE = 'PaymentCardNumber';

    /**
     * @return numeric-string eight digits
     */
    public function iin(): string
    {
        $iin = \substr($this->value, 0, 8);
        \assert(\is_numeric($iin));

        return $iin;
    }

    /**
     * @return numeric-string four digits
     */
    public function last4(): string
    {
        $last4 = \substr($this->value, -4);
        \assert(\is_numeric($last4));

        return $last4;
    }

    /**
     * Several schemes for a co-badged or overlapping range; empty when no listed range holds the IIN.
     *
     * @return list<CardScheme>
     */
    public function schemes(): array
    {
        return IinRanges::default()->schemes($this->iin());
    }

    /**
     * Whether the length is published for at least one decoded scheme; false when no scheme is decoded.
     */
    public function matchesSchemeLength(): bool
    {
        return array_any($this->schemes(), fn (CardScheme $scheme): bool => \in_array(\strlen($this->value), $scheme->lengths(), true));
    }

    #[Override]
    public function masked(): string
    {
        $length = \strlen($this->value);
        $head = \min(6, $length - 5);

        $masked = \substr($this->value, 0, $head) . \str_repeat('*', $length - $head - 4) . $this->last4();
        \assert($masked !== '');

        return $masked;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $digits = \str_replace([' ', '-'], '', $value);

        if (\preg_match('/^\d{10,19}$/D', $digits) !== 1) {
            throw new InvalidPaymentCardNumberException(\sprintf('%s must have ten to nineteen digits.', self::TYPE));
        }

        if (!Luhn::isValid($digits)) {
            throw new InvalidPaymentCardNumberException(\sprintf('%s has a wrong check digit.', self::TYPE));
        }

        return $digits;
    }
}
