<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

use MSSTC4PHP\ValueObject\Financial\Internal\SecurityRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Wertpapier-Kenn-Nummer, format only: six digits or uppercase letters other than `I` and `O`. WM Datenservice defines
 * no check digit.
 *
 * @see https://www.wmdatenservice.com/wp-content/uploads/2022/10/Vergaberegeln_WKN.pdf WM Datenservice, "Struktur und Aufbau der deutschen WKN", July 2012
 * @see docs/sources/FINANCE.md "WKN, WM Datenservice"
 */
final class Wkn extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        SecurityRules::shape($value, 'WKN', 6, '/^[0-9A-HJ-NP-Z]{6}$/D', 'digits or uppercase letters except I and O');

        return $value;
    }
}
