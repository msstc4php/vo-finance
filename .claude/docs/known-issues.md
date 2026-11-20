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
- Discover's IIN Range Summary prints the `a`/`b` footnote markers as superscripts above each row in text extraction;
  the marker belongs to the row below it (checked with `pdftotext -bbox-layout`). 3337-3349 is JCB. The unmarked rows
  are shared by Discover, Diners Club International and PULSE and name no single scheme, so `IinRanges` omits them.
- `PaymentCardNumber::masked()` uses `*` for hidden digits, keeps `min(6, length - 5)` leading and 4 trailing digits.
- `IinRanges` takes its table in the constructor (`IinRanges::default()` is the real one) so tests can use a synthetic
  table with a co-badged range; the real table has no overlaps.
- EMVCo QRCPS 1.1 is reached only through a third-party copy (emvco.com 404, no archive copy); HKMA and BCB quote the same tables. The 512-character payload limit is a "should" there and appears in neither HKMA nor BCB.
- EMVCo lengths count characters, not bytes: `Internal\EmvcoTlv` splits the payload with `preg_split('//u')` before slicing.
- `MerchantPresentedQr::additionalData()` returns `array<array-key, non-empty-string>`: PHP turns the key `"10"` into an integer; look values up with the string ID.
- `Decimal` normalizes `10.50` to `10.5`; the written form stays in `get('54')`. A trailing mark (`98.`) is valid in EMVCo and is trimmed before parsing.
- BCB sets the postal code (ID 61) up to 99 characters where EMVCo and HKMA say 10: root value maxima are not enforced.
- String-format objects (sub-fields of 26-51, 62 sub-IDs 12-99, templates 64-99) accept UTF-8 and their lengths count characters; a decoder that counts bytes reads such a payload differently. That differential is spec-mandated (EMV QRCPS 4.4.1.1, 4.5.3); N/ans objects are limited to printable ASCII so they cannot show it.
- `masked()` hides IDs 02-51, 80-99 and the CRC (`6304****`); the masked string is no valid payload.
- Annex B.7 of the EMVCo document has line breaks inside values in its printed text; use the hexadecimal representation printed under it.
- The GUI (sub-ID 00) is accepted anywhere in its template (EMV QRCPS 4.6.1); `MerchantPresentedQrBuilder` writes it first.
- DuitNow uses payload format indicator `02` and Thai QR makes ID 52 and 60 optional; the generic `MerchantPresentedQr` refuses both. `ProfilePayload` validates a twin (indicator 01, placeholders `0000` / `NA`, CRC recomputed) so no structural logic is duplicated; never expose the twin.
- The BCB's own BR Code example (manual 2.0.1, 2.2) has the txid `RP12345678-2019`; the Pix manual (2.6.2) says letters and digits only. `PixBrCode` does not enforce the charset.
- Pix dynamic and composite codes: value and txid in the QR are ignored by the payer (Pix manual 2.7); `txid()` returns null for `***`.
- PayNet's DuitNow pages are flagged "no longer updated" (V1.5); a newer PayNet docs site exists and was not read. Its four examples have valid CRCs.
- The Thai guideline prints no full payload; PromptPay tests are synthetic. Thai tag 29 AID `...114` is for customer-presented QR and is refused.
- bi.go.id drops curl connections; files there were read through WebFetch (binary saved by the harness). A browser-like User-Agent was not used.
- `Internal\ProfileRule::atMost` replaces min/max pairs: every present data object has at least one character, so the minimum 1 is implied.
