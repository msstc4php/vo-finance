# Domain model

Empty until Task 3. Canonical forms and the exception family (`Financial\Exception\Invalid*Exception`) are fixed by the
finance wave plan; `docs/sources/FINANCE.md` states per rule whether it is enforced or format only.

## Payment cards

`Card\PaymentCardNumber` (Maskable): 10..19 digits, Luhn, spaces/hyphens stripped; `iin()` 8 digits, `schemes()` from
`Internal\IinRanges::default()` (Amex, JCB, UnionPay only), `matchesSchemeLength()` false when no scheme.

## Non-EMVCo payment payloads

- `EpcQr` / `QrBill` store the payload byte-exact; `TextLines` drops one trailing separator before counting elements, so
  `X\n` round-trips and the LF/CRLF/trailing forms are distinct values. QR-bill: 31..34 elements, uniform separator; EPC:
  7..12 elements, mixed separators allowed.
- Constructors take UTF-8; a declared non-UTF-8 charset (EPC 2-8, GOST 1/3) is accepted there only for pure-ASCII text;
  `fromBytes()` transcodes and rewrites the charset element (EPC `1`, GOST `2`).
- QR-bill: QR-IBAN (IBAN chars 5-9 in 30000..31999) <=> `QRR`; plain IBAN => `SCOR`/`NON`. `reference()` returns
  `QrReference|CreditorReference|null`. Amount 0.00 accepted (notification bill).
- GOST: last occurrence of an alias wins (standard); every occurrence of a mandatory alias must have its format;
  20-digit accounts must pass the Order 515 key with either the RKC (`0`+BIC[5..6]) or credit-institution (BIC[7..9])
  number.
- `masked()`: EPC/QR-bill IBAN line, GOST `PersonalAcc`/`PersAcc`, SBP identifier, UPI `pa`; first 2 + last 4 kept.
