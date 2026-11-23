<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Card;

/**
 * A card scheme whose IIN ranges and lengths a reached publication states. Mastercard, Visa, Mir and RuPay have no
 * case: no reached publication gives them a range. Discover, Diners Club International and PULSE share the summary's
 * unmarked rows, which name no single scheme (docs/sources/FINANCE.md, "Card IIN publications").
 *
 * No source gives the schemes a code, so the backed value is the scheme's name in lowercase snake_case (`UnionPay` is
 * one word: `unionpay`).
 *
 * Cases may be added in a minor release, so a `match` over them needs a default arm; backed values never change in
 * 1.x.
 *
 * @see https://www.discoverglobalnetwork.com/content/dam/discover/en_us/dgn/docs/IPP-VAR-Enabler-Compliance.pdf Discover Global Network, IIN Range Summary: JCB and UnionPay ranges and lengths
 * @see https://www.americanexpress.com/uk/merchant/get-started.html American Express, "34" or "37", 15 digits
 */
enum CardScheme: string
{
    case Amex = 'amex';
    case Jcb = 'jcb';
    case UnionPay = 'unionpay';

    /**
     * @return non-empty-list<positive-int>
     */
    public function lengths(): array
    {
        return match ($this) {
            self::Amex => [15],
            self::Jcb, self::UnionPay => [16, 17, 18, 19],
        };
    }
}
