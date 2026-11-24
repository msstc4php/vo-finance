<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\India;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\Mask;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\QueryString;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentPayload;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * UPI payment URI `upi://pay?` with its query parameters, the deep-linking URL of NPCI's UPI Linking Specifications
 * 1.5.1.
 *
 * Enforced from NPCI's circular OC 18 (which quotes that specification): the payee address `pa` and the payee name `pn`
 * are present and filled, and a transaction reference `tr` has 1 to 35 characters; no other parameter, the VPA shape or
 * the amount format is checked. Values are kept as written: 1.5.1 writes a space as `%20` and requires PSPs to read the
 * legacy bare `%` too, so nothing is percent-decoded. A repeated parameter makes its single-value accessor throw.
 * {@see masked()} rebuilds the query fail-closed: the payee address `pa` keeps the first character of its local part
 * and its handle (`anna@okaxis` gives `a`, `***` and `@okaxis`; `***` without a handle of letters, digits, `.` and
 * `-`), the payee name `pn` its first character, the transaction reference `tr` and the transaction ID `tid` their
 * first and last character, and the note `tn` and `url` become `***`, as do `mode`, `purpose`, `orgid`, `qrMedium` and
 * `sign`, which 1.5.1 does not define; the codes `am` and `mam` (digits with up to two decimals), `cu` (`INR`, the one
 * value 1.5.1 allows) and `mc` (four digits) stay only in that shape, otherwise `***`. Any other pair, a name that
 * differs in case included, becomes `***=***`, `***=` when empty, and every pair without `=` becomes `***`; an empty
 * value of a known parameter and an empty pair stay empty. At most 7089 characters, the largest QR content: a bound
 * against abusive input.
 *
 * @see https://web.archive.org/web/20190917070533id_/https://www.npci.org.in/sites/all/themes/npcl/images/PDF/UPI_Linking_Specs_ver_1.5.1.pdf NPCI, UPI Linking Specifications 1.5.1, section 1.2 and notes
 * @see https://www.npci.org.in/PDF/npci/upi/circular/2017/Circular18_BankCompliances_to_enbaleUPIMerchantecosystem_0.pdf NPCI, UPI OC No. 18/2016-17, Annexure A Table 1 and D.7
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "NPCI UPI linking specification"
 */
final class UpiPaymentUri extends AbstractStringValueObject implements PaymentPayload
{
    /** The longest stored payload, in characters: the abuse bound of 7089; frozen for 1.x. */
    public const int MAX_LENGTH = PayloadBound::MAX_CHARACTERS;

    private const string TYPE = 'UpiPaymentUri';

    private const string AMOUNT = '/^\d{1,18}(\.\d{1,2})?$/D';

    /** The code fields of the NPCI deep-linking parameters kept visible, with the shape a value must have. */
    private const array VISIBLE_PARAMETERS = [
        'am' => self::AMOUNT,
        'mam' => self::AMOUNT,
        'cu' => '/^INR$/D',
        'mc' => '/^\d{4}$/D',
    ];

    /**
     * Parameters whose name stays and whose value is masked: the payee, references, note and URL of 1.5.1, and the
     * later parameters `mode`, `purpose`, `orgid`, `qrMedium` and `sign`, for which no NPCI text was reached.
     */
    private const array MASKED_PARAMETERS = ['pa', 'pn', 'tr', 'tid', 'tn', 'url', 'mode', 'purpose', 'orgid', 'qrMedium', 'sign'];

    private QueryString $query;

    /**
     * The virtual payment address (`pa`), as written.
     *
     * @return non-empty-string
     *
     * @throws AmbiguousPaymentQrFieldException when `pa` repeats with distinct values
     */
    public function payeeAddress(): string
    {
        $address = $this->query->single('pa', self::TYPE);
        \assert($address !== null && $address !== '');

        return $address;
    }

    /**
     * @return non-empty-string
     *
     * @throws AmbiguousPaymentQrFieldException when `pn` repeats with distinct values
     */
    public function payeeName(): string
    {
        $name = $this->query->single('pn', self::TYPE);
        \assert($name !== null && $name !== '');

        return $name;
    }

    /**
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when `tr` repeats with distinct values
     */
    public function transactionReference(): ?string
    {
        return $this->query->filled('tr', self::TYPE);
    }

    /**
     * The amount (`am`) as written; null when absent or empty.
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when `am` repeats with distinct values
     */
    public function writtenAmount(): ?string
    {
        return $this->query->filled('am', self::TYPE);
    }

    /**
     * Null when `am` is absent or not digits with one optional decimal point.
     *
     * @throws AmbiguousPaymentQrFieldException when `am` repeats with distinct values
     */
    public function amount(): ?Decimal
    {
        $amount = $this->writtenAmount();

        return $amount !== null && \preg_match('/^\d+(\.\d+)?$/D', $amount) === 1 ? new Decimal($amount) : null;
    }

    /**
     * The value of any parameter as written; null when absent or empty.
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when the parameter repeats with distinct values
     */
    public function get(string $name): ?string
    {
        return $this->query->filled($name, self::TYPE);
    }

    /**
     * @return list<string>
     */
    public function parameterNames(): array
    {
        return $this->query->names();
    }

    #[Override]
    public function masked(): string
    {
        $scheme = \substr($this->value, 0, 10);
        \assert($scheme !== '');

        return $scheme . $this->query->rewritten($this->maskedPair(...));
    }

    #[Override]
    protected function parse(string $value): string
    {
        PayloadBound::ensure($value, self::TYPE);
        if (\preg_match('//u', $value) !== 1 || \preg_match('/^upi:\/\/pay\?([^\s#]*)$/Di', $value, $match) !== 1) {
            throw new InvalidPaymentQrException(\sprintf('%s requires upi://pay? and a query without spaces or fragment.', self::TYPE));
        }

        $query = QueryString::parse($match[1]);
        foreach (['pa' => 'the payee address pa', 'pn' => 'the payee name pn'] as $name => $requirement) {
            $values = $query->all($name);
            ProfileRule::ensure($values !== [] && !\in_array('', $values, true), self::TYPE, $requirement);
        }

        foreach ($query->all('tr') as $reference) {
            ProfileRule::ensure(ProfileRule::atMost($reference, 35) && $reference !== '', self::TYPE, 'a transaction reference tr of 1 to 35 characters');
        }

        $this->query = $query;

        return $value;
    }

    private function maskedPair(string $name, ?string $value): string
    {
        $known = \in_array($name, self::MASKED_PARAMETERS, true) || isset(self::VISIBLE_PARAMETERS[$name]);
        if ($value === null) {
            return Mask::TEXT;
        }

        if (!$known) {
            return Mask::TEXT . '=' . Mask::text($value);
        }

        $shape = self::VISIBLE_PARAMETERS[$name] ?? null;

        return $name . '=' . match (true) {
            $value === '' => '',
            $name === 'pa' => $this->maskedAddress($value),
            $name === 'pn' => Mask::name($value),
            $name === 'tr', $name === 'tid' => Mask::reference($value),
            $shape !== null && \preg_match($shape, $value) === 1 => $value,
            default => Mask::TEXT,
        };
    }

    /**
     * @return non-empty-string
     */
    private function maskedAddress(string $address): string
    {
        return \preg_match('/^(.)[^@]*@([A-Za-z0-9.\-]{1,64})\z/u', $address, $parts) === 1 ? $parts[1] . Mask::TEXT . '@' . $parts[2] : Mask::TEXT;
    }
}
