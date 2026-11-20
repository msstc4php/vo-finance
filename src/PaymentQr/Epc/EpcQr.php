<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Epc;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Finance\Bic;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Iso8859;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\ReparsesOnUnserialize;
use MSSTC4PHP\ValueObject\Financial\Internal\TextLines;
use MSSTC4PHP\ValueObject\Financial\Internal\Transcoder;
use MSSTC4PHP\ValueObject\Financial\Reference\CreditorReference;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * EPC QR code for a SEPA credit transfer (EPC069-12, versions `001` and `002`): service tag `BCD`, version, character
 * set `1` to `8`, `SCT`, the BIC (mandatory in `001`), the beneficiary name (1 to 70), the IBAN, the amount `EUR` with
 * 0.01 to 999999999.99, the purpose (1 to 4), then the structured reference (1 to 35) or the unstructured text (1 to
 * 140), never both, and the beneficiary to originator information (1 to 70): 7 to 12 elements, separated by LF or CRLF,
 * the last one populated and not followed by a separator. At most 331 bytes; a payload longer in UTF-8 whose every
 * character has one byte in one of the ISO 8859 sets of section 2.1 is held to 331 characters instead, since the
 * transcoded form of {@see fromBytes()} input must still parse (checked by `Internal\Iso8859`, no extension needed).
 *
 * The payload is kept byte for byte, its separators included. {@see __construct()} takes
 * UTF-8 text: a declared set other than `1` is accepted only for ASCII text, whose bytes are the same in every set;
 * other text goes through {@see fromBytes()}, which transcodes it and rewrites the character set to `1`.
 * {@see masked()} hides the IBAN.
 *
 * @see https://www.europeanpaymentscouncil.eu/sites/default/files/kb/file/2022-09/EPC069-12%20v3.0%20Quick%20Response%20Code%20-%20Guidelines%20to%20Enable%20the%20Data%20Capture%20for%20the%20Initiation%20of%20an%20SCT_0.pdf EPC069-12 v3.0, sections 2.1 to 2.3
 * @see docs/sources/FINANCE.md "EPC069-12"
 */
final class EpcQr extends AbstractStringValueObject implements Maskable
{
    use ReparsesOnUnserialize;

    private const string TYPE = 'EpcQr';

    private const int MAX_LENGTH = 331;

    /** The character sets of section 2.1 as mbstring names; `1` is UTF-8. */
    private const array ENCODINGS = [
        '2' => 'ISO-8859-1',
        '3' => 'ISO-8859-2',
        '4' => 'ISO-8859-4',
        '5' => 'ISO-8859-5',
        '6' => 'ISO-8859-7',
        '7' => 'ISO-8859-10',
        '8' => 'ISO-8859-15',
    ];

    private TextLines $lines;

    private Iban $iban;

    /**
     * Accepts a payload in its declared character set; text in sets `2` to `8` is transcoded to UTF-8 and the
     * character set element becomes `1`, so the stored bytes differ from the scanned ones in that element.
     *
     * @throws InvalidPaymentQrException
     * @throws LogicException without ext-mbstring, for character sets 2 to 8
     */
    public static function fromBytes(string $bytes): self
    {
        if (\strlen($bytes) > self::MAX_LENGTH) {
            throw new InvalidPaymentQrException(\sprintf('%s is limited to %d bytes.', self::TYPE, self::MAX_LENGTH));
        }

        if (\preg_match('/^BCD\r?\n\d{3}\r?\n([1-8])\r?\n/', $bytes, $match, \PREG_OFFSET_CAPTURE) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s requires the header BCD, a version and a character set 1 to 8.', self::TYPE));
        }

        [$code, $offset] = $match[1];
        if ($code === '1') {
            return new self($bytes);
        }

        return new self(\substr_replace(Transcoder::toUtf8($bytes, self::ENCODINGS[$code], self::TYPE), '1', $offset, 1));
    }

    /**
     * @return '001'|'002'
     */
    public function version(): string
    {
        return $this->lines->line(1) === '001' ? '001' : '002';
    }

    /**
     * @return int<1, 8>
     */
    public function characterSet(): int
    {
        $code = (int) $this->lines->line(2);
        \assert($code >= 1 && $code <= 8);

        return $code;
    }

    public function bic(): ?Bic
    {
        return Bic::tryFrom($this->lines->line(4));
    }

    /**
     * @return non-empty-string
     */
    public function beneficiaryName(): string
    {
        $name = $this->lines->line(5);
        \assert($name !== '');

        return $name;
    }

    public function iban(): Iban
    {
        return $this->iban;
    }

    /**
     * Null when the amount is absent. It may carry more than two decimals (`EUR1.999`): the guidelines bound the amount
     * but give no number of decimals.
     */
    public function amount(): ?Decimal
    {
        $amount = $this->lines->line(7);

        return $amount === '' ? null : new Decimal(\substr($amount, 3));
    }

    /**
     * @return non-empty-string|null
     */
    public function purpose(): ?string
    {
        return $this->optional(8);
    }

    /**
     * The structured remittance information as written.
     *
     * @return non-empty-string|null
     */
    public function reference(): ?string
    {
        return $this->optional(9);
    }

    /**
     * The reference as an ISO 11649 creditor reference; null when absent or not one ("ISO 11649 RF Creditor
     * Reference may be used").
     */
    public function creditorReference(): ?CreditorReference
    {
        $reference = $this->reference();

        return $reference === null ? null : CreditorReference::tryFrom($reference);
    }

    /**
     * The unstructured remittance information.
     *
     * @return non-empty-string|null
     */
    public function remittanceText(): ?string
    {
        return $this->optional(10);
    }

    /**
     * The beneficiary to originator information.
     *
     * @return non-empty-string|null
     */
    public function information(): ?string
    {
        return $this->optional(11);
    }

    #[Override]
    public function masked(): string
    {
        $masked = $this->lines->withLine(6, Mask::keepEnds($this->iban->value));
        \assert($masked !== '');

        return $masked;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $lines = TextLines::parse($value, self::TYPE);
        $count = \count($lines->lines);

        ProfileRule::ensure($this->withinSize($value), self::TYPE, 'at most 331 bytes, or 331 characters of one ISO 8859 set of section 2.1');
        ProfileRule::ensure($count >= 7 && $count <= 12, self::TYPE, '7 to 12 elements');
        ProfileRule::ensure($lines->line($count - 1) !== '', self::TYPE, 'a populated last element not followed by a separator');
        ProfileRule::ensure($lines->line(0) === 'BCD', self::TYPE, 'the service tag BCD');
        ProfileRule::ensure(\in_array($lines->line(1), ['001', '002'], true), self::TYPE, 'version 001 or 002');
        ProfileRule::ensure(\preg_match('/^[1-8]$/D', $lines->line(2)) === 1, self::TYPE, 'a character set 1 to 8');
        if ($lines->line(2) !== '1' && \preg_match('/[^\x00-\x7F]/', $value) === 1) {
            throw new InvalidPaymentQrException(\sprintf('%s takes text declared in a character set other than UTF-8 only through fromBytes().', self::TYPE));
        }

        ProfileRule::ensure($lines->line(3) === 'SCT', self::TYPE, 'the identification code SCT');
        $bic = $lines->line(4);
        ProfileRule::ensure($bic === '' ? $lines->line(1) === '002' : Bic::isValid($bic), self::TYPE, 'a BIC of 8 or 11 characters, which version 001 makes mandatory');
        ProfileRule::ensure($this->filled($lines->line(5), 70), self::TYPE, 'a beneficiary name of 1 to 70 characters');
        $iban = Iban::tryFrom($lines->line(6)) ?? throw new InvalidPaymentQrException(\sprintf('%s requires a valid IBAN.', self::TYPE));
        ProfileRule::ensure($this->isAmount($lines->line(7)), self::TYPE, 'an amount EUR 0.01 to 999999999.99 of at most 12 digits and a decimal point');
        ProfileRule::ensure(ProfileRule::atMost($lines->line(8), 4), self::TYPE, 'a purpose of at most 4 characters');
        ProfileRule::ensure(ProfileRule::atMost($lines->line(9), 35), self::TYPE, 'a reference of at most 35 characters');
        ProfileRule::ensure(ProfileRule::atMost($lines->line(10), 140), self::TYPE, 'a remittance text of at most 140 characters');
        ProfileRule::ensure($lines->line(9) === '' || $lines->line(10) === '', self::TYPE, 'a reference or a remittance text, not both');
        ProfileRule::ensure(ProfileRule::atMost($lines->line(11), 70), self::TYPE, 'information of at most 70 characters');

        $this->lines = $lines;
        $this->iban = $iban;

        return $value;
    }

    /**
     * @return non-empty-string|null
     */
    private function optional(int $index): ?string
    {
        $line = $this->lines->line($index);

        return $line === '' ? null : $line;
    }

    /**
     * @param positive-int $max
     */
    private function filled(string $line, int $max): bool
    {
        return $line !== '' && ProfileRule::atMost($line, $max);
    }

    private function withinSize(string $value): bool
    {
        return \strlen($value) <= self::MAX_LENGTH
            || (\preg_match_all('/./su', $value) <= self::MAX_LENGTH && Iso8859::fitsOneSet($value));
    }

    /**
     * Within 0.01 and 999999999.99: at most 12 digits and decimal point, a fraction of 0.01 or more when the integer
     * part is zero, and at most nine integer digits (twelve characters leave no room for a larger fraction).
     */
    private function isAmount(string $line): bool
    {
        if ($line === '') {
            return true;
        }

        if (\strlen($line) > 15 || \preg_match('/^EUR(\d+)(?:\.(\d+))?$/D', $line, $match) !== 1) {
            return false;
        }

        $integer = \ltrim($match[1], '0');
        $cents = \substr($match[2] ?? '', 0, 2);

        return \strlen($integer) <= 9 && ($integer !== '' || \trim($cents, '0') !== '');
    }
}
