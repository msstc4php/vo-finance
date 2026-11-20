# Architecture

One area so far: `src/Internal/` holds `@internal final` check-digit helpers (Luhn, ISO 7064 MOD 97-10, ISIN, CUSIP,
SEDOL, FIGI, CRC-16/CCITT-FALSE, Swiss recursive modulo 10). The value objects arrive with Tasks 3 to 7 under
`Security\`, `Card\` and `PaymentQr\`. `deptrac.yaml` forbids every framework namespace; Doctrine lives in
`vo-finance-doctrine`.

`src/PaymentQr/Emvco/` holds the EMVCo merchant-presented QR: `MerchantPresentedQr` (parse, accessors, `masked()`),
`MerchantPresentedQrBuilder` (immutable), `MerchantAccount`, `DataObject` and `PointOfInitiation`. The reader and writer
of the ID/length/value encoding are `Internal\EmvcoTlv`.
National profiles: `PaymentQr\Brazil\PixBrCode`, `PaymentQr\Thailand\PromptPayQr` (+ `PromptPayProxyType`),
`PaymentQr\Malaysia\DuitNowQr`, and `PaymentQr\PaymentQrFactory` (profiles, else the generic `MerchantPresentedQr`;
several profiles throw `AmbiguousValueException`). Each profile is an `AbstractStringValueObject` that parses through
`Internal\ProfilePayload`, which runs `MerchantPresentedQr` on a twin of the payload (format indicator forced to 01,
missing category/city filled with placeholders when the profile allows, CRC recomputed) while keeping the original
objects for accessors and `masked()` (`Internal\EmvcoMask`). Shared accessors come from the `Internal\ExposesRootFields`
trait; checks use `Internal\ProfileRule`.
