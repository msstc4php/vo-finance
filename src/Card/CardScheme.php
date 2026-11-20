<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Card;

/**
 * A card scheme whose IIN ranges and lengths a reached publication states. Mastercard, Visa, Mir, RuPay, Discover and
 * Diners Club have no case: no reached publication assigns them a range (docs/sources/FINANCE.md, "Card IIN
 * publications").
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
