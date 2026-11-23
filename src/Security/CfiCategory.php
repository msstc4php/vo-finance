<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

/**
 * The first letter of a CFI code: the category of the instrument.
 *
 * Cases may be added in a minor release, so a `match` over them needs a default arm; backed values never change in
 * 1.x.
 *
 * @see https://www.six-group.com/dam/download/financial-information/data-center/cfi/cfi-20210507-current.xlsx SIX, ISO 10962 CFI list, sheet "Categories"
 * @see https://github.com/msstc4php/vo-finance/blob/main/docs/sources/FINANCE.md "CFI, ISO 10962"
 */
enum CfiCategory: string
{
    case Equities = 'E';
    case CollectiveInvestmentVehicles = 'C';
    case DebtInstruments = 'D';
    case Entitlements = 'R';
    case ListedOptions = 'O';
    case Futures = 'F';
    case Swaps = 'S';
    case NonListedAndComplexListedOptions = 'H';
    case Spot = 'I';
    case Forwards = 'J';
    case Strategies = 'K';
    case Financing = 'L';
    case ReferentialInstruments = 'T';
    case Others = 'M';
}
