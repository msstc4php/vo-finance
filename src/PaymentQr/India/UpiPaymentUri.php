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
 * UPI payment URI `upi://pay?` with its query parameters. Enforced from NPCI's circular OC 18 (which quotes the deep
 * linking specification): the payee address `pa` and the payee name `pn` are present and filled, and a transaction
 * reference `tr` has 1 to 35 characters. The scheme is the plan's; the NPCI linking specification itself was not
 * reached, so no other parameter, the VPA shape or the amount format is checked. Values are kept as written: NPCI
 * writes a space as a bare `%`, so nothing is percent-decoded. A repeated parameter makes its single-value accessor
 * throw. {@see masked()} hides the payee address, keeps the first character of the payee name and replaces a filled
 * transaction note `tn` (free text) with `***`. At most 7089 characters, the largest QR content: a bound against
 * abusive input.
 *
 * @see https://www.npci.org.in/PDF/npci/upi/circular/2017/Circular18_BankCompliances_to_enbaleUPIMerchantecosystem_0.pdf NPCI, UPI OC No. 18/2016-17, Annexure A Table 1 and D.7
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "NPCI UPI linking specification"
 */
final class UpiPaymentUri extends AbstractStringValueObject implements PaymentPayload
{
    /** The longest stored payload, in characters: the abuse bound of 7089; frozen for 1.x. */
    public const int MAX_LENGTH = PayloadBound::MAX_CHARACTERS;

    private const string TYPE = 'UpiPaymentUri';

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
     * @throws AmbiguousPaymentQrFieldException when `tr` repeats with distinct non-empty values
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
     * @throws AmbiguousPaymentQrFieldException when `am` repeats with distinct non-empty values
     */
    public function writtenAmount(): ?string
    {
        return $this->query->filled('am', self::TYPE);
    }

    /**
     * Null when `am` is absent or not digits with one optional decimal point.
     *
     * @throws AmbiguousPaymentQrFieldException when `am` repeats with distinct non-empty values
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
     * @throws AmbiguousPaymentQrFieldException when the parameter repeats with distinct non-empty values
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

        return $scheme . $this->query->withMasked(['pa' => Mask::keepEnds(...), 'pn' => Mask::name(...), 'tn' => Mask::text(...)]);
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
}
