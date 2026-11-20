# Known issues

- ISIN check digit: ISO 6166 Annex C is paywalled; enforced on the strength of SET's three worked examples and ANNA's naming.
- CUSIP: no official value for letters or `*@#`; the check is for all-digit CUSIPs only, the rest is format only.
- ISO 11649:2009 Annex A.3 prints `RF68 5390 0754 7034`, which fails clause 6.2; the valid pair is 18 (SIX IG, EPC069-12).
- OMG FIGI 1.0 lists 5 excluded prefixes in clause 2 and 7 (`GH`, `KY` too) in 6.1 and the regex.
- ISO/IEC 13239 is the reflected X.25 CRC; the EMVCo CRC (poly 1021, init FFFF, no reflection, no XOR) is fixed by the BCB payload `...6304AD38`.
- No official card-number vector for Luhn; no no-Luhn range in any reached publication.
- UPI (ISO 4914), Valor, Mastercard, Mir ranges, SBP and NPCI UPI documents: unreachable or unconfirmed (FINANCE.md).
- The Internet Archive was offline mid-research (2026-11-20); WebFetch cannot reach web.archive.org, curl can (allowed domain).
- Mastercard and Visa developer test-card pages are script-rendered: curl returns no numbers.
- Discover's IIN Range Summary prints the `a`/`b` footnote markers as superscripts above each row in text extraction; the marker belongs to the row below it (checked with `pdftotext -bbox-layout`). 3337-3349 is JCB. Unmarked rows (Discover/Diners/PULSE) name no scheme, so `IinRanges` omits them.
- `PaymentCardNumber::masked()` uses `*` for hidden digits, keeps `min(6, length - 5)` leading and 4 trailing digits.
- `IinRanges::match()` takes the table as a parameter only so tests can exercise a co-badged range; the real table has no overlaps.
