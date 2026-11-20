<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Thailand;

use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Internal\ExposesRootFields;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Thai QR Code (PromptPay) merchant-presented payload as the Bank of Thailand guideline defines it: tag 29 (credit
 * transfer, AID `A000000677010111`, hex compared case-insensitively) with at least one proxy of sub-tags 01 to 04 (no source makes them exclusive; {@see proxyTypes()} lists all), or tag 30 (bill payment, AID
 * `A000000677010112` domestic or `A000000677012006` cross-border) with a biller ID of 15 digits and a reference 1 of up
 * to 20 characters (reference 2 up to 20, optional); at least one of the two. The merchant category code and the city
 * are optional here, as the guideline makes them; tags 55, 56 and 57 are refused. Not enforced: currency 764 and country
 * TH (the guideline gives other countries for cross-border use), tags 31, 39 and 80, and the character set of the
 * references.
 *
 * @see https://www.bot.or.th/content/dam/bot/fipcs/documents/FPG/2562/EngPDF/25620084.pdf Bank of Thailand, Policy Guideline: Standardized Thai QR Code for Payment Transactions (17 April 2019), section 2.3
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
final class PromptPayQr extends AbstractStringValueObject implements Maskable
{
    use ExposesRootFields;

    private const string TYPE = 'PromptPayQr';

    private const string CREDIT_TRANSFER_AID = 'A000000677010111';

    /** @var non-empty-list<non-empty-string> */
    private const array BILL_PAYMENT_AIDS = ['A000000677010112', 'A000000677012006'];

    private ?MerchantAccount $creditTransfer = null;

    private ?MerchantAccount $billPayment = null;

    /**
     * @return list<PromptPayProxyType> the proxy types the credit transfer carries, in sub-tag order; empty without one
     */
    public function proxyTypes(): array
    {
        if (!$this->creditTransfer instanceof MerchantAccount) {
            return [];
        }

        return \array_values(\array_filter(PromptPayProxyType::cases(), fn (PromptPayProxyType $type): bool => $this->creditTransfer->subField($type->value) !== null));
    }

    /**
     * The proxy of the given type as written: mobile number, national or tax ID, e-wallet ID, or bank code plus account
     * number.
     *
     * @return non-empty-string|null
     */
    public function proxyOf(PromptPayProxyType $type): ?string
    {
        return $this->creditTransfer?->subField($type->value);
    }

    /**
     * The only proxy type of the credit transfer; null without a credit transfer.
     *
     * @throws AmbiguousPaymentQrFieldException when the template carries several proxies; see {@see proxyTypes()}
     */
    public function proxyType(): ?PromptPayProxyType
    {
        $types = $this->proxyTypes();
        if (\count($types) > 1) {
            throw new AmbiguousPaymentQrFieldException(self::TYPE, 'the proxy');
        }

        return $types[0] ?? null;
    }

    /**
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when the template carries several proxies
     */
    public function proxy(): ?string
    {
        $type = $this->proxyType();

        return $type instanceof PromptPayProxyType ? $this->proxyOf($type) : null;
    }

    /**
     * @return non-empty-string|null
     */
    public function billerId(): ?string
    {
        return $this->billPayment?->subField('01');
    }

    /**
     * @return non-empty-string|null
     */
    public function reference1(): ?string
    {
        return $this->billPayment?->subField('02');
    }

    /**
     * @return non-empty-string|null
     */
    public function reference2(): ?string
    {
        return $this->billPayment?->subField('03');
    }

    #[Override]
    protected function parse(string $value): string
    {
        $payload = ProfilePayload::parse($value, self::TYPE, categoryOptional: true, cityOptional: true);

        $credit = $this->template($payload, '29', [self::CREDIT_TRANSFER_AID]);
        $bill = $this->template($payload, '30', self::BILL_PAYMENT_AIDS);
        ProfileRule::ensure($credit instanceof MerchantAccount || $bill instanceof MerchantAccount, self::TYPE, 'a PromptPay credit transfer (tag 29) or bill payment (tag 30) template');

        if ($credit instanceof MerchantAccount) {
            $carried = \array_filter(PromptPayProxyType::cases(), static fn (PromptPayProxyType $type): bool => $credit->subField($type->value) !== null);
            ProfileRule::ensure($carried !== [], self::TYPE, 'a proxy in the credit transfer template');
            foreach ($carried as $type) {
                ProfileRule::ensure($type->accepts((string) $credit->subField($type->value)), self::TYPE, 'a proxy of the digits and length of its type');
            }
        }

        if ($bill instanceof MerchantAccount) {
            $billerId = $bill->subField('01');
            ProfileRule::ensure($billerId !== null && \preg_match('/^[0-9]{15}$/D', $billerId) === 1, self::TYPE, 'a biller ID of 15 digits');
            $reference1 = $bill->subField('02');
            ProfileRule::ensure($reference1 !== null && ProfileRule::atMost($reference1, 20), self::TYPE, 'a reference 1 of at most 20 characters');
            ProfileRule::ensure(ProfileRule::atMost($bill->subField('03'), 20), self::TYPE, 'a reference 2 of at most 20 characters');
        }

        foreach (['55', '56', '57'] as $id) {
            ProfileRule::ensure($payload->get($id) === null, self::TYPE, 'no tip or convenience fee data objects');
        }

        $this->payload = $payload;
        $this->creditTransfer = $credit;
        $this->billPayment = $bill;

        return $value;
    }

    /**
     * @param non-empty-list<non-empty-string> $aids
     */
    private function template(ProfilePayload $payload, string $id, array $aids): ?MerchantAccount
    {
        return array_find(
            $payload->accounts(),
            static fn (MerchantAccount $account): bool => $account->id === $id && \in_array(\strtoupper((string) $account->globallyUniqueIdentifier), $aids, true),
        );
    }
}
