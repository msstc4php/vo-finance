<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Financial\Security;

/**
 * Names of the CFI groups, keyed by category and group letter, from the SIX list of ISO 10962 (edition 2021-05-07).
 * Decode-only: a code outside the table decodes as unknown, never as invalid.
 *
 * @internal
 *
 * @see https://www.six-group.com/dam/download/financial-information/data-center/cfi/cfi-20210507-current.xlsx SIX, ISO 10962 CFI list
 * @see docs/sources/FINANCE.md "CFI, ISO 10962"
 */
final class CfiGroups
{
    /** @var array<string, non-empty-string> */
    public const array NAMES = [
        'CB' => 'Real estate investment trust (REITs)',
        'CE' => 'Exchange traded funds (ETFs)',
        'CF' => 'Funds of funds',
        'CH' => 'Hedge funds',
        'CI' => 'Standard (vanilla) investment funds/mutual funds',
        'CM' => 'Others (miscellaneous)',
        'CP' => 'Private equity funds',
        'CS' => 'Pension funds',
        'DA' => 'Asset-backed securities',
        'DB' => 'Bonds',
        'DC' => 'Convertible bonds',
        'DD' => 'Depositary receipts on debt instruments',
        'DE' => 'Structured instruments (without capital protection)',
        'DG' => 'Mortgage-backed securities',
        'DM' => 'Others (miscellaneous)',
        'DN' => 'Municipal bonds',
        'DS' => 'Structured instruments (capital protection)',
        'DT' => 'Medium-term notes',
        'DW' => 'Bonds with warrants attached',
        'DY' => 'Money market instruments',
        'EC' => 'Common/ordinary convertible shares',
        'ED' => 'Depositary receipts on equities',
        'EF' => 'Preferred/preference convertible shares',
        'EL' => 'Limited partnership units',
        'EM' => 'Others (miscellaneous)',
        'EP' => 'Preferred/preference shares',
        'ES' => 'Common/ordinary shares',
        'EY' => 'Structured instruments (participation)',
        'FC' => 'Commodities futures',
        'FF' => 'Financial futures',
        'HC' => 'Credit',
        'HE' => 'Equity',
        'HF' => 'Foreign exchange',
        'HM' => 'Others (miscellaneous)',
        'HR' => 'Rates',
        'HT' => 'Commodities',
        'IF' => 'Foreign exchange',
        'IT' => 'Commodities',
        'JC' => 'Credit',
        'JE' => 'Equity',
        'JF' => 'Foreign exchange',
        'JR' => 'Rates',
        'JT' => 'Commodities',
        'KC' => 'Credit',
        'KE' => 'Equity',
        'KF' => 'Foreign exchange',
        'KM' => 'Others (miscellaneous)',
        'KR' => 'Rates',
        'KT' => 'Commodities',
        'KY' => 'Mixed assets',
        'LL' => 'Loan-lease',
        'LR' => 'Repurchase agreements',
        'LS' => 'Securities lending',
        'MC' => 'Combined instruments',
        'MM' => 'Other assets (miscellaneous)',
        'OC' => 'Call options',
        'OM' => 'Others (miscellaneous)',
        'OP' => 'Put options',
        'RA' => 'Allotment (bonus) rights',
        'RD' => 'Depositary receipts on entitlements',
        'RF' => 'Mini-future certificates, constant leverage certificates',
        'RM' => 'Others (miscellaneous)',
        'RP' => 'Purchase rights',
        'RS' => 'Subscription rights',
        'RW' => 'Warrants',
        'SC' => 'Credit',
        'SE' => 'Equity',
        'SF' => 'Foreign exchange',
        'SM' => 'Others (miscellaneous)',
        'SR' => 'Rates',
        'ST' => 'Commodities',
        'TB' => 'Baskets',
        'TC' => 'Currencies',
        'TD' => 'Stock dividends',
        'TI' => 'Indices',
        'TM' => 'Others (miscellaneous)',
        'TR' => 'Interest rates',
        'TT' => 'Commodities',
    ];
}
