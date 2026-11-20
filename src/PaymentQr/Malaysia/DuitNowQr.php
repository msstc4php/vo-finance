<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Malaysia;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\ExposesRootFields;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\Internal\ReparsesOnUnserialize;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * DuitNow QR merchant-presented payload, PayNet's national standard: payload format indicator `02` (not the generic
 * `01`), a point of initiation (11 static, 12 dynamic), the merchant account template ID 26 with the AID
 * `A0000006150001` (compared case-insensitively), an acquirer ID (sub-ID 01, up to 6 characters) and a QR ID (02, up to
 * 28); currency 458, country MY, merchant name up to 25 and city up to 15 characters. Enforced besides: a postal code
 * of 5 digits; amounts (54, 56) other than zero; the fixed fee (56) exactly when the indicator 55 is 02 and the
 * percentage (57) exactly when it is 03; a language template (64) with its language and name; for JomPAY (acquirer ID
 * 898989) the additional data template with the RRN (62-90). Not enforced: the hash of ID 82, the acquirer ID table,
 * and the character sets beyond the generic ones.
 *
 * @see https://docs.developer.paynet.my/docs/duitNow-QR/integration/QR-generation-specification/merchant-presented-mode/qr-data-object PayNet, DuitNow QR merchant-presented mode 1.5, "QR Data Object"
 * @see https://docs.developer.paynet.my/docs/duitNow-QR/integration/QR-generation-specification/merchant-presented-mode/example PayNet, DuitNow QR merchant-presented mode 1.5, "Examples"
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
final class DuitNowQr extends AbstractStringValueObject implements Maskable
{
    use ExposesRootFields;
    use ReparsesOnUnserialize;

    private const string TYPE = 'DuitNowQr';

    private const string AID = 'A0000006150001';

    private const string JOMPAY_ACQUIRER_ID = '898989';

    private MerchantAccount $account;

    /**
     * @return non-empty-string
     */
    public function acquirerId(): string
    {
        return $this->required('01');
    }

    /**
     * The merchant or recipient ID assigned by the acquirer; for JomPAY, the biller code.
     *
     * @return non-empty-string
     */
    public function qrId(): string
    {
        return $this->required('02');
    }

    /**
     * @return non-empty-string|null
     */
    public function merchantDescriptor(): ?string
    {
        return $this->account->subField('03');
    }

    /**
     * @return non-empty-string|null
     */
    public function mobileNumber(): ?string
    {
        return $this->account->subField('04');
    }

    /**
     * @return non-empty-string|null
     */
    public function postalCode(): ?string
    {
        return $this->payload->get('61');
    }

    public function isJomPay(): bool
    {
        return $this->acquirerId() === self::JOMPAY_ACQUIRER_ID;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $payload = ProfilePayload::parse($value, self::TYPE, formatIndicator: '02');

        ProfileRule::ensure($payload->get('01') !== null, self::TYPE, 'a point of initiation method');

        $account = array_find(
            $payload->accounts(),
            static fn (MerchantAccount $candidate): bool => $candidate->id === '26' && \strtoupper((string) $candidate->globallyUniqueIdentifier) === self::AID,
        ) ?? throw new InvalidPaymentQrException(\sprintf('%s requires merchant account template 26 with the AID %s.', self::TYPE, self::AID));

        $acquirerId = $account->subField('01');
        $qrId = $account->subField('02');
        ProfileRule::ensure($acquirerId !== null && ProfileRule::atMost($acquirerId, 6), self::TYPE, 'an acquirer ID of at most 6 characters');
        ProfileRule::ensure($qrId !== null && ProfileRule::atMost($qrId, 28), self::TYPE, 'a QR ID of at most 28 characters');
        ProfileRule::ensure(ProfileRule::atMost($account->subField('03'), 20), self::TYPE, 'a merchant descriptor of at most 20 characters');
        ProfileRule::ensure(ProfileRule::atMost($account->subField('04'), 15), self::TYPE, 'a mobile number of at most 15 characters');

        ProfileRule::ensure($payload->get('53') === '458', self::TYPE, 'currency 458');
        ProfileRule::ensure($payload->get('58') === 'MY', self::TYPE, 'country MY');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('59'), 25), self::TYPE, 'a merchant name of at most 25 characters');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('60'), 15), self::TYPE, 'a merchant city of at most 15 characters');

        $postalCode = $payload->get('61');
        ProfileRule::ensure($postalCode === null || \preg_match('/^[0-9]{5}$/D', $postalCode) === 1, self::TYPE, 'a postal code of 5 digits');

        $this->assertAmounts($payload);
        $this->assertLanguageTemplate($payload);

        if ($acquirerId === self::JOMPAY_ACQUIRER_ID) {
            ProfileRule::ensure($payload->additional('90') !== null, self::TYPE, 'the RRN (62-90) for JomPAY');
        }

        $this->payload = $payload;
        $this->account = $account;

        return $value;
    }

    private function assertAmounts(ProfilePayload $payload): void
    {
        foreach (['54', '56'] as $id) {
            $amount = $payload->get($id);
            ProfileRule::ensure($amount === null || \preg_match('/^[0.]+$/D', $amount) !== 1, self::TYPE, 'an amount other than zero');
        }

        $indicator = $payload->get('55');
        ProfileRule::ensure(($payload->get('56') !== null) === ($indicator === '02'), self::TYPE, 'the fixed convenience fee exactly when the indicator is 02');
        ProfileRule::ensure(($payload->get('57') !== null) === ($indicator === '03'), self::TYPE, 'the percentage convenience fee exactly when the indicator is 03');
    }

    private function assertLanguageTemplate(ProfilePayload $payload): void
    {
        if ($payload->get('64') === null) {
            return;
        }

        $subFields = $payload->templateFields('64');
        ProfileRule::ensure(isset($subFields['00'], $subFields['01']), self::TYPE, 'a language preference and a merchant name in the language template');
    }

    /**
     * @return non-empty-string
     */
    private function required(string $subId): string
    {
        return $this->account->subField($subId) ?? throw new LogicException('A validated payload lacks a mandatory sub-field.');
    }
}
