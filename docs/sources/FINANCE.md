# Finance identifier sources

Every rule that `vo-finance` enforces is quoted here from an official source: the issuing body or registration
authority, a regulator's publication, a standard cited by number (a publisher's unmodified preview counts), or an
unmodified Internet Archive copy of one. An aggregator counts only "as quoted from". What could not be quoted is marked
**unreachable** or **not confirmed**, and the class checks the format only (or uses the rule with the gap stated).

Research date: 2026-11-20 (UTC). Scope of this file at Task 2: the sources of the check-digit helpers and of the
securities and card classes (Tasks 3 and 4). The EMVCo QR body, the national profiles, EPC069-12, the QR-bill layout,
GOST R 56042, NSPK and NPCI are researched by Tasks 5 to 7; their headings are placeholders at the end.

Status words: **reached** (read the source text), **preview** (publisher's unmodified preview of a paid standard),
**archive** (Internet Archive copy), **unreachable** (could not be read), **not confirmed** (reached only a secondary
statement).

## Summary

| Topic | Source | Status | What it fixes |
|---|---|---|---|
| ISIN structure | ISO 6166:2013 preview; ANNA identifiers page; ANNA ISIN Guidelines Jun 2026 | preview, reached | 12 characters: prefix, 9-character basic number, check digit |
| ISIN prefixes | ANNA, RA list of ISIN prefixes, January 2024 | reached | `XS`, `EU`, `EZ`, `XA`-`XD` and `XT` exist beside ISO 3166 codes: the prefix is never refused |
| ISIN check digit | ISO 6166 Annex C; ANNA | **not confirmed** | the formula is named by ANNA and ISO 6166 clause 4.1; the Annex C text is paywalled |
| LEI | ISO 17442:2012 preview (full text of clauses 4 to 5 and Annex A); GLEIF | preview, reached | 20 characters, check pair by ISO/IEC 7064 MOD 97-10, examples |
| UPI (ISO 4914) | ANNA DSB; ISO 4914 | **unreachable** | format and check character are researched by Task 3 |
| CFI | ISO 10962:1997 preview; SIX CFI page | preview, reached | six letters, `X` is "not applicable"; the code list is a SIX download |
| FISN | ANNA FISN Guidelines, June 2026 (ISO 18774 2nd edition) | reached | at most 35 characters, issuer name at most 15, `/`, description |
| MIC | ISO 10383 RA FAQ (January 2023) and Release 2.0 factsheet | reached | four alphanumeric characters, `[A-Z0-9]{4}` |
| FIGI | OMG FIGI 1.0, formal/2015-12-01 | reached | syntax, excluded prefixes, check digit |
| CUSIP | CUSIP Global Services publications | reached, partly | nine characters; "Modulus 10 Double Add Double"; the PPN characters; the letter and `*@#` values are **not confirmed** |
| SEDOL | LSE SEDOL Masterfile Service and Technical Guide v8.8 | reached | structure, alphabet, weights, check digit |
| WKN | WM Datenservice, "Struktur und Aufbau der deutschen WKN", July 2012 | reached | six characters, no check digit |
| Valor | SIX | **not confirmed** | SIX issues the Valor; no published length or check rule found |
| ISO/IEC 7812-1 | Pay.UK (BSI's IIN agent); ISO/IEC 7812-1 itself | reached (secondary), standard **unreachable** | eight-digit IIN, at most 19 digits, Luhn |
| Luhn | US patent 2,950,048 | reached | the algorithm |
| Card schemes | Discover Global Network IIN Range Summary (2026); Mastercard FAQ | Discover reached; Mastercard **not confirmed**; Visa, Amex, Mir **unreachable** | see "Card IIN publications" |
| PCI DSS 3.4.1 | PCI SSC FAQ 1492 | reached | "no more than the BIN and last four digits" |
| CRC | HKMA Common QR Code Specification 1.0 (quoting EMV QRCPS) | reached | polynomial 1021, initial value FFFF, ISO/IEC 13239 |
| QR reference | SIX Swiss Implementation Guidelines for the QR-bill 2.3, Annex B | reached | the recursive modulo 10 matrix |
| Creditor reference | ISO 11649:2009 preview (full normative clauses) | preview, reached | `RF`, check pair, MOD 97-10 |

## ISIN, ISO 6166

Standard: ISO 6166:2013 (7th edition; the 2021 edition is the 8th). The preview at
<https://standards.iteh.ai/catalog/standards/sist/b80d38b9-fca3-47d4-b4f1-ae10b5f2ca62/iso-6166-2013> reaches clause
4.1:

> "a prefix using the alpha-2 country code specified in ISO 3166, a prefix "XS" as specified in Annex A, or other
> prefixes (refer to the Registration Authority website for a complete list of ISIN prefixes)"

> "the basic number, which is nine characters (alphanumeric) in length"; "zeros shall be inserted in front of the
> national number"

> "a check digit, computed using the modulus 10 "double-add-double" formula as specified in Annex C."

ANNA, <https://anna-web.org/identifiers/>: "The final character is a check digit computed according to the modulus 10
“Double-Add-Double” formula"; the ISIN Guidelines (Version 26 June 2026, section 7) say "ISIN code is a 12-character
alphanumeric code ... containing first two alpha-characters as ISIN prefix and last digit as check digit" and refer to
"ISO 6166 (Annex C – Normative) - Formula for computing modulus 10 “Double-Add-Double” check digit".

Prefixes, <https://anna-web.org/wp-content/uploads/2024/01/ISIN-prefixes_0124-1.pdf> (RA list, January 2024): beside the
ISO 3166 codes it lists `EU` (European Union, exceptionally reserved), `EZ` (OTC derivatives), `XS`
(Euroclear/Clearstream, user-assigned) and `XT` (tokenized referential instruments, user-assigned); the Guidelines name
`XA`-`XD` for substitute numbering agencies. A prefix outside ISO 3166 is therefore normal.

**Check digit, not confirmed.** The text of Annex C is not public. ANNA and ISO 6166 name the formula; the operational
description is not quoted from any official source reached. OMG FIGI 1.0 section 6.1 (below) describes the
"familiar Modulus 10 algorithm developed initially by Hans Peter Luhn" and says "A familiar instantiation of this
algorithm is specified in ISO 6166 and is used in generating the check digit for ISIN identifiers", differing from the
FIGI's in which character is doubled. `Internal\IsinCheckDigit` implements: letters as two digits (`A` = 10 ...
`Z` = 35), then the Luhn check of `Internal\Luhn` on the digit string. The letter table is the one ISO 17442 and ISO
11649 print (identical `A` = 10 ... `Z` = 35). The test values `US0378331005` (CGS's Apple example, 037833 10 0, gives
the same CUSIP part) and `AU0000XVGZA3` come from Wikipedia, which does not cite them. **Decision for the owner:**
the Isin class either enforces this check on the strength of ANNA's naming plus the cross-checks above, or is format
only until Annex C is obtained.

Test vector sources: Apple's CUSIP 037833100 is quoted by CGS (see CUSIP); the ISIN check digit 5 of `US037833100`
follows from the helper and agrees with Wikipedia and with every open-source implementation consulted (aggregators).

## LEI, ISO 17442

ISO 17442:2012 preview,
<https://cdn.standards.iteh.ai/samples/59771/b4526b6c9dc04897b014c659ed70ad27/ISO-17442-2012.pdf>, clause 4:

> "The format of the LEI shall be 18!an2!n."; "The LEI consists of 20 characters"; "the 19th and 20th characters (2!n)
> shall be the check digit or digits"

Clause 5: "The check digits shall be calculated based on the scheme defined in ISO/IEC 7064 (MOD 97-10)."; checking:
convert letters (`A` = 10 ... `Z` = 35), "Apply the check character system, MOD 97-10"; "If the remainder is 1 (one), the
number is valid."; generating: "Add "00" to the right-hand end of the LEI", convert, apply MOD 97-10. Annex A.1: `A123456702`;
Annex A.2: `F50EOCWSQFAUVO9Q8Z97`, both valid.

The edition is superseded by ISO 17442-1:2020 (the part 1 text is not reached); GLEIF publishes `506700GE1G29325QX363`
as its own LEI at <https://www.gleif.org/en/about-lei/iso-17442-the-lei-code-structure> and says it "adheres to four
necessary principles of ISO 17442". The LOU prefix (characters 1 to 4) is not defined in the 2012 text. The GLEIF
structure page does not state it; the ISO 10383 factsheet's LEI pattern `[A-Z0-9]{18,18}[0-9]{2,2}` agrees with 18!an2!n.
`louPrefix()` (Task 3) therefore rests on GLEIF's LEI Code Structure documentation, **not confirmed** at Task 2.

## UPI, ISO 4914

**Unreachable.** ISO 4914:2021 has its check character calculation in a normative annex; ISO is paywalled. The ANNA DSB
site (<https://www.anna-dsb.com/>) links "UPI Technical Guidance" at cosp.anna-dsb.com, which is not reachable without
an account; the pages reached (anna-dsb.com home, `?p=22405`) carry no format or check rule. A python-stdnum pull
request (unmerged, aggregator) describes `QZ` prefix, 12 characters and ISO 7064 MOD 31,30; **not used**. Per the
controller's ruling the helper, if the DSB defines a check character, is added in Task 3; at this point the `Upi` class
would be format only.

## CFI, ISO 10962

ISO 10962:1997 preview, <https://cdn.standards.iteh.ai/samples/1636/f4fe59e339b841149b7a05f1f93935e3/ISO-10962-1997.pdf>:

> "The CFI Code consists of six alphabetic characters."; "X = not applicable, unknown, not available."

SIX (maintenance agency secretariat), <https://www.six-group.com/en/products-services/financial-information/market-reference-data/data-standards/iso-cfi.html>:
"The six-letter code describes the structure and function of each financial instrument"; updates are published as
spreadsheets, CSV, JSON-LD and TTL. The current list is
<https://www.six-group.com/dam/download/financial-information/data-center/cfi/cfi-20210507-current.xlsx> (link found on
the SIX page, file not downloaded at Task 2). The decode table (`category()`, `group()`) is typed from it by Task 4;
the class itself accepts any six letters.

## FISN, ISO 18774

ANNA FISN Guidelines, Version 1, June 2026 (ISO 18774, 2nd edition),
<https://anna-web.org/wp-content/uploads/2026/06/FISN-Guidelines-Version-1-Jun-2026.pdf>, section 3.2:

> "FISN consists of a maximum of 35 alphanumeric characters (as defined in ISO/IEC 8859-1)"; "An Issuer name with a
> maximum length of 15 alphanumeric characters"; "“/” as the delimiter between issuer name and instrument description";
> "An instrument description with a maximum length of 19 alphanumeric characters"; "A FISN has a maximum limit of 35
> characters (including the delimiter)."

Points 1 and 2 of the structure: "Use only upper case."; "Use only maximum of a single blank and no special
characters within Issuer and Instrument short name elements, unless specified in the FISN guidelines." For collective
investment vehicles and OTC derivatives "the Issuer name can be extended beyond 15 alphanumeric characters up to the
maximum length of the FISN". The abbreviation list (Appendix 1) is guidance, not a validity rule.

## MIC, ISO 10383

ISO 10383 Registration Authority (SWIFT), FAQ January 2023,
<https://www.iso20022.org/sites/default/files/2023-01/FAQ_ISO_10383_January2023.pdf>: "The MIC is a code of 4
alphanumeric characters randomly allocated."; "the format of the operating and segment MICs are identical". Release 2.0
factsheet: MIC `[A-Z0-9]{4,4}`, operating or segment type `OPRT`/`SGMT`. Format only: the list changes monthly and the
operating/segment link needs it (plan decision).

## FIGI, OMG

OMG Financial Instrument Global Identifier 1.0, formal/2015-12-01 (<https://www.omg.org/spec/FIGI/1.0>), section 6.1 and
the conformance table (clause 2):

> positions 1 and 2: "Any combination of upper case consonants with the following exceptions: BS, BM, GG, GB, GH, KY,
> VG"; position 3 "The upper case letter G"; positions 4 to 11 "Any combination of upper case consonants and the
> numerals 0 – 9"; position 12 "A check digit (0 – 9)". "The exact list of permissible letters is B,C, D, F, G, H, J, K,
> L, M, N, P, Q, R, S, T, V, W, X, Y, Z"

**Inconsistency inside the specification:** the conformance table in clause 2 lists only "BS, BM, GG, GB, VG" as the
excluded prefixes; section 6.1 and the DataTypeRestriction regex in clause 8
(`(?!BS|BM|GG|GB|VG|GH|KY)`) list seven. The helper does not decide this (it checks the check digit only); the class
should refuse the seven of section 6.1 and the regex, the two places that list them as the syntax rule, and the README
must state the conflict.

Check digit (section 6.1): "Letters are converted to integers as illustrated below. Using the first 11 characters and
beginning at the last character in integer format and working right to left, every second integer is multiplied by two.
The resulting string of integers (numbers greater than 10 become two separate digits) are added up. Subtract the total
from the next higher integer ending in zero. If the total obtained when summing up the digits is a integer ending in
zero, then the check digit is zero." Letter values `B` = 11 ... `Z` = 35 (no vowels). Worked examples: `BBG000BLNQ1`
-> `BBG000BLNQ16` (sum 34, digit 6) and `NRG92C84SB3` -> `NRG92C84SB39` (sum 71, digit 9). In both examples the last of
the eleven characters is taken as it is and the one before it doubled, which is the reading the helper uses.

## CUSIP, CUSIP Global Services

CGS, "CUSIP: A Common Language for Efficient Markets" (2022),
<https://www.cusip.com/pdf/news/CUSIP-ACommonLanguageForEfficientMarkets_2022.pdf>:

> "Each CUSIP identifier consists of nine characters. The first six identify the issuer, the next two identify the
> issue, and the final one is a check digit."; "The check digit is the complement of the final number of the sum of the
> numbers in the identifier when it is calculated using the Modulus 10 Double Add Double technique."

Example in the same guide: "037833 10 0 identifies Apple common stock (with a 0 as the final, or check, number) and
037833 AL 4 ... identifies a specific Apple bond issue." CGS identifiers page (<https://www.cusip.com/identifiers.html>):
FactSet Research Systems common stock `303075105`. Letters: "alpha characters ... were added" in 1984; "the check digit
is always a number". CGS Private Placement Numbers fact sheet
(<https://www.cusip.com/pdf/fact_sheet/CGS_Private_Placement_Numbers.pdf>): "The following characters *, @, # are utilized
in position 6, 7 or 8 of the CUSIP number to identify PPNs".

**Not confirmed:** the value of letters (`A` = 10 ... `Z` = 35) and of `*`, `@`, `#` in the check-digit sum is not
printed in any CGS publication reached; ANSI X9.6 (the standard) is paywalled (the ANSI preview returned 403). The helper
uses 36, 37, 38 for `*`, `@`, `#`, from aggregators (Wikipedia and open-source libraries disagree among themselves in
places). The Apple, Apple bond and FactSet vectors contain letters only in `AL` and agree with the helper. **Decision for
the owner:** `Cusip` enforces the check digit for digit and letter bodies; for `*`, `@`, `#` it either does the same on
the aggregator values or skips the check for PPNs.

## SEDOL, London Stock Exchange Group

SEDOL Masterfile Service and Technical Guide v8.8,
<https://www.lseg.com/content/dam/lseg/en_us/documents/sedol/sedol-masterfile-service-and-technical-guide-v8.8.pdf>,
sections 8.1 to 8.4:

> "SEDOL codes are a 7 character alphanumeric code."; "Active 7 character numeric SEDOL codes, created prior to March
> 2004, also remain in circulation."; structure: "1 Alpha, 2 to 6 Alphanumeric, 7 Numeric check digit"; "Alpha
> characters are B-Z excluding vowels"; "Numeric characters are 0-9"; "No SEDOL code will be issued without an alpha
> first character."

Values: B 11, C 12, D 13, F 15, G 16, H 17, J 19, K 20, L 21, M 22, N 23, P 25, Q 26, R 27, S 28, T 29, V 31, W 32,
X 33, Y 34, Z 35. Weights 1, 3, 1, 7, 3, 9 and 1 for the check digit: "If the sum of the results is a multiple of ten, the
number is taken to be correct." Derivation (8.4): "94 taken away from a 100 means a check digit of 6" for `B12345`.
Examples: `B1F3M59` (sum 161, correct) and `B1H54P6` (sum 309, quoted incorrectly). "There will be no codes issued with
0,3,2,4,5,6,7 and 9 as the lead character, which allows historic series to be maintained as well as dummy codes by
customers": the guide's list omits 1 and 8 as well; legacy numeric SEDOLs, such as the brief's `0263494` (sum 126 with
the check digit 4), are valid, so the class accepts a numeric first character.

## WKN, WM Datenservice

WM Datenservice, "Struktur und Aufbau der deutschen Wertpapier-Kenn-Nummer (WKN)", status July 2012,
<https://www.wmdatenservice.com/wp-content/uploads/2022/10/Vergaberegeln_WKN.pdf>:

> "Die Struktur der 6-stelligen WKN ist seit 2003 alpha-numerisch; die ausschließlich numerischen WKN sind weiterhin
> gültig."; "Gültige Zeichen in der alphanumerischen WKN sind alle arabischen Zahlen (0 – 9) sowie alle Buchstaben des
> deutschen Alphabets, mit Ausnahme der Buchstaben I und O"; "Buchstaben werden ausschließlich versal dargestellt."

The document defines no check digit. Prefix patterns for issuer or instrument codes (`AAnxxx`, `AAAnxx`, `AAAAnx`) are
reservation conventions, not validity rules. Format only: six characters from `0-9` and `A-Z` without `I` and `O`.

## Valor, SIX

SIX is "the official securities numbering agency in Switzerland, Liechtenstein, and Belgium. We issue Valor (Swiss
National Number) and/or ISIN identifiers" (<https://www.six-group.com/en/products-services/financial-information/data-standards.html>).
**Not confirmed:** no SIX publication reached states the length, the digits-only rule or a check digit. ISO 6166 clause
4.1 caps a national number at nine characters ("zeros shall be inserted in front of the national number"), so a Valor
is at most nine characters in an ISIN. Format only, and only the bound that clause gives: digits, at most nine; the class
does not claim a lower bound. WKN and Valor overlap for six digits (factory ambiguity).

## ISO/IEC 7812-1, IIN and PAN

ISO/IEC 7812-1 is **unreachable** (paid; the ANSI blog and preview returned 403). Pay.UK, which "acts as agent for the
British Standards Institute in assigning UK national and international IINs",
<https://www.wearepay.uk/what-we-do/standards-authority/obtaining-a-standard-or-identifier-number/iin/>:

> "an IIN is the first eight digits of the long account number"; "In January 2017, ISO/IEC 7812 expanded from six digits
> to eight digits."; the PAN "can be up to a maximum of 19 digits"; "an accompanying check digit (1 digit), calculated by
> the Luhn formula"

The standard's minimum PAN length is not stated by any source reached. **Decision for the owner:** the minimum is
needed for the "ISO/IEC 7812-1" length rule; a floor equal to the shortest published scheme length in Discover's table
(14, Diners) is recorded here as the evidence, not as the standard's rule.

## Luhn

US patent 2,950,048, "Computer for verifying numbers" (H. P. Luhn, IBM, filed 6 January 1954, patented 23 August 1960),
<https://patents.google.com/patent/US2950048A/en>: alternate digits are replaced by "twice the original digit plus an end
around carry"; the check digit "is derived by adding to this sum its tens complement"; "A number with its check digit
appended is verified when the cross addition yields zero." OMG FIGI 1.0 section 6.1 cites it: "the familiar Modulus 10
algorithm developed initially by Hans Peter Luhn and described in U.S. Patent No. 2,950,048 and part of the public
domain". Which end the doubling starts from for a card number is not in the patent text reached (OCR); ISO/IEC 7812-1
Annex B is unreachable. `Internal\Luhn` doubles the digit next to the check digit and every second one to its left,
which is the common reading and agrees with every vector below.

## Card IIN publications

| Scheme | Publication | Status | Fixes |
|---|---|---|---|
| Discover, Diners Club International, JCB, UnionPay | Discover Global Network, "Global Payment Network – IIN Range Summary", © 2026, <https://www.discoverglobalnetwork.com/content/dam/discover/en_us/dgn/docs/IPP-VAR-Enabler-Compliance.pdf> | reached | the ranges and lengths below |
| Mastercard | Mastercard FAQ, <https://www.mastercard.us/en-us/frequently-asked-questions.html> | **not confirmed** (the page returned 403; a search snippet of it states 222100-272099, "processed the same as 51-55") | needs a direct read |
| Visa | Visa 8-Digit BIN Expansion FAQ (Visa-hosted PDF) | reached, but it states no prefix or length list | "no change to the length of the PAN" only |
| American Express | none reached | **unreachable** | not listed |
| Mir (NSPK) | none reached (secondary articles only) | **unreachable** | not listed |
| RuPay | not in Discover's 2026 summary | **unreachable** | not listed |

Discover's summary (start, end, PAN length min-max), with its footnotes `a` = JCB, `b` = UnionPay:

```
3000 0000 - 3059 9999  16-19      3528 0000 - 3589 9999  16-19 (a)   6221 2600 - 6237 9699  16-19 (b)
3088 0000 - 3094 9999  16-19 (a)  3600 0000 - 3699 9999  14-19        6240 0000 - 6269 9999  16-19 (b)
3095 0000 - 3095 9999  16-19      3800 0000 - 3999 9999  16-19        6282 0000 - 6288 9999  16-19 (b)
3096 0000 - 3102 9999  16-19 (a)  6011 0000 - 6011 9999  16-19        6440 0000 - 6589 9999  16-19
3112 0000 - 3120 9999  16-19 (a)  6210 9400 - 6210 9499  16-19 (b)    8100 0000 - 8171 9999  16-19 (b)
3158 0000 - 3159 9999  16-19 (a)
3337 0000 - 3349 9999  16-19
```

The document says an IIN is "the first six (6) or eight (8) digits" and that "To receive additional details ... please use
the latest IIN range table publication", which is under NDA: the summary does not name the scheme of every range (only
the `a`/`b` footnotes and, in the text, Diners Club International and Discover). Task 4 assigns a range to a scheme only
where this summary or another publication names it, and decodes the rest as an empty list.

**No-Luhn ranges: none found.** No publication reached states that a scheme's numbers carry no Luhn digit. Baymard
(an aggregator) says some China UnionPay cards fail Luhn; it names no UnionPay publication, so it is **not used**. Until a
scheme's own publication says so, Luhn is enforced for every range.

## PCI DSS 4.0 requirement 3.4.1

PCI SSC FAQ 1492, <https://www.pcisecuritystandards.org/faqs/1492/>:

> "PCI DSS Requirement 3.4.1 requires that no more than the BIN and last four digits of the PAN are displayed on computer
> screens, reports, etc. unless there is a documented business justification for seeing more digits."

The BIN is six or eight digits (see IIN). Keeping the first six and the last four is within the limit for both lengths.
Visa's 8-digit BIN FAQ adds: "Rule 3.4 has been recently updated to allow exposure of up to, but no more than, the first 8
digits and any other 4 digits in a 16 digit PAN."

## CRC of the EMVCo merchant-presented QR

The EMV QRCPS document itself (version 1.1, 27 November 2020; <https://www.emvco.com/specifications/>) is **unreachable**
(no direct PDF link served; the archive was offline). The HKMA, a regulator, quotes the rule in its Common QR Code
Specification for Retail Payments in Hong Kong 1.0 (December 2017, "uses ... EMV QRCPS ... as a basis"),
<https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf>,
section 4.1:

> "The CRC (ID “63”) shall be calculated according to [ISO/IEC 13239] using the polynomial '1021' (hex) and initial value
> 'FFFF' (hex). The data over which the checksum is calculated shall cover all data objects, including their ID, Length
> and Value ... as well as the ID and Length of the CRC itself (but excluding its Value)."; "the resulting 2-byte
> hexadecimal value shall be encoded as a 4-character Alphanumeric Special value ... a CRC with a two-byte hexadecimal
> value of '007B' is included in the QR Code as "6304007B"."

Reflection and final XOR are not stated in that text; ISO/IEC 13239's default CRC-16 has none, which is the
CRC-16/CCITT-FALSE parametrisation. Its catalogue check value, `29B1` for `123456789`, comes from the brief and the RevEng
CRC catalogue (<https://reveng.sourceforge.io/crc-catalogue/16.htm>, unreachable at Task 2: HTTP 503); the helper computes
it. Task 5 re-reads the EMV QRCPS text and records the payload marker `000201` (HKMA: "Payload Format Indicator (ID “00”)
... A fixed value of “01”", ID `00` length `02`).

## SIX QR reference, recursive modulo 10

Swiss Implementation Guidelines for the QR-bill, version 2.3, valid from 21 November 2025,
<https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf>, section 2.12 and
Annex B:

> "The structure of the QR reference must always have 26 numerical characters followed by a Modulo 10 recursive check
> digit (see Annex B) ... The reference must not consist exclusively of zeros."; "The sequence of numbers to be checked is
> processed from left to right. For the first digit, the carry-forward = 0. The number to be checked corresponds to the
> column number, and the carry-forward to the line number in the table."

The matrix of Figure 21 (rows = carry 0-9, columns = digit 0-9, then the check digit of the final carry) is an image; it
was read from the page render. Row 0 is `0 9 4 6 8 2 7 1 3 5`; each next row is that sequence shifted one place left;
the check digits by carry are `0 9 8 7 6 5 4 3 2 1`. Example (Figure 22): `21 00000 00003 13947 14300 0901` gives the
check digit 7.

## Creditor reference, ISO 11649

ISO 11649:2009 preview (clauses 4 to 6 and Annex A and B in full),
<https://cdn.standards.iteh.ai/samples/50649/a769e57fc5a34724bac3a5d18a2b8407/ISO-11649-2009.pdf>:

> "The format of the RF Creditor Reference shall be: 2!a2!n21c"; "the first two letters ... shall always be “RF”";
> "the remaining part ... (up to 21c) ... shall only contain upper and lower case letters (A to Z and a to z) and numeric
> characters (0 to 9)"; "The check digits are calculated based on the scheme defined in ISO/IEC 7064 (MOD 97-10)."

Checking: delete blanks, "Move the first four characters to the right-hand end", convert letters, MOD 97-10, "If the
remainder is 1 (one), the number is valid." Generating: add "RF" and "00" at the right, convert, mod 97, "Subtract the
remainder from 98 and, if the result is less than ten, insert a leading zero." Annex B example: `2348231` -> `RF712348231`.
The ISO text converts "upper and lower case letters" without giving a lowercase table; the helper accepts capital
letters only and Task 4 decides the case policy.

**Discrepancy in the standard:** Annex A.3 prints `RF68539007547034` / `RF68 5390 0754 7034` as its layout example, but the
check of clause 6.2 on that string leaves a remainder other than 1 (the check pair of `539007547034` is `18`, as
Wikipedia and the EPC and SIX documents that reuse it show). The helper follows clause 6, not the example, and a test
records that the printed example fails the standard's own check. The Finance Finland note
(<https://www.finanssiala.fi/wp-content/uploads/2024/04/structure-of-the-rf-creditor-reference-iso-11649.pdf>) states
"The check digit algorithm is the same as the algorithm used for calculating the check digit for the IBAN account
transfer" and gives `RF54 1234` and `RF98 REF1 234` as examples; those two examples were not checked.

SIX requires "a minimum of 5 and a maximum of 25 alphanumerical characters. Starting with RF, followed by the check
digits" (IG 2.3, section 2.12.2); ISO 11649 allows a shorter reference (2 + 2 + at least one). The QR-bill profile applies
SIX's bounds.

## Leading markers of the payment payloads

For `PaymentQrFactory` (Task 7).

| Payload | Marker | Source | Status |
|---|---|---|---|
| EMVCo merchant-presented QR | `000201` (ID 00, length 02, value 01) | HKMA Common QR Code Specification 1.0, table 4.1, quoting EMV QRCPS | reached (via HKMA) |
| EPC QR | line 1 `BCD`, version `001` or `002`, identification `SCT` | EPC069-12 v3.0, <https://europeanpaymentscouncil.eu/sites/default/files/kb/file/2022-09/EPC069-12%20v3.0%20Quick%20Response%20Code%20-%20Guidelines%20to%20Enable%20the%20Data%20Capture%20for%20the%20Initiation%20of%20an%20SCT_0.pdf> "Service Tag: ‘BCD’", "V1: ‘001’ V2: ‘002’" | reached |
| Swiss QR-bill | `SPC` ("Swiss Payments Code") | SIX IG QR-bill 2.3, data structure, "QRType" | reached |
| GOST R 56042-2014 | `ST0001` followed by the charset digit | text of the standard as mirrored at <https://files.stroyinf.ru/Data2/1/4293771/4293771168.htm> ("Версия стандарта ... "0001" ST0001") | reached via a mirror; Task 7 confirms against the standard |
| SBP link | `https://qr.nspk.ru/` | NSPK documentation | **unreachable** (secondary articles only) |
| UPI | `upi://pay?` | NPCI UPI Linking Specification; a third-party copy of version 1.6 and an NPCI circular were found | **not confirmed** (the NPCI document itself was not read) |

## Placeholders for Tasks 5 to 7

### EMVCo QRCPS merchant-presented mode

Task 5: TLV rules, templates 26-51 and 62, data object table, EMVCo version and page.

### National EMVCo profiles

Task 5/6: Pix BR Code (BCB), PromptPay (BOT), SGQR (MAS/IMDA), DuitNow (PayNet), QRIS (Bank Indonesia), VietQR (NAPAS),
KHQR (NBC), HK FPS (HKMA; the Common QR Code Specification read here is its base), JPQR, QR Ph (BSP), Bharat QR (NPCI).

### EPC069-12

Task 6: the line layout, field limits, version differences. Marker found above.

### SIX QR-bill layout

Task 6: the data structure, address types, reference types, trailer. Reference check digit and markers above.

### GOST R 56042-2014 and the Bank of Russia account key

Task 7.

### NSPK SBP

Task 7.

### NPCI UPI linking specification

Task 7.
