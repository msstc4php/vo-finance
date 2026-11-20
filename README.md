# msstc4php/vo-finance

Value objects for securities identifiers (ISIN, LEI, FIGI, CUSIP, SEDOL and others), payment card numbers and payment
QR payloads. Built on [`msstc4php/vo-base`](https://github.com/msstc4php/vo-base); PHP >= 8.4.

Work in progress: the value objects arrive with the 0.1.0 release. Every rule cites its official source in
[`docs/sources/FINANCE.md`](docs/sources/FINANCE.md); what a class cannot check from a published source is checked for
format only and listed here under "Format only" once the classes exist.

## Format only

Checked for length and character set, never for a check digit or a range no official source gives; the reasons are in
[`docs/sources/FINANCE.md`](docs/sources/FINANCE.md).

- `Upi`: the format is the Regulatory Oversight Committee's (`QZ` and ten characters); the check character is not verified, because ISO 4914 Annex C and the ANNA DSB guidance are not reachable.
- `Cusip`: the check digit is verified only for an all-digit CUSIP; letters and `*`, `@`, `#` have no published value.
- `Isin::sedol()`: no official text states that a GB or IE ISIN carries a SEDOL; it returns null rather than refusing anything.
- `Lei::louPrefix()`: a plain slice of four characters; its meaning is not confirmed.
- `Mic`: no embedded list (it changes monthly), so no operating-MIC link.
- `Wkn`: WM Datenservice defines no check digit.
- `Valor`: SIX publishes no rule reached; digits-only is the owner's ruling and the nine-digit bound an inference.
- `Figi`: the specification lists five excluded prefixes in its conformance table and seven in section 6.1 and its
  regular expression; the seven are refused.

## License

MIT, see [`LICENSE`](LICENSE).
