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
  UnionPay rows) and American Express's page; Mastercard, Visa, Mir and RuPay have no listed range, and the summary's
  unmarked rows are shared by Discover, Diners Club International and PULSE and name no single scheme.
- EMVCo merchant-presented QR: `MerchantPresentedQr` (TLV parser with nested templates 26-51 and 62, CRC, accessors,
  `masked()`), `MerchantPresentedQrBuilder`, `MerchantAccount`, `DataObject`, `PointOfInitiation` and
  `InvalidPaymentQrException`.
- EMVCo QR fix round 1: the builder keeps insertion order (marker first, CRC last); `masked()` also hides the templates
  80-99 and the CRC (`6304****`); numeric and alphanumeric objects accept only printable ASCII, UTF-8 only in String
  objects; the 512-character refusal is dropped (7089-character abuse bound); `amount()` accepts `.5`; `MerchantAccount`
  validates its input. `DataObject` is a public type and `additionalData()` is `array<array-key, non-empty-string>` (PHP
  turns the key "10" into an integer).
- National EMVCo QR profiles: `PixBrCode` (BCB), `PromptPayQr` with `PromptPayProxyType` (Bank of Thailand), `DuitNowQr`
  (PayNet) and `PaymentQrFactory`. SGQR, QRIS, VietQR, KHQR, HK FPS, JPQR, QR Ph and Bharat QR are not built: no official
  specification was reached (README, `docs/sources/FINANCE.md`). `MerchantPresentedQr::masked()` now shares
  `Internal\EmvcoMask` with the profiles (no behaviour change); `MerchantAccount::subField()` is typed
  `non-empty-string|null`.
