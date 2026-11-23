# Changelog

## [1.0.0] - unreleased

First stable release: from here on a breaking change needs 2.0.

**Backward compatibility (README, "Backward compatibility").** 1.x covers public classes, methods, parameter names and
return types; canonical strings, JSON values and the `__serialize()` key; the `MAX_LENGTH` bounds and the bridge's type
names and column lengths; enum backed values and case names; exception classes and their parents. A minor never refuses
a value an earlier 1.x release accepted (a check that gets a source later is reported by a `hasValid…()` method), may
accept more only within `MAX_LENGTH`, may add enum cases (match with a default arm) and IIN table rows, and never
changes a factory's candidate set. `SecurityIdentifier`, `PaymentPayload` and `EmvcoPayload` are implemented by this
package only; methods may be added to them in minors. Not covered: exception message wording, `Internal\` and
`@internal`; `masked()` may hide more, never less.

### Added

- `Security\SecurityIdentifier`, implemented by every identifier and returned by `SecurityIdentifierFactory::fromString()`.
- `PaymentQr\PaymentPayload`, implemented by every payment payload and returned by `PaymentQrFactory::fromString()` and
  `fromBytes()`; `PaymentQr\Emvco\EmvcoPayload`, implemented by `MerchantPresentedQr` and the five profiles.
- On the profiles: `merchantAccounts()`, `postalCode()` (now on `PixBrCode` and `PromptPayQr` too), `writtenAmount()`,
  `additionalData()`, `additionalDataField()` and `get()`. `writtenAmount()` on `MerchantPresentedQr` and `EpcQr`;
  `additionalDataField()` on `MerchantPresentedQr`.
- Public `MAX_LENGTH` on every value object with a stored-value bound (and `JpqrCode::MAX_BYTES`), the bounds the
  bridge sizes its columns by. `Valor::MAX_LENGTH` is 12, above the nine characters accepted.
- `fromParts()` on `Lei`, `Figi`, `Sedol`, `Cusip` (all digits only), `CreditorReference` and `QrReference`: the check
  digits are computed with the algorithm the class enforces.
- `MerchantPresentedQr::toBuilder()`.

### Changed

- Requires `msstc4php/vo-base ^1.4` (was `^1.3`). `ext-bcmath` stays required: vo-base `Decimal::compareTo()`, on the
  amounts the payloads return, calls `bccomp()`.
- Exception messages hold no part of the input, not even its length: "ISIN must have 12 characters." (was "..., got
  13."), "FISN must have at most 35 characters.".
- The exception classes are `final`.
- `PaymentQrFactory`: when every identified profile refuses a payload that `MerchantPresentedQr` accepts, it returns
  `MerchantPresentedQr` (a Pix GUI with currency 840 gave the Pix refusal). Otherwise the first refusal is thrown, as
  before.
- `SecurityIdentifierFactory` no longer tries `Valor`, so a MIC, WKN, CFI, SEDOL or CUSIP is returned instead of an
  `AmbiguousValueException` with `Valor`; a value only a `Valor` accepts is refused. Build a `Valor` directly.
- `additionalData()` returns `list<DataObject>` in payload order, repeats included (was an array keyed by sub-ID, whose
  keys "10" to "99" PHP turned into integers).
- `EmvcoPayload` types: `merchantName()` is non-null on the profiles. `EmvcoPayload` declares
  `merchantCategoryCode()`, `merchantCity()` and `postalCode()` nullable (Thai QR makes 52 and 60 optional);
  `MerchantPresentedQr` keeps `string` for the first two, and `JpqrCode::postalCode()` keeps `string`.
- `PixBrCode::pixKeys()`, `paymentUrls()` and `recurrenceUrls()` give distinct values in payload order.
- `get()` returns a non-empty string or null everywhere: `Gost56042Payload::get()`, `SbpPaymentLink::get()`,
  `UpiPaymentUri::get()` and `writtenAmount()` read an empty value as absent. A repeat with another value, the empty
  one included (`am=&am=10`), still throws `AmbiguousPaymentQrFieldException`.
- `masked()` hides every field that names or locates a person, and all free text: names keep their first character
  (`X***`), addresses and free text become `***`. EPC beneficiary name, remittance text and beneficiary to originator
  information; QR-bill creditor and ultimate debtor, unstructured message and both alternative procedures (the billing
  information stays); EMVCo 59, 60, 61, 64-01, 64-02 and the purpose of transaction 62-08; UPI `pn` and note `tn`; GOST
  `Name`, `LastName`, `FirstName`, `MiddleName`, `ChildFio`, `SpecFio`, `PayerAddress`, `Flat`, `BirthDate` and
  `Purpose`, and `Phone` like an account (last two digits kept).
- `QrBillAddress` throws `InvalidPaymentQrException` (a subclass of the `InvalidArgumentException` it threw).
- `MerchantPresentedQrBuilder` has an argument-less constructor; the `MerchantAccount` constructor is `@internal`.
- `PaymentQrFactory` and `SecurityIdentifierFactory` have a private constructor; they are static only.
- Every enum documents that cases may be added in a minor; backed values are unchanged and pinned by a test.

### Removed

- The `@phpstan-type PaymentPayload` union of `PaymentQrFactory` (replaced by the interface) and
  `Security\CfiGroups` (now `Internal\CfiGroups`, it was `@internal`).

### Upgrading from 0.x

- Renamed: `EpcQr::information()` to `beneficiaryToOriginatorInformation()`; `Gost56042Payload::bic()` to `bik()`.
- Moved: `Security\CfiGroups` to `Internal\CfiGroups` (internal; use `Cfi::group()`).
- Removed: the `MerchantPresentedQrBuilder` constructor argument; start empty or from `MerchantPresentedQr::toBuilder()`.
- `additionalData()['05']` becomes `additionalDataField('05')`; iterate `additionalData()` as `DataObject`s.
- Code that typed the factory results with the eleven-class union types them with `PaymentPayload` (or narrows with
  `instanceof`); `SecurityIdentifierFactory::fromString()` returns `SecurityIdentifier` (was `ValueObject`).
- Code that caught `InvalidArgumentException` from `QrBillAddress` keeps working; catching `InvalidPaymentQrException`
  is the 1.x form. Subclasses of the exceptions are no longer possible.
- Code relying on `AmbiguousValueException` with `Valor` from `SecurityIdentifierFactory` now gets the other type, or
  `InvalidSecurityIdentifierException` for a value only a `Valor` takes.
- Code that used `PaymentQrFactory` as a validator of a profile (Pix, PromptPay, ...) checks the class: a payload that
  identifies a profile but fails its rules comes back as `MerchantPresentedQr` when that accepts it, instead of
  throwing. Test `instanceof PixBrCode` (or build `PixBrCode` directly, which still throws).
- `get()` on `Gost56042Payload`, `SbpPaymentLink` and `UpiPaymentUri`, and `UpiPaymentUri::writtenAmount()`, return
  `null` for `Alias=` / `am=` (was `''`): replace `=== ''` checks with `=== null`.
- `PixBrCode::pixKeys()`, `paymentUrls()` and `recurrenceUrls()` drop repeated values, so counts and indexes can differ;
  an `array_unique()` around them is no longer needed.
- `PaymentQrFactory` and `SecurityIdentifierFactory` have a private constructor: call them statically, never with `new`.
- Log pipelines that read names, addresses or free text (QR-bill message and alternative procedures, EPC remittance
  text, UPI `tn`, GOST `Purpose`, EMVCo 62-08) from `masked()` get `X***` and `***` instead; GOST `Phone` keeps its last
  two digits.
- `ext-bcmath` is still required (vo-base `Decimal::compareTo()` on `amount()`); keep it installed.
- No value object accepts or refuses differently (only the factory results change, as above): `tryFrom()` and
  `isValid()` of every value object accept exactly what 0.2.0 accepted, so 0.x rows need no data check before the
  upgrade. Stored data: no canonical string, serialized key or enum backed value changed, so 0.x rows keep loading.

### Upgrading from vo-base `Finance\CreditCardNumber` (deprecated since vo-base 1.3)

- Use `Card\PaymentCardNumber`; it accepts every number `CreditCardNumber` accepts (12 to 19 digits, Luhn) and also 10
  and 11 digits (ISO/IEC 7812-1). The canonical string is the same (digits, spaces and hyphens stripped).
- `brand()` (`visa`, `mastercard`, `amex`, `discover`, `unknown`) has no equivalent: `schemes()` decodes only ranges a
  reached publication assigns (Amex, JCB, UnionPay), so Visa, Mastercard and Discover numbers give an empty list.
- The exception is `InvalidPaymentCardNumberException` (was `InvalidCreditCardNumberException`); its message holds no
  length.
- `masked()` (first six and last four kept) replaces masking by hand; `last4()` is unchanged.
- Doctrine: the `vo_credit_card_number` type of vo-base-doctrine becomes `fin_payment_card_number` of
  vo-finance-doctrine (`VARCHAR(19)`); the stored strings are the same, so only the mapping changes.

## [0.2.0] - 2026-11-23

### Changed

- Requires `msstc4php/vo-base ^1.3` (was `^1.2`). vo-base 1.3 runs `parse()` again on `unserialize()`, so
  `Internal\ReparsesOnUnserialize` is removed and the payment payloads rely on the base. A forged value still throws `InvalidPaymentQrException`. Two changes: a serialized form without a
  string `value` now throws vo-base `InvalidValueObjectException` ("A serialized <class> must hold its value as a
  string.") instead of `InvalidPaymentQrException` ("A serialized payment payload must hold only its value, as a
  string."), and keys beside `value` are ignored instead of refused; the derived state is rebuilt from the value either
  way. `__unserialize()` on a built object says "A value object is immutable; ..." (was "A payment payload is
  immutable; ...").
  `InvalidPaymentQrException` extends `InvalidValueObjectException`: catch the latter to handle both cases.
- `^1.3` and not `^1.3.1` on purpose: 1.3.1 fixes vo-base's composite value objects, which this package does not use.

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
- Requires `ext-bcmath`: vo-base 1.2.0's `Iban`, used by `EpcQr` and `QrBill`, calls `bcmod()`. (The requirement was
  in fact kept, and stays in 1.0.0 for vo-base `Decimal::compareTo()`.)
