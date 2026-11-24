# Architecture

Areas under `src/`: `Security\` (securities and entity identifiers, the `SecurityIdentifier` interface,
`SecurityIdentifierFactory`), `Card\`
(`PaymentCardNumber`, `CardScheme`), `Reference\` (`QrReference`, `CreditorReference`), `PaymentQr\` (EMVCo, national
profiles, non-EMVCo payloads, `PaymentQrFactory`), `Exception\` (one family per area) and `Internal\`.
Public interfaces (G1: implemented by this package only, methods may be added in minors): `Security\SecurityIdentifier`,
`PaymentQr\PaymentPayload` (every payload, returned by `PaymentQrFactory`), `PaymentQr\Emvco\EmvcoPayload` (the
generic class and the five profiles; the profiles get it from `Internal\ExposesRootFields`).
`src/Internal/` holds `@internal final` helpers (also `CfiGroups`, moved from `Security\` for 1.0): the check digits (Luhn, ISO 7064 MOD 97-10, ISIN, CUSIP, SEDOL, FIGI,
CRC-16/CCITT-FALSE, Swiss recursive modulo 10) and the payload machinery below. `deptrac.yaml` forbids every framework
namespace; Doctrine lives in `vo-finance-doctrine`.

`src/PaymentQr/Emvco/` holds the EMVCo merchant-presented QR: `MerchantPresentedQr` (parse, accessors, `masked()`),
`MerchantPresentedQrBuilder` (immutable), `MerchantAccount`, `DataObject` and `PointOfInitiation`. The reader and writer
of the ID/length/value encoding are `Internal\EmvcoTlv`. The stateless `Internal\EmvcoMerchantPresented` holds the
validation (bounded by 7089 characters or not) and the field derivations (`get`, `pointOfInitiation`, `accounts`,
`amount`, `currency`, `countryCode`, `additionalData`); `MerchantPresentedQr` and `Internal\ProfilePayload` delegate
to it.
National profiles: `PaymentQr\Brazil\PixBrCode`, `PaymentQr\Thailand\PromptPayQr` (+ `PromptPayProxyType`),
`PaymentQr\Malaysia\DuitNowQr`, `PaymentQr\Cambodia\KhqrCode`, `PaymentQr\Japan\JpqrCode`, `PaymentQr\Brazil\PixKeyType`
(decode-only enum behind `PixBrCode::pixKeyType()`), and `PaymentQr\PaymentQrFactory` (the profiles the payload
identifies by GUI/AID marker; the generic `MerchantPresentedQr` when none is identified, or when every identified one
refuses and the generic class accepts (owner ruling O3); otherwise the first refusal; several accepting profiles throw
`AmbiguousValueException`). Each profile is an `AbstractStringValueObject` that parses through
`Internal\ProfilePayload`, which checks the length bound on the original and runs
`EmvcoMerchantPresented::validate()` unbounded on a modified copy (format indicator forced to 01, missing category/city
filled with placeholders when the profile allows, CRC recomputed) while keeping the original objects for `get()`,
`pointOfInitiation()` and `masked()` (`Internal\EmvcoMask`). Shared accessors come from the `Internal\ExposesRootFields`
trait; checks use `Internal\ProfileRule`.

Non-EMVCo payloads: `PaymentQr\Epc\EpcQr`, `PaymentQr\Swiss\QrBill` (+ `QrBillAddress` readonly DTO,
`QrBillReferenceType` enum), `PaymentQr\Ru\Gost56042Payload`, `PaymentQr\Ru\SbpPaymentLink` (`SbpQrType` enum), `PaymentQr\India\UpiPaymentUri`;
references `Reference\QrReference`, `Reference\CreditorReference`. Helpers: `Internal\Iso8859` (whether text fits one ISO 8859 set of EPC069-12 section 2.1; PCRE classes generated
from the Unicode 8859-N.TXT tables, no extension), `Internal\TextLines` (line split keeping every
LF/CRLF separator, rebuild with one line replaced), `Internal\QueryString` (raw `name=value` pairs, no decoding),
`Internal\Transcoder` (mbstring, single-byte charset to UTF-8), `Internal\Mask` (at most about a third kept, by
length tier), `Internal\PayloadBound` (the shared 7089-character abuse bound); serialization of the value alone with `parse()`
run again on unserialize comes from vo-base `^1.3` (0.1.0 had its own `Internal\ReparsesOnUnserialize`),
`Internal\RussianAccountKey` (Order 515). `PaymentQrFactory` dispatches by marker (`BCD`, `SPC`, `ST0001`,
`https://qr.nspk.ru/`, `https://sub.nspk.ru/`, `https://b2b.cbrpay.ru/` (`SBP_PREFIXES`, since 1.2.0) and `upi://` case-insensitive) before the EMVCo profiles; a marked payload never falls back to EMVCo.
