<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Cambodia;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ExposesRootFields;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * KHQR merchant-presented payload as the National Bank of Cambodia's KHQR Code Specification defines it, identified by
 * a Bakong merchant account template: tag 29 (remittance, solo merchant) or tag 30 (corporate merchant), sub-tag 00 the
 * Bakong account ID, 01 the account information (29) or merchant ID (30), 02 the acquiring bank, and country KH (the
 * Prakas makes KHQR the code for payment in Cambodia, and the specification has "KH" put for a merchant transacting in
 * Cambodia; without it any EMVCo payload with tag 29 or 30, a Thai QR or EMVCo's own example, would pass as KHQR).
 * Enforced from the specification: merchant name up to 25, city up to 15 and postal code up to 10 characters; an amount (54) of up to 13
 * characters other than zero; the tip indicator (55) of 2 digits, the fixed fee (56) up to 13 and the percentage (57) up
 * to 5 characters; sub-IDs 01 to 09 of the additional data template up to 25 characters; a language template (64) with
 * a language of 2 letters and a merchant name up to 25 (city up to 15). Not enforced: currency 116 (an example in the
 * specification; the guideline's own examples use 840 too), the content of the Bakong templates beyond their tags (the
 * specification leaves it to the operator), template 99.
 *
 * @see https://web.archive.org/web/20230330080021id_/https://www.nbc.gov.kh/download_files/legislation/prakas_kh/Prakas_F14-020-351-PR_KHQR_for_Payment_in_Cambodia_appendix.pdf National Bank of Cambodia, Prakas on KHQR for Payment in Cambodia, appendix "KHQR Code Specification in Cambodia, Merchant-Presented Mode" 3.0 (April 2020), section 4
 * @see https://web.archive.org/web/20230201043137id_/https://bakong.nbc.gov.kh/download/KHQR/integration/KHQR%20Content%20Guideline%20v.1.3.pdf National Bank of Cambodia, KHQR Content Guideline 1.3, tags 29 and 30 and FAQ 3 to 5
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
final class KhqrCode extends AbstractStringValueObject implements Maskable
{
    use ExposesRootFields;

    private const string TYPE = 'KhqrCode';

    private const string REMITTANCE = '29';

    private const string CORPORATE = '30';

    /** @var list<MerchantAccount> */
    private array $bakong = [];

    /**
     * @return list<non-empty-string> the distinct Bakong account IDs of tags 29 and 30, in payload order
     */
    public function bakongAccountIds(): array
    {
        return $this->distinct('00');
    }

    /**
     * @return non-empty-string
     *
     * @throws AmbiguousPaymentQrFieldException when tags 29 and 30 carry different account IDs; {@see bakongAccountIds()} lists them
     */
    public function bakongAccountId(): string
    {
        $ids = $this->bakongAccountIds();
        if (\count($ids) > 1) {
            throw new AmbiguousPaymentQrFieldException(self::TYPE, 'the Bakong account ID', 'several distinct values');
        }

        return $ids[0] ?? throw new LogicException('A validated payload lacks the Bakong account ID.');
    }

    /**
     * The account number or phone number of a remittance code (tag 29, sub-tag 01).
     *
     * @return non-empty-string|null
     */
    public function accountInformation(): ?string
    {
        return $this->template(self::REMITTANCE)?->subField('01');
    }

    /**
     * The merchant ID or merchant account number of a corporate merchant code (tag 30, sub-tag 01).
     *
     * @return non-empty-string|null
     */
    public function merchantId(): ?string
    {
        return $this->template(self::CORPORATE)?->subField('01');
    }

    /**
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when tags 29 and 30 name different acquiring banks
     */
    public function acquiringBank(): ?string
    {
        $banks = $this->distinct('02');
        if (\count($banks) > 1) {
            throw new AmbiguousPaymentQrFieldException(self::TYPE, 'the acquiring bank', 'several distinct values');
        }

        return $banks[0] ?? null;
    }

    /**
     * @return non-empty-string|null
     */
    public function postalCode(): ?string
    {
        return $this->payload->get('61');
    }

    #[Override]
    protected function parse(string $value): string
    {
        $payload = ProfilePayload::parse($value, self::TYPE);

        $bakong = \array_values(\array_filter($payload->accounts(), static fn (MerchantAccount $account): bool => \in_array($account->id, [self::REMITTANCE, self::CORPORATE], true)));
        ProfileRule::ensure($bakong !== [], self::TYPE, 'a Bakong merchant account template (tag 29 or 30)');
        ProfileRule::ensure($payload->get('58') === 'KH', self::TYPE, 'country KH');

        ProfileRule::ensure(ProfileRule::atMost($payload->get('59'), 25), self::TYPE, 'a merchant name of at most 25 characters');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('60'), 15), self::TYPE, 'a merchant city of at most 15 characters');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('61'), 10), self::TYPE, 'a postal code of at most 10 characters');

        $amount = $payload->get('54');
        ProfileRule::ensure($amount === null || \preg_match('/^[0.]+$/D', $amount) !== 1, self::TYPE, 'an amount other than zero');
        ProfileRule::ensure(ProfileRule::atMost($amount, 13), self::TYPE, 'an amount of at most 13 characters');
        $indicator = $payload->get('55');
        ProfileRule::ensure($indicator === null || EmvcoTlv::length($indicator) === 2, self::TYPE, 'a tip or convenience indicator of 2 digits');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('56'), 13), self::TYPE, 'a fixed convenience fee of at most 13 characters');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('57'), 5), self::TYPE, 'a percentage convenience fee of at most 5 characters');

        foreach (['01', '02', '03', '04', '05', '06', '07', '08', '09'] as $subId) {
            ProfileRule::ensure(ProfileRule::atMost($payload->additional($subId), 25), self::TYPE, 'additional data sub-fields 01 to 09 of at most 25 characters');
        }

        $this->assertLanguageTemplate($payload);

        $this->payload = $payload;
        $this->bakong = $bakong;

        return $value;
    }

    private function assertLanguageTemplate(ProfilePayload $payload): void
    {
        $template = $payload->get('64');
        if ($template === null) {
            return;
        }

        $subFields = [];
        foreach (EmvcoTlv::parse($template) as $subField) {
            $subFields[$subField->id] = $subField->value;
        }

        $language = $subFields['00'] ?? null;
        ProfileRule::ensure($language !== null && \preg_match('/^[A-Za-z]{2}$/D', $language) === 1, self::TYPE, 'a language preference of 2 letters in the language template');
        ProfileRule::ensure(isset($subFields['01']) && ProfileRule::atMost($subFields['01'], 25), self::TYPE, 'an alternate merchant name of at most 25 characters in the language template');
        ProfileRule::ensure(ProfileRule::atMost($subFields['02'] ?? null, 15), self::TYPE, 'an alternate merchant city of at most 15 characters in the language template');
    }

    private function template(string $id): ?MerchantAccount
    {
        return array_find($this->bakong, static fn (MerchantAccount $account): bool => $account->id === $id);
    }

    /**
     * @return list<non-empty-string>
     */
    private function distinct(string $subId): array
    {
        $values = [];
        foreach ($this->bakong as $account) {
            $value = $subId === '00' ? $account->globallyUniqueIdentifier : $account->subField($subId);
            if ($value !== null && !\in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        return $values;
    }
}
