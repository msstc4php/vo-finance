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
  (PayNet) and `PaymentQrFactory`. SGQR, QRIS, VietQR, KHQR, HK FPS, JPQR, QR Ph and Bharat QR are not built: no
  official specification was reached (README, `docs/sources/FINANCE.md`). `MerchantPresentedQr::masked()` now shares
  `Internal\EmvcoMask` with the profiles (no behaviour change); `MerchantAccount::subField()` is typed
  `non-empty-string|null`.
- National profiles fix round 1: `PixBrCode` accepts several Pix templates and a key with a URL (list accessors
  `pixKeys()`, `paymentUrls()`, `recurrenceUrls()`; the single-value accessors throw when values differ) and skips
  non-TLV templates 80-99; `PromptPayQr` accepts several proxies (`proxyTypes()`, `proxyOf()`) and compares AIDs
  case-insensitively; failures in the generic format name the profile; `PaymentQrFactory::fromString()` returns the
  union of the four classes.
- National profiles fix round 2: `AmbiguousPaymentQrFieldException` thrown by single-value accessors facing several
  distinct values; the 7089-character bound runs on the original payload only
  (`MerchantPresentedQr::withoutLengthBound()`, internal); a Pix template in 80-99 without sub-ID 25 is refused again;
  `DuitNowQr` compares the AID case-insensitively.
- National profiles fix round 3: the generic validation moved to the stateless internal `EmvcoMerchantPresented`
  (`MerchantPresentedQr::withoutLengthBound()` and its static flag are gone; profiles validate a modified copy without a
  twin instance); `AmbiguousPaymentQrFieldException` takes a reason from the call site.
- More national EMVCo QR profiles: `KhqrCode` (National Bank of Cambodia, KHQR specification 3.0) and `JpqrCode`
  (Payments Japan MPM guideline 2.0), both in `PaymentQrFactory`; `PixBrCode::pixKeyType()` with `PixKeyType` decoded
  from the BCB DICT formats (null for any other key, never refused). SGQR, QRIS, VietQR, HK FPS, Bharat QR and QR Ph
  stay unbuilt (reasons in the README). `ProfilePayload` names its modified copy accordingly (no behaviour change).
- National profiles 6b fix round 1: `JpqrCode` refuses payloads over 512 bytes (Payments Japan); the internal
  `ProfilePayload::templateFields()` replaces three copies of the template-64 reader (KHQR, JPQR, DuitNow); search log,
  SGQR and HK FPS verdicts and the KHQR/JPQR source notes corrected in `docs/sources/FINANCE.md`.
- Non-EMVCo payment payloads: `Epc\EpcQr` (EPC069-12 v3.0, versions 001 and 002, `fromBytes()` for character sets 2 to
  8), `Swiss\QrBill` with `QrBillAddress` and `QrBillReferenceType` (SIX IG 2.3; QR-IBAN only with `QRR`, IBAN only
  with `SCOR` or `NON`), `Ru\Gost56042Payload` (GOST R 56042-2014 with the Bank of Russia account control key,
  `fromBytes()` for WIN1251 and KOI8-R), `Ru\SbpPaymentLink` (format only) and `India\UpiPaymentUri` (NPCI OC 18
  parameters); `Reference\QrReference`, `Reference\CreditorReference` and `InvalidPaymentReferenceException`.
  `PaymentQrFactory::fromString()` routes them by leading marker before the EMVCo profiles. Line separators are kept
  byte for byte; EPC refuses a separator after its last element, and on the QR-bill a final separator is one more
  (empty) element. ext-mbstring is suggested for `fromBytes()`.
- Payload fix round 1: `Gost56042Payload` checks the Order 515 key only within its scope (treasury accounts starting
  with 0 are accepted) and on every occurrence of `PersonalAcc`/`CorrespAcc`, and masks `PersonalAccount`, `PensAcc`,
  `PayerIdNum` too; `EpcQr` refuses a separator after the last populated element and bounds 331 bytes (331 characters
  for text of one ISO 8859 set); `QrBill` counts a final separator as an element; `PaymentQrFactory::fromBytes()`;
  `UpiPaymentUri::masked()` keeps the URI as written; `QrBillAddress` validates its constructor arguments;
  `@throws LogicException` declared on the `fromBytes()` methods.
- Payload fix round 2: the EPC 331-character exception for text of one ISO 8859 set no longer needs ext-mbstring
  (`Internal\Iso8859`, tables from the Unicode Consortium mapping files), so `EpcQr` validity does not depend on the
  host.
