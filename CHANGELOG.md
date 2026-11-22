# Changelog

## [0.1.0] - 2026-11-22

### Added

- Package scaffold, CI and tool configuration from `vo-gs1`; source notes in `docs/sources/FINANCE.md`, the authority
  for every rule (enforced, format only, or not built, each with its reason).
- Securities and entity identifiers: `Isin` (check digit; `cusip()` for a US or CA prefix, `sedol()` for GB or IE),
  `Lei`, `Upi` (format only, the check character is not verified), `Cfi` (decoded with `CfiCategory`), `Fisn` (ISO 8859-1
  capitals, lowercase folded), `Mic`, `Figi` (the seven excluded prefixes of section 6.1 are refused), `Cusip` (check
  digit for an all-digit CUSIP only), `Sedol`, `Wkn`, `Valor` (one to nine ASCII letters or digits), all stored
  uppercase; `SecurityIdentifierFactory` builds the one type that accepts a value and throws `AmbiguousValueException`
  listing every accepting type; `InvalidSecurityIdentifierException`.
- Payment cards: `PaymentCardNumber` (ISO/IEC 7812-1, 10 to 19 digits, Luhn, `masked()`), `CardScheme` (Amex, JCB,
  UnionPay) and `InvalidPaymentCardNumberException`. The IIN table holds only the ranges Discover's IIN Range Summary
  (JCB and UnionPay rows) and American Express's page give; Mastercard, Visa, Mir and RuPay have no listed range, and
  the summary's unmarked rows name no single scheme. The table changes in minor releases.
- EMVCo merchant-presented QR: `MerchantPresentedQr` (TLV with nested templates 26-51 and 62, CRC-16/CCITT-FALSE,
  accessors, `masked()` hiding IDs 02-51, 80-99 and the CRC), the immutable `MerchantPresentedQrBuilder`,
  `MerchantAccount`, `DataObject`, `PointOfInitiation` and `InvalidPaymentQrException`. Lengths count characters; a
  7089-character abuse bound applies instead of the 512-character recommendation.
- National EMVCo profiles: `PixBrCode` (BCB; `PixKeyType` decoded from the DICT formats, never enforced), `PromptPayQr`
  (`PromptPayProxyType`), `DuitNowQr`, `KhqrCode` (National Bank of Cambodia) and `JpqrCode` (Payments Japan, at most
  512 bytes). A payload may carry several templates; single-value accessors throw `AmbiguousPaymentQrFieldException`
  when the values differ and the list accessors (`pixKeys()`, `proxyTypes()`, `bakongAccountIds()`, `storeIds()` and
  others) return all of them. SGQR, QRIS, VietQR, HK FPS, Bharat QR and QR Ph are not built: no official specification
  was reached (README, `docs/sources/FINANCE.md`).
- Non-EMVCo payment payloads: `Epc\EpcQr` (EPC069-12 v3.0, versions 001 and 002), `Swiss\QrBill` with `QrBillAddress`
  and `QrBillReferenceType` (SIX IG 2.3), `Ru\Gost56042Payload` (GOST R 56042-2014 with the Bank of Russia account
  control key of Order 515, treasury accounts exempt), `Ru\SbpPaymentLink` (format only) and `India\UpiPaymentUri`
  (NPCI OC 18 parameters); `fromBytes()` on `EpcQr` and `Gost56042Payload` transcodes a declared single-byte character
  set (needs ext-mbstring, suggested). EPC holds a payload to 331 bytes, or 331 characters when every character has one
  byte in one ISO 8859 set (tables of the Unicode Consortium, no extension needed). Line separators are kept byte for
  byte.
- Payment references: `Reference\QrReference` (27 digits, recursive modulo 10) and `Reference\CreditorReference`
  (ISO 11649 `RF`, MOD 97-10), with `InvalidPaymentReferenceException`.
- `PaymentQrFactory::fromString()` and `fromBytes()`: routing by leading marker (`BCD`, `SPC`, `ST0001`,
  `https://qr.nspk.ru/`, `upi://`), otherwise the EMVCo profiles the payload identifies by GUI or AID, of which the one
  that accepts it is built; several accepting profiles throw `AmbiguousValueException`, identified profiles that all
  refuse throw the first one's refusal, and only a payload that identifies no built profile gives `MerchantPresentedQr`.
- Every payment card and payment QR value object implements vo-base `Maskable`; `__toString()` and `jsonSerialize()`
  give the full value. Securities and entity identifiers have no `masked()`. Account identifiers are masked to at most
  about a third of their characters; EMVCo `masked()` also hides the mobile number and customer label of template 62,
  and GOST `masked()` the payer's `PayerINN`.
- Payment QR value objects serialize as their payload alone and validate it again on `unserialize()`.
- GOST, SBP and UPI payloads share the EMVCo classes' 7089-character abuse bound.
- Requires `ext-bcmath`: vo-base 1.2.0's `Iban`, used by `EpcQr` and `QrBill`, calls `bcmod()`. The release switches
  to `msstc4php/vo-base ^1.3`, whose `Iban` needs no bcmath, and drops the requirement.
