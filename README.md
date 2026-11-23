# msstc4php/vo-finance

Value objects for securities identifiers (ISIN, LEI, FIGI, CUSIP, SEDOL and others), payment card numbers and payment
QR payloads. Built on [`msstc4php/vo-base`](https://github.com/msstc4php/vo-base); PHP >= 8.4.

Every check digit and structural rule cites its official source; the notes, with quotes, are in
[`docs/sources/FINANCE.md`](docs/sources/FINANCE.md). What a class cannot check from a published source is checked for
format only and listed under "Format only" below.

**Status: stable, 1.x.** From 1.0.0 on a breaking change needs 2.0; see [Backward compatibility](#backward-compatibility).
Upgrading from 0.x, or from vo-base's deprecated `CreditCardNumber`: see the "Upgrading" block of the 1.0.0 entry in
[`CHANGELOG.md`](CHANGELOG.md).

## Install

Not on Packagist yet; register the GitHub repositories:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-base" },
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-finance" }
    ],
    "require": {
        "msstc4php/vo-finance": "^1.0"
    }
}
```

Runtime requirement: `msstc4php/vo-base ^1.4`. No framework. Suggested: `ext-mbstring`, which `EpcQr::fromBytes()`,
`Gost56042Payload::fromBytes()` and `PaymentQrFactory::fromBytes()` need for payloads in a single-byte character set
(they throw `LogicException` without it), and `ext-bcmath`, which vo-base `Decimal::compareTo()` calls on the amounts
the payloads return.

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

$qr = PaymentQrFactory::fromString($payload);    // a PaymentPayload: the class that fits, or AmbiguousValueException
$qr->masked();                                   // for logs: accounts hidden, names cut to their first character
```

## Classes

All value objects are string value objects (`AbstractStringValueObject`, with `from()`, `tryFrom()`, `isValid()`):
`(string) $vo` and `jsonSerialize()` give the full value, card numbers and payment payloads included, and `equals()`
compares it. A failure is an exception of the area's family in `MSSTC4PHP\ValueObject\Financial\Exception\`, all
`final` and extending vo-base `InvalidValueObjectException` (`InvalidSecurityIdentifierException`,
`InvalidPaymentCardNumberException`, `InvalidPaymentQrException`, `InvalidPaymentReferenceException`). Exception
messages contain no part of the input, not even its length: they name the type, a fixed bound or a fixed reason. A
value that fits several classes throws vo-base `AmbiguousValueException` with the candidates; the factories never pick
silently.

Every value object with a stored-value bound has a public `MAX_LENGTH` (characters), the frozen bound the Doctrine
bridge sizes its columns by. Where the class has an official check algorithm, `fromParts()` builds the value from its
body and computes the check digits.

### Securities and entity identifiers

| Class | Value |
|---|---|
| `Security\Isin` | ISO 6166, check digit enforced; `fromNsin()`, `prefix()`, `nsin()`, `countryCode()`, `cusip()` (US, CA), `sedol()` (GB, IE) |
| `Security\Lei` | ISO 17442, MOD 97-10 check pair enforced; `fromParts()` (18 characters), `louPrefix()` |
| `Security\Upi` | ISO 4914 UPI, format of the Regulatory Oversight Committee |
| `Security\Cfi` | ISO 10962, six letters; `category()` (`CfiCategory`) and `group()` decode from the SIX list, null for an unknown letter |
| `Security\Fisn` | ANNA FISN, issuer and description; `issuerName()`, `description()` |
| `Security\Mic` | ISO 10383, four characters (format only) |
| `Security\Figi` | OMG FIGI 1.0, check digit enforced; `fromParts()` (11 characters) |
| `Security\Cusip` | CUSIP, nine characters; check digit enforced for an all-digit CUSIP; `fromParts()` (8 digits only: no published values for letters or `*@#`) |
| `Security\Sedol` | SEDOL, check digit enforced; `fromParts()` (6 characters) |
| `Security\Wkn` | German WKN (format only) |
| `Security\Valor` | Swiss valor number (format only); `MAX_LENGTH` is 12, above the nine characters accepted today |
| `Security\SecurityIdentifier` | the interface every identifier above implements (vo-base `ValueObject` and `StringConvertible`) |
| `Security\SecurityIdentifierFactory` | `fromString(): SecurityIdentifier` builds the one type that accepts the value; several types accepting it (a 12-character value can be an ISIN and a FIGI, six letters without `I` and `O` a CFI and a WKN) throw `AmbiguousValueException`; none throws `InvalidSecurityIdentifierException`. `Valor` takes no part: it accepts any one to nine letters or digits, so it would make every MIC, WKN, CFI, SEDOL and CUSIP ambiguous; construct a `Valor` directly |

Securities and entity identifiers carry no `masked()`.

### Payment cards and references

| Class | Value |
|---|---|
| `Card\PaymentCardNumber` | ISO/IEC 7812-1: 10 to 19 digits, Luhn; spaces and hyphens are stripped; `iin()` (8 digits), `last4()`, `schemes()`, `matchesSchemeLength()`; `masked()` keeps at most the first six and the last four digits and hides at least one |
| `Card\CardScheme` | `Amex` (`amex`), `Jcb` (`jcb`), `UnionPay` (`unionpay`) with `lengths()`; a decoding result only, never a reason to refuse |
| `Reference\QrReference` | Swiss QR reference, 27 digits, recursive modulo 10; `fromParts()` (26 digits), `checkDigit()` |
| `Reference\CreditorReference` | ISO 11649 `RF` reference, MOD 97-10; lowercase is folded to capitals; `fromParts()` (the body), `checkDigits()`, `body()` |

**IIN table.** `schemes()` reads a built-in table (`Internal\IinRanges`) of the ranges a reached publication assigns to
a scheme. The table decodes and never refuses: a well-formed number outside it has an empty `schemes()`, and a length
outside the scheme's published lengths is reported by `matchesSchemeLength()`, not refused. The table changes only in a
minor release of this package, so a given version always decodes the same way; a range is added when a reached
publication states it (see "Format only" for what is missing today).

### Payment QR payloads

All implement `PaymentQr\PaymentPayload` (vo-base `ValueObject`, `StringConvertible` and `Maskable`), which both
factory methods return; the EMVCo classes implement `PaymentQr\Emvco\EmvcoPayload` too. Both interfaces are implemented
by this package only, and methods may be added to them in minor releases.

`masked()` is for logs and screens; `__toString()` and the JSON form still give the full payload. On the EMVCo classes
it replaces the values of the merchant accounts (IDs 02 to 51), the templates 80 to 99, the mobile number (62-02), the
customer label (62-06) and the CRC with asterisks of the same length. On the others it hides the IBAN, account,
identifier or VPA, keeping at most about a third of it: nothing at six characters or fewer, the last two at 7 to 11,
the last four at 12 to 17, the first two and the last four from 18. Personal data is hidden as well: a name keeps its
first character and becomes `X***`, an address line becomes `***` (the EPC beneficiary name; the QR-bill creditor's and
ultimate debtor's name, street, building number, postal code and town, the address type and country staying; EMVCo 59
and 64-01, the city 60 and 64-02 and the postal code 61; the UPI payee name `pn`; the GOST `Name`, `LastName`,
`FirstName`, `MiddleName`, `ChildFio` and `PayerAddress`). A masked string is not a valid payload. A minor release may
hide more, never less.

`serialize()` stores only the payload, and `unserialize()` validates it again, so the parsed fields are never restored
apart from the value they came from (vo-base 1.3 runs `parse()` again). A forged payload throws the class's
`InvalidPaymentQrException`, a serialized form without a string `value` vo-base `InvalidValueObjectException`; other
keys are ignored.

| Class | Payload |
|---|---|
| `PaymentQr\Emvco\MerchantPresentedQr` | EMVCo merchant-presented QR (generic): the `EmvcoPayload` accessors below; `toBuilder()` |
| `PaymentQr\Brazil\PixBrCode` | Pix BR Code (BCB): `pixKey()`, `pixKeyType()` (`PixKeyType`: CPF, CNPJ, phone, e-mail, EVP; null for a key of no DICT format), `paymentUrl()`, `recurrenceUrl()`, `additionalInfo()`, `withdrawalProvider()`, `txid()`; lists `pixKeys()`, `paymentUrls()`, `recurrenceUrls()` |
| `PaymentQr\Thailand\PromptPayQr` | Thai QR / PromptPay (BOT): `proxyType()` (`PromptPayProxyType`), `proxy()`, `billerId()`, `reference1()`, `reference2()`; lists `proxyTypes()`, `proxyOf()` |
| `PaymentQr\Malaysia\DuitNowQr` | DuitNow QR (PayNet): `acquirerId()`, `qrId()`, `merchantDescriptor()`, `mobileNumber()`, `isJomPay()` |
| `PaymentQr\Cambodia\KhqrCode` | KHQR (National Bank of Cambodia, Bakong tags 29/30): `bakongAccountId()`, `accountInformation()`, `merchantId()`, `acquiringBank()`; lists `bakongAccountIds()` |
| `PaymentQr\Japan\JpqrCode` | JPQR (Payments Japan): `storeId()` (29-digit unified store ID), `alternateMerchantName()`; lists `storeIds()`; `MAX_BYTES` 512 |
| `PaymentQr\Epc\EpcQr` | EPC QR for a SEPA credit transfer (EPC069-12 v3.0, versions 001 and 002): `version()`, `characterSet()`, `bic()` (vo-base `Bic`), `beneficiaryName()`, `iban()`, `writtenAmount()`, `amount()`, `purpose()`, `reference()`, `creditorReference()`, `remittanceText()`, `beneficiaryToOriginatorInformation()`; `fromBytes()` for character sets 2 to 8 |
| `PaymentQr\Swiss\QrBill` | Swiss QR-bill (SIX IG 2.3): `iban()`, `isQrIban()`, `creditor()` and `ultimateDebtor()` (`QrBillAddress`), `writtenAmount()`, `amount()`, `currency()`, `referenceType()` (`QrBillReferenceType`), `reference()` (`QrReference` or `CreditorReference`), `unstructuredMessage()`, `billingInformation()`, `alternativeProcedures()` |
| `PaymentQr\Ru\Gost56042Payload` | GOST R 56042-2014: `characterSet()`, `separator()`, `name()`, `personalAccount()`, `bankName()`, `bik()` (the nine-digit Russian BIK), `correspondentAccount()`, `get()` by alias; `fromBytes()` for WIN1251 and KOI8-R |
| `PaymentQr\Ru\SbpPaymentLink` | SBP link `https://qr.nspk.ru/<id>` (format only): `id()`, `get()`, `parameterNames()` |
| `PaymentQr\India\UpiPaymentUri` | UPI `upi://pay?` URI: `payeeAddress()`, `payeeName()`, `transactionReference()`, `writtenAmount()`, `amount()`, `get()`, `parameterNames()` |
| `PaymentQr\PaymentQrFactory` | `fromString()` and `fromBytes()`, returning `PaymentPayload`: by leading marker `BCD`, `SPC`, `ST0001`, `https://qr.nspk.ru/`, `upi://` the class above; otherwise the EMVCo profiles the payload identifies (Pix GUI, PromptPay AID in 29/30, DuitNow AID in 26, template 29/30 with country KH, JPQR GUI) and the one of them that accepts it; several accepting throw `AmbiguousValueException`. When every identified profile refuses, `MerchantPresentedQr` is the one candidate left: it is returned when it accepts the payload (a Pix GUI with currency 840 is a valid EMVCo payload, not a valid Pix code), otherwise the first profile's `InvalidPaymentQrException` is thrown. A payload that identifies no built profile gives `MerchantPresentedQr` |

`EmvcoPayload`, on `MerchantPresentedQr` and the five profiles: `pointOfInitiation()` (`PointOfInitiation`, null when
absent), `merchantAccounts()` (list of `MerchantAccount`), `merchantCategoryCode()`, `currency()` (vo-base
`CurrencyCode`), `writtenAmount()` and `amount()` (vo-base `Decimal`), `countryCode()` (vo-base `CountryCode`),
`merchantName()` (never null), `merchantCity()`, `postalCode()`, `additionalData()` (the sub-fields of template 62 as a
list of `DataObject` in payload order, repeats included), `additionalDataField($subId)` (the last value of a sub-ID)
and `get($id)` (a root data object as written). The category code and the city are nullable everywhere, since Thai QR
makes them optional; on `MerchantPresentedQr` they are always present. Every `get()` of the package returns a
non-empty string or null: an empty value (`Alias=`, `am=`) reads as absent.

Parts of an EMVCo payload: `Emvco\DataObject` (`$id`, `$value`); `Emvco\MerchantAccount` (`$id`, `$value`,
`$globallyUniqueIdentifier`, `$subFields`, `subField($id)`; built by the payloads, its constructor is `@internal`);
`Emvco\MerchantPresentedQrBuilder`, immutable and started empty or from `MerchantPresentedQr::toBuilder()`: `with($id,
$value)`, `withPointOfInitiation()`, `withMerchantAccount($id, $gui, $subFields)`, `withAdditionalData($subFields)`,
`build()` (format indicator first, the CRC computed). `Swiss\QrBillAddress` (`$name`, `$street`, `$buildingNumber`,
`$postalCode`, `$town`, `$country`, `countryCode()`) throws `InvalidPaymentQrException` for an invalid element.

Payment references, typed parts of the QR-bill: `Reference\QrReference` (27 digits, recursive modulo 10) and
`Reference\CreditorReference` (ISO 11649 `RF`, MOD 97-10; lowercase is folded to capitals). Both throw
`Exception\InvalidPaymentReferenceException`; they stay visible in `masked()`.

IBANs follow each specification: EPC069-12 gives the IBAN element as "1..34an", so `EpcQr` takes any IBAN vo-base
`Iban` accepts, spaces and lowercase included, and `iban()` gives the normalised form while the payload keeps the
written one; SIX IG 2.3 says "Fixed length: 21 alphanumeric characters, no spaces allowed", so `QrBill` refuses spaces.

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
accessors (`pixKeys()`, `paymentUrls()`, `recurrenceUrls()`, `proxyTypes()`, `bakongAccountIds()`, `storeIds()`) never
throw and return the distinct values in payload order. On `PixBrCode`, `additionalInfo()` and `withdrawalProvider()`
throw the same exception when several Pix templates do not all carry the same value; they have no list accessor.
`AmbiguousPaymentQrFieldException` is a `DomainException`, not an `InvalidValueObjectException`: the payload is valid,
only the question has no single answer.

## Format only

Checked for length and character set, never for a check digit or a range no official source gives; the reasons are in
[`docs/sources/FINANCE.md`](docs/sources/FINANCE.md).

- `Upi`: the format is the Regulatory Oversight Committee's (`QZ` and ten characters); the check character is not
  verified, because ISO 4914 Annex C and the ANNA DSB guidance are not reachable.
- `Cusip`: the check digit is verified only for an all-digit CUSIP; letters and `*`, `@`, `#` have no published value.
- `Isin`: the prefix is two letters and is never refused for being outside ISO 3166 (`XS`, `EZ`, `ZZ` and other prefixes
  set by the registration authority exist); `countryCode()` is null for them.
- `Isin::sedol()`: no official text states that a GB or IE ISIN carries a SEDOL; it returns null rather than refusing
  anything.
- `Lei::louPrefix()`: a plain slice of four characters; its meaning is not confirmed.
- `Mic`: no embedded list (it changes monthly), so no operating-MIC link.
- `Wkn`: WM Datenservice defines no check digit.
- `Valor`: SIX publishes no rule reached; one to nine ASCII letters or digits, the bound an inference. `MAX_LENGTH`
  (12) leaves room for longer numbers should a SIX rule allow them.
- `Figi`: the specification lists five excluded prefixes in its conformance table and seven in section 6.1 and its
  regular expression; the seven are refused.
- `PaymentCardNumber::schemes()`: only the ranges a reached publication assigns to a scheme (Amex 34/37, the JCB and
  UnionPay rows of Discover's IIN Range Summary). Mastercard, Visa, Mir and RuPay decode as an empty list, and so do
  the summary's unmarked rows, which are shared by Discover, Diners Club International and PULSE and name no single
  scheme. Luhn is enforced for every number, UnionPay included: some industry references claim that certain UnionPay
  numbers carry no Luhn digit, but no UnionPay publication says so. If one is found, a minor release relaxes the rule
  (accepting more is allowed in 1.x).
- `MerchantPresentedQr`: the TLV shape, the CRC, the mandatory data objects, the character sets and a 7089-character
  abuse bound are enforced; the 512-character recommendation (a "should", third-party copy only), the "var. up to"
  lengths of the other objects, the content of IDs 02-25 and of the templates 64 and 80-99, the position of the payload
  format indicator and repeated IDs inside templates are not. The EMVCo document is read through a third-party copy and
  two official publications (HKMA, Banco Central do Brasil).
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
  checked. The amount is held to EPC069-12's bounds only (0.01 to 999999999.99, 12 characters): the guidelines quoted
  give no number of decimals, so `EUR1.999` is accepted and `amount()` may carry more than two decimals.
- `QrBill`: the postal code "without a country", the billing-information syntax (`//` and an agreed code) and the
  alternative-procedure syntax are not checked; amount 0.00 is accepted (the "DO NOT USE FOR PAYMENT" notification).
  SIX IG 2.3's own Annex A example 4 (`RF72 0191 ...`) fails the ISO 11649 check and is refused.
- `Gost56042Payload`: only the mandatory requisites have a format; the additional ones (Annex V maxima such as `Sum`
  18 characters, `Purpose` 210) are kept as written. The control key follows Bank of Russia Order 515 (read through a
  legal database); since the payload does not say whether an account is opened at an RKC or a credit institution, either
  conditional number gives a valid key. Treasury accounts of the Federal Treasury (first digit 0, e.g. `03100...`) are
  outside the order's scope (accounts of credit institutions and RKCs) and are never refused for their key. `masked()`
  hides `PersonalAcc`, `PersAcc`, `PersonalAccount`, `PensAcc`, `PayerIdNum` and `PayerINN`. The separator must be an
  ASCII graphic character (inferred from the eight-byte service block).
- `Gost56042Payload`, `SbpPaymentLink`, `UpiPaymentUri`: at most 7089 characters, the largest QR content and, as on the
  EMVCo classes, only a bound against abusive input.
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

## Backward compatibility

From 1.0.0 on, a breaking change needs 2.0. Covered by 1.x:

- public classes, methods and parameter names (named arguments), and return types, the interfaces
  `SecurityIdentifier`, `PaymentPayload` and `EmvcoPayload` included;
- the canonical (stored) string of every value object, which is also its `__toString()`, JSON value and the `value` key
  of its `__serialize()` payload;
- the `MAX_LENGTH` (and `JpqrCode::MAX_BYTES`) bounds, and in `vo-finance-doctrine` the type names and column lengths;
- enum backed values and case names;
- exception classes and their parents.

Rules for minor releases:

- **A minor never refuses a value that an earlier 1.x release accepted.** Through the Doctrine bridge a refused stored
  value would throw on hydration, so a check that gets an official source later is reported only, by a new
  `hasValid…(): bool` method; refusing waits for 2.0. The format-only checks this concerns are listed under "Format
  only": the UPI check character, CUSIP letters and `*@#`, `Valor`, `Mic`, `Wkn`, the query fields of
  `SbpPaymentLink` and `UpiPaymentUri`, the Pix txid character set and the EMVCo "var. up to" maxima.
- A minor may accept more (relax a rule, for example a UnionPay range without Luhn), but only values within the frozen
  `MAX_LENGTH`.
- **The IIN table** ships in minors; a range is added when a reached publication states it. It only decodes, so a new
  edition changes what `schemes()` returns and never refuses a number.
- **Factory candidate sets are frozen per major.** A new profile or identifier is usable directly in a minor and joins
  `PaymentQrFactory` or `SecurityIdentifierFactory` in the next major, so a value that resolves to one class in 1.x
  keeps resolving to it.
- Enum cases may be added in a minor (a new `CardScheme` or `PixKeyType`, for example); give every `match` over a
  package enum a default arm. Backed values never change.
- `SecurityIdentifier`, `PaymentPayload` and `EmvcoPayload` are implemented by this package only; methods may be added
  to them in minor releases. Implementing them outside the package is outside the promise.

Not covered: the wording of exception messages; everything in `Internal\` or marked `@internal` (the `MerchantAccount`
constructor, for one); `masked()` may hide more in a minor, never less.

Refusals frozen for 1.x, each documented above: the factory's choice when a payload identifies a profile, the Luhn
check on every card number, the EPC rule against a separator after the last element, `QrBill`'s refusal of an IBAN with
spaces and of SIX IG 2.3's own Annex A example 4.

## With the bridges

Every class is a string value object, so `ValueObjectMapper` (vo-base) and every
[framework bridge](https://github.com/msstc4php/vo-base#framework-integration) (Symfony, Laravel, Yii 2, CodeIgniter 4,
Cycle, Spiral) validates, binds and stores it with no codec: the stored value is the canonical string, the full value
for a card number too (`masked()` is for logs and screens, not storage). Type a column, a property or a parameter with
the concrete class (`Isin`, `PaymentCardNumber`, `EpcQr`); `PaymentQrFactory` and `SecurityIdentifierFactory` are for
input whose kind is not known in advance, and their results are stored under their own class.

`msstc4php/vo-finance-doctrine` stores these value objects in Doctrine ORM and MongoDB ODM columns: one type per string
value object, `fin_<snake_name>`. A column for "any payment QR" (one type resolving through `PaymentQrFactory`) does not
exist yet; it can come later as an additional type.

## License

MIT, see [`LICENSE`](LICENSE).
