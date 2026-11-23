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
  with either the RKC (`0`+BIC[5..6]) or credit-institution (BIC[7..9]) number.
- `masked()` (1.0 masking principle, fix10/fix11): hide what names or locates a person and all free text; keep
  machine codes. The per-class list is the README table "Payment QR payloads" (single source; keep code, README,
  CHANGELOG and FINANCE.md in step). Helpers: `Mask::keepEnds` (accounts/identifiers: nothing at <= 6, last 2 at 7-11,
  last 4 at 12-17, first 2 + last 4 from 18), `Mask::name` (first char + `***`), `Mask::ADDRESS` (locating data),
  `Mask::TEXT`/`text()` (free text; empty stays empty). A slot meant for a code (EPC reference, QR-bill billing
  information) stays visible only when it holds that code (RF creditor reference / starts with `//`); parse is not
  stricter. SBP fails closed: only `type`, `bank`, `sum`, `cur`, `crc` values stay. EMVCo 62: 02/04/06/10 and 50-99
  asterisks, 08 `***`.
