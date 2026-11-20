# Changelog

## [Unreleased]

### Added

- Package scaffold, CI and tool configuration from `vo-gs1`.
- Source notes `docs/sources/FINANCE.md`.
- Internal check-digit helpers: Luhn, ISO 7064 MOD 97-10, ISIN, CUSIP, SEDOL, FIGI, CRC-16/CCITT-FALSE and the Swiss
  recursive modulo 10.
- Securities and entity identifiers: `Isin`, `Lei`, `Upi`, `Cfi` (with `CfiCategory`), `Fisn`, `Mic`, `Figi`, `Cusip`,
  `Sedol`, `Wkn`, `Valor`, `SecurityIdentifierFactory` and `InvalidSecurityIdentifierException`.
- Fixes: `Upi` enforces the ROC format, `Valor` stays format only, `Fisn` accepts ISO 8859-1 capitals, every identifier folds to uppercase, and `SecurityIdentifierFactory` lists every accepting type.
- Fixes: `Valor` stays format only (one to nine ASCII letters or digits), `Fisn` folds ISO 8859-1 lowercase letters to capitals.
