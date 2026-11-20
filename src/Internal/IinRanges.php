<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Internal;

use MSSTC4PHP\ValueObject\Financial\Card\CardScheme;

/**
 * IIN ranges, each typed from a scheme's own publication; a range no reached publication assigns to a scheme is absent,
 * so its numbers decode to an empty list. Discover's summary leaves the unmarked rows (3000-3059, 3095, 3600-3699,
 * 3800-3999, 6011, 6440-6589) without a scheme name, so they are not listed.
 *
 * @internal
 *
 * @see https://www.discoverglobalnetwork.com/content/dam/discover/en_us/dgn/docs/IPP-VAR-Enabler-Compliance.pdf Discover Global Network, IIN Range Summary: "a. JCB IIN Ranges", "b. UnionPay IIN Ranges"
 * @see https://www.americanexpress.com/uk/merchant/get-started.html American Express: "Card account numbers begin with "34" or "37""
 * @see docs/sources/FINANCE.md "Card IIN publications"
 */
final class IinRanges
{
    /**
     * @param string $iin eight digits
     *
     * @return list<CardScheme>
     */
    public static function schemes(string $iin): array
    {
        return self::match($iin, self::table());
    }

    /**
     * @param string $iin eight digits
     * @param list<IinRange> $ranges
     *
     * @return list<CardScheme>
     */
    public static function match(string $iin, array $ranges): array
    {
        $schemes = [];
        foreach ($ranges as $range) {
            if ($range->contains($iin) && !\in_array($range->scheme, $schemes, true)) {
                $schemes[] = $range->scheme;
            }
        }

        return $schemes;
    }

    /**
     * @return list<IinRange>
     */
    private static function table(): array
    {
        $jcb = ['30880000' => '30949999', '30960000' => '31029999', '31120000' => '31209999', '31580000' => '31599999', '33370000' => '33499999', '35280000' => '35899999'];
        $unionPay = ['62109400' => '62109499', '62212600' => '62379699', '62400000' => '62699999', '62820000' => '62889999', '81000000' => '81719999'];

        $ranges = [
            new IinRange(CardScheme::Amex, '34000000', '34999999'),
            new IinRange(CardScheme::Amex, '37000000', '37999999'),
        ];
        foreach ($jcb as $start => $end) {
            $ranges[] = new IinRange(CardScheme::Jcb, (string) $start, $end);
        }
        foreach ($unionPay as $start => $end) {
            $ranges[] = new IinRange(CardScheme::UnionPay, (string) $start, $end);
        }

        return $ranges;
    }
}
