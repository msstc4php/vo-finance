<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\PaymentQr\Japan;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Maskable;
use MSSTC4PHP\ValueObject\Financial\Exception\AmbiguousPaymentQrFieldException;
use MSSTC4PHP\ValueObject\Financial\Internal\ExposesRootFields;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfilePayload;
use MSSTC4PHP\ValueObject\Financial\Internal\ProfileRule;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\Emvco\MerchantAccount;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * JPQR unified merchant-presented code as the Payments Japan MPM guideline defines it: a merchant account template (ID
 * 26 to 51) with the GUI `jp.or.paymentsjapan` (compared case-insensitively, as DNS names are; RFC 4343) carrying the unified store ID, 29 digits in
 * four management levels (sub-IDs 01 to 04 of 13, 4, 6 and 6 digits). Enforced besides: the point of initiation, the
 * postal code (61) and the language template (64) with its language and merchant name are mandatory, and a dynamic code
 * (12) carries the amount, and the payload has at most 512 bytes ("512byte を上限とする"). No source forbids several JPQR templates; {@see storeIds()} lists them. Not enforced:
 * currency 392 and country JP (the guideline gives them as the yen and Japan entries, not as fixed values) and the
 * language value `JA` (decided per merchant by the Payments Japan secretariat).
 *
 * @see https://web.archive.org/web/20200918130533id_/https://www.paymentsjapan.or.jp/wordpress/wp-content/uploads/2020/04/MPM_Guideline_2.0.pdf Payments Japan, Unified technical specification guidelines for code payments, merchant-presented mode (MPM) 2.0 (27 April 2020), part 1 and part 2, tables 2.1-1, 2.1-2 and 3.2
 * @see https://www.rfc-editor.org/rfc/rfc4343 RFC 4343, Domain Name System (DNS) Case Insensitivity Clarification
 * @see docs/sources/FINANCE.md "National EMVCo profiles"
 */
final class JpqrCode extends AbstractStringValueObject implements Maskable
{
    use ExposesRootFields;

    private const string TYPE = 'JpqrCode';

    private const string GUI = 'jp.or.paymentsjapan';

    private const int MAX_BYTES = 512;

    /** @var array<non-empty-string, positive-int> */
    private const array LEVELS = ['01' => 13, '02' => 4, '03' => 6, '04' => 6];

    /** @var list<MerchantAccount> */
    private array $accounts = [];

    /**
     * @return list<non-empty-string> the distinct unified store IDs (29 digits, levels 1 to 4 joined), in payload order
     */
    public function storeIds(): array
    {
        $ids = [];
        foreach ($this->accounts as $account) {
            $id = \implode('', \array_map(static fn (string $subId): string => (string) $account->subField($subId), \array_keys(self::LEVELS)));
            if ($id !== '' && !\in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return non-empty-string
     *
     * @throws AmbiguousPaymentQrFieldException when several JPQR templates carry different store IDs; {@see storeIds()} lists them
     */
    public function storeId(): string
    {
        $ids = $this->storeIds();
        if (\count($ids) > 1) {
            throw new AmbiguousPaymentQrFieldException(self::TYPE, 'the unified store ID', 'several distinct values');
        }

        return $ids[0] ?? throw new LogicException('A validated payload lacks the unified store ID.');
    }

    /**
     * @return non-empty-string
     */
    public function postalCode(): string
    {
        return $this->payload->get('61') ?? throw new LogicException('A validated payload lacks the postal code.');
    }

    /**
     * The merchant name in the language of the language template (64-01), in Japanese as a rule.
     *
     * @return non-empty-string
     */
    public function alternateMerchantName(): string
    {
        return $this->payload->templateFields('64')['01'] ?? throw new LogicException('A validated payload lacks the alternate merchant name.');
    }

    #[Override]
    protected function parse(string $value): string
    {
        ProfileRule::ensure(\strlen($value) <= self::MAX_BYTES, self::TYPE, \sprintf('a payload of at most %d bytes', self::MAX_BYTES));
        $payload = ProfilePayload::parse($value, self::TYPE);

        $accounts = \array_values(\array_filter($payload->accounts(), static fn (MerchantAccount $account): bool => $account->id >= '26' && \strtolower((string) $account->globallyUniqueIdentifier) === self::GUI));
        ProfileRule::ensure($accounts !== [], self::TYPE, 'a merchant account template with the GUI jp.or.paymentsjapan');
        foreach ($accounts as $account) {
            foreach (self::LEVELS as $subId => $digits) {
                ProfileRule::ensure(\preg_match(\sprintf('/^[0-9]{%d}$/D', $digits), (string) $account->subField($subId)) === 1, self::TYPE, 'a unified store ID of 13, 4, 6 and 6 digits (sub-IDs 01 to 04)');
            }
        }

        $point = $payload->get('01');
        ProfileRule::ensure($point !== null, self::TYPE, 'a point of initiation method');
        ProfileRule::ensure($point !== '12' || $payload->get('54') !== null, self::TYPE, 'the transaction amount in a dynamic code');
        ProfileRule::ensure($payload->get('61') !== null, self::TYPE, 'a postal code');

        $language = $payload->templateFields('64');
        ProfileRule::ensure(isset($language['00'], $language['01']), self::TYPE, 'a language template with the language preference and the merchant name');

        $this->payload = $payload;
        $this->accounts = $accounts;

        return $value;
    }
}
