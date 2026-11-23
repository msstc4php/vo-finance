<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Swiss;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentPayload;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use MSSTC4PHP\ValueObject\Financial\Reference\QrReference;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Swiss QR Code of a QR-bill (SIX Implementation Guidelines 2.3): `SPC`, version `0200`, coding `1`, the CH or LI IBAN
 * of 21 characters, the creditor's structured address, the ultimate creditor (seven empty elements), the amount and
 * currency, the optional ultimate debtor, the reference type and reference, the unstructured message, the trailer
 * `EPD`, then the billing information and up to two alternative procedures: 31 to 34 elements.
 *
 * Enforced: the element limits of Table 8; address type `S` (combined addresses are gone in 2.3); postal code, town and
 * country in a filled address; an amount of at most 9 integer digits without leading zeros and two decimals (0.00 is
 * the "DO NOT USE FOR PAYMENT" notification of section 4.4), currency CHF or EUR; a QR-IBAN (IID 30000 to 31999 at
 * IBAN positions 5 to 9) with `QRR` and a {@see QrReference}, an IBAN with `SCOR` and a {@see CreditorReference} of 5
 * to 25 letters or digits, or `NON` and no reference; message and billing information together at most 140
 * characters; the character set of section 4.1.1. One kind of line separator, LF or CRLF, is used throughout, and every
 * separator ends an element, so a final separator counts as one more (empty) element within the 34; the payload is
 * kept byte for byte. {@see masked()} hides the IBAN, keeps the first character of the creditor's and the ultimate
 * debtor's name and replaces their street, building number, postal code and town with `***`; the address type and
 * the country stay. The free text of a filled unstructured message (`Ustrd`) and of both alternative procedures
 * (`AltPmt`) becomes `***` too. The reference stays. The billing information (`StrdBkgInf`) stays only in the Swico
 * S1 syntax of Annex D (`//S1` and `/<tag>/<value>` groups of the tags 10, 11, 20, 30, 31, 32, 33 and 40 in
 * ascending order, each value in its form), with the free-text invoice number (10) and customer reference (20) as
 * `***`; any other billing text becomes `***` (parsing still accepts it).
 *
 * @see https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf SIX Swiss Implementation Guidelines for the QR-bill 2.3, sections 2.8, 4.1 to 4.4 and Annex A
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "SIX QR-bill layout"
 */
final class QrBill extends AbstractStringValueObject implements PaymentPayload
{
    /**
     * The Swiss QR Code maximum of SIX IG 2.3 section 6.2, 997 characters; frozen for 1.x. The element limits of Table 8
     * keep every accepted payload within it (the longest has 902 characters).
     */
    public const int MAX_LENGTH = 997;

    private const string TYPE = 'QrBill';

    /** Section 4.1.1: Basic Latin, Latin-1 Supplement, Latin Extended-A, Ș ș Ț ț and €. */
    /**
     * Swico S1 tags (SIX IG 2.3 Annex D, tables 28 and 29) with the form of their value; null marks free text. A value
     * escapes `/` and `\` with `\`.
     */
    private const array SWICO_TAGS = [
        '10' => null,
        '11' => '/^\d{6}$/D',
        '20' => null,
        '30' => '/^\d{9}$/D',
        '31' => '/^\d{6}(\d{6})?$/D',
        '32' => '/^[0-9.:;]+$/D',
        '33' => '/^[0-9.:;]+$/D',
        '40' => '/^[0-9.:;]+$/D',
    ];

    private const string SWICO_GROUP = '\/(\d{2})\/((?:[^\/\\\\]|\\\\[\/\\\\])*)';

    private const string CHARACTERS = '/^[\x{20}-\x{7E}\x{A0}-\x{17F}\x{218}-\x{21B}\x{20AC}]*$/Du';

    private TextLines $lines;

    private Iban $iban;

    public function iban(): Iban
    {
        return $this->iban;
    }

    /**
     * Whether the IBAN is a QR-IBAN: its institution identification (positions 5 to 9) is 30000 to 31999.
     */
    public function isQrIban(): bool
    {
        $iid = (int) \substr($this->iban->value, 4, 5);

        return $iid >= 30000 && $iid <= 31999;
    }

    public function creditor(): QrBillAddress
    {
        $address = $this->address(4);
        \assert($address instanceof QrBillAddress);

        return $address;
    }

    public function ultimateDebtor(): ?QrBillAddress
    {
        return $this->address(20);
    }

    /**
     * The amount as written, e.g. "1949.75"; null when the debtor enters it.
     *
     * @return non-empty-string|null
     */
    public function writtenAmount(): ?string
    {
        return $this->optional(18);
    }

    /**
     * Normalised ("50.00" gives 50); null when absent.
     */
    public function amount(): ?Decimal
    {
        $amount = $this->writtenAmount();

        return $amount === null ? null : new Decimal($amount);
    }

    public function currency(): CurrencyCode
    {
        return CurrencyCode::from($this->lines->line(19));
    }

    public function referenceType(): QrBillReferenceType
    {
        return QrBillReferenceType::from($this->lines->line(27));
    }

    /**
     * Null for reference type `NON`.
     */
    public function reference(): QrReference|CreditorReference|null
    {
        return match ($this->referenceType()) {
            QrBillReferenceType::QrReference => new QrReference($this->lines->line(28)),
            QrBillReferenceType::CreditorReference => new CreditorReference($this->lines->line(28)),
            QrBillReferenceType::None => null,
        };
    }

    /**
     * @return non-empty-string|null
     */
    public function unstructuredMessage(): ?string
    {
        return $this->optional(29);
    }

    /**
     * @return non-empty-string|null
     */
    public function billingInformation(): ?string
    {
        return $this->optional(31);
    }

    /**
     * @return list<non-empty-string> the filled alternative procedure parameters, in payload order
     */
    public function alternativeProcedures(): array
    {
        return \array_values(\array_filter([$this->optional(32), $this->optional(33)], static fn (?string $line): bool => $line !== null));
    }

    #[Override]
    public function masked(): string
    {
        $replacements = [3 => Mask::keepEnds($this->iban->value)];
        foreach ([29, 32, 33] as $index) {
            if ($this->optional($index) !== null) {
                $replacements[$index] = Mask::TEXT;
            }
        }

        $billing = $this->billingInformation();
        if ($billing !== null) {
            $replacements[31] = $this->maskedBillingInformation($billing);
        }

        foreach ([4, 20] as $start) {
            foreach ($this->maskedAddress($start) as $index => $line) {
                $replacements[$index] = $line;
            }
        }

        $masked = $this->lines->withLines($replacements);
        \assert($masked !== '');

        return $masked;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $lines = TextLines::parse($value, self::TYPE);
        $count = \count($lines->lines);

        ProfileRule::ensure($lines->isUniform(), self::TYPE, 'one kind of line separator throughout');
        ProfileRule::ensure($count >= 31 && $count <= 34, self::TYPE, '31 to 34 elements');
        foreach ($lines->lines as $line) {
            ProfileRule::ensure(\preg_match(self::CHARACTERS, $line) === 1, self::TYPE, 'the Latin character set of the guidelines');
        }

        ProfileRule::ensure($lines->line(0) === 'SPC', self::TYPE, 'the QR type SPC');
        ProfileRule::ensure($lines->line(1) === '0200', self::TYPE, 'version 0200');
        ProfileRule::ensure($lines->line(2) === '1', self::TYPE, 'coding type 1');

        $account = $lines->line(3);
        $iban = \strlen($account) === 21 && !\str_contains($account, ' ') ? Iban::tryFrom($account) : null;
        if (!$iban instanceof Iban || !\in_array($iban->countryCode(), ['CH', 'LI'], true)) {
            throw new InvalidPaymentQrException(\sprintf('%s requires a valid CH or LI IBAN of 21 characters without spaces.', self::TYPE));
        }

        $this->ensureAddress($lines, 4, true, 'creditor');
        for ($index = 11; $index <= 17; $index++) {
            ProfileRule::ensure($lines->line($index) === '', self::TYPE, 'an empty ultimate creditor');
        }

        ProfileRule::ensure(\preg_match('/^((0|[1-9]\d{0,8})\.\d{2})?$/D', $lines->line(18)) === 1, self::TYPE, 'an amount of up to 999999999.99 with two decimals');
        ProfileRule::ensure(\in_array($lines->line(19), ['CHF', 'EUR'], true), self::TYPE, 'currency CHF or EUR');
        $this->ensureAddress($lines, 20, false, 'ultimate debtor');

        $this->iban = $iban;
        $this->ensureReference($lines->line(27), $lines->line(28));

        ProfileRule::ensure($lines->line(30) === 'EPD', self::TYPE, 'the trailer EPD');
        ProfileRule::ensure(ProfileRule::atMost($lines->line(29) . $lines->line(31), 140), self::TYPE, 'message and billing information of at most 140 characters together');
        ProfileRule::ensure(ProfileRule::atMost($lines->line(32), 100) && ProfileRule::atMost($lines->line(33), 100), self::TYPE, 'alternative procedures of at most 100 characters');

        $this->lines = $lines;

        return $value;
    }

    /**
     * @param non-empty-string $party
     */
    private function ensureAddress(TextLines $lines, int $start, bool $mandatory, string $party): void
    {
        $elements = [];
        for ($index = $start; $index < $start + 7; $index++) {
            $elements[] = $lines->line($index);
        }

        if (!$mandatory && \implode('', $elements) === '') {
            return;
        }

        [$type, $name, $street, $building, $postalCode, $town, $country] = $elements;
        ProfileRule::ensure($type === 'S', self::TYPE, \sprintf('the address type S for the %s', $party));
        ProfileRule::ensure($name !== '' && ProfileRule::atMost($name, 70), self::TYPE, \sprintf('a %s name of 1 to 70 characters', $party));
        ProfileRule::ensure(ProfileRule::atMost($street, 70), self::TYPE, \sprintf('a %s street of at most 70 characters', $party));
        ProfileRule::ensure(ProfileRule::atMost($building, 16), self::TYPE, \sprintf('a %s building number of at most 16 characters', $party));
        ProfileRule::ensure($postalCode !== '' && ProfileRule::atMost($postalCode, 16), self::TYPE, \sprintf('a %s postal code of 1 to 16 characters', $party));
        ProfileRule::ensure($town !== '' && ProfileRule::atMost($town, 35), self::TYPE, \sprintf('a %s town of 1 to 35 characters', $party));
        ProfileRule::ensure(\preg_match('/^[A-Z]{2}$/D', $country) === 1, self::TYPE, \sprintf('a two-letter %s country code', $party));
    }

    private function ensureReference(string $type, string $reference): void
    {
        if ($this->isQrIban()) {
            ProfileRule::ensure($type === 'QRR', self::TYPE, 'reference type QRR with a QR-IBAN');
            ProfileRule::ensure(!\str_contains($reference, ' ') && QrReference::isValid($reference), self::TYPE, 'a valid QR reference of 27 digits');

            return;
        }

        ProfileRule::ensure($type === 'SCOR' || $type === 'NON', self::TYPE, 'reference type SCOR or NON with an IBAN that is not a QR-IBAN');
        if ($type === 'NON') {
            ProfileRule::ensure($reference === '', self::TYPE, 'no reference with reference type NON');

            return;
        }

        ProfileRule::ensure(!\str_contains($reference, ' ') && CreditorReference::isValid($reference), self::TYPE, 'a valid creditor reference of 5 to 25 letters or digits');
    }

    /**
     * @return non-empty-string the Swico S1 billing information with its free-text values as `***`, or `***` for any
     *                          other text
     */
    private function maskedBillingInformation(string $billing): string
    {
        if (\preg_match('/^\/\/S1(?:' . self::SWICO_GROUP . ')+$/D', $billing) !== 1) {
            return Mask::TEXT;
        }

        \preg_match_all('/' . self::SWICO_GROUP . '/', $billing, $groups, \PREG_SET_ORDER);
        $masked = '//S1';
        $previous = '';
        foreach ($groups as [, $tag, $value]) {
            if (!\array_key_exists($tag, self::SWICO_TAGS) || $tag <= $previous) {
                return Mask::TEXT;
            }

            $form = self::SWICO_TAGS[$tag];
            if ($form !== null && $value !== '' && \preg_match($form, $value) !== 1) {
                return Mask::TEXT;
            }

            $masked .= '/' . $tag . '/' . ($form === null ? Mask::text($value) : $value);
            $previous = $tag;
        }

        return $masked;
    }

    /**
     * @return array<int, string> the name with its first character kept and the filled street, building number,
     *                            postal code and town as `***`, by line index; empty for an absent address
     */
    private function maskedAddress(int $start): array
    {
        $name = $this->optional($start + 1);
        if ($name === null) {
            return [];
        }

        $masked = [$start + 1 => Mask::name($name)];
        foreach ([2, 3, 4, 5] as $offset) {
            if ($this->optional($start + $offset) !== null) {
                $masked[$start + $offset] = Mask::ADDRESS;
            }
        }

        return $masked;
    }

    private function address(int $start): ?QrBillAddress
    {
        $name = $this->optional($start + 1);
        $postalCode = $this->optional($start + 4);
        $town = $this->optional($start + 5);
        $country = $this->optional($start + 6);
        if ($name === null || $postalCode === null || $town === null || $country === null) {
            return null;
        }

        return new QrBillAddress($name, $this->optional($start + 2), $this->optional($start + 3), $postalCode, $town, $country);
    }

    /**
     * @return non-empty-string|null
     */
    private function optional(int $index): ?string
    {
        $line = $this->lines->line($index);

        return $line === '' ? null : $line;
    }
}
