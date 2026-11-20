# Finance identifier sources

Every rule that `vo-finance` enforces is quoted here from an official source: the issuing body or registration
authority, a regulator's publication, a standard cited by number (a publisher's unmodified preview counts), or an
unmodified Internet Archive copy of one. An aggregator counts only "as quoted from". What could not be quoted is marked
**unreachable** or **not confirmed**; for it the outcome is **format only** (the class checks length and character set,
never a check digit or a range that no official source gives). No test vector comes from Wikipedia or a library.

Outcome per rule (revised after review round 1):

| Rule | Outcome |
|---|---|
| ISIN check digit | enforced: three worked examples of a numbering agency (SET), the formula named by ANNA and ISO 6166 |
| ISIN prefix | format only: two letters (alpha-2 country codes, reserved codes, `XS`, `EZ`, `ZZ` or other RA-determined prefixes per ISO 6166:2021 clause 4.1); never refused for being outside ISO 3166 |
| LEI check pair | enforced (ISO 17442:2012, text reached) |
| UPI | format enforced (ROC: `QZ`, 12 characters, no vowels or `Y`); check character not verified (ISO 4914 Annex C and the ANNA DSB guidance unreachable) |
| CFI | six letters; decoding from SIX's list (Task 4) |
| FISN, MIC, FIGI, SEDOL, WKN | as quoted below |
| CUSIP check digit | enforced for an all-digit CUSIP only; **format only for any CUSIP with a letter, `*`, `@` or `#`** (no official value table) |
| Valor | format only: 1 to 9 ASCII letters or digits; the nine-character bound is an inference |
| Card Luhn | enforced; the doubling position is cross-confirmed, the Annex B text is a gap (see Luhn) |
| PAN length | 10 to 19 digits (ISO/IEC 7812-1:2017 preview) |
| Card schemes | only what a reached publication states (Discover's summary); no no-Luhn range exists in any reached publication |

Research date: 2026-11-20 (UTC). Scope of this file at Task 2: the sources of the check-digit helpers and of the
securities and card classes (Tasks 3 and 4). The EMVCo QR body, the national profiles, EPC069-12, the QR-bill layout,
GOST R 56042, NSPK and NPCI are researched by Tasks 5 to 7; their headings are placeholders at the end.

Status words: **reached** (read the source text), **preview** (publisher's unmodified preview of a paid standard),
**archive** (Internet Archive copy), **unreachable** (could not be read), **not confirmed** (reached only a secondary
statement).

## Summary

| Topic | Source | Status | What it fixes |
|---|---|---|---|
| ISIN structure | ISO 6166:2021 preview (clause 4.1, unchanged from 2013); ANNA identifiers page; ANNA ISIN Guidelines Jun 2026 | preview, reached | 12 characters: prefix, 9-character basic number, check digit |
| ISIN prefixes | ANNA, RA list of ISIN prefixes, January 2024 | reached | `XS`, `EU`, `EZ`, `XA`-`XD` and `XT` exist beside ISO 3166 codes: the prefix is never refused |
| ISIN check digit | Stock Exchange of Thailand "ISIN calculation" (three worked examples); ANNA; ISO 6166 Annex C | reached (examples); Annex C text paywalled | the doubling position and the letter values T, H, B, Z; the formula name |
| LEI | ISO 17442:2012 preview (full text of clauses 4 to 5 and Annex A); GLEIF; ISO 17442-1:2020 not reached | preview, reached | 20 characters, check pair by ISO/IEC 7064 MOD 97-10, examples |
| UPI (ISO 4914) | ISO 4914:2021 preview (iteh); ANNA DSB | preview reached (contents only); rest **unreachable** | the preview lists Annex C "Check character calculation" as normative; no format or algorithm text |
| CFI | ISO 10962:2021 preview; ISO 10962:1997 preview; SIX CFI page | preview, reached | six letters, `X` is "not applicable"; the code list is a SIX download |
| FISN | ANNA FISN Guidelines, June 2026 (ISO 18774 2nd edition) | reached | at most 35 characters, issuer name at most 15, `/`, description |
| MIC | ISO 10383 RA FAQ (January 2023) and Release 2.0 factsheet | reached | four alphanumeric characters, `[A-Z0-9]{4}` |
| FIGI | OMG FIGI 1.0, formal/2015-12-01 | reached | syntax, excluded prefixes, check digit |
| CUSIP | CUSIP Global Services publications | reached, partly | nine characters; "Modulus 10 Double Add Double"; the PPN characters; check digit enforced for digit-only CUSIPs; letter and `*@#` values **not available**: format only |
| SEDOL | LSE SEDOL Masterfile Service and Technical Guide v8.8 | reached | structure, alphabet, weights, check digit |
| WKN | WM Datenservice, "Struktur und Aufbau der deutschen WKN", July 2012 | reached | six characters, no check digit |
| Valor | SIX | **not confirmed** | SIX issues the Valor; no published length, character set or check rule found: format only |
| ISO/IEC 7812-1:2017 | iteh preview (clauses 1-4.2); Pay.UK (BSI's IIN agent) | preview reached; clause 4.4 and Annex B not in it | eight-digit IIN, PAN 10 to 19 digits, Annex B named "Luhn formula ... modulus-10 double-add-double" |
| Luhn | US patent 2,950,048; SET ISIN examples; ISO/IEC 7812-1 Annex B (title only) | reached | the algorithm; doubling position cross-confirmed. **Gap:** no official card-number vector (scheme test-card pages are script-rendered; none read) |
| Card schemes | Discover IIN Range Summary (2026); Amex UK merchant page; Visa Core Rules (public); NPCI RuPay circular | Discover, Amex reached; Visa, RuPay reached without ranges; Mastercard, Mir **unreachable** | see "Card IIN publications" |
| PCI DSS 3.4.1 | PCI SSC FAQ 1492 | reached | "no more than the BIN and last four digits" |
| CRC | BCB Manual do BR Code 2.0.1 (complete payload with CRC AD38); HKMA Common QR Code Specification 1.0 | reached | polynomial 1021, initial value FFFF, and the printed result that fixes no reflection and no final XOR |
| QR reference | SIX Swiss Implementation Guidelines for the QR-bill 2.3, Annex B | reached | the recursive modulo 10 matrix |
| Creditor reference | ISO 11649:2009 preview (full normative clauses); SIX IG 2.3 Annex A; EPC069-12 v3.0 section 2.3 | preview, reached | `RF`, check pair, MOD 97-10; RF18 5390 0754 7034 |
| EPC QR | EPC069-12 v3.0 (13 September 2022) | reached | line layout, character sets, limits, examples V1 and V2 |
| Swiss QR-bill | SIX IG QR-bill 2.3 (21 November 2025) | reached | data elements, separators, character set, reference rules, Annex A examples (example 4's RF72 fails ISO 11649) |
| GOST R 56042-2014 | text as quoted by files.stroyinf.ru | as quoted | service block, pairs, mandatory requisites, Annex D example |
| Russian account key | Bank of Russia Order 515 (1997) as quoted by pravo.ppt.ru | as quoted | 7-1-3 weights, RKC or credit institution number, annex examples |
| SBP link | NSPK Rules P.235 v4.0 (archive) | archive; layout **unreachable** | format only |
| UPI URI | NPCI OC 18 (2017) and OC 37 (archive); linking specification not reached | archive | `pa`, `pn` mandatory, `tr` up to 35; scheme not confirmed |

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

ISO 6166:2021 (8th edition), preview
<https://cdn.standards.iteh.ai/samples/78502/82ffc17dd05c47bd962b9769222010f9/ISO-6166-2021.pdf>, clause 4.1 repeats the
rule: prefixes are "the alpha-2 country codes or reserved codes specified in the ISO 3166 series; “XS”, “EZ” or “ZZ” as
specified in Annex A, Table A.1; or other prefixes as may be determined by the RA", "The basic code, which is nine
characters (alphanumeric) in length", and "A check digit, computed using the modulus 10 “double-add-double” formula as
specified in Annex C." The 2013 and 2021 clause 4.1 agree on the structure; the 2021 text uses "ZZ" too.

**Check digit.** The text of Annex C is paywalled, but the Stock Exchange of Thailand (SET; the Thai numbering agency's
group publishes it under TSD, the Thailand Securities Depository) prints the method with worked examples at
<https://www.set.or.th/en/tsd/information/isin-calculate>: "Computed according to modules 10 " Double-Add-Double "". Each
example writes the 11 characters of ISIN without its check digit, expands letters to their numbers, aligns the weights
`1 2 1 2 ...` so that the last digit of the body is doubled, adds the digits of the products, and takes the check digit
as the complement to ten:

| ISIN body | Expanded digits (T = 29, H = 17, B = 11, Z = 35) | Sum | Check digit |
|---|---|---|---|
| `TH0013057B0` | `2 9 1 7 0 0 1 3 0 5 7 1 1 0` | 35 | 5 |
| `TH064601000` | `2 9 1 7 0 6 4 6 0 1 0 0 0` | 43 | 7 |
| `TH0268010Z1` | `2 9 1 7 0 2 6 8 0 1 0 3 5 1` | 49 | 1 |

These are the ISINs `TH0013057B05`, `TH0646010007` and `TH0268010Z11`, and they are the test vectors of
`Internal\IsinCheckDigit` and (as digit strings) `Internal\Luhn`. The letter table `A` = 10 ... `Z` = 35 is printed by
ISO 17442:2012 clause 5.2.1 and ISO 11649:2009 clause 6.2.3 and agrees with SET's T, H, B, Z. OMG FIGI 1.0 section 6.1
says the ISIN's formula is a "familiar instantiation" of the Luhn modulus 10 algorithm, differing from the FIGI's in
which character is doubled first. **Outcome:** `Isin` enforces the check digit. Wikipedia and open-source libraries
(which also give `US0378331005`, `AU0000XVGZA3`) are not used as vectors.

## LEI, ISO 17442

ISO 17442:2012 preview,
<https://cdn.standards.iteh.ai/samples/59771/b4526b6c9dc04897b014c659ed70ad27/ISO-17442-2012.pdf>, clause 4:

> "The format of the LEI shall be 18!an2!n."; "The LEI consists of 20 characters"; "the 19th and 20th characters (2!n)
> shall be the check digit or digits"

Clause 5: "The check digits shall be calculated based on the scheme defined in ISO/IEC 7064 (MOD 97-10)."; checking:
convert letters (`A` = 10 ... `Z` = 35), "Apply the check character system, MOD 97-10"; "If the remainder is 1 (one),
the number is valid."; generating: "Add "00" to the right-hand end of the LEI", convert, apply MOD 97-10. Annex A.1:
`A123456702`; Annex A.2: `F50EOCWSQFAUVO9Q8Z97`, both valid.

The 2012 edition is superseded by ISO 17442-1:2020, whose text is **not reached** (the iteh page for the 2020 edition
served no preview; the 2012 rules, 20 characters with a MOD 97-10 check pair, are the ones the ISO 10383 factsheet's LEI
pattern `[A-Z0-9]{18,18}[0-9]{2,2}` and the SIX/GLEIF material still match, but the unchanged-ness of clause 5 is an
inference); GLEIF publishes `506700GE1G29325QX363` as its own LEI at
<https://www.gleif.org/en/about-lei/iso-17442-the-lei-code-structure> and says it "adheres to four necessary principles
of ISO 17442". The LOU prefix (characters 1 to 4) is not defined in the 2012 text. The GLEIF structure page does not
state it; the ISO 10383 factsheet's LEI pattern `[A-Z0-9]{18,18}[0-9]{2,2}` agrees with 18!an2!n. `louPrefix()` (Task 3)
is therefore a plain slice with no confirmed meaning (see "Securities classes, Task 3 additions").

## UPI, ISO 4914

ISO 4914:2021 preview, <https://cdn.standards.iteh.ai/samples/80506/11803bc283ce4b6d8c633ca72ef6ab03/ISO-4914-2021.pdf>:
contents "4 UPI code structure", "5 UPI reference data library", "Annex C (normative) Check character calculation". The
preview stops before clause 4: no length, character set or algorithm text. The ANNA DSB site
(<https://www.anna-dsb.com/>) links "UPI Technical Guidance" at cosp.anna-dsb.com, which is not reachable without an
account; the pages reached carry no format or check rule. **Outcome:** `Upi` is format only at Task 2; the controller's
ruling stands that a helper is added in Task 3 only if the DSB's own publication defines the check character and is
reached. (An unmerged python-stdnum pull request describing a `QZ` prefix and ISO 7064 MOD 31,30 is an aggregator and is
not used.)

## CFI, ISO 10962

ISO 10962:2021 preview (a redline against the 2019 edition),
<https://cdn.standards.iteh.ai/samples/81140/575a3327c8d34576ad2932719988f966/ISO-10962-2021.pdf>, clause 5:

> "The CFI code consists of six alphabetical characters."; "X  Not applicable/ or undefined: the character 'X' may be used
> for any respective Attribute if the information is unknown, not available or applicable at the time of assignment";
> "The character 'X' shall not be used as a value in the above described manner for Category or Group."

The 1997 edition (<https://cdn.standards.iteh.ai/samples/1636/f4fe59e339b841149b7a05f1f93935e3/ISO-10962-1997.pdf>)
says "The CFI Code consists of six alphabetic characters." and "X = not applicable, unknown, not available."; the six-letter
rule is the same in both. In 2021 `X` is refused for Category and Group; the class accepts any six letters, so this is
decode-only.

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

**Not available, so format only.** The value of letters and of `*`, `@`, `#` in the check-digit sum is printed in no CGS
publication reached (the CGS pages, guide and fact sheet were all read); ANSI X9.6, the standard, is paywalled (its ANSI
preview returned 403); the NAIC, SEC, FINRA, DTCC and Federal Reserve sites searched do not state it; isin.org, which
spells out an ISIN letter table, says on its about page that it "has no affiliation with" ANNA or any numbering agency,
so it is not an official source. CGS's three examples have no `*@#` and only one letter pair: the Apple bond
`037833AL4` agrees with `A` = 10 and `L` = 21, which is consistent with the ISIN letter table but is one
example, not a rule. **Outcome:** `Internal\CusipCheckDigit` serves digit-only CUSIPs (`037833100`, `303075105`, which
fix the doubling of every second digit starting with the second); `Cusip` checks the format only for any CUSIP with a
letter, `*`, `@` or `#`, and the README's "Format only" list says why.

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
**Not confirmed:** no SIX publication reached states the length, the character set or a check digit. The only bound is
an **inference**: ISO 6166:2021 clause 4.1 b) makes the basic code of an ISIN nine characters with "zeros ... inserted in
front of the national code", so a national number fits in nine characters. **Outcome:** format only, and the class
claims only what the inference supports: a non-empty value of at most nine ASCII alphanumerics; it does not claim
digits-only. WKN and Valor can overlap for six digits (factory ambiguity).

## Securities classes, Task 3 additions

**CFI decode table (reached).** SIX, the ISO 10962 maintenance agency's secretariat, publishes the list at
<https://www.six-group.com/dam/download/financial-information/data-center/cfi/cfi-20210507-current.xlsx>, downloaded
2026-11-20 (UTC) and read sheet by sheet ("Categories" and one sheet per category). The sheet "Categories" gives the
14 category letters (`E` Equities, `C` CIVs, `D` Debt instruments, `R` Entitlement (rights), `O` Listed options, `F`
Futures, `S` Swaps, `H` Non-listed and complex listed options, `I` Spot, `J` Forwards, `K` Strategies, `L` Financing,
`T` Referential instruments, `M` Others (miscellaneous)); each category sheet lists its groups with the pattern
`E-S-*-*-*-*` (`ES` = "Common/ordinary shares"). `Cfi::category()` and `Cfi::group()` decode from these; the group
rows whose letter is `X` (the category's own row) are not groups, because ISO 10962:2021 clause 5 forbids `X` for
Category and Group. A letter outside the table decodes as null, never as invalid. `ESVUFR` decodes as category `E`,
group `ES`.

**LEI `louPrefix()` (not confirmed).** The GLEIF structure page
(<https://www.gleif.org/en/about-lei/iso-17442-the-lei-code-structure>) read by fetch shows no breakdown of the 20
characters; a web-search summary attributed "characters 1-4: prefix ensuring uniqueness among LEI issuers" to it, which
the fetch did not reproduce. `Lei::louPrefix()` is therefore a plain slice of the first four characters with no check
and no registry; the docblock says no official text reached confirms what the four characters mean.

**ISIN `cusip()` (sourced) and `sedol()` (not confirmed).** CGS, <https://www.cusip.com/identifiers.html>: a CUSIP fills
the national-number position of a US or Canadian ISIN ("A CUSIP or CINS identifier will always appear in positions 3-11
of a CGS ISIN"); examples there: Amazon `US0231351067` from CUSIP `023135106`, Air Canada `CA0089117034` from
`008911703` (both check digits agree with `IsinCheckDigit`; they are test vectors). For `sedol()` (GB, IE, SEDOL behind
two zeros) the targeted attempt found nothing: the ANNA identifiers page and ISIN Guidelines (June 2026) say only that
national numbers are zero-padded and name no SEDOL, and the LSEG technical guide lists ISIN as a data field only. The
accessor is a convenience that refuses nothing and returns null when the NSIN is not a valid `Sedol`.

**UPI format (reached, Regulatory Oversight Committee).** <https://www.leiroc.org/international_bodies.htm>: "The UPI
is a 12-character reference code", a two-character prefix `QZ`, nine alphanumeric body characters of uppercase A-Z and
0-9 "excluding the vowel characters (A, E, I, O, U) and the character Y", and one final alphanumeric check character
under the same restriction. `Upi` enforces `QZ` + ten such characters. The check-character algorithm is not stated
there (ISO 4914 Annex C, the ANNA DSB guidance, and the CPMI-IOSCO technical guidance PDF at bis.org, which served only
an HTML landing page, were not reachable), so it is not verified. The `[A-Z0-9]{1,64}` fallback was not needed.

**Valor.** No SIX text states a character set (the SIX data-standards page was fetched again: only "We issue Valor
(Swiss National Number) and/or ISIN identifiers"; search results quote only low-authority pages). The class stays format
only: one to nine ASCII letters or digits, case-folded; the bound is the inference above.

**FISN enforced.** From the ANNA Guidelines section 3.2 quoted above: at most 35 characters including `/` (counted as
characters, the text being UTF-8), a description of at most 19, uppercase only, single blanks ("Use only maximum of a
single blank"), exactly one `/`. "alphanumeric characters (as defined in ISO/IEC 8859-1)": the class takes ASCII
capitals, digits, punctuation and the ISO 8859-1 capitals U+00C0 to U+00DE without U+00D7. Lowercase ASCII input and
ISO 8859-1 lowercase input (U+00E0 to U+00FE without U+00F7) are folded to uppercase (to U+00C0 to U+00DE for the
latter). A leading, trailing or `/`-adjacent blank is not refused: the guidelines quoted here do not forbid it. The
issuer length of 15 is not enforced (CIVs and OTC derivatives may extend it up to the total). The guidelines' list of
permitted special characters was not reached, so printable ASCII punctuation is accepted.

**Case.** Every identifier folds input to uppercase (ASCII `strtoupper`; `Fisn` also folds ISO 8859-1 letters)
before checking, as `Isin` does. Each class has a lowercase-input test.

**Test vectors without a source** are marked synthetic in the tests: `QZ4TM8GJN9XW`, the `*`, `@`, `#` CUSIPs, `XNAS`
and `1234` (MIC), `519000` and `A0D9PT` (WKN), the valor numbers.

**Factory.** Every type takes part (Isin, Lei, Upi, Figi, Cfi, Fisn, Mic, Cusip, Sedol, Wkn, Valor). Overlaps throw
`AmbiguousValueException` with every accepting type, never a subset: ISIN and FIGI (`MWG26Z8NW5M4`), WKN and Valor (six
characters), CFI and WKN (six letters without `I`, `O`; Valor too), CUSIP and Valor (nine characters), SEDOL and Valor
(seven), MIC and Valor (four), and so on: Valor accepts any 1 to 9 alphanumerics, so it joins every such overlap.

## ISO/IEC 7812-1, IIN and PAN

ISO/IEC 7812-1:2017 (fifth edition) preview,
<https://cdn.standards.iteh.ai/samples/70484/1b747b382337445fbbdae5584d268bcc/ISO-IEC-7812-1-2017.pdf>; the preview
ends before clause 4.4 and Annex B:

> "Clause 4: revised the length of an IIN to 8 digits (from 6 digits)"; "Clause 4: revised the minimum length of a PAN to
> 10 digits (from 8 digits)"; "primary account number ... number consisting of a maximum of 19 digits that identifies the
> card issuer and the cardholder"; "the IIN, 8 numeric digits in length"; "the individual account number"; "a check digit
> (see 4.4)". Contents: "Annex B (normative) Luhn formula for computing modulus-10 “double-add-double” check digits".

So the PAN is 10 to 19 digits; the earlier proposal of a 14-digit floor is withdrawn (the standard's own minimum is 10).
Pay.UK, which "acts as agent for the British Standards Institute in assigning UK national and international IINs",
<https://www.wearepay.uk/what-we-do/standards-authority/obtaining-a-standard-or-identifier-number/iin/>, agrees: "an IIN
is the first eight digits of the long account number"; "up to a maximum of 19 digits"; "an accompanying check digit (1
digit), calculated by the Luhn formula".

## Luhn

US patent 2,950,048, "Computer for verifying numbers" (H. P. Luhn, IBM, filed 6 January 1954, patented 23 August 1960),
<https://patents.google.com/patent/US2950048A/en>: alternate digits are replaced by "twice the original digit plus an end
around carry"; the check digit "is derived by adding to this sum its tens complement"; "A number with its check digit
appended is verified when the cross addition yields zero." OMG FIGI 1.0 section 6.1 cites it: "the familiar Modulus 10
algorithm developed initially by Hans Peter Luhn and described in U.S. Patent No. 2,950,048 and part of the public
domain".

Doubling position: the patent text (an OCR scan) does not fix it for a card number. ISO/IEC 7812-1 names its Annex B "Luhn
formula for computing modulus-10 “double-add-double” check digits" (contents of the preview); the text of Annex B is not
in the preview. The same "Double-Add-Double" formula, with the last digit of the body doubled, is shown with three
worked examples by SET for the ISIN (see ISIN). `Internal\Luhn` follows that, and its tests are those three examples
expanded to digit strings. **Gap:** no official card-number vector was reached (Mastercard's and Visa's test-card pages
are rendered by script and returned no numbers; ISO/IEC 7812-1's Annex B example is outside the preview).

## Card IIN publications

| Scheme | Publication | Status | Fixes |
|---|---|---|---|
| Discover, Diners Club International, JCB, UnionPay | Discover Global Network, "Global Payment Network – IIN Range Summary", © 2026, <https://www.discoverglobalnetwork.com/content/dam/discover/en_us/dgn/docs/IPP-VAR-Enabler-Compliance.pdf> | reached | the ranges and lengths below |
| American Express | American Express UK merchant page, <https://www.americanexpress.com/uk/merchant/get-started.html> | reached | "Card account numbers begin with "34" or "37" and consist of 15 digits in a four-six-five formation." (no Luhn statement) |
| Visa | Visa Core Rules and Visa Product and Service Rules (public), 24 October 2026, <https://usa.visa.com/content/dam/VCOM/download/about-visa/visa-rules-public.pdf>; Visa 8-Digit BIN Expansion FAQ | reached, searched for "Luhn", "modulus 10", "check digit" and account-number lengths: nothing | no prefix, length or Luhn statement; the FAQ says only "There is no change to the length of the PAN" |
| Mastercard | Mastercard FAQ (`mastercard.us/en-us/frequently-asked-questions.html`, 403 live); Internet Archive raw copies of it and of `checkout.mastercard.com/faq/` | archive copies reached, they do not contain the 2-series Q&A | **not confirmed** (second attempt, Task 4: `mastercard.ae` and `mastercard.co.za` FAQs 403 live, their Internet Archive `id_` copies hold no 2-series text; a search snippet states 222100-272099, "processed the same as 51-55"; not usable as a quote) |
| Mir (NSPK) | nspk.ru pages found; the product standard `support.nspk.ru/documents/642` | **unreachable** (certificate error; Task 4 search found only aggregators and news pages) | not listed |
| RuPay | NPCI RuPay Consolidated Circular V1.0, <https://www.npci.org.in/uploads/Ru_Pay_Consolidated_Circular_V1_0_b9a1bd52b3.pdf> | reached | "the first six or eight digits of the Card that identifies the Issuer"; new issuance "exclusively using the 8-digit IIN structure"; revision list "withdrawal of 81/82 files". No prefix list or length statement: not listed |

Discover's summary (start, end, PAN length min-max), with its footnotes `a` = JCB, `b` = UnionPay:

```
3000 0000 - 3059 9999  16-19      3528 0000 - 3589 9999  16-19 (a)   6221 2600 - 6237 9699  16-19 (b)
3088 0000 - 3094 9999  16-19 (a)  3600 0000 - 3699 9999  14-19        6240 0000 - 6269 9999  16-19 (b)
3095 0000 - 3095 9999  16-19      3800 0000 - 3999 9999  16-19        6282 0000 - 6288 9999  16-19 (b)
3096 0000 - 3102 9999  16-19 (a)  6011 0000 - 6011 9999  16-19        6440 0000 - 6589 9999  16-19
3112 0000 - 3120 9999  16-19 (a)  6210 9400 - 6210 9499  16-19 (b)    8100 0000 - 8171 9999  16-19 (b)
3158 0000 - 3159 9999  16-19 (a)
3337 0000 - 3349 9999  16-19 (a)
```

The document says an IIN is "the first six (6) or eight (8) digits" and that "To receive additional details ... please
use the latest IIN range table publication", which is under NDA: the summary does not name the scheme of every range
(only the `a`/`b` footnotes). The text says "all IIN ranges supported by the Discover® Network, Diners Club
International®, and PULSE®" without assigning any row to one of them, so the unmarked rows name no single scheme.
Footnote positions were read from the PDF's text coordinates (Task 4): the `a`/`b` marker sits on the row it labels,
so 3337 0000 - 3349 9999 is JCB (the block above omitted that marker). Unmarked rows: 3000-3059, 3095, 3600-3699,
3800-3999, 6011, 6440-6589 (scheme not named). Task 4 assigns a range to a scheme only where this summary or another
publication names it, and decodes the rest as an empty list.

**No-Luhn ranges: none found.** No publication reached (Discover's summary, the Amex page, Visa's public rules and FAQ,
NPCI's circular) states that a scheme's numbers carry no Luhn digit. Baymard (an aggregator) says some China UnionPay
cards fail Luhn; it names no UnionPay publication, so it is **not used**. ISO/IEC 7812-1 gives the PAN a check digit.
Luhn is enforced for every range.

## PCI DSS 4.0 requirement 3.4.1

PCI SSC FAQ 1492, <https://www.pcisecuritystandards.org/faqs/1492/>:

> "PCI DSS Requirement 3.4.1 requires that no more than the BIN and last four digits of the PAN are displayed on computer
> screens, reports, etc. unless there is a documented business justification for seeing more digits."

The BIN is six or eight digits (see IIN). Keeping the first six and the last four is within the limit for both lengths.
Visa's 8-digit BIN FAQ adds: "Rule 3.4 has been recently updated to allow exposure of up to, but no more than, the first 8
digits and any other 4 digits in a 16 digit PAN."

## CRC of the EMVCo merchant-presented QR

The EMV QRCPS document itself (version 1.1, 27 November 2020) is **unreachable** (the specification page serves no PDF
link). Two official publications quote its rule. Banco Central do Brasil, "Manual do BR Code" 2.0.1 (31 August 2020),
<https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf>, section 2.1 "Definições Comuns", note 10:

> "Segundo seção 4.7.3 CRC (ID “63”) da referência #1, utiliza-se para o cálculo do CRC, o polinômio ‘1021’ (hexa) e valor
> inicial ‘FFFF’ (hexa), que corresponde ao CRC “CRC-16-CCITT-FFFF”."

The HKMA, in its Common QR Code Specification for Retail Payments in Hong Kong 1.0 (December 2017), section 4.1: "The CRC
(ID “63”) shall be calculated according to [ISO/IEC 13239] using the polynomial '1021' (hex) and initial value 'FFFF'
(hex). The data over which the checksum is calculated shall cover all data objects, including their ID, Length and Value
... as well as the ID and Length of the CRC itself (but excluding its Value)", and "a CRC with a two-byte hexadecimal
value of '007B' is included in the QR Code as "6304007B"". Pix's EMV-QRCPS profile (the BCB manual) writes the CRC as
`6304` plus four hexadecimal digits.

**Reflection and final XOR are not stated in words by either publication.** The BCB manual prints a complete payload (270
characters, section 2.2 "Exemplo de um BR Code": `00020104141234567890123426580014BR.GOV.BCB.PIX0136123e4567-e12b-12d1-a456-426655440000` and on,
ending `6304AD38`) with the table entry "63 CRC16-CCITT (0xffff) 04 0xAD38". The checksum over the payload up to and
including `6304` is `AD38` only with no input or output reflection and no final XOR; the reflected, XOR-final and both
variants of the same text give `BFA2`, `52C7` and `405D`. That payload, with its printed CRC, is the test of
`Internal\Crc16CcittFalse`. (An earlier draft of this file claimed that ISO/IEC 13239's default CRC-16 has no reflection;
that is false: ISO/IEC 13239 / HDLC FCS is the reflected X.25 variant, and the claim is deleted.) The catalogue check value
`29B1` for `123456789` is from the RevEng catalogue (<https://reveng.sourceforge.io/crc-catalogue/16.htm>, unreachable:
HTTP 503); the helper's test keeps it as a second vector, agreeing with the BCB payload. Task 5 re-read the EMV QRCPS text (see "EMVCo QRCPS merchant-presented mode") and records the payload marker `000201` (HKMA table 4.1: ID `00`, length `02`, "A fixed value of “01”"; the BCB payload
starts `000201`).

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
**Lowercase is allowed.** Clause 5 permits "upper and lower case letters (A to Z and a to z)" in the reference part and
clause 6.2.3 converts "upper and lower case letters to digits" with one table (`A` = 10 ... `Z` = 35, printed for capitals
only). Task 7 must not refuse lowercase references; `Internal\Iso7064Mod97` takes capitals only, so the caller
upper-cases a copy for the check and keeps the value as given. Whether `RF` itself may be lowercase is not stated.

**Discrepancy in the standard:** Annex A.3 prints `RF68539007547034` / `RF68 5390 0754 7034` as its layout example, but the
check of clause 6.2 on that string leaves a remainder other than 1. The check pair of `539007547034` is `18`: SIX
Implementation Guidelines 2.3, Annex A (page 57, the "Reference" row of the example, `RF18539007547034`) and EPC069-12 v3.0,
section 2.3 "Examples" (V1, "Remittance (Reference) RF18539007547034") both print the valid `RF18`. The helper follows
clause 6, not the example, and a test records that the printed `RF68` example fails the standard's own check; the valid
`RF18` reference is a test of the helper with the SIX and EPC source.

The Finance Finland note
(<https://www.finanssiala.fi/wp-content/uploads/2024/04/structure-of-the-rf-creditor-reference-iso-11649.pdf>) states
"The check digit algorithm is the same as the algorithm used for calculating the check digit for the IBAN account
transfer" and gives `RF54 1234` and `RF98 REF1 234` as examples; both pass the clause 6.2 check and are tests of the helper.

SIX requires "a minimum of 5 and a maximum of 25 alphanumerical characters. Starting with RF, followed by the check
digits" (IG 2.3, section 2.12.2); ISO 11649 allows a shorter reference (2 + 2 + at least one). The QR-bill profile applies
SIX's bounds.

## Leading markers of the payment payloads

For `PaymentQrFactory` (Task 7).

| Payload | Marker | Source | Status |
|---|---|---|---|
| EMVCo merchant-presented QR | `000201` (ID 00, length 02, value 01) | HKMA Common QR Code Specification 1.0, table 4.1; BCB Manual do BR Code 2.0.1 example payload | reached (via HKMA and BCB) |
| EPC QR | line 1 `BCD`, version `001` or `002`, identification `SCT` | EPC069-12 v3.0, <https://europeanpaymentscouncil.eu/sites/default/files/kb/file/2022-09/EPC069-12%20v3.0%20Quick%20Response%20Code%20-%20Guidelines%20to%20Enable%20the%20Data%20Capture%20for%20the%20Initiation%20of%20an%20SCT_0.pdf> "Service Tag: ‘BCD’", "V1: ‘001’ V2: ‘002’" | reached |
| Swiss QR-bill | `SPC` ("Swiss Payments Code") | SIX IG QR-bill 2.3, section 4.2 data structure, "QRType" `"SPC" (Swiss Payments Code)`; Annex A examples | reached |
| GOST R 56042-2014 | `ST0001` followed by the charset digit | text of the standard as mirrored at <https://files.stroyinf.ru/Data2/1/4293771/4293771168.htm> (Table 3: "ST0001") | as quoted (Task 7: Rosstandart's own text unreachable) |
| SBP link | `https://qr.nspk.ru/` | NSPK documentation | **unreachable**: NSPK Rules P.235 v4.0 put the QR standards on the closed portal (Task 7); format only |
| UPI | `upi://pay?` | NPCI UPI Linking Specification; a third-party copy of version 1.6 and NPCI circulars OC 18 and OC 37 were found | **not confirmed** (the NPCI document itself was not read; OC 18 gives the parameters, not the scheme) |

## Payment payload sections (Tasks 5 to 7)

### EMVCo QRCPS merchant-presented mode

Research date 2026-11-20 (UTC). The EMVCo document itself, "EMV QR Code Specification for Payment Systems (EMV QRCPS)
Merchant-Presented Mode", version 1.1, November 2020, is **unreachable at emvco.com** (the download URL answers 404, and
the Internet Archive has no copy of the PDF). It was **reached as a third-party copy**, not an Internet Archive copy:
<https://mvallim.github.io/emv-qrcode/docs/EMVCo-Merchant-Presented-QR-Specification-v1.1.pdf> (50 pages, SHA-256
`0bba35e01068801ebef6be4d35c080dd99a6bf8f7c4caee9318ada3bb6bb6025`), whose title page and revision log are EMVCo's. Two
official publications reproduce the same data objects and are the primary citations wherever they overlap:

- HKMA, Common QR Code Specification for Retail Payments in Hong Kong 1.0 (December 2017),
  <https://www.hkma.gov.hk/media/chi/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Common_QR_Code_Specification.pdf>
  ("HKMA" below), sections 2 and 4, tables 4.1 to 4.5. It states its tables are extracted from the EMV QRCPS.
- Banco Central do Brasil, Manual do BR Code 2.0.1 ("BCB" below), section 2.1 table 1 and section 2.2.

Rules the class enforces, with their text:

| Rule | Quote | Source |
|---|---|---|
| ID: two digits `00`-`99`; length: two digits `01`-`99`; value 1-99 characters | "The ID is coded as a two-digit numeric value, with a value ranging from "00" to "99"", "The length is coded as a two-digit numeric value, with a value ranging from "01" to "99"", "minimum length of one character and maximum length of 99 characters" | EMV QRCPS 3.2, 4.3.1.1, 4.4.1.2; HKMA section 2 |
| Length counts characters of the value | "A data object ... length field ... explicitly indicates the number of characters included in the third field, i.e. the value field" (HKMA section 2); "Length shall be equal to the number of characters in the value field." | HKMA section 2 (official); EMV QRCPS 4.4.1.1 |
| A template's contents fit in 99 | "the total size of data within a template must not exceed the maximum allowed length of "99"" | EMV QRCPS 4.4 |
| One occurrence of an ID under the root | "não há, segundo a referência #1, IDs duplicados no root do documento" (no duplicate IDs in the root). Inside templates repeated IDs are **not refused**: only the third-party copy (4.3.1.2) says so | BCB section 2.1 |
| Payload format indicator `00`, value `01`, mandatory | "The Payload Format Indicator (ID "00") shall contain a value of "01"" (HKMA table 4.1: `00`, length `02`, M, "A fixed value of "01""); BCB table 1 "fixo em "01"". Its position is **not enforced**: "first" is stated only by the third-party copy (4.6.1.1) | HKMA table 4.1; BCB table 1 |
| CRC (ID 63, length 04) last | "The data over which the checksum is calculated shall cover all data objects ... as well as the ID and Length of the CRC itself (but excluding its Value)", so the value ends the payload; BCB table 1 lists `63` last | HKMA section 4.1; BCB table 1 (and EMV QRCPS 4.6.1.2 in the third-party copy) |
| CRC upper-case hex | "Hex values 'A' to 'F' must be converted to uppercase characters" | EMV QRCPS 4.7.3.2 |
| Point of initiation `11` or `12` | "shall contain a value of "11" or "12". All other values are RFU." | EMV QRCPS 4.7.2.1; HKMA table 4.1 |
| At least one merchant account in `02`-`51` | "At least one Merchant Account Information data object in the range "02" to "51" shall be present." | EMV QRCPS 4.7.9.1, table 3.6; BCB section 2.1; HKMA table 4.2A |
| IDs 26-51 are templates with a GUI (sub-ID `00`, up to 32 characters); IDs 02-25 are primitives allocated to card systems | "shall contain a primitive Globally Unique Identifier data object with a data object ID "00"", table 4.2 "Var. up to "32"", table 4.1 | EMV QRCPS 4.7.9-4.7.11; HKMA tables 4.2A, 4.2B; BCB section 2.1. The GUI may sit at any position inside the template (EMV QRCPS 4.6.1: data objects within a template "may be placed in any position") |
| Mandatory under the root: `52` (N, length 04), `53` (N, 03), `58` (ans, 02), `59`, `60` | "Data objects that are labelled [M] in Table 3.6 shall be present under the root of the QR Code." | EMV QRCPS 4.2.1.1, table 3.6; HKMA tables 4.3A, 4.4 |
| Template `62` (Additional Data Field) is nested | sub-IDs `01`-`09` (bill number .. additional consumer data request), `10` merchant tax ID, `11` merchant channel, `12`-`49` RFU, `50`-`99` payment-system templates | EMV QRCPS table 3.7 and 4.3; HKMA table 4.5 (`01`-`09` only); BCB table 1 (sub-ID `05`) |
| Maximum payload 512 characters | "The length of the payload **should** not exceed 512 alphanumeric characters, and the number of characters should be reduced proportionally when multi-byte [Unicode] characters are used." **Not enforced**: a "should", present only in the third-party copy (4.1); HKMA and BCB are silent. The class refuses only payloads over 7089 characters, the largest QR content (numeric mode, QR version 40-L), as a bound against abusive input, not as an EMVCo rule | EMV QRCPS 4.1 |
| Character sets | numeric: digits; alphanumeric special: "can only contain the Common Character Set", String objects "may contain a different character set". Enforced as printable ASCII 0x20-0x7E (the minimum of the Common Character Set; EMV Book 4 Annex B unreached) for the N and ans objects: root `00` `01` `52` `53` `55` (digits), `02`-`25` `54` `56`-`61` `63`, the GUI (sub-ID 00) of 26-51 and 62 sub-IDs `01`-`11`. UTF-8, counted in characters, only in the String objects: sub-IDs `01`-`99` of 26-51, 62 sub-IDs `12`-`99`, templates `64`-`99` | HKMA section 4.3 and tables 4.2B, 4.3A, 4.3B, 4.4, 4.5; EMV QRCPS 3.2 (Numeric / Alphanumeric Special definitions), 4.5, tables 3.6 to 3.8 |
| Amount `54` format (for `amount()`) | "shall only include (numeric) digits "0" to "9" and may contain a single "." character"; "98." is a valid example | EMV QRCPS 4.7.4.1; HKMA section 4.4 |
| CRC parameters | see "CRC of the EMVCo merchant-presented QR" above | BCB, HKMA, EMV QRCPS 4.7.3.1 |

Test vector 1: BCB section 2.2 (270 characters, ending `6304AD38`): IDs 00, 04 (primitive card account, length 14), 26 (GUI
`BR.GOV.BCB.PIX` plus sub-ID 01), 27, 52, 53 (`986`), 54 (`123.45`), 58, 59, 60, 61, 62 (sub-ID 05), 80 (unreserved
template), 63. Test vector 2: **EMV QRCPS-MPM 1.1 Annex B.7 "Representation"** (read via the third-party copy; it is the
specification's own example, not synthetic): 248 characters ending `6304A13A`, IDs 00, 01 (`12`), 29 and 31 (templates),
52 (`4111`), 58 (`CN`), 59 (`BEST TRANSPORT`), 60, 64 (template with `最佳运输` and `北京`, String format), 54
(`23.72`), 53 (`156`), 55 (`01`), 62 (sub-IDs 03, 06 `***`, 07, 09), 91 (unreserved template), 63. The printed text wraps
lines inside values; the characters used in the tests are decoded from the hexadecimal representation printed under it,
and their CRC-16/CCITT-FALSE over the UTF-8 bytes is `A13A`, which also confirms the CRC covers bytes. Every other
payload in the tests is marked synthetic.

Masking: the BCB manual and Annex B.6 treat the templates `80`-`99` as payment-system data like the accounts, so
`masked()` hides the values of IDs 02-51 and 80-99 and the CRC (`6304****`, not recomputed).

**Format only (not enforced):** the "var. up to" maxima of IDs 54-57, 59, 60, 61 and the sub-IDs of 62 (the BCB manual
sets the postal code to 99 where EMVCo and HKMA say 10, so national profiles differ); the content of IDs 02-25; the
templates 64 and 80-99 (parsed only as text); IDs 65-79 (RFU); the value of the amount beyond its character set (zero is
not refused); the MCC and ISO 3166 / 4217 membership (the accessors give null
for an unknown code). The 512-character recommendation is not enforced (see the table).

### National EMVCo profiles

Research date 2026-11-20 (UTC). Each built profile composes `MerchantPresentedQr` (through `Internal\ProfilePayload`) and adds
only the rules quoted below. A profile is built only when its publisher's own specification was read.

**Built**

| Profile | Source read | Edition |
|---|---|---|
| Pix BR Code | Banco Central do Brasil, Manual de Padroes para Iniciacao do Pix, <https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/II_ManualdePadroesparaIniciacaodoPix.pdf> (SHA-256 `fd4422bac3bb3237d26decedc8725c08449aeb7729c243165fbe5832cb236b3d`); Manual do BR Code, <https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf> | Pix manual 2.10.0 (the page also announces 2.11.0-rc1); BR Code manual 2.0.1 |
| PromptPay (Thai QR) | Bank of Thailand, Policy Guideline "Standardized Thai QR Code for Payment Transactions", 17 April 2019, <https://www.bot.or.th/content/dam/bot/fipcs/documents/FPG/2562/EngPDF/25620084.pdf> (SHA-256 `1b924d0befa061766c5b0d3ff403b8bcc83630bdac641fea910b544492977b5f`) | 2019 (B.E. 2562), section 2.3 holds the data structure |
| KHQR | National Bank of Cambodia, Prakas on KHQR for Payment in Cambodia, appendix "KHQR Code Specification in Cambodia, Merchant-Presented Mode", read as the Internet Archive raw copy <https://web.archive.org/web/20230330080021id_/https://www.nbc.gov.kh/download_files/legislation/prakas_kh/Prakas_F14-020-351-PR_KHQR_for_Payment_in_Cambodia_appendix.pdf> (SHA-256 `20b92419060a59ce5609a80e8900bf280714957b8204ea5a4012df313ab3b8c4`, a stamped scan, read page by page); NBC "KHQR Content Guideline" 1.3, <https://web.archive.org/web/20230201043137id_/https://bakong.nbc.gov.kh/download/KHQR/integration/KHQR%20Content%20Guideline%20v.1.3.pdf> (SHA-256 `549d98091718ab128629b8f414093885ac50015352536980af32469b6d22e2ec`); NBC "KHQR SDK Documentation" 2.7, <https://web.archive.org/web/20240702234224id_/https://bakong.nbc.gov.kh/download/KHQR/integration/KHQR%20SDK%20Document.pdf> (SHA-256 `5f77168caae91702033710215aee4f0c8c707ca5578133400a0deee37d36cfcd`) | specification 3.0, April 2020 |
| JPQR | Payments Japan (キャッシュレス推進協議会), コード決済に関する統一技術仕様ガイドライン【店舗提示型】MPM, read as the Internet Archive raw copy <https://web.archive.org/web/20200918130533id_/https://www.paymentsjapan.or.jp/wordpress/wp-content/uploads/2020/04/MPM_Guideline_2.0.pdf> (SHA-256 `de0596c5b3e208ec44062cdc74d14851d024249f718bed555f18c0bdf7ba4f37`) | 2.0, 27 April 2020 |
| DuitNow QR | PayNet developer documentation, DuitNow QR, merchant-presented mode V1.5, "QR Data Object" and "Examples": <https://docs.developer.paynet.my/docs/duitNow-QR/integration/QR-generation-specification/merchant-presented-mode/qr-data-object> and `.../example` | V1.5; the pages carry the notice that the site is no longer updated and point to a newer PayNet documentation site, which was not read |

Pix rules (all Pix manual 2.10.0 unless marked BR Code manual):

| Rule | Quote | Section |
|---|---|---|
| GUI `br.gov.bcb.pix`, case-insensitive | "GUI - Globally Unique Identifier: br.gov.bcb.pix ... 14 caracteres"; "O GUI - DNS reverso - e case insensitive" (footnote 10) | 2.5 |
| Several Pix templates, or a key with a URL, are accepted: no BCB text forbids them (accessors throw or list, never pick). Template 26..51 with sub-IDs 00, 01 chave (1..77), 02 infoAdicional (1..72), 03 fss (08) | "01 chave 01..77 M Chave Pix", "02 infoAdicional 01..72 O", "03 fss 08 O ISPB do facilitador de servico de saque" | 2.6 |
| Static code: the key is mandatory; dynamic: the URL (sub-ID 25, 1..77) | "Chave Pix ... Obrigatorio"; "25 URL 01..77 M Link para payload JSON"; "Link URL ... Obrigatorio" | 2.6, 2.7 |
| The maxima 77 and 72 follow from the 99-character template | "os campos: chave, infoAdicional e fss disputam o espaco de 99 caracteres do ID raiz" | 2.6.1 |
| URL without protocol prefix | "As URLs presentes nos QR Codes ... nao devem incluir prefixo de protocolo" | 2.5.2 |
| Composite code: recurrence URL in a template 80..99 with the Pix GUI, mandatory; key or payment URL optional in the recurrence-only case | "80..99 ... 25 URL 01..77 M Link para payload JSON dos parametros de recorrencia"; "sem necessidade de preenchimentos da chave DICT ou da URL de payload" | 2.8.3, 2.4 |
| Currency 986, country BR | BR Code manual table 1: `53` "986 - BRL", `58` "BR" | BR Code 2.1 |
| Merchant name 1..25, city 1..15 | BR Code table 1: `59` "01..25", `60` "01..15" | BR Code 2.1 |
| Reference label 62-05 present, 1..25 characters; `***` when no identifier is used | "Additional Data Field - Reference Label, sempre presente em um BR Code" (footnote 21); BR Code table 1 `62` "M", `05` "01..25"; "se o gerador do QR optar por nao utilizar um transactionID, o valor '***' devera ser usado" (footnote 25) | 2.6 |
| Not enforced: letters and digits for the txid | "Os caracteres permitidos no contexto do Pix para o campo txid (EMV 62-05) sao: letras minusculas, maiusculas, digitos" (2.6.2); the BR Code manual's own example payload carries `RP12345678-2019` | 2.6.2, BR Code 2.2 |
| The form of the key: decoded only (`pixKeyType()`, see "Pix key types" below), never enforced | "A regra ... segue estritamente as regras definidas no Manual Operacional do DICT" | 2.5.1 |

Pix official vectors: static, section 2.6.3, `...6304 1D3D`; dynamic, section 2.7, `...630464E4`; BR Code manual section 2.2, `...6304AD38` (a Pix template next to a card account and another arrangement). Their CRCs verify.

PromptPay rules (BOT guideline section 2.3 tag table):

| Rule | Quote |
|---|---|
| Tag 29, credit transfer, AID `A000000677010111` for merchant-presented QR | "29 ... Reserved for PromptPay - Credit Transfer with PromptPayID"; "AID 00 ans 16 M A000000677010111 for merchant-presented QR" |
| At least one proxy (no quote makes them exclusive; `proxyTypes()` lists all): 01 mobile number N 13, 02 national or tax ID N 13, 03 e-wallet ID N 15, 04 bank account N up to 43 | "Mobile number 01 N 13 One of them is mandatory", "National ID or Tax ID 02 N 13", "E-Wallet ID 03 N 15", "Bank account 04 N var. up to 43" |
| Tag 30, bill payment, AID `A000000677010112` (domestic) or `A000000677012006` (cross-border); Biller ID N 15 M; reference 1 ans up to 20 M; reference 2 up to 20 O | "30 ... Reserved for PromptPay - Bill Payment ... Biller ID 01 N 15 M ... Reference 1 02 ans var. up to 20 M ... Reference 2 03 ans var. up to 20 O" |
| Merchant category code and city optional | "Merchant Category Code 52 ... Only for payment/funds transfer through PromptPay - optional"; "Merchant City 60 ... Only for payment/funds transfer through PromptPay - optional" |
| Tags 55, 56, 57 not allowed | "Payment/funds transfer service providers through PromptPay are not allowed to use this Tag ID" |
| Not enforced: currency 764 / country TH | "For payment in Baht - enter 764"; "For domestic transactions - enter TH, for overseas transactions - enter country code of the respective country" (conditional wording) |
| Not enforced: tag 31, 39, 80 | optional or reserved |

The guideline prints no complete payload (only `0066XXXXXXXXX` and `3920000003000.00JP` fragments), so every PromptPay test payload is synthetic. The guideline makes the AID of tag 29 for customer-presented QR `A000000677010114`; it is refused here (merchant-presented only).

DuitNow rules (PayNet V1.5):

| Rule | Quote |
|---|---|
| Payload format indicator `02` | "Payload Format Indicator ... 00 N 02 M Version 02"; all four examples start `000202` |
| Point of initiation mandatory, 11 static / 12 dynamic | "Point of initiation method 01 N 02 M 11 - Static QR Code 12 - Dynamic QR Code" |
| Template 26 with AID `A0000006150001`; acquirer ID up to 6 (M); QR ID up to 28 (M); descriptor up to 20; mobile up to 15; 27 reserved | "Malaysia AID: A0000006150001", "Acquirer ID 01 ... Var. up to 06 M", "QR ID 02 ... Var. up to 28 M", "Merchant Descriptor 03 ... up to 20 O", "Mobile Number 04 ... up to 15 O", "ID 27 is Reserved for Future Use" |
| MCC mandatory; currency 458; country MY | "Merchant Category Code 52 ... M", "Use 458 for Malaysian Ringgit", "Use MY for Malaysia" |
| Name up to 25, city up to 15 (M) | "Merchant Name 59 ... Var. up to 25 M", "Merchant City 60 ... Var. up to 15 M" |
| Postal code 5 digits | "Postal Code 61 ANS 05 O Must be 5 numeric values" |
| Amounts non-zero | "If present, the amount shall be different from zero" |
| Fee 56 exactly when 55 = 02; 57 exactly when 55 = 03 | "must be present if the data object ID 55 is present with a value of 02. Otherwise, this data object must be absent"; the same for 03 and ID 57 |
| Template 64 needs 00 and 01 | "If the ID 64 is present, the Language Preference (ID 00) and Merchant Name (ID 01) must be present" |
| JomPAY (acquirer 898989) needs template 62 with RRN 90 | "RRN 90 ... C ... Required for JomPAY QR"; "Required for JomPAY QR" on ID 62 |
| Not enforced | the hash of ID 82 (the input of the SHA-256 is not stated), the acquirer ID table (BIC listing "shared by your PayNet Partner") |

DuitNow official vectors: examples 1 to 4 of the "Examples" page (POS, P2P, with a data integrity check, JomPAY); CRCs `343F`, `3A23`, `B623`, `5DFB` verify.

KHQR rules (specification 3.0 unless marked guideline):

| Rule | Quote | Section |
|---|---|---|
| Identified by a Bakong template, tag 29 or 30 | table 4.2A: "29"-"30" "Reserved for National Payment (Bakong/KHQR) for use in Cambodia", "29 Reserved For Remittance", "30 Reserved For Payment"; guideline FAQ 3 to 5: "How to identify remittance KHQR? ... Use tag 29", "How to identify corporate merchant KHQR? ... Use tag 30" | 4.2; guideline p. 11 |
| Sub-tags of 29/30 (accessors only): 00 the Bakong account ID, 01 account information (29) or merchant ID (30), 02 acquiring bank | "Gobally Unique Indentifer in tag 00 is bank main wallet or desktop wallet", "Has customer account information in sub tag 01 (account number or phone number)", "Has merchant information in sub tag 01 (merchant id or merchant account number)"; table "30 00 Unique ID of payment, 01 Merchant ID, 02 Acquiring Bank" | guideline pp. 07-11 |
| Country KH | "Put "KH" in the Country Code field if the merchant transacts in Cambodia"; table 4.3A `58` "For Cambodia is "KH""; the Prakas is "KHQR for Payment in Cambodia". Derived, not a verbatim "shall": the class needs it to tell KHQR from other codes using tags 29/30 (Thai QR, EMVCo Annex B.7) | 4.3 |
| MCC mandatory (dummy `0000` allowed), currency mandatory | table 4.3A `52` "M ... Put a dummy code of "0000""; table 4.4 `53` "M" | 4.3, 4.4 |
| Merchant name up to 25, city up to 15, postal code up to 10 | table 4.3A `59` "var. up to "25" M", `60` "var. up to "15" M", `61` "var. up to "10" O" | 4.3 |
| Language template 64: 00 and 01 present, 00 two letters, 01 up to 25, 02 up to 15 | "If the Merchant Information - Language Template (ID "64") is present, the template shall contain the Language Preference field (ID "00") and Merchant Name - Alternate Language field (ID "01")"; "shall contain 2 alphabetical characters coded to a value defined by [ISO 639]"; table 4.3B `01` "Var. up to "25", `02` "var. up to "15"" | 4.3 |
| Amount up to 13 characters, not zero | table 4.4 `54` "Var. up to "13""; "The Transaction Amount (ID "54"), if present, shall be different from zero" | 4.4 |
| Tip indicator N 2, fixed fee up to 13, percentage up to 5 | table 4.4 `55` "N "02"", `56` "Var. up to "13"", `57` "Var. up to "05"" | 4.4 |
| Additional data 62 sub-IDs 01 to 09 up to 25 | table 4.5: each of "01" to "09" "var. up to "25"" | 4.5 |
| Not enforced: currency 116 | "e.g. put "116" for KHR" (an example; the guideline's own tag-30 example uses 840) | 4.4 |
| Not enforced: the GUI form | 4.2 requires an AID, UUID or reverse domain name, but every NBC example puts a Bakong account ID (`test_bakong@devb`) in sub-tag 00; the generic 32-character bound applies | 4.2; guideline |
| Point of initiation optional | table 4.1: `"01"` Point of Initiation Method, length `"02"`, presence `O`; so not required (only its values 11/12, generic) | 4.1 |
| Not enforced: template 99 (timestamp in the NBC examples), the content of other operators' templates | "the meaning of the other data objects in the template are context specific and outside of the scope of this specification" | 4.2 |

KHQR official vectors (all CRCs verify): guideline 1.3 pages 04 (`...6304102F`, tag 29 static), 06 (`...6304D1EB`, dynamic, amount 1000), 07 (`...63042ACD`, remittance, account information `0000001112`), 08 (`...630402F7`, tag 30, USD 10.0, template 62); SDK 2.7 "Generate KHQR for Individual" (`...63042B5B`) and "for Merchant" (`...63049740`), both with a UnionPay ID 15 and a Khmer language template. The guideline prints line-wrapped payloads; the joined strings are the ones whose CRC verifies.

JPQR rules (MPM guideline 2.0, parts 1 static and 2 dynamic, tables 2.1-1, 2.1-2, 3.2):

| Rule | Quote |
|---|---|
| A template 26-51 with GUI `jp.or.paymentsjapan` carrying the unified store ID in sub-IDs 01 (13 digits), 02 (4), 03 (6), 04 (6) | table 2.1-1 契約店情報 "26"-"51" 必須: "“xx680019jp.or.paymentsjapan0113aaaaaaaaaaaaa0204bbbb0306cccccc0406dddddd” (... 13 桁の a には統一店舗識別コードの管理レベル 1 が、4 桁の b には ... 管理レベル 2 が、6 桁の c には ... 管理レベル 3 が、6 桁の d には ... 管理レベル 4 がそれぞれ入る。)"; 3.2 "統一店舗識別コードは 29 桁の数字で構成される"; table 3.2 levels 13/4/6/6 桁 |
| GUI compared case-insensitively | not stated by Payments Japan. The GUI is a reverse domain name, and DNS names compare case-insensitively (RFC 4343, abstract: "Domain Name System (DNS) names are "case insensitive"", and section 4: "ASCII label comparisons are case insensitive"; <https://www.rfc-editor.org/rfc/rfc4343.txt>, fetched 11:59 UTC); the BCB applies the same to its GUI ("O GUI - DNS reverso - e case insensitive", Pix manual footnote 10, above). Inference, documented |
| Payload at most 512 bytes | "統一静的 QR コードのデータ容量は EMV 仕様(MPM)に従い 512byte を上限とする" (part 1, 2.1) and the same for the dynamic code (part 2, 2.1) |
| Not stated (format only): maxima of 59, 60 and the 62 sub-fields | the tables give the content of 59 and 60 ("英字表記による契約店名", "英字表記による契約店所在地") without a length; 62 does not appear; the generic EMVCo bounds apply |
| Point of initiation mandatory (11 static, 12 dynamic) | table 2.1-1 静的/動的フラグ "01" 必須 "“11”（静的 QR コードの場合）"; part 2 "“12”（動的 QR コードの場合）" |
| Amount mandatory in a dynamic code | part 2 table 2.1-1 取引金額 "54" 必須 (part 1: 任意) |
| Postal code mandatory | 契約店郵便番号 "61" 必須 |
| Language template mandatory with 00 and 01 | 契約店情報（日本語） "64" 必須; table 2.1-2 使用言語 "00" 必須, 契約店名（日本語） "01" 必須 |
| Several JPQR templates: accepted (no text forbids them); `storeIds()` lists, `storeId()` throws | -- |
| Not enforced: currency 392, country JP | "通貨コード（円 “392”）", "国コード（日本 “JP”）": the yen and Japan entries in the content column, not stated as fixed values |
| Not enforced: language `JA` | "Value： JA ... 契約店ごとに協議会事務局が決定" (decided per merchant by the secretariat) |

The guideline prints the template layout but no complete payload; every JPQR test payload is synthetic and labelled.

Pix key types (decoded only, `PixBrCode::pixKeyType()` and `PixKeyType::ofKey()`; a key of no listed format gives null and is never refused):

| Type (DICT `KeyType`) | Rule | Source |
|---|---|---|
| CPF | `^[0-9]{11}$`; "O CPF será utilizado com 11 números, incluindo os dígitos verificadores. Deverá ser informado sem pontos ou traços." | API do DICT 2.13.0 "Chave" table; Manual Operacional do DICT 8.5, section 1 |
| CNPJ | `^[A-Z0-9]{12}[0-9]{2}$`; "O CNPJ será utilizado em formato alfanumérico, com 14 caracteres, incluindo os dígitos verificadores" | same |
| PHONE | `^\+[1-9]\d{1,14}$`; "O telefone celular usará o padrão E.164" | same |
| EMAIL | the schema's `Email` pattern, printed below (the rendered table drops `%&`); "E-mail deve possuir no máximo 77 caracteres e deve ser em minúsculo" | same |
| EVP | `^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$`; "UUID gerado pelo DICT, conforme formato especificado na RFC4122" | same |

```text
^[a-z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$
```

Not enforced: the CPF and CNPJ check digits (the DICT texts mention them but give no algorithm); `KeyType` is open ("Tipo de chave. _Novos tipos podem surgir_."). Sources: <https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/X_ManualOperacionaldoDICT.pdf> (Versão 8.5, SHA-256 `b97a2ae8ec2086ccf74c6b23d7caac84c46913c3e153df2509629955983eb713`); <https://www.bcb.gov.br/content/estabilidadefinanceira/pix/API-DICT.html> (DICT API 2.13.0, SHA-256 `b0fec5cdecf81f112c9e4bd6a3650d54365115dd926b7026904c4cf1d9bc3867`). The BCB's GitHub `bacen/pix-dict-api` says it is obsolete and points to that page. Test vectors: the examples of the API table (`12345678901`, `12345678901234`, `+5510998765432`, `pix@bcb.gov.br`, `123e4567-e89b-12d3-a456-426655440000`).

**Not built** (no official specification reached; the generic `MerchantPresentedQr` parses their payloads)

| Profile | Reason |
|---|---|
| SGQR (MAS/IMDA) | Not reached: the MAS/IMDA publications reached carry no data-object rule. MAS's pages, the IMDA 2018 fact sheet ("The SGQR specifications are based on the "QR Code Specification for Payment System – Merchant-Presented Mode" issued by EMVCo ... customised it for the Singapore market") and the 2022 MAS/IMDA consultation paper ("SGQR is based on internationally recognised EMVCo merchant-presented QR code specifications") give no data-object rule |
| QRIS (Bank Indonesia) | PADG 21/18/PADG/2019 article 5(1): "Penyelenggara Jasa Sistem Pembayaran dan pihak lain yang bermaksud memperoleh salinan dokumen QRIS ... harus mengajukan permohonan tertulis kepada Lembaga Standar" (the QRIS document is given on written request only); the PADG has no technical annex |
| VietQR (NAPAS) | SBV Decision 1928/QĐ-NHNN (5 Oct 2018, TCCS 03:2018/NHNNVN, the merchant-presented base standard) was not reached; Decision 2525/QĐ-NHNN is TCCS 04:2024, the customer-presented standard; NAPAS's VietQR specification is found only on third-party sites |
| HK FPS (HKMA/HKICL) | Not reached: the HKICL/HKMA publications reached carry no data-object rule for the FPS template (HKMA: the common template and an implementation guideline; HKICL: the QR page and scheme documents) |
| Bharat QR (NPCI) | NPCI circular NPCI/2017-18/PD/004: "NPCI had released technical specifications (Merchant QR Code Specification V 4.0) ... to all RuPay acquiring member banks"; no copy among NPCI's archived public files |
| QR Ph (BSP) | none found (Task 6) |

#### Task 6b search log

All dates 2026-11-20 UTC. Downloads went to fresh directories and were only read (pdftotext or page images).

| Time (UTC) | Target | URL | Result |
|---|---|---|---|
| 11:18 | Pix DICT | <https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/X_ManualOperacionaldoDICT.pdf> | 200, PDF, Versão 8.5 (146 pages); section 1 key formats |
| 11:19 | Pix DICT | <https://api.github.com/repos/bacen/pix-dict-api> | BCB's verified GitHub org; README "OBSOLETO", points to the BCB page |
| 11:20 | Pix DICT | <https://www.bcb.gov.br/content/estabilidadefinanceira/pix/API-DICT.html> | 200, DICT API 2.13.0; key type regex table |
| 11:21 | SGQR | <https://web.archive.org/cdx/search/cdx?url=mas.gov.sg/-/media/&matchType=prefix&filter=original:.*(?i)sgqr.*&collapse=urlkey&limit=50> (also with `www.` and `~/media/`) | "Temporarily Offline" page; retried at 11:56 (see below) |
| 11:21 | SGQR | <https://web.archive.org/web/20190826085949id_/https://www.mas.gov.sg/development/e-payments/sgqr>, `.../20261118084218id_/...` | pages; no specification link |
| 11:22 | SGQR | <https://www.mas.gov.sg/publications/consultations/2022/consultation-paper-on-strengthening-participation-in-sgqr> (WebFetch); <https://www.mas.gov.sg/-/media/mas-media-library/publications/consultations/ftig/2022/guidelines-on-participation-in-sgqr.pdf> and <https://www.mas.gov.sg/-/media/mas-media-library/publications/consultations/ftig/p005-2022__consultation-paper-on-strengthening-participation-in-sgqr.pdf> (curl) | page lists the paper, annexes B-D, response and guidelines; both direct PDF fetches return the same 853817-byte HTML page |
| 11:23 | SGQR | <https://web.archive.org/web/20240718173417id_/https://www.mas.gov.sg/-/media/mas-media-library/publications/consultations/ftig/2022/guidelines-on-participation-in-sgqr.pdf> | PDF (SHA-256 `b77811b2...`), participation guidelines, no data objects |
| 11:23-11:25 | SGQR | <https://web.archive.org/web/20240912090437id_/https://www.mas.gov.sg/-/media/MAS-Media-Library/publications/consultations/FTIG/P005-2022__Consultation-Paper-on-Strengthening-Participation-in-SGQR.pdf> (truncated at 1 MiB, twice) and <https://web.archive.org/web/20221026033408id_/https://www.mas.gov.sg/-/media/MAS-Media-Library/publications/consultations/FTIG/P005-2022__Consultation-Paper-on-Strengthening-Participation-in-SGQR.pdf> | PDF (SHA-256 `cc0f10b1...`), section 2.3: based on EMVCo, no rule |
| 11:24-11:27 | SGQR | `https://web.archive.org/cdx/search/cdx?url=<prefix>&matchType=prefix&collapse=urlkey&fl=original,mimetype&limit=3000` for the prefixes `www.mas.gov.sg/-/media/MAS/SGQR/`, `www.mas.gov.sg/-/media/MAS/Development/Payments/`, `www.mas.gov.sg/-/media/MAS/News%20and%20Publications/Press%20Releases/`, `www.mas.gov.sg/~/media/MAS/Development/Payments/`, `sgqr.sg/`, `www.sgqr.sg/`, `www.imda.gov.sg/-/media/Imda/Files/` | only IMDA 2018 media-release annexes A and B |
| 11:27 | SGQR | <https://web.archive.org/web/20260316060244id_/https://www.imda.gov.sg/-/media/Imda/Files/About/Media-Releases/2018/Annex-A--Singapore-Quick-Response-Code-SGQR.pdf> (SHA-256 `d1fa1987...`), <https://web.archive.org/web/20250322004200id_/https://www.imda.gov.sg/-/media/Imda/Files/About/Media-Releases/2018/annex-b--singapore-quick-response-code-sgqr.pdf> (SHA-256 `155bae3a...`) | fact sheet and scheme list, no data objects |
| 11:27 | SGQR | <https://www.mas.gov.sg/news/media-releases/2017/payments-council-endorses-singapore-quick-response-code-specifications-for-electronic-payments> | no document attached |
| 11:28 | QRIS | <https://www.bi.go.id/id/publikasi/peraturan/Pages/padg_211819.aspx> (curl: SSL reset; WebFetch: OK) | two attachments (PADG, FAQ); Lampiran 3-10 empty |
| 11:28 | QRIS | <https://www.bi.go.id/id/publikasi/peraturan/Documents/padg_211819.pdf> | PDF (SHA-256 `a02fd6e3...`); article 5: copies on written request |
| 11:28 | VietQR | <https://sbv.gov.vn/documents/d/sbv_portal/587714>, <https://sbv.gov.vn/documents/d/sbv_portal/587715>, <https://sbv.gov.vn/documents/d/sbv_portal/331375>, <https://sbv.gov.vn/documents/d/sbv_portal/539196> (curl: "Request Rejected"; WebFetch for the first three: OK) | 587714 = Decision 2525/QĐ-NHNN; 587715 = TCCS 04:2024 (customer-presented); 331375 = an IT-security circular |
| 11:28-11:29 | VietQR | the WebSearch tool of this agent session (a search engine without a result URL), queries `Quyết định 1928/QĐ-NHNN 2018 tiêu chuẩn cơ sở đặc tả kỹ thuật QR Code hiển thị từ phía đơn vị chấp nhận thanh toán TCCS 03:2018/NHNN`, `"1928/QĐ-NHNN" QR Code`, `"TCCS 03:2018/NHNN"`; then <https://thuvienphapluat.vn/page/tim-van-ban.aspx?keyword=1928%2FQ%C4%90-NHNN&match=True&area=0> (403) | no copy of the decision or the standard |
| 11:30 | VietQR | found by the WebSearch tool of this agent session (a search engine without a result URL) (extended mode), query `tiêu chuẩn cơ sở "QR Code hiển thị từ phía đơn vị chấp nhận thanh toán" NHNN 2018 TCCS`; <https://storage-edu.vnpt.vn/edu-tnh/2266/VANBAN/BGDDT/qd-4597.pdf> (MOET Decision 4597/QĐ-BGDĐT, SHA-256 `ca363ba9...`) | quotes sections 4.2.3.2, 4.2.4, 4.2.14 of 1928 for the education sector only; no NAPAS template |
| 11:30 | VietQR | the WebSearch tool of this agent session (a search engine without a result URL), query `NAPAS VietQR technical specification pdf "A000000727" "QRIBFTTA" napas.com.vn OR vietqr.net` | only third-party copies (v1.5.2) and libraries |
| 11:31 | HK FPS | <https://fps.hkicl.com.hk/eng/fps/merchants/qr_code.php> | points to the HKMA specification; no FPS template |
| 11:32 | HK FPS | <https://fps.hkicl.com.hk/eng/fps/about_fps/scheme_documentation.php>, <https://fps.hkicl.com.hk/files/page_file/126/3857/FPS%20Merchant%20Payment%20Scheme.pdf> (SHA-256 `7370985a...`), <https://fps.hkicl.com.hk/files/page_file/126/10937/HKD%20FPS%20Rules_redacted%20version_Jun%202025_dist%20ver..pdf> (SHA-256 `f31c390e...`) | no QR content |
| 11:32 | HK FPS | <https://www.hkma.gov.hk/media/eng/doc/key-functions/financial-infrastructure/infrastructure/retail-payment-initiatives/Implementation_Guideline_on_Common_QR_Code.pdf> | guideline (SHA-256 `da12e3bc...`), no data objects |
| 11:32 | KHQR | <https://bakong.nbc.gov.kh/download/KHQR/> and sub-directories | 403 |
| 11:33 | KHQR | `https://web.archive.org/cdx/search/cdx?url=<prefix>&matchType=prefix&collapse=urlkey&fl=original,timestamp,mimetype,length&limit=5000` for `bakong.nbc.gov.kh/download/`, `nbc.gov.kh/download_files/`, `www.nbc.gov.kh/download_files/` | Prakas appendix, Content Guideline 1.3, SDK documents |
| 11:33-11:37 | KHQR | <https://web.archive.org/web/20230330080021id_/https://www.nbc.gov.kh/download_files/legislation/prakas_kh/Prakas_F14-020-351-PR_KHQR_for_Payment_in_Cambodia_appendix.pdf>, <https://web.archive.org/web/20230201043137id_/https://bakong.nbc.gov.kh/download/KHQR/integration/KHQR%20Content%20Guideline%20v.1.3.pdf> | specification 3.0 read page by page; guideline tags 29/30 and examples |
| 11:39 | Bharat QR | `https://web.archive.org/cdx/search/cdx?url=<prefix>&matchType=prefix&collapse=urlkey&fl=original,timestamp,mimetype&limit=20000` for `www.npci.org.in/PDF/npci/bharat-qr/`, `npci.org.in/PDF/npci/bharat-qr/`, `www.npci.org.in/PDF/npci/` | only circular NPCI/2017-18/PD/004 and brand kits |
| 11:39-11:41 | JPQR | `https://web.archive.org/cdx/search/cdx?url=<prefix>&matchType=prefix&collapse=urlkey&fl=original,timestamp,mimetype&limit=20000` for `www.paymentsjapan.or.jp/`, `paymentsjapan.or.jp/`, then `www.paymentsjapan.or.jp/wordpress/wp-content/uploads/` filtered to `application/pdf`; <https://web.archive.org/web/20210923160004id_/https://www.paymentsjapan.or.jp/wordpress/wp-content/uploads/2019/10/20191023_JPQR_NewMember.pdf> (truncated at 1 MiB), <https://web.archive.org/web/20201205035101id_/https://www.paymentsjapan.or.jp/wordpress/wp-content/uploads/2020/04/20200427_JPQR_Developments.pdf> (news, no data objects) | MPM guidelines 1.0, 1.1, 2.0; CPM 1.1, 1.2 |
| 11:40 | Bharat QR | <https://web.archive.org/web/20220117094506id_/https://www.npci.org.in/PDF/npci/bharat-qr/Circular-Integration-%20Bharat-QR-%20UPI.pdf> | scanned circular (SHA-256 `043c88e4...`): specification 4.0 released to member banks |
| 11:41 | JPQR | <https://web.archive.org/web/20200918130533id_/https://www.paymentsjapan.or.jp/wordpress/wp-content/uploads/2020/04/MPM_Guideline_2.0.pdf> | read; built |
| 11:42 | KHQR | <https://web.archive.org/web/20240702234224id_/https://bakong.nbc.gov.kh/download/KHQR/integration/KHQR%20SDK%20Document.pdf>, <https://web.archive.org/web/20260807120547id_/https://bakong.nbc.gov.kh/download/KHQR/integration/Bakong%20Open%20API%20Document.pdf> (SHA-256 `aedb6303...`) | examples; no Bakong account ID format |
| 11:56 | SGQR | retry: <https://web.archive.org/cdx/search/cdx?url=www.mas.gov.sg/-/media/&matchType=prefix&filter=original:.*(?i)sgqr.*&collapse=urlkey&fl=original,timestamp,mimetype&limit=200> | HTTP 500 |
| 11:56 | SGQR | retry without the server-side filter: <https://web.archive.org/cdx/search/cdx?url=www.mas.gov.sg/-/media/&matchType=prefix&collapse=urlkey&fl=original,timestamp,mimetype&limit=150000> (20108 rows) and `.../MAS-Media-Library/` (5208 rows), filtered locally for "sgqr" | the consultation documents, the 2018 press-release annexes, images, and the SGQR+ PoC report; no specification |
| 11:57 | SGQR | <https://web.archive.org/web/20260117005303id_/https://www.mas.gov.sg/-/media/mas-media-library/development/e-payments/sgqr/interoperable-qr-payments-in-singapore---poc-report> (SHA-256 `db62afee...`), <https://web.archive.org/web/20240228141408id_/https://www.mas.gov.sg/-/media/mas-media-library/publications/consultations/ftig/2022/response-to-feedback-received-on-strengthening-participation-in-sgqr.pdf> (SHA-256 `6e073bde...`), <https://web.archive.org/web/20211029212000id_/https://www.mas.gov.sg/-/media/MAS/resource/news_room/press_releases/2018/Annex-A-Fact-sheet-on-SGQR.pdf> (SHA-256 `b718c3fd...`) | no data-object rule; the fact sheet repeats "based on" EMVCo |
| 11:57 | VietQR | <https://vbpl.vn/pages/vbpq-timkiem.aspx?type=0&s=1&Keyword=1928/Q%C4%90-NHNN&SearchIn=Title,Title1&IsRec=1&pv=0> (curl and WebFetch) | 200, but the page only shows "Đang tải dữ liệu..." (results load by script); no result |

### EPC069-12

EPC069-12 "Quick Response Code - Guidelines to Enable the Data Capture for the Initiation of a SEPA Credit Transfer",
version 3.0, issued and effective 13 September 2022 (the current version: no later one found, see the log),
<https://www.europeanpaymentscouncil.eu/sites/default/files/kb/file/2022-09/EPC069-12%20v3.0%20Quick%20Response%20Code%20-%20Guidelines%20to%20Enable%20the%20Data%20Capture%20for%20the%20Initiation%20of%20an%20SCT_0.pdf>
(SHA-256 `6fd09d5e...`), status **reached**. Its revision table lists V1.0 (2012), V2.0, V2.1 and V3.0; versions `001`
and `002` of the payload are both defined by it.

| Rule | Quote (section) | Outcome |
|---|---|---|
| Character sets | 2.1: "1: UTF-8 ... 2: ISO 8859-1 ... 3: ISO 8859-2 ... 4: ISO 8859-4 ... 5: ISO 8859-5 ... 6: ISO 8859-7 ... 7: ISO 8859-10 ... 8: ISO 8859-15" | enforced; `fromBytes()` transcodes 2-8 |
| Service tag, version, identification | 2.2: "Service Tag: ‘BCD’"; "V1: ‘001’ V2: ‘002’"; "Identification code: ‘SCT’" | enforced |
| BIC | 2.2: "V1: M ... V2: O/M", "8/11an" | enforced (vo-base `Bic`, ISO 9362 shape); empty only in `002` |
| Name | 2.2: "1..70an", "AT-21 The name of the Beneficiary", M | enforced (characters) |
| IBAN | 2.2: "1..34an", "Only IBAN is allowed." | enforced (vo-base `Iban`) |
| Amount | 2.2: "3..3an, 1..12n", max. 12, "Amount must be larger than or equal to 0.01, and cannot be larger than 999999999.99"; example `EUR12.3` | enforced: `EUR`, at most 12 digits and decimal point, the bounds |
| Purpose | 2.2: "1..4an" | enforced (length) |
| Reference / text | 2.2: "{Or 1..35an" structured, "Or} 1..140an" unstructured, "{Or}: Only one of the elements may be populated."; "Creditor Reference (ISO 11649 RF Creditor Reference may be used)" | enforced; `creditorReference()` decodes an RF reference, never refuses another |
| Information | 2.2: "1..70an Beneficiary to Originator information" | enforced (length) |
| Payload size | 2.2: "The total payload is limited to 331 bytes." | enforced: 331 bytes; a payload whose every character has one byte in one ISO 8859 set of 2.1 is held to 331 characters (fix round 1 ruling, option a: the transcoded form of `fromBytes()` input must still parse). The sets are checked without an extension (fix round 2 ruling): ISO 8859-1 as U+0000 to U+00FF, the others from the Unicode Consortium's mapping tables (below). Residual gap: such text given directly as UTF-8 is not held to 331 UTF-8 bytes |
| Separators | 2.2: "The elements separator is either a line feed (LF) or a carriage return line feed (CRLF)." | enforced; mixing is not forbidden and is accepted |
| Last element | 2.2: "The last populated element is not followed by any character or element separator." | enforced (fix round 1 ruling: source over plan): a trailing separator or a trailing empty element is refused, so Review Focus 3's trailing-newline round trip does not hold for EPC |

Examples (2.3), the tests of `EpcQr`: V1 `BCD / 001 / 1 / SCT / BHBLDEHHXXX / Franz Mustermänn /
DE71110220330123456789 / EUR12.3 / GDDS / RF18539007547034`, "95 characters including spaces (1) and line feeds (9), 96
byte UTF-8 code payload" (the test checks 96 bytes); V2 `BCD / 002 / 2 / SCT / (empty BIC) / François D'Alsace S.A. /
FR1420041010050500013M02606 / EUR12.3 / (empty purpose) / (empty reference) / Client:Marie Louise La Lune`, "103
characters including spaces (5) and line feeds (10), 103 byte ISO 8859-1 code payload" (the test encodes it in ISO
8859-1, checks 103 bytes and reads it through `fromBytes()`).

Mapping tables of `Internal\Iso8859` (fix round 2), Unicode Consortium, <https://www.unicode.org/Public/MAPPINGS/ISO8859/>,
downloaded 2026-11-20 13:15 UTC, status **reached**; every file's header was updated 2015-12-02:

| File | Table version | SHA-256 |
|---|---|---|
| `8859-2.TXT` | 2.0 | `020946e1...` |
| `8859-4.TXT` | 2.0 | `e7176999...` |
| `8859-5.TXT` | 2.0 | `9a2efea3...` |
| `8859-7.TXT` | 3.0 (2003-Nov-12) | `94f4a1c8...` |
| `8859-10.TXT` | 2.0 | `3441c373...` |
| `8859-15.TXT` | 2.0 | `bc72d7bd...` |

(Fix round 3: the round 2 text had paired these hashes with the wrong files; they were recomputed from the downloads.) Each maps 0x00 to 0x9F to U+0000 to U+009F (checked while generating
the tables); the 0xA0 to 0xFF rows are the per-set character classes. 8859-7 leaves 0xAE, 0xD2 and 0xFF unmapped (93
characters) and maps 0xA4 to U+20AC, 0xA5 to U+20AF and 0xAA to U+037A (the 2003 additions). ISO 8859-1 needs no table
(0x00 to 0xFF map to U+0000 to U+00FF).

### SIX QR-bill layout

Swiss Implementation Guidelines for the QR-bill, version 2.3 (valid from 21 November 2025),
<https://www.six-group.com/dam/download/banking-services/standardization/qr-bill/ig-qr-bill-v2.3-en.pdf> (SHA-256
`26bdc055...`), status **reached** (re-downloaded for Task 7). Version 2.3 removed "the option for combined address
fields in chapter 4.2.2" (revision history), so only address type `S` remains.

| Rule | Quote (section) | Outcome |
|---|---|---|
| Character set | 4.1.1: "Basic Latin (Unicode codepoints U+0020–U+007E) ... Latin1 Supplement (U+00A0–U+00FF) ... Latin Extended A (U+0100–U+017F)", Ș ș Ț ț (U+0218 to U+021B), "€ ... U+20AC"; "The message and the data in the Swiss QR Code must be UTF-8 encoded." | enforced on every element |
| Separators | 4.1.4: "All data elements must be present. If the data element has no content, at least a new line must be present. The same type of carriage return must always be used within a document. ... CR + LF ... LF"; status "A" elements "are omitted if they are not used, and no further subsequent data element is used." | enforced: 31 to 34 elements, one separator kind |
| Final separator | 4.1.4: "The carriage return after the final element is eliminated." | every separator ends an element (fix round 1 ruling): a final separator is one more, empty element and counts against the 34; Annex A examples end with an empty AP2 and no final ¶ |
| Header | Table 8: QRType "Fixed value "SPC""; Version "only the version designation "0200" is permitted in master version 02"; Coding "Fixed value 1" | enforced |
| IBAN | Table 8: "Fixed length: 21 alphanumeric characters, no spaces allowed, only IBANs with CH or LI country code permitted." | enforced |
| Creditor | Table 8: AdrTp "S - structured address"; Name "Maximum 70"; StrtNmOrAdrLine1 "Maximum 70"; BldgNbOrAdrLine2 "max. 16"; PstCd "Maximum 16"; TwnNm "Maximum 35"; Ctry "Two-digit country code according to ISO 3166-1"; 4.3.1 ""Postal code", "Town" and "Country" must be filled in." | enforced (country: two capitals, decoded by vo-base `CountryCode`) |
| Ultimate creditor | Table 8: "This entire data group must not be filled in" (status X) | enforced: seven empty elements |
| Amount | Table 8: "without leading zeroes, including decimal separators and two decimal places. Decimal, maximum 12-digits permitted, including decimal separators. Only decimal points ... between CHF/EUR 0.01 and 999,999,999.99"; 4.4 Table 10 amount "0.00" for "DO NOT USE FOR PAYMENT" | enforced; 0.00 accepted (section 4.4) |
| Currency | Table 8: "Only CHF and EUR are permitted." | enforced |
| Ultimate debtor | Table 8: optional data group, AdrTp/Name/PstCd/TwnNm/Ctry "D"; 4.1.5 "all sub-elements marked as "Dependent" must be filled" | enforced |
| Reference type | Table 8 Tp: "Must contain the code QRR where a QR-IBAN is used; where the IBAN is used, either the SCOR or NON code can be entered"; 4.3.2 "Use of the QR reference presupposes that a QR-IBAN has been used ... An IBAN cannot therefore be used." and "Use of the Creditor Reference (ISO 11649) presupposes that an IBAN has been used. A QR-IBAN cannot be used." | enforced |
| QR-IID | 2.8: "QR-IIDs consist exclusively of numbers from 30000 to 31999." | enforced: IBAN positions 5 to 9 |
| Reference | Table 8 Ref: QR reference "Always 27 characters - Numeric - Check-digit calculation as per modulo 10 recursive"; creditor reference "5 to 25 characters - Alphanumeric - ... modulo 97-10"; "The element must not be filled in for the reference type NON."; "The banks draw no distinction between lower and upper case when processing." | enforced (`QrReference`, `CreditorReference`; lowercase accepted) |
| Message and billing | Table 8: Ustrd "Maximum 140", StrdBkgInf "Maximum 140", "may contain a common total of up to 140 characters"; Trailer "Fixed value "EPD"" | enforced |
| Alternative procedures | Table 8 AltPmt: "A maximum of two occurrences ... Maximum of 100 characters per alternative procedure" | enforced |
| Postal code "without a country"; billing-information syntax (4.3.3, Annex D) | | not checked (no testable rule) |

Annex A examples are the tests: example 1 (QR-IBAN `CH6431961000004421557`, QRR `000008207791225857421286694`), 2 (QR-IBAN
`CH4431999123000889012`, QRR `210000000003139471430009017`, billing information `//S1/10/1234/11/201021/30/102673386/32/7.7/40/0:30`
joined from its two printed lines, AP1 `eBill/B/simon.muster@example.com`), 3 (`CH5204835012345671000`, `NON`), 5 and 6
(`CH5800791123000889012`, SCOR `RF18539007547034`). **Example 4 prints `RF720191230100405JSH0438`, which fails the
ISO 11649 check** (the check pair of `0191230100405JSH0438` is 24, computed with `Internal\Iso7064Mod97`); `QrBill`
refuses it and a test records it, as for ISO 11649's own `RF68` example.

### GOST R 56042-2014 and the Bank of Russia account key

**GOST R 56042-2014** "Стандарты финансовых операций. Двумерные символы штрихового кода для осуществления платежей
физических лиц", approved by Rosstandart order 552-ст of 11 June 2014, in force 1 September 2014, reprint February 2020.
Rosstandart's own text was **unreachable** (protect.gost.ru search page served no document; the standard is sold, not
published); read **as quoted** by the database mirror <https://files.stroyinf.ru/Data2/1/4293771/4293771168.htm>.

| Rule | Quote (section) | Outcome |
|---|---|---|
| Service block | 5.2.1 Table 1: "Идентификатор формата 2 ... "ST""; "Версия 4 ... ‘0001’"; "Признак набора кодированных знаков 1 ... 1 - WIN1251; 2 - UTF8; 3 - KOI8-R"; "Разделитель 1 ... По умолчанию используется знак "|""; "Длина блока равна строго 8 байт." | enforced; the separator must be an ASCII graphic character (inference from the eight bytes, which UTF-8 allows only for ASCII) |
| Pairs | 5.2.2: "Каждый реквизит состоит из псевдонима (наименования) и значения, которые между собой разделены знаком "="... "значением" признается все содержимое данной пары после первого "="... Псевдонимы могут состоять только из латинских букв, цифр и знака "_" ... регистр букв не важен" | enforced |
| Mandatory block | 5.2.3: "Обязательные реквизиты располагаются в строго определенном порядке ... Не допускается отсутствие значений"; Table 2: Name "от 1 до 160 знаков", PersonalAcc "Строка цифр строго 20 знаков", BankName "от 1 до 45 знаков", BIC "Строка цифр строго 9 знаков", CorrespAcc "Строка цифр до 20 знаков. При отсутствии ... корр. счета поле заполнять значением "0"" | enforced |
| Last separator | 5.2.4: "Знак-разделитель после последнего значения последнего элемента этого блока не используется." | enforced (an empty pair has no `=`) |
| Repeated aliases | 5.2.4: "При включении двух и более реквизитов с одинаковыми псевдонимами при разборе учитываться будет только последнее вхождение"; Table 4 step 6 "без учета регистра" | `get()` gives the last; a repeated mandatory alias must also have its format |
| Additional aliases | Annex V (Sum "Макс. 18", Purpose "Макс. 210", PayeeINN, PersAcc "Номер лицевого счета плательщика в организации", ...) | decoded only; `masked()` hides `PersonalAcc` and `PersAcc` |
| Binary string | 5.4.2: "В двумерный символ штрихового кода кодируется строка двоичного типа ... программное обеспечение принимающей организации будет получать информацию об используемом наборе кодируемых знаков из элемента N 3" | `fromBytes()` transcodes 1 and 3 and rewrites the digit to 2 |

Annex D example (declared `1`, WIN1251), the test vector in UTF-8, through `fromBytes()` from WIN1251 and KOI8-R:
`ST00011|Name=ООО "Три кита"|PersonalAcc=40702810138250123017|BankName=ОАО "БАНК"|BIC=044525225|CorrespAcc=30101810400000000225|PayeeINN=6200098765|LastName=Иванов|FirstName=Иван|MiddleName=Иванович|Purpose=Оплата членского взноса|PayerAddress=г.Рязань ул.Ленина д.10 кв.15|Sum=100000`.
The mirror prints the payee INN alias as `PayeelNN` (a lowercase L, an OCR or typesetting slip for Annex V's `PayeeINN`) and
splits the line with stray hyphens; the test uses Annex V's alias. Table 3 step 6 of the mirror has Cyrillic letters in
"РауегАddress", which the alias rule forbids; the Annex D text `PayerAddress` is used.

**Control key.** Bank of Russia, "Порядок расчета контрольного ключа в номере лицевого счета", 8 September 1997 No. 515
(in force from 1 January 1998). Not found on cbr.ru (see the log); read **as quoted** by <https://pravo.ppt.ru/poryadok/98817>
Положение 579-П (the chart of accounts of credit institutions) was not read; the search found the key rule only in
Order 515, which is what `Internal\RussianAccountKey` implements.

> "2. Для расчета контрольного ключа используется совокупность двух реквизитов - условного номера РКЦ (если лицевой счет
> открыт в РКЦ) или кредитной организации (если лицевой счет открыт в кредитной организации) и номера лицевого счета.
> 3. Значение трехзначного условного номера РКЦ соответствует разрядам 5 и 6 банковского идентификационного кода (БИК),
> дополненным слева нулем до трех разрядов. 4. Значение условного номера кредитной организации соответствует разрядам 7,
> 8 и 9 БИК. 5. Контрольный ключ рассчитывается с использованием весовых коэффициентов" (7 1 3 repeated over the 23
> digits); "7.2. Рассчитывается сумма младших разрядов полученных произведений. При получении суммы, кратной 10 ...
> значение контрольного ключа считается верным."

Annex examples, tests of `Internal\RussianAccountKey`: `30101810800000000746` at BIC `049805000` and `40102810100000010001`
at BIC `040305000` (both with the RKC number 005). A payment payload does not say whether the account is opened at an RKC
or a credit institution, so either number gives a valid key (the GOST example account `40702810138250123017` passes with
the credit institution number 225, its correspondent account with the RKC number 045). Item 8 (a letter in the sixth
digit for clearing currency) does not arise: GOST requires 20 digits. Accounts shorter than 20 digits (`CorrespAcc=0`)
have no key.

**Scope (fix round 1).** The order's preamble ties the key to "Схема обозначения лицевых счетов, приведенная в
Правилах ведения бухгалтерского учета в кредитных организациях", and item 2 to accounts opened "в РКЦ" or "в кредитной
организации". A treasury account of the Federal Treasury (since 2021; first digit 0, e.g. `03100643000000017300` at the
treasury BIC `004525988` of the Federal Tax Service's Moscow requisites) is numbered outside that scheme and fails the
key; it is accepted. The boundary "first digit 0" is an inference: no Federal Treasury text on the numbering was reached
(nalog.gov.ru memo 403, its archive copy 503, legalacts.ru TLS failure, 2026-11-20 12:58 UTC). The single treasury
account `40102810545370000003` passes the key and stays checked. Every occurrence of `PersonalAcc` and `CorrespAcc` is
checked against the BIC that counts (the last). `masked()` hides `PersonalAcc`, `PersAcc`, `PersonalAccount` (Annex V
"Лицевой счет бюджетного получателя"), `PensAcc` (SNILS) and `PayerIdNum` (identity document number); structured
references stay visible (ruling).

### NSPK SBP

**Format only.** NSPK's Rules P.235 "Правила информационно-технологического взаимодействия Агента ТСП, Банка, АО «НСПК»
при использовании QR-кодов в целях оплаты товаров (работ, услуг) в СБП", version 4.0 (in force 12.09.2024), Internet
Archive copy
<https://web.archive.org/web/20251221101611id_/https://sbp.nspk.ru/file/pravila-informacionno-tekhnologicheskogo-vzaimodejstviya_v.4.0.pdf>
(SHA-256 `812dcc19...`), status **archive**: "Правила размещаются на Сайте. Стандарты ОПКЦ СБП размещаются на Портале."
and "взаимодействие ... с использованием QR-кода, в соответствии со Стандартами ОПКЦ СБП"; the portal is
<https://support.nspk.ru>, open to participants only. The text contains no `qr.nspk.ru` link, no identifier shape and
no query field. The only link examples found are third-party (a Habr article with `?type=01&bank=...&crc=...`), so
`SbpPaymentLink` checks the host `qr.nspk.ru` over HTTPS, one identifier segment of ASCII letters and digits and a query
without spaces or fragment, as the plan allows ("otherwise format only (host, id shape)"). Its test link is synthetic.

### NPCI UPI linking specification

The NPCI "UPI Linking Specifications" (deep linking specs 1.5.1, the version NPCI's circulars name) were **not reached**:
NPCI says they were "provided to member banks"; the copies found (labnol.org, version 1.6 draft) are third-party. Reached,
from NPCI itself through the Internet Archive:

- NPCI/UPI/OC No. 18/2016-17, 20 March 2017, "Bank Compliances to enable UPI Merchant ecosystem",
  <https://web.archive.org/web/2024id_/https://www.npci.org.in/PDF/npci/upi/circular/2017/Circular18_BankCompliances_to_enbaleUPIMerchantecosystem_0.pdf>
  (SHA-256 `6e543716...`), status **archive**. Annexure A Table 1 "QR (Key Deep Linking parameters)", column "As per
  'Deep linking specs'": "Pa Payee virtual payment address M", "Pn Payee Name M", "Tr Transaction Ref No. (upto 35
  digits) C", "Am Amount O", "Mam Minimum amount O"; "PN: ... other additional parameters which are optional and can be
  referred to, from the Deep Linking Specs provided to member banks." D.7: "While generating an intent/QR string, ‘space’
  has to be represented by ’%’ ... ‘’tn=test%transaction’’".
- NPCI/UPI/OC No. 37/2017-18, 1 November 2017,
  <https://web.archive.org/web/20241010082959id_/https://www.npci.org.in/PDF/npci/upi/circular/2017/Circular%20on%20Non-compliance%20of%20UPI%20apps%20to%20Deeplinking%20specs%20Circular%2037-%201.11.2017.pdf>
  (SHA-256 `6b39d795...`): "UPI Deep linking Specs 1.5.1" is the binding version; no parameter text.

Outcome: `pa` and `pn` present and filled; `tr` 1 to 35 characters (the length; "digits" is not enforced, merchant
references are commonly alphanumeric and the specification itself was not read); no percent-decoding (the bare `%`
space). The scheme `upi://pay` is **not confirmed** from an NPCI text (the plan's and the marker table's); the test URI is
synthetic.

### Task 7 search log

All times 2026-11-20, UTC.

| Time | Topic | Attempt | Result |
|---|---|---|---|
| 12:16 | EPC | curl the EPC069-12 v3.0 URL of the marker table | 200, 442142 bytes, redirected to www.europeanpaymentscouncil.eu |
| 12:17 | EPC | WebSearch "EPC069-12 Quick Response Code guidelines latest version 3.1" | only v3.0 (EPC) and a v2.1 copy; no later version |
| 12:16 | SIX | curl ig-qr-bill-v2.3-en.pdf | 200, 2610009 bytes |
| 12:18 | GOST | WebSearch "ГОСТ Р 56042-2014 Стандарты финансовых операций двумерные символы штрихового кода ST00012 текст" | mirrors (stroyinf, meganorm), a Sberbank PDF; no Rosstandart text |
| 12:19 | GOST | curl <https://files.stroyinf.ru/Data2/1/4293771/4293771168.htm> | 200, full text read |
| 12:19 | GOST | curl <https://www.sberbank.ru/common/img/uploaded/files/shtrih-kod-standart.pdf> | TLS failure (certificate chain not trusted); not retried without verification |
| 12:19 | GOST | curl <https://protect.gost.ru/v.aspx?control=8&baseC=6&page=0&month=-1&year=-1&search=56042> | 200, no document in the page |
| 12:20 | Key | WebSearch "Порядок расчета контрольного ключа в номере лицевого счета 579-П приложение весовые коэффициенты 7 1 3 cbr.ru" | Order 515 of 08.09.1997 on legal databases; 579-П is the chart of accounts |
| 12:20 | Key | WebSearch ""контрольного ключа" "лицевого счета" 515 1997 site:cbr.ru" | no cbr.ru copy; ConsultantPlus, pravo.ppt.ru, garant |
| 12:20 | Key | curl <https://pravo.ppt.ru/poryadok/98817> and <https://www.consultant.ru/document/cons_doc_LAW_16870/> | 200; pravo.ppt.ru has the full text with the annex examples; `cons_doc_LAW_16870` is Instruction 26-U (budget account numbering), not Order 515, and is readable only on a schedule |
| 12:21 | SBP | WebSearch "НСПК СБП платежная ссылка qr.nspk.ru формат type=02 bank sum cur crc документация sbp.nspk.ru" | third-party articles and bank documents only |
| 12:21 | SBP | curl <https://sbp.nspk.ru/business/> | 403 |
| 12:21 | SBP | curl <https://habr.com/ru/companies/nspk/news/849738/> | 200; no link layout |
| 12:21 | SBP | Internet Archive CDX `url=sbp.nspk.ru&matchType=domain&filter=mimetype:application/pdf` | 84 PDFs: rules, brand guides, lists |
| 12:22 | SBP | archive copy of Rules P.235 v4.0 (20251221101611); of P.235 v2.0 (20210506225242) | v4.0 read: standards on the portal; v2.0 copy truncated at 1 MiB twice, unreadable |
| 12:23 | UPI | WebSearch "NPCI UPI Linking Specifications pdf upi://pay pa pn am cu tr npci.org.in" | labnol copies (third-party), NPCI OC 18 |
| 12:23 | UPI | curl the NPCI OC 18 URL | 403; Internet Archive copy 200 |
| 12:24 | UPI | Internet Archive CDX `url=npci.org.in/PDF/npci/upi/&matchType=prefix` | 312 rows; OC 37 on the deep linking specs; no specification PDF |
| 12:24 | UPI | WebSearch ""upi://pay" site:npci.org.in" | the UPI Product Booklet and pages; none defines the URI |
| 12:25 | UPI | archive copy of Product-Booklet.pdf (20241007101808) | read: "UPI Intent", QR scan; no `upi://pay` text |
