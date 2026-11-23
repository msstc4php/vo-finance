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
- `masked()`: EPC/QR-bill IBAN line, GOST `PersonalAcc`/`PersAcc`/`PersonalAccount`/`PensAcc`/`PayerIdNum`/`PayerINN`,
  SBP identifier, UPI `pa` (rest of the URI as written); `Mask::keepEnds` keeps nothing at <= 6 characters, the last 2
  at 7-11, the last 4 at 12-17, first 2 + last 4 from 18; references stay visible. EMVCo `masked()` also hides 62-02
  (mobile number) and 62-06 (customer label).
- Personal data in `masked()` (owner ruling O4): `Mask::name()` keeps the first character + `***` (length not kept),
  `Mask::ADDRESS` is `***`. EPC line 5; QR-bill name/street/building/postal/town of creditor and ultimate debtor (empty
  elements stay empty, type and country stay); EMVCo 59, 60, 61, 64-01, 64-02 (64 not TLV: `***` whole); UPI `pn`;
  GOST `name`, `lastname`, `firstname`, `middlename`, `childfio`, `payeraddress` (empty values stay empty).
