# Domain model

Every value object is a string value object with the canonical form stored uppercase where the identifier is
case-insensitive. Exceptions are `Financial\Exception\Invalid*Exception` (messages never carry the input);
`docs/sources/FINANCE.md` states per rule whether it is enforced or format only. Registry data decodes, never refuses
(IIN ranges, CFI tables, `Isin::countryCode()`), and a value that fits several types is `AmbiguousValueException` in the
two factories.

## Securities and entity identifiers

`Isin` (check digit; `cusip()` for US/CA, `sedol()` for GB/IE, null elsewhere), `Lei`, `Upi` (format only), `Cfi`
(`category()`/`group()` null for an unknown letter), `Fisn`, `Mic` (format only), `Figi`, `Cusip` (check digit for an
all-digit value only), `Sedol`, `Wkn`, `Valor` (format only, accepted 1..9, `MAX_LENGTH` 12 as headroom; not a
factory candidate). No `masked()`. `fromParts()` on `Lei`, `Figi`, `Sedol`, `Cusip` (digits only), and on the two
references.

## Payment cards

`Card\PaymentCardNumber` (Maskable): 10..19 digits, Luhn, spaces/hyphens stripped; `iin()` 8 digits, `schemes()` from
`Internal\IinRanges::default()` (Amex, JCB, UnionPay only), `matchesSchemeLength()` false when no scheme.

## Non-EMVCo payment payloads

- `EpcQr` / `QrBill` store the payload byte-exact; `TextLines` counts every separator as an element boundary. EPC: 7..12
  elements, mixed separators allowed, last element non-empty (no trailing separator). QR-bill: 31..34 elements, uniform
  separator, a final LF is an empty element.
- Constructors take UTF-8; a declared non-UTF-8 charset (EPC 2-8, GOST 1/3) is accepted there only for pure-ASCII text;
  `fromBytes()` transcodes and rewrites the charset element (EPC `1`, GOST `2`).
- QR-bill: QR-IBAN (IBAN chars 5-9 in 30000..31999) <=> `QRR`; plain IBAN => `SCOR`/`NON`. `reference()` returns
  `QrReference|CreditorReference|null`. Amount 0.00 accepted (notification bill).
- GOST: last occurrence of an alias wins (standard); every occurrence of a mandatory alias must have its format;
  20-digit accounts in Order 515's scope (first digit 1-9; treasury `0...` exempt), every occurrence, must pass the key
  with either the RKC (`0`+BIC[5..6]) or credit-institution (BIC[7..9]) number. Since 1.2.0 an account may carry an
  УФЭБС album letter (`ABCEHKMPTX`) in place 6; it is outside the key scope (unverified key).
- SBP (1.2.0): hosts `qr.nspk.ru`, `sub.nspk.ru`, `b2b.cbrpay.ru`; `qrType(): ?SbpQrType`; NSPK's table (QR_ID 32,
  type/host pairing, bank 12, sum 1-12 digits for 02 / absent for 03, cur with sum, crc 4) is report-only via
  `hasValidFunctionalLinkStructure()`. GOST `hasValidBudgetPaymentDetails()` is report-only (УФЭБС budget checks).
- `masked()` (1.0 rule, fix10..fix11 round 4): fail-closed on every payment profile. A field is visible only on the
  profile's allowlist AND in its shape; everything else (unknown alias, RFU, bare pair, unknown parameter name) is
  masked. The per-profile tables in the README ("Payment QR payloads") are the single source; keep code, README,
  CHANGELOG and FINANCE.md in step. Masks: `Mask::keepEnds` (accounts/identifiers), `Mask::name` (`X***`),
  `Mask::reference` (first + `***` + last, `***` at <= 3 characters: EMVCo 62-01/03/05/07, GOST DocNo/DocIdx/QuittId/
  UIN, UPI tr/tid), `Mask::TEXT`/`text()` (`***`, empty stays empty). SBP and UPI rebuild the query with
  `QueryString::rewritten()`. `MaskedLeakTest` probes every slot of every profile with a name, an e-mail and a phone
  number; update its pinned counts when parse or the slot list changes.