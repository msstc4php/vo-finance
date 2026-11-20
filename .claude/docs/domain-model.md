# Domain model

Empty until Task 3. Canonical forms and the exception family (`Financial\Exception\Invalid*Exception`) are fixed by the
finance wave plan; `docs/sources/FINANCE.md` states per rule whether it is enforced or format only.

## Payment cards

`Card\PaymentCardNumber` (Maskable): 10..19 digits, Luhn, spaces/hyphens stripped; `iin()` 8 digits, `schemes()` from
`Internal\IinRanges::default()` (Amex, JCB, UnionPay only), `matchesSchemeLength()` false when no scheme.
