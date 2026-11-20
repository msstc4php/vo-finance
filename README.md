# msstc4php/vo-finance

Value objects for securities identifiers (ISIN, LEI, FIGI, CUSIP, SEDOL and others), payment card numbers and payment
QR payloads. Built on [`msstc4php/vo-base`](https://github.com/msstc4php/vo-base); PHP >= 8.4.

Every check digit and structural rule cites its official source; the notes, with quotes, are in
[`docs/sources/FINANCE.md`](docs/sources/FINANCE.md). What a class cannot check from a published source is checked for
format only and listed under "Format only" below.

## Install

Not on Packagist yet; register the GitHub repositories:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-base" },
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-finance" }
    ],
    "require": {
        "msstc4php/vo-finance": "^0.1"
    }
}
```

Runtime requirement: `msstc4php/vo-base ^1.2`. No framework. `ext-mbstring` is suggested: `EpcQr::fromBytes()`,
`Gost56042Payload::fromBytes()` and `PaymentQrFactory::fromBytes()` need it for payloads in a single-byte character set
and throw `LogicException` without it.

## Usage

```php
use MSSTC4PHP\ValueObject\Financial\Card\PaymentCardNumber;
use MSSTC4PHP\ValueObject\Financial\PaymentQr\PaymentQrFactory;
use MSSTC4PHP\ValueObject\Financial\Security\Isin;

$isin = Isin::from('us0378331005');              // stored uppercase
$isin->nsin();                                   // 037833100
$isin->cusip();                                  // Cusip 037833100

$card = PaymentCardNumber::from('3782 822463 10005');
(string) $card;                                  // 378282246310005 (full value; spaces and hyphens are stripped)
$card->masked();                                 // 378282*****0005
$card->schemes();                                // [CardScheme::Amex]

$qr = PaymentQrFactory::fromString($payload);    // the class that fits, or AmbiguousValueException
```

## Classes

All value objects are string value objects (`AbstractStringValueObject`): `(string) $vo` and `jsonSerialize()` give the
full value, card numbers and payment payloads included, and `equals()` compares it. A failure is an exception of the
area's family in `MSSTC4PHP\ValueObject\Financial\Exception\`, all extending vo-base `InvalidValueObjectException`
(`InvalidSecurityIdentifierException`, `InvalidPaymentCardNumberException`, `InvalidPaymentQrException`,
`InvalidPaymentReferenceException`); exception messages never contain the input. A value that fits several classes
throws vo-base `AmbiguousValueException` with the candidates; the factories never pick silently.

### Securities and entity identifiers

| Class | Value |
|---|---|
| `Security\Isin` | ISO 6166, check digit enforced; `fromNsin()`, `prefix()`, `nsin()`, `countryCode()`, `cusip()` (US, CA), `sedol()` (GB, IE) |
| `Security\Lei` | ISO 17442, MOD 97-10 check pair enforced; `louPrefix()` |
| `Security\Upi` | ISO 4914 UPI, format of the Regulatory Oversight Committee |
| `Security\Cfi` | ISO 10962, six letters; `category()` (`CfiCategory`) and `group()` decode from the SIX list, null for an unknown letter |
| `Security\Fisn` | ANNA FISN, issuer and description; `issuerName()`, `description()` |
| `Security\Mic` | ISO 10383, four characters (format only) |
| `Security\Figi` | OMG FIGI 1.0, check digit enforced |
| `Security\Cusip` | CUSIP, nine characters; check digit enforced for an all-digit CUSIP |
| `Security\Sedol` | SEDOL, check digit enforced |
| `Security\Wkn` | German WKN (format only) |
| `Security\Valor` | Swiss valor number (format only) |
| `Security\SecurityIdentifierFactory` | `fromString()` builds the one type that accepts the value; several types accepting it (a 12-character value can be an ISIN and a FIGI, and a `Valor` accepts any one to nine letters or digits) throw `AmbiguousValueException`; none throws `InvalidSecurityIdentifierException` |

Securities and entity identifiers carry no `masked()`.

### Payment cards and references

| Class | Value |
|---|---|
| `Card\PaymentCardNumber` | ISO/IEC 7812-1: 10 to 19 digits, Luhn; spaces and hyphens are stripped; `iin()` (8 digits), `last4()`, `schemes()`, `matchesSchemeLength()`; `masked()` keeps at most the first six and the last four digits and hides at least one |
| `Card\CardScheme` | `Amex`, `Jcb`, `UnionPay` with `lengths()`; a decoding result only, never a reason to refuse |
| `Reference\QrReference` | Swiss QR reference, 27 digits, recursive modulo 10; `checkDigit()` |
| `Reference\CreditorReference` | ISO 11649 `RF` reference, MOD 97-10; lowercase is folded to capitals; `checkDigits()`, `body()` |

**IIN table.** `schemes()` reads a built-in table (`Internal\IinRanges`) of the ranges a reached publication assigns to
a scheme. The table decodes and never refuses: a well-formed number outside it has an empty `schemes()`, and a length
outside the scheme's published lengths is reported by `matchesSchemeLength()`, not refused. The table changes only in a
minor release of this package, so a given version always decodes the same way; a range is added when a reached
publication states it (see "Format only" for what is missing today).

### Payment QR payloads

All implement `Maskable`. On the EMVCo classes `masked()` replaces the values of the merchant accounts (IDs 02 to 51),
the templates 80 to 99 and the CRC with asterisks; on the others it hides the IBAN, account, identifier or VPA, keeping
its first two and last four characters. A masked string is not a valid payload.

| Class | Payload |
|---|---|
| `PaymentQr\Emvco\MerchantPresentedQr` | EMVCo merchant-presented QR (generic) |
| `PaymentQr\Brazil\PixBrCode` | Pix BR Code (BCB): `pixKey()`, `pixKeyType()` (`PixKeyType`: CPF, CNPJ, phone, e-mail, EVP; null for a key of no DICT format), `paymentUrl()`, `recurrenceUrl()`, `txid()`; lists `pixKeys()`, `paymentUrls()`, `recurrenceUrls()` |
| `PaymentQr\Thailand\PromptPayQr` | Thai QR / PromptPay (BOT): `proxyType()`, `proxy()`, `billerId()`, `reference1()`; lists `proxyTypes()`, `proxyOf()` |
| `PaymentQr\Malaysia\DuitNowQr` | DuitNow QR (PayNet): `acquirerId()`, `qrId()`, `isJomPay()` |
| `PaymentQr\Cambodia\KhqrCode` | KHQR (National Bank of Cambodia, Bakong tags 29/30): `bakongAccountId()`, `accountInformation()`, `merchantId()`, `acquiringBank()`; lists `bakongAccountIds()` |
| `PaymentQr\Japan\JpqrCode` | JPQR (Payments Japan): `storeId()` (29-digit unified store ID), `postalCode()`, `alternateMerchantName()`; lists `storeIds()` |
| `PaymentQr\Epc\EpcQr` | EPC QR for a SEPA credit transfer (EPC069-12 v3.0, versions 001 and 002): `bic()`, `beneficiaryName()`, `iban()`, `amount()`, `purpose()`, `reference()`, `creditorReference()`, `remittanceText()`, `information()`; `fromBytes()` for character sets 2 to 8 |
| `PaymentQr\Swiss\QrBill` | Swiss QR-bill (SIX IG 2.3): `iban()`, `isQrIban()`, `creditor()` and `ultimateDebtor()` (`QrBillAddress`), `amount()`, `currency()`, `referenceType()` (`QrBillReferenceType`), `reference()` (`QrReference` or `CreditorReference`), `unstructuredMessage()`, `billingInformation()`, `alternativeProcedures()` |
| `PaymentQr\Ru\Gost56042Payload` | GOST R 56042-2014: `name()`, `personalAccount()`, `bankName()`, `bic()`, `correspondentAccount()`, `get()` by alias; `fromBytes()` for WIN1251 and KOI8-R |
| `PaymentQr\Ru\SbpPaymentLink` | SBP link `https://qr.nspk.ru/<id>` (format only): `id()`, `get()`, `parameterNames()` |
| `PaymentQr\India\UpiPaymentUri` | UPI `upi://pay?` URI: `payeeAddress()`, `payeeName()`, `transactionReference()`, `amount()`, `get()`, `parameterNames()` |
| `PaymentQr\PaymentQrFactory` | by leading marker `BCD`, `SPC`, `ST0001`, `https://qr.nspk.ru/`, `upi://` the class above; otherwise the profile that accepts an EMVCo payload; several profiles throw `AmbiguousValueException`; none gives `MerchantPresentedQr` |

Payment references, typed parts of the QR-bill: `Reference\QrReference` (27 digits, recursive modulo 10) and
`Reference\CreditorReference` (ISO 11649 `RF`, MOD 97-10; lowercase is folded to capitals). Both throw
`Exception\InvalidPaymentReferenceException`; they stay visible in `masked()`.

`PaymentQrFactory::fromBytes()` takes scanned bytes: EPC and GOST payloads go through their own `fromBytes()`, any other
payload must be UTF-8 and is handled as by `fromString()`.

Line-based payloads (EPC, QR-bill) keep their separators (LF or CRLF) byte for byte; the LF and CRLF forms of one
payload are not equal. EPC refuses any separator after the last populated element (EPC069-12 section 2.2), so a
trailing newline does not round-trip there; on the QR-bill every separator ends an element, so a final separator is an
empty element within the 34. The constructors take UTF-8 text. A payload declared in another character set
goes through `fromBytes()`, which transcodes it and rewrites the character set element to UTF-8 (EPC `1`, GOST `2`), so
the stored bytes differ from the scanned ones in that element; `fromBytes()` needs ext-mbstring (composer `suggest`).

The single-value accessors (`pixKey()`, `pixKeyType()`, `paymentUrl()`, `recurrenceUrl()`, `proxyType()`, `proxy()`,
`bakongAccountId()`, `acquiringBank()`, `storeId()`, and the query accessors of `SbpPaymentLink` and `UpiPaymentUri`)
throw `Exception\AmbiguousPaymentQrFieldException` when the payload carries several distinct values; their list
accessors (`pixKeys()`, `paymentUrls()`, `recurrenceUrls()`, `proxyTypes()`, `proxyOf()`, `bakongAccountIds()`,
`storeIds()`) return all of them and never throw. On `PixBrCode`, `additionalInfo()` and `withdrawalProvider()` throw
the same exception when several Pix templates do not all carry the same value; they have no list accessor.

## Format only

Checked for length and character set, never for a check digit or a range no official source gives; the reasons are in
[`docs/sources/FINANCE.md`](docs/sources/FINANCE.md).

- `Upi`: the format is the Regulatory Oversight Committee's (`QZ` and ten characters); the check character is not
  verified, because ISO 4914 Annex C and the ANNA DSB guidance are not reachable.
- `Cusip`: the check digit is verified only for an all-digit CUSIP; letters and `*`, `@`, `#` have no published value.
- `Isin`: the prefix is two letters and is never refused for being outside ISO 3166 (`XS`, `EZ` and other prefixes set
  by the registration authority exist); `countryCode()` is null for them.
- `Isin::sedol()`: no official text states that a GB or IE ISIN carries a SEDOL; it returns null rather than refusing
  anything.
- `Lei::louPrefix()`: a plain slice of four characters; its meaning is not confirmed.
- `Mic`: no embedded list (it changes monthly), so no operating-MIC link.
- `Wkn`: WM Datenservice defines no check digit.
- `Valor`: SIX publishes no rule reached; one to nine ASCII letters or digits, the bound an inference.
- `Figi`: the specification lists five excluded prefixes in its conformance table and seven in section 6.1 and its
  regular expression; the seven are refused.
- `PaymentCardNumber::schemes()`: only the ranges a reached publication assigns to a scheme (Amex 34/37, the JCB and
  UnionPay rows of Discover's IIN Range Summary). Mastercard, Visa, Mir and RuPay decode as an empty list, and so do
  the summary's unmarked rows, which are shared by Discover, Diners Club International and PULSE and name no single
  scheme.
- `MerchantPresentedQr`: the TLV shape, the CRC, the mandatory data objects, the character sets and a 7089-character
  abuse bound are enforced; the 512-character recommendation (a "should", third-party copy only), the "var. up to"
  lengths of the other objects, the content of IDs 02-25 and of the templates 64 and 80-99, the position of the payload
  format indicator and repeated IDs inside templates are not. The EMVCo document is read through a third-party copy and
  two official publications (HKMA, Banco Central do Brasil). `additionalData()` keys such as "10" become integers in
  PHP.
- `PixBrCode`, `PromptPayQr`, `DuitNowQr`, `KhqrCode`, `JpqrCode`: only the rules quoted from the publisher's own
  specification (see [`docs/sources/FINANCE.md`](docs/sources/FINANCE.md)). Not enforced: the form of a Pix key (the
  DICT formats only decode `pixKeyType()`), the letters-and-digits rule for a Pix txid (the BCB's own BR Code example
  breaks it), PromptPay currency 764 and country TH, the DuitNow ID 82 hash and acquirer ID table, the KHQR currency and
  the content of the Bakong templates beyond their sub-tags, JPQR currency 392, country JP, language `JA` and the
  maxima of 59, 60 and 62 (none stated). `JpqrCode` refuses a payload over 512 bytes (Payments Japan's own bound).
- `EpcQr`: at most 331 bytes, except a payload whose every character has one byte in one of the ISO 8859 sets of
  section 2.1, which is held to 331 characters (the transcoded form of `fromBytes()` input must still parse; the sets
  are checked without any extension, from the Unicode Consortium's mapping tables). Residual gap: such a payload given
  directly as UTF-8 may exceed 331 bytes in UTF-8. The content of the purpose code and of a non-RF reference is not
  checked.
- `QrBill`: the postal code "without a country", the billing-information syntax (`//` and an agreed code) and the
  alternative-procedure syntax are not checked; amount 0.00 is accepted (the "DO NOT USE FOR PAYMENT" notification).
  SIX IG 2.3's own Annex A example 4 (`RF72 0191 ...`) fails the ISO 11649 check and is refused.
- `Gost56042Payload`: only the mandatory requisites have a format; the additional ones (Annex V maxima such as `Sum`
  18 characters, `Purpose` 210) are kept as written. The control key follows Bank of Russia Order 515 (read through a
  legal database); since the payload does not say whether an account is opened at an RKC or a credit institution, either
  conditional number gives a valid key. Treasury accounts of the Federal Treasury (first digit 0, e.g. `03100...`) are
  outside the order's scope (accounts of credit institutions and RKCs) and are never refused for their key. `masked()`
  hides `PersonalAcc`, `PersAcc`, `PersonalAccount`, `PensAcc` and `PayerIdNum`. The separator must be an ASCII
  graphic character (inferred from the eight-byte service block).
- `SbpPaymentLink`: format only. NSPK keeps the link layout in its SBP OPKTs standards on a closed portal, so neither
  the identifier length nor any query field (`type`, `bank`, `sum`, `cur`, `crc`) is checked.
- `UpiPaymentUri`: the NPCI linking specification was not reached; NPCI's circular OC 18 gives `pa` and `pn` as
  mandatory and `tr` "upto 35 digits" (the length is enforced, not the digits, which merchant references do not follow).
  The scheme `upi://pay` is the plan's, not confirmed from an NPCI text; nothing is percent-decoded (NPCI writes a space
  as a bare `%`).

## Profiles not built

No official specification was reached for these (QRIS and Bharat QR: their publishers give it only to applicants or
members), so their payloads parse as the generic `MerchantPresentedQr`:

- SGQR (MAS/IMDA): not reached; the MAS/IMDA publications reached (MAS's pages, the 2018 fact sheets, the 2022
  consultation paper, its response and guidelines, the SGQR+ PoC report) say only that SGQR is based on EMVCo and carry
  no data-object rule.
- QRIS (Bank Indonesia/ASPI): PADG 21/18/PADG/2019 article 5 makes the QRIS document available only on written request
  to the standards body; the regulation has no data-object annex.
- VietQR (NAPAS): the State Bank of Vietnam's base standard TCCS 03:2018 (Decision 1928/QĐ-NHNN) was not reached
  (Decision 2525/QĐ-NHNN is the customer-presented standard); the NAPAS specification circulates only as third-party
  copies.
- HK FPS (HKICL): not reached; the HKICL/HKMA publications reached (the HKMA common template and implementation
  guideline, HKICL's QR page and scheme documents) carry no data-object rule for the FPS template.
- Bharat QR (NPCI): NPCI's circular says the Merchant QR Code Specification 4.0 was released to member banks; no copy
  was found among NPCI's archived public files.
- QR Ph (BSP): none found.

## With the bridge

`msstc4php/vo-finance-doctrine` stores these value objects in Doctrine ORM and MongoDB ODM columns: one type per string
value object, `fin_<snake_name>`.

## License

MIT, see [`LICENSE`](LICENSE).
