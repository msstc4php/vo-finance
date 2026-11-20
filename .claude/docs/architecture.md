# Architecture

One area so far: `src/Internal/` holds `@internal final` check-digit helpers (Luhn, ISO 7064 MOD 97-10, ISIN, CUSIP,
SEDOL, FIGI, CRC-16/CCITT-FALSE, Swiss recursive modulo 10). The value objects arrive with Tasks 3 to 7 under
`Security\`, `Card\` and `PaymentQr\`. `deptrac.yaml` forbids every framework namespace; Doctrine lives in
`vo-finance-doctrine`.

`src/PaymentQr/Emvco/` holds the EMVCo merchant-presented QR: `MerchantPresentedQr` (parse, accessors, `masked()`),
`MerchantPresentedQrBuilder` (immutable), `MerchantAccount`, `DataObject` and `PointOfInitiation`. The reader and writer
of the ID/length/value encoding are `Internal\EmvcoTlv`. National profiles (Task 6) build on these.
