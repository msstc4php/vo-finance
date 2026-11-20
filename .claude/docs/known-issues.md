# Known issues

- ISIN check digit: ISO 6166 Annex C is paywalled; the formula is named by ANNA, the procedure is not quoted. See FINANCE.md.
- CUSIP: letter values and `*@#` (36, 37, 38) not printed in any CGS publication reached.
- ISO 11649:2009 Annex A.3 prints `RF68 5390 0754 7034`, which fails clause 6.2; the valid pair is 18.
- OMG FIGI 1.0 lists 5 excluded prefixes in clause 2 and 7 (`GH`, `KY` too) in 6.1 and the regex.
- No source reached for a no-Luhn card range; Luhn is enforced for all.
- UPI (ISO 4914), Valor length, Amex/Visa/Mir/RuPay IIN ranges, SBP and NPCI documents: unreachable at Task 2.
- The Internet Archive was offline mid-research (2026-11-20); WebFetch cannot reach web.archive.org, curl can (allowed domain).
