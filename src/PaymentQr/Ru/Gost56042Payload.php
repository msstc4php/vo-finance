<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Ru;

use LogicException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\RussianAccountKey;
use MSSTC4PHP\ValueObject\Financial\Internal\Transcoder;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentPayload;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Payment payload of GOST R 56042-2014: the eight-byte service block `ST`, version `0001`, the character set digit (`1`
 * WIN1251, `2` UTF-8, `3` KOI8-R) and the separator, then `alias=value` pairs. The first five are, in this order,
 * `Name` (1 to 160 characters), `PersonalAcc` (20 digits, or a Latin letter of the Bank of Russia album in the sixth
 * place), `BankName` (1 to 45), `BIC` (9 digits) and `CorrespAcc` (up to 20 digits, `0` when the bank has none, or 20
 * characters with that letter); further pairs follow. Aliases are Latin letters, digits and `_`, compared
 * case-insensitively; a value is everything after the first `=`; of repeated aliases the last counts, as the standard
 * says, and every occurrence of a mandatory alias must have its format. A 20-digit account carries the Bank of Russia
 * control key against the BIC within the scope of Bank of Russia Order 515 (not a treasury account starting with 0, and
 * not an account with a letter, whose key needs a letter table not reached), checked on every occurrence of
 * `PersonalAcc` and `CorrespAcc` against the BIC that counts (the last). The separator must be one ASCII graphic
 * character (the block is eight bytes in UTF-8 too). At most 7089 characters, the largest QR content: a bound against
 * abusive input.
 *
 * {@see __construct()} takes UTF-8 text: a declared set `1` or `3` is accepted only for ASCII text; other text goes
 * through {@see fromBytes()}, which transcodes it and rewrites the character set to `2`. {@see masked()} is fail-closed
 * over the aliases of Annex V table В.1. Accounts and identifiers keep their ends: `PersonalAcc`, `PersAcc`,
 * `PersonalAccount`, `PensAcc` (SNILS), `PayerIdNum`, `PayerINN`, `Phone`, `CounterId`, `ExecId` and `RuleId`. Names
 * keep their first character: `Name`, `BankName`, `LastName`, `FirstName`, `MiddleName`, `ChildFio` and `SpecFio`. The
 * payee's document, bill and accrual references `DocNo`, `DocIdx`, `QuittId` and `UIN` keep their first and last
 * character. Codes, dates and amounts stay only in their shape, otherwise `***`: `BIC` (nine digits, checked by parse),
 * `CorrespAcc` and `CBC` (`0` or 20 digits), `Sum` and `AddAmount` (up to 18 digits), `PayeeINN` (10 digits),
 * `DrawerStatus` (a code of the УФЭБС field 101 list), `PayerIdType` and `TechCode` (two digits), `KPP` (four digits,
 * two digits or capitals, three digits), `OKTMO` (`0` or 8 digits), `PaytReason` (`0` or a code of the УФЭБС field 106
 * list), `TaxPaytKind` (one or two digits or capitals), `TaxPeriod` (`0`, a date or a period such as `МС.02.2014`),
 * `DocDate`, `PaymTerm` and `QuittDate` (`0` or `DD.MM.YYYY`), `PaymPeriod` (`MMYYYY` or `MM.YYYY`), `CounterVal` (a
 * reading) and `RegType` (up to four digits). Every other alias of table В.1 becomes `***`: what locates a person
 * (`PayerAddress`, `Flat`, `InstNum`, `ClassNum`), the birth date and free text (`BirthDate`, `Purpose`, `Contract`,
 * `Category`, `ServiceName`). An alias outside table В.1 is author-chosen text in its name as in its value and becomes
 * `***=***`. An empty value stays empty. {@see hasValidBudgetPaymentDetails()} reports the УФЭБС checks of budget
 * payments; it never refuses.
 *
 * @see https://files.stroyinf.ru/Data2/1/4293771/4293771168.htm GOST R 56042-2014 (as quoted), sections 5.2 to 5.5, Annexes V and D
 * @see https://pravo.ppt.ru/poryadok/98817 Bank of Russia, Order 515 of 08.09.1997 (as quoted)
 * @see https://cbr.ru/development/formats/ Bank of Russia, УФЭБС 2026.09: XSD leaf types and "Проверки в ПС БР платежей в бюджет (с 21.09.2026)", section 1
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "GOST R 56042-2014 and the Bank of Russia account key"
 */
final class Gost56042Payload extends AbstractStringValueObject implements PaymentPayload
{
    /** The longest stored payload, in characters: the abuse bound of 7089; frozen for 1.x. */
    public const int MAX_LENGTH = PayloadBound::MAX_CHARACTERS;

    private const string TYPE = 'Gost56042Payload';

    private const array ENCODINGS = ['1' => 'Windows-1251', '3' => 'KOI8-R'];

    /** The mandatory block in its order, lowercased, with the format of each value. */
    private const array MANDATORY = [
        'name' => '/^.{1,160}$/Dsu',
        'personalacc' => '/^\d{5}[0-9ABCEHKMPTX]\d{14}$/D',
        'bankname' => '/^.{1,45}$/Dsu',
        'bic' => '/^\d{9}$/D',
        'correspacc' => '/^(\d{1,20}|\d{5}[ABCEHKMPTX]\d{14})$/D',
    ];

    private const array MASKED = ['personalacc', 'persacc', 'personalaccount', 'pensacc', 'payeridnum', 'payerinn', 'phone', 'counterid', 'execid', 'ruleid'];

    private const array NAMES = ['name', 'bankname', 'lastname', 'firstname', 'middlename', 'childfio', 'specfio'];

    private const array REFERENCES = ['docno', 'docidx', 'quittid', 'uin'];

    /** УФЭБС budget checks 1.3: the payer statuses of field 101. */
    private const array DRAWER_STATUSES = ['01', '03', '04', '05', '06', '07', '08', '13', '15', '16', '17', '19', '20', '23', '24', '27', '28', '29', '30', '31', '32', '33', '34'];

    /** УФЭБС budget checks 1.11: the payment reasons of field 106. */
    private const array PAYMENT_REASONS = ['0', 'ПК', 'КЭ', 'УВ', 'ИЛ', 'ПБ', 'ПР', 'ТГ', 'ТБ', 'ТД', 'ПВ', 'ИН', '00'];

    private const string DATE = '/^(0|\d{2}\.\d{2}\.\d{4})$/D';

    /**
     * The code, date and amount aliases of table В.1 with the shape a visible value must have: a pattern, a code list,
     * or null when parse checks it.
     */
    private const array SHAPES = [
        'bic' => null,
        'correspacc' => '/^(0|\d{20})$/D',
        'cbc' => '/^(0|\d{20})$/D',
        'sum' => '/^\d{1,18}$/D',
        'addamount' => '/^\d{1,18}$/D',
        'payeeinn' => '/^\d{10}$/D',
        'drawerstatus' => self::DRAWER_STATUSES,
        'payeridtype' => '/^\d{2}$/D',
        'techcode' => '/^\d{2}$/D',
        'kpp' => '/^\d{4}[0-9A-Z]{2}\d{3}$/D',
        'oktmo' => '/^(0|\d{8})$/D',
        'paytreason' => self::PAYMENT_REASONS,
        'taxpaytkind' => '/^[0-9A-ZА-Я]{1,2}$/Du',
        'taxperiod' => '/^(0|[0-9A-ZА-Я]{2}\.\d{2}\.\d{4}|\d{2}\.\d{2}\.\d{4})$/Du',
        'docdate' => self::DATE,
        'paymterm' => self::DATE,
        'quittdate' => self::DATE,
        'paymperiod' => '/^\d{2}\.?\d{4}$/D',
        'counterval' => '/^\d{1,15}([.,]\d{1,6})?$/D',
        'regtype' => '/^\d{1,4}$/D',
    ];

    /** The remaining aliases of table В.1: known names whose values are always masked. */
    private const array HIDDEN = ['payeraddress', 'flat', 'instnum', 'classnum', 'birthdate', 'purpose', 'contract', 'category', 'servicename'];

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
     * @return non-empty-string 20 digits, or 20 characters with a Latin letter in the sixth place
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
     * The Russian bank identification code (BIK, alias `BIC`), not an ISO 9362 BIC.
     *
     * @return non-empty-string 9 digits
     */
    public function bik(): string
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
     * The value of the last pair with this alias, compared case-insensitively; null when absent or written `Alias=`.
     *
     * @return non-empty-string|null
     */
    public function get(string $alias): ?string
    {
        $found = null;
        foreach ($this->pairs as [$current, $value]) {
            if (\strcasecmp($current, $alias) === 0) {
                $found = $value;
            }
        }

        return $found === '' ? null : $found;
    }

    /**
     * Whether the requisites of a budget payment meet the УФЭБС 2026.09 checks of section 1 (from 21.09.2026), read
     * field by field: `DrawerStatus` (101) one of the codes of 1.3, `PayeeINN` 10 digits not starting with `00` (1.6),
     * `KPP` (103) 9 characters not starting with `00` (1.8), `CBC` (104) `0` or 20 digits not all zero (1.9), `OKTMO`
     * (105) `0` or 8 digits not all zero (1.10), `PaytReason` (106) `0` or a code of 1.11 and `TaxPeriod` (107, now the
     * customs authority code) `0` or 8 digits (1.12), each present; `DocNo` (108) of 1 to 15 characters (1.13) and
     * `PayerINN` `0`, 5 digits not all zero, or 10 or 12 digits not starting with `00` (1.5) when present, since the
     * payer's bank may fill them. The checks that depend on the payee's account and on combinations of fields are not
     * applied. Report only: a payload that fails is still accepted.
     */
    public function hasValidBudgetPaymentDetails(): bool
    {
        $optional = ['DocNo' => '/^.{1,15}$/Du', 'PayerINN' => '/^(0|(?!0{5})\d{5}|(?!00)\d{10}|(?!00)\d{12})$/D'];
        foreach ($optional as $alias => $shape) {
            $value = $this->get($alias);
            if ($value !== null && \preg_match($shape, $value) !== 1) {
                return false;
            }
        }

        $required = [
            'DrawerStatus' => self::DRAWER_STATUSES,
            'PayeeINN' => '/^(?!00)\d{10}$/D',
            'KPP' => '/^(?!00).{9}$/Du',
            'CBC' => '/^(0|(?!0{20})\d{20})$/D',
            'OKTMO' => '/^(0|(?!0{8})\d{8})$/D',
            'PaytReason' => self::PAYMENT_REASONS,
            'TaxPeriod' => '/^(0|\d{8})$/D',
        ];

        return \array_all($required, fn (array|string $shape, string $alias): bool => $this->hasShape($this->get($alias), $shape));
    }

    #[Override]
    public function masked(): string
    {
        $parts = [];
        foreach ($this->pairs as [$alias, $value]) {
            $parts[] = $this->maskPair($alias, $value);
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

        $bic = $this->bik();
        foreach ($pairs as [$alias, $content]) {
            if (\in_array(\strtolower($alias), ['personalacc', 'correspacc'], true) && RussianAccountKey::isInScope($content)) {
                ProfileRule::ensure(RussianAccountKey::isValid($content, $bic), self::TYPE, 'a correct control key of each account in the scope of Bank of Russia Order 515 against the BIC');
            }
        }

        return $value;
    }

    private function maskPair(string $alias, string $value): string
    {
        $known = \strtolower($alias);
        $shape = self::SHAPES[$known] ?? null;

        return match (true) {
            \in_array($known, self::MASKED, true) => $alias . '=' . ($value === '' ? '' : Mask::keepEnds($value)),
            \in_array($known, self::NAMES, true) => $alias . '=' . ($value === '' ? '' : Mask::name($value)),
            \in_array($known, self::REFERENCES, true) => $alias . '=' . Mask::reference($value),
            \array_key_exists($known, self::SHAPES) => $alias . '=' . ($this->hasShape($value, $shape) ? $value : Mask::text($value)),
            \in_array($known, self::HIDDEN, true) => $alias . '=' . Mask::text($value),
            default => Mask::TEXT . '=' . Mask::text($value),
        };
    }

    /**
     * @param list<string>|string|null $shape a pattern or a code list; null accepts any value
     */
    private function hasShape(?string $value, array|string|null $shape): bool
    {
        return match (true) {
            $value === null => false,
            $shape === null => true,
            \is_array($shape) => \in_array($value, $shape, true),
            default => \preg_match($shape, $value) === 1,
        };
    }

    /**
     * @return non-empty-string
     */
    private function mandatory(string $alias): string
    {
        $value = $this->get($alias);
        \assert($value !== null);

        return $value;
    }
}
