<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Brazil;

use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Internal\EmvcoTlv;
use MSSTC4PHP\ValueObject\Financial\Internal\ExposesRootFields;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Pix BR Code: an EMVCo merchant-presented payload with a merchant account template (ID 26 to 51) whose GUI is
 * `br.gov.bcb.pix`, compared case-insensitively. Enforced from the BCB manuals: the template holds the Pix key (sub-ID
 * 01, 1 to 77 characters; a static code) or the payment URL (sub-ID 25, 1 to 77 characters, no protocol prefix; a
 * dynamic code), not both, unless the code is a composite one whose recurrence URL (sub-ID 25 of a template 80 to 99
 * with the Pix GUI) stands in; the additional information (02, 1 to 72), the withdrawal provider ISPB (03, 8 characters);
 * currency 986, country BR, merchant name up to 25 and city up to 15 characters; the reference label (62-05, 1 to 25
 * characters). The maxima of the key, URL and additional information (77, 77, 72) follow from the 99-character template
 * and need no check of their own. Not enforced: the form of the key (the DICT manual is not reached) and the letters and
 * digits rule for a static code's txid, which the BCB's own BR Code example (`RP12345678-2019`) breaks.
 *
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/II_ManualdePadroesparaIniciacaodoPix.pdf BCB, Manual de Padroes para Iniciacao do Pix 2.10.0, section 2.5 to 2.8
 * @see https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf BCB, Manual do BR Code 2.0.1, section 2.1 table 1 and section 2.2
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
final class PixBrCode extends AbstractStringValueObject implements Maskable
{
    use ExposesRootFields;

    private const string TYPE = 'PixBrCode';

    private const string GUI = 'br.gov.bcb.pix';

    private ?MerchantAccount $account = null;

    /** @var non-empty-string|null */
    private ?string $recurrenceUrl = null;

    /**
     * The Pix key of a static code, as written.
     *
     * @return non-empty-string|null
     */
    public function pixKey(): ?string
    {
        return $this->account?->subField('01');
    }

    /**
     * The URL of the payment data of a dynamic code, without protocol prefix.
     *
     * @return non-empty-string|null
     */
    public function paymentUrl(): ?string
    {
        return $this->account?->subField('25');
    }

    /**
     * The URL of the recurrence parameters of a composite code, without protocol prefix.
     *
     * @return non-empty-string|null
     */
    public function recurrenceUrl(): ?string
    {
        return $this->recurrenceUrl;
    }

    /**
     * @return non-empty-string|null
     */
    public function additionalInfo(): ?string
    {
        return $this->account?->subField('02');
    }

    /**
     * The ISPB of the withdrawal service provider; its presence marks a Pix Saque code.
     *
     * @return non-empty-string|null
     */
    public function withdrawalProvider(): ?string
    {
        return $this->account?->subField('03');
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

        $templates = \array_values(\array_filter($payload->accounts(), $this->isPixTemplate(...)));
        ProfileRule::ensure(\count($templates) === 1, self::TYPE, 'exactly one merchant account template with the Pix GUI');
        $account = $templates[0];

        $recurrenceUrl = $this->recurrenceUrlOf($payload);
        $key = $account->subField('01');
        $url = $account->subField('25');
        ProfileRule::ensure(
            $recurrenceUrl !== null ? ($key === null || $url === null) : ($key === null) !== ($url === null),
            self::TYPE,
            'the Pix key or the payment URL, not both',
        );
        ProfileRule::ensure(!str_contains($url ?? '', '://') && !str_contains($recurrenceUrl ?? '', '://'), self::TYPE, 'a URL without protocol prefix');
        ProfileRule::ensure($account->subField('03') === null || EmvcoTlv::length($account->subField('03')) === 8, self::TYPE, 'a withdrawal provider of 8 characters');

        ProfileRule::ensure($payload->get('53') === '986', self::TYPE, 'currency 986');
        ProfileRule::ensure($payload->get('58') === 'BR', self::TYPE, 'country BR');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('59'), 25), self::TYPE, 'a merchant name of at most 25 characters');
        ProfileRule::ensure(ProfileRule::atMost($payload->get('60'), 15), self::TYPE, 'a merchant city of at most 15 characters');

        $txid = $payload->additional('05');
        ProfileRule::ensure($txid !== null && ProfileRule::atMost($txid, 25), self::TYPE, 'a reference label of 1 to 25 characters');

        $this->payload = $payload;
        $this->account = $account;
        $this->recurrenceUrl = $recurrenceUrl;

        return $value;
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
     * @return non-empty-string|null
     */
    private function recurrenceUrlOf(ProfilePayload $payload): ?string
    {
        $url = null;
        for ($id = 80; $id <= 99; $id++) {
            $template = $payload->get((string) $id);
            if ($template === null) {
                continue;
            }

            $subFields = EmvcoTlv::parse($template);
            $gui = array_find($subFields, static fn ($subField): bool => $subField->id === '00')?->value;
            if (!$this->hasPixGui($gui)) {
                continue;
            }

            $found = array_find($subFields, static fn ($subField): bool => $subField->id === '25')?->value;
            ProfileRule::ensure($found !== null && $url === null, self::TYPE, 'one recurrence URL in the unreserved templates');
            $url = $found;
        }

        return $url;
    }
}
