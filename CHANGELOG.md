# Changelog

## [Unreleased]

### Added

- Package scaffold, CI and tool configuration from `vo-gs1`.
- Source notes `docs/sources/FINANCE.md`.
- Internal check-digit helpers: Luhn, ISO 7064 MOD 97-10, ISIN, CUSIP, SEDOL, FIGI, CRC-16/CCITT-FALSE and the Swiss
  recursive modulo 10.
- Securities and entity identifiers: `Isin`, `Lei`, `Upi`, `Cfi` (with `CfiCategory`), `Fisn`, `Mic`, `Figi`, `Cusip`,
  `Sedol`, `Wkn`, `Valor`, `SecurityIdentifierFactory` and `InvalidSecurityIdentifierException`.
- Fixes after review: `Upi` enforces the ROC format, `Valor` stays format only (one to nine ASCII letters or digits),
  `Fisn` accepts ISO 8859-1 capitals and folds ISO 8859-1 lowercase letters, every identifier folds to uppercase, and
  `SecurityIdentifierFactory` lists every accepting type.
- Payment cards: `PaymentCardNumber` (ISO/IEC 7812-1, 10 to 19 digits, Luhn, `masked()`), `CardScheme` (Amex, JCB,
  UnionPay) and `InvalidPaymentCardNumberException`. IIN ranges come only from Discover's IIN Range Summary (JCB and
  UnionPay rows) and American Express's page; Mastercard, Visa, Mir, RuPay, Discover and Diners have no listed range.
