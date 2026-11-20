<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\ReparsesOnUnserialize;
use MSSTC4PHP\ValueObject\Financial\Internal\RussianAccountKey;
use MSSTC4PHP\ValueObject\Financial\Internal\Transcoder;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Payment payload of GOST R 56042-2014: the eight-byte service block `ST`, version `0001`, the character set digit (`1`
 * WIN1251, `2` UTF-8, `3` KOI8-R) and the separator, then `alias=value` pairs. The first five are, in this order,
 * `Name` (1 to 160 characters), `PersonalAcc` (20 digits), `BankName` (1 to 45), `BIC` (9 digits) and `CorrespAcc` (up
 * to 20 digits, `0` when the bank has none); further pairs follow. Aliases are Latin letters, digits and `_`, compared
 * case-insensitively; a value is everything after the first `=`; of repeated aliases the last counts, as the standard
 * says, and every occurrence of a mandatory alias must have its format. A 20-digit account carries the Bank of Russia
 * control key against the BIC within the scope of Bank of Russia Order 515 (not a treasury account starting with 0),
 * checked on every occurrence of `PersonalAcc` and `CorrespAcc` against the BIC that counts (the last). The separator
 * must be one ASCII graphic character (the block is eight bytes in UTF-8 too). At most 7089 characters, the largest
 * QR content: a bound against abusive input.
 *
 * {@see __construct()} takes UTF-8 text: a declared set `1` or `3` is accepted only for ASCII text; other text goes
 * through {@see fromBytes()}, which transcodes it and rewrites the character set to `2`. {@see masked()} hides the
 * accounts and identifiers of Annex V: the payee's account (`PersonalAcc`), the payer's account with the provider
 * (`PersAcc`), the budget recipient's personal account (`PersonalAccount`), and the identifiers of the payer as a
 * person: the SNILS (`PensAcc`), the identity document number (`PayerIdNum`) and the taxpayer number (`PayerINN`).
 *
 * @see https://files.stroyinf.ru/Data2/1/4293771/4293771168.htm GOST R 56042-2014 (as quoted), sections 5.2 to 5.5, Annexes V and D
 * @see https://pravo.ppt.ru/poryadok/98817 Bank of Russia, Order 515 of 08.09.1997 (as quoted)
 * @see docs/sources/FINANCE.md "GOST R 56042-2014 and the Bank of Russia account key"
 */
final class Gost56042Payload extends AbstractStringValueObject implements Maskable
{
    use ReparsesOnUnserialize;

    private const string TYPE = 'Gost56042Payload';

    private const array ENCODINGS = ['1' => 'Windows-1251', '3' => 'KOI8-R'];

    /** The mandatory block in its order, lowercased, with the format of each value. */
    private const array MANDATORY = [
        'name' => '/^.{1,160}$/Dsu',
        'personalacc' => '/^\d{20}$/D',
        'bankname' => '/^.{1,45}$/Dsu',
        'bic' => '/^\d{9}$/D',
        'correspacc' => '/^\d{1,20}$/D',
    ];

    private const array MASKED = ['personalacc', 'persacc', 'personalaccount', 'pensacc', 'payeridnum', 'payerinn'];

    /** @var non-empty-list<array{non-empty-string, string}> */
    private array $pairs;

    /**
     * Accepts a payload in its declared character set; WIN1251 and KOI8-R text is transcoded to UTF-8 and the
     * character set digit becomes `2`, so the stored bytes differ from the scanned ones in that digit.
     *
     * @throws InvalidPaymentQrException
     * @throws LogicException without ext-mbstring, for character sets 1 and 3
     */
    public static function fromBytes(string $bytes): self
    {
        if (\preg_match('/^ST0001([123])/', $bytes, $match) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s requires the service block ST0001 and a character set 1 to 3.', self::TYPE));
        }

        if ($match[1] === '2') {
            return new self($bytes);
        }

        PayloadBound::ensureSingleByte($bytes, self::TYPE);

        return new self(\substr_replace(Transcoder::toUtf8($bytes, self::ENCODINGS[$match[1]], self::TYPE), '2', 6, 1));
    }

    /**
     * @return int<1, 3>
     */
    public function characterSet(): int
    {
        $code = (int) $this->value[6];
        \assert($code >= 1 && $code <= 3);

        return $code;
    }

    /**
     * @return non-empty-string
     */
    public function separator(): string
    {
        return $this->value[7];
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->mandatory('Name');
    }

    /**
     * @return non-empty-string 20 digits
     */
    public function personalAccount(): string
    {
        return $this->mandatory('PersonalAcc');
    }

    /**
     * @return non-empty-string
     */
    public function bankName(): string
    {
        return $this->mandatory('BankName');
    }

    /**
     * @return non-empty-string 9 digits
     */
    public function bic(): string
    {
        return $this->mandatory('BIC');
    }

    /**
     * @return non-empty-string up to 20 digits; `0` when the bank has no correspondent account
     */
    public function correspondentAccount(): string
    {
        return $this->mandatory('CorrespAcc');
    }

    /**
     * The value of the last pair with this alias, compared case-insensitively; null when absent.
     *
     * @return string|null empty when the pair is written `Alias=`
     */
    public function get(string $alias): ?string
    {
        $found = null;
        foreach ($this->pairs as [$current, $value]) {
            if (\strcasecmp($current, $alias) === 0) {
                $found = $value;
            }
        }

        return $found;
    }

    #[Override]
    public function masked(): string
    {
        $parts = [];
        foreach ($this->pairs as [$alias, $value]) {
            $parts[] = $alias . '=' . (\in_array(\strtolower($alias), self::MASKED, true) ? Mask::keepEnds($value) : $value);
        }

        return \substr($this->value, 0, 8) . \implode($this->separator(), $parts);
    }

    #[Override]
    protected function parse(string $value): string
    {
        PayloadBound::ensure($value, self::TYPE);
        if (\preg_match('//u', $value) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s must be valid UTF-8.', self::TYPE));
        }

        ProfileRule::ensure(\preg_match('/^ST0001[123][\x21-\x7E]/', $value) === 1, self::TYPE, 'the service block ST0001, a character set 1 to 3 and an ASCII graphic separator');
        if ($value[6] !== '2' && \preg_match('/[^\x00-\x7F]/', $value) === 1) {
            throw new InvalidPaymentQrException(\sprintf('%s takes text declared in a character set other than UTF-8 only through fromBytes().', self::TYPE));
        }

        $separator = $value[7];
        $pairs = [];
        foreach (\explode($separator, \substr($value, 8)) as $pair) {
            $parts = \explode('=', $pair, 2);
            ProfileRule::ensure(\count($parts) === 2 && \preg_match('/^[A-Za-z0-9_]+$/D', $parts[0]) === 1, self::TYPE, 'alias=value pairs with aliases of Latin letters, digits and underscores');
            [$alias, $content] = $parts;
            \assert($alias !== '');
            $pairs[] = [$alias, $content];
        }

        $aliases = \array_keys(self::MANDATORY);
        foreach ($aliases as $position => $alias) {
            ProfileRule::ensure(\strtolower($pairs[$position][0] ?? '') === $alias, self::TYPE, 'the mandatory Name, PersonalAcc, BankName, BIC and CorrespAcc in this order');
        }

        $this->pairs = $pairs;
        foreach ($pairs as [$alias, $content]) {
            $format = self::MANDATORY[\strtolower($alias)] ?? null;
            ProfileRule::ensure($format === null || \preg_match($format, $content) === 1, self::TYPE, 'the formats of the mandatory requisites');
        }

        $bic = $this->bic();
        foreach ($pairs as [$alias, $content]) {
            if (\in_array(\strtolower($alias), ['personalacc', 'correspacc'], true) && RussianAccountKey::isInScope($content)) {
                ProfileRule::ensure(RussianAccountKey::isValid($content, $bic), self::TYPE, 'a correct control key of each account in the scope of Bank of Russia Order 515 against the BIC');
            }
        }

        return $value;
    }

    /**
     * @return non-empty-string
     */
    private function mandatory(string $alias): string
    {
        $value = $this->get($alias);
        \assert($value !== null && $value !== '');

        return $value;
    }
}
