<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil;

use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Exception\InvalidPaymentQrException;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ExposesRootFields;
use MSSTC4PHP\ValueObject\Financial\Internal\PayloadBound;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\DataObject;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\EmvcoPayload;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Pix BR Code: an EMVCo merchant-presented payload with a merchant account template (ID 26 to 51) whose GUI is
 * `br.gov.bcb.pix`, compared case-insensitively. Enforced from the BCB manuals: at least one Pix template, and
 * somewhere the Pix key (sub-ID 01, a static code), the payment URL (sub-ID 25, no protocol prefix; a dynamic code) or
 * the recurrence URL (sub-ID 25 of a template 80 to 99 with the Pix GUI, a composite code). No source forbids several
 * Pix templates or a key with a URL, so they are accepted; the single-value accessors throw when the values differ and
 * the list accessors give all. Templates 80 to 99 that are not data objects are skipped. The additional information
 * (02, 1 to 72), the withdrawal provider ISPB (03, 8 characters); currency 986, country BR, merchant name up to 25 and
 * city up to 15 characters; the reference label (62-05, 1 to 25 characters). The maxima of the key, URL and additional
 * information (77, 77, 72) follow from the 99-character template and need no check of their own. The key type is
 * decoded ({@see pixKeyType()}), never enforced. Not enforced: the letters and digits rule for a static code's txid,
 * which the BCB's own BR Code example (`RP12345678-2019`) breaks.
 *
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/II_ManualdePadroesparaIniciacaodoPix.pdf BCB, Manual de Padroes para Iniciacao do Pix 2.10.0, section 2.5 to 2.8
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf BCB, Manual do BR Code 2.0.1, section 2.1 table 1 and section 2.2
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
final class PixBrCode extends AbstractStringValueObject implements EmvcoPayload
{
    use ExposesRootFields;

    /** The longest stored payload, in characters: the abuse bound of 7089; frozen for 1.x. */
    public const int MAX_LENGTH = PayloadBound::MAX_CHARACTERS;

    private const string TYPE = 'PixBrCode';

    private const string GUI = 'br.gov.bcb.pix';

    /** @var list<MerchantAccount> */
    private array $accounts;

    /** @var list<non-empty-string> */
    private array $recurrenceUrls;

    /**
     * The Pix key of a static code, as written.
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when several Pix templates carry different keys; {@see pixKeys()} lists
     *                                          them
     */
    public function pixKey(): ?string
    {
        return $this->single($this->pixKeys(), 'the Pix key');
    }

    /**
     * The type of the Pix key, decoded from the DICT formats; null without a key or for a key of no DICT format.
     *
     * @throws AmbiguousPaymentQrFieldException when several Pix templates carry different keys
     */
    public function pixKeyType(): ?PixKeyType
    {
        $key = $this->pixKey();

        return $key === null ? null : PixKeyType::ofKey($key);
    }

    /**
     * @return list<non-empty-string> the distinct keys of the Pix templates, in payload order
     */
    public function pixKeys(): array
    {
        return $this->distinct($this->subFields('01'));
    }

    /**
     * The URL of the payment data of a dynamic code, without protocol prefix.
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when several Pix templates carry different URLs; {@see paymentUrls()}
     *                                          lists them
     */
    public function paymentUrl(): ?string
    {
        return $this->single($this->paymentUrls(), 'the payment URL');
    }

    /**
     * @return list<non-empty-string> the distinct payment URLs, in payload order
     */
    public function paymentUrls(): array
    {
        return $this->distinct($this->subFields('25'));
    }

    /**
     * The URL of the recurrence parameters of a composite code, without protocol prefix.
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException when several templates carry different URLs; {@see recurrenceUrls()}
     *                                          lists them
     */
    public function recurrenceUrl(): ?string
    {
        return $this->single($this->recurrenceUrls, 'the recurrence URL');
    }

    /**
     * @return list<non-empty-string> the distinct recurrence URLs, in payload order
     */
    public function recurrenceUrls(): array
    {
        return $this->distinct($this->recurrenceUrls);
    }

    /**
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException with several Pix templates when they do not all carry the same value
     */
    public function additionalInfo(): ?string
    {
        return $this->sameInEveryTemplate('02', 'the additional information');
    }

    /**
     * The ISPB of the withdrawal service provider; its presence marks a Pix Saque code.
     *
     * @return non-empty-string|null
     *
     * @throws AmbiguousPaymentQrFieldException with several Pix templates when they do not all carry the same value
     */
    public function withdrawalProvider(): ?string
    {
        return $this->sameInEveryTemplate('03', 'the withdrawal provider');
    }

    /**
     * The transaction identifier (62-05); null when absent or `***`, which says the identifier is not used.
     *
     * @return non-empty-string|null
     */
    public function txid(): ?string
    {
        $txid = $this->payload->additional('05');

        return $txid === '***' ? null : $txid;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $payload = ProfilePayload::parse($value, self::TYPE);

        $accounts = \array_values(\array_filter($payload->accounts(), $this->isPixTemplate(...)));
        ProfileRule::ensure($accounts !== [], self::TYPE, 'a merchant account template with the Pix GUI');

        $recurrenceUrls = $this->recurrenceUrlsOf($payload);
        $urls = [];
        $hasKey = false;
        foreach ($accounts as $account) {
            $hasKey = $hasKey || $account->subField('01') !== null;
            $url = $account->subField('25');
            if ($url !== null) {
                $urls[] = $url;
            }

            ProfileRule::ensure($account->subField('03') === null || EmvcoTlv::length($account->subField('03')) === 8, self::TYPE, 'a withdrawal provider of 8 characters');
        }

        ProfileRule::ensure($hasKey || $urls !== [] || $recurrenceUrls !== [], self::TYPE, 'the Pix key, the payment URL or the recurrence URL');
        foreach ([...$urls, ...$recurrenceUrls] as $url) {
            ProfileRule::ensure(!str_contains($url, '://'), self::TYPE, 'a URL without protocol prefix');
        }

        ProfileRule::ensure($payload->get('53') === '986', self::TYPE, 'currency 986');
        ProfileRule::ensure($payload->get('58') === 'BR', self::TYPE, 'country BR');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('59'), 25), self::TYPE, 'a merchant name of at most 25 characters');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('60'), 15), self::TYPE, 'a merchant city of at most 15 characters');

        $txid = $payload->additional('05');
        ProfileRule::ensure($txid !== null && ProfileRule::atMost($txid, 25), self::TYPE, 'a reference label of 1 to 25 characters');

        $this->payload = $payload;
        $this->accounts = $accounts;
        $this->recurrenceUrls = $recurrenceUrls;

        return $value;
    }

    /**
     * @param list<non-empty-string> $values
     *
     * @return list<non-empty-string>
     */
    private function distinct(array $values): array
    {
        return \array_values(\array_unique($values));
    }

    /**
     * @param list<non-empty-string> $values
     * @param non-empty-string $field
     *
     * @return non-empty-string|null
     */
    private function single(array $values, string $field): ?string
    {
        $distinct = $this->distinct($values);
        if (\count($distinct) > 1) {
            throw new AmbiguousPaymentQrFieldException(self::TYPE, $field, 'several distinct values');
        }

        return $distinct[0] ?? null;
    }

    /**
     * @param non-empty-string $field
     *
     * @return non-empty-string|null
     */
    private function sameInEveryTemplate(string $subId, string $field): ?string
    {
        $values = $this->subFields($subId);
        if ($values !== [] && \count($values) !== \count($this->accounts)) {
            throw new AmbiguousPaymentQrFieldException(self::TYPE, $field, 'present in only some Pix templates');
        }

        return $this->single($values, $field);
    }

    /**
     * @return list<non-empty-string>
     */
    private function subFields(string $subId): array
    {
        $values = [];
        foreach ($this->accounts as $account) {
            $found = $account->subField($subId);
            if ($found !== null) {
                $values[] = $found;
            }
        }

        return $values;
    }

    private function isPixTemplate(MerchantAccount $account): bool
    {
        return (int) $account->id >= 26 && $this->hasPixGui($account->globallyUniqueIdentifier);
    }

    private function hasPixGui(?string $gui): bool
    {
        return $gui !== null && \strtolower($gui) === self::GUI;
    }

    /**
     * @return list<non-empty-string>
     */
    private function recurrenceUrlsOf(ProfilePayload $payload): array
    {
        $urls = [];
        for ($id = 80; $id <= 99; $id++) {
            $template = $payload->get((string) $id);
            if ($template === null) {
                continue;
            }

            try {
                $subFields = EmvcoTlv::parse($template);
            } catch (InvalidPaymentQrException) {
                continue;
            }

            $gui = array_find($subFields, static fn (DataObject $subField): bool => $subField->id === '00')?->value;
            if (!$this->hasPixGui($gui)) {
                continue;
            }

            $urls[] = (array_find($subFields, static fn (DataObject $subField): bool => $subField->id === '25')
                ?? throw new InvalidPaymentQrException(\sprintf('%s requires the URL (sub-ID 25) in a Pix template of the unreserved range.', self::TYPE)))->value;
        }

        return $urls;
    }
}
