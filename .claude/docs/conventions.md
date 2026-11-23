# Conventions

- `src/Internal/*` helpers are `@internal final`, static `compute(string $body): string` and `isValid(string $value): bool`.
  `compute` throws `\LogicException` on a body outside the alphabet; `isValid` returns false instead.
- No bcmath: MOD 97-10 builds the remainder digit by digit (`Iso7064Mod97`).
- `Iso7064Mod97` does not rearrange: LEI is used as written, an RF reference has `RF` + pair moved behind the body first.
- Tests use the source's own examples; each test names the source in a comment. Where a test pins a value that only an
  aggregator gives (ISIN, CUSIP `*@#`), FINANCE.md says so.
- Infection needs `#[UsesClass]` for helpers that call other helpers, or PHPUnit marks the tests risky (coverage metadata is strict).
- Exception messages hold no part of the input: no value, no excerpt, no length or count (`SecurityRules::shape()` says
  "must have 12 characters", never what it got). Fixed bounds, the type and a fixed reason are fine. A test with an
  over-long input asserts that its length is not in the message (`IdentifiersTest`, `IsinTest`, `FisnTest`).
- `MAX_LENGTH` (characters) is public on every value object with a stored bound and frozen for 1.x; payload classes
  reference `PayloadBound::MAX_CHARACTERS`; `JpqrCode::MAX_LENGTH = MAX_BYTES`; `QrBill::MAX_LENGTH` 997 is SIX's
  maximum, not enforced (Table 8 limits stay below it). `StoredBoundsTest` pins them.
- `fromParts()` validates the body with its own regex and fixed message before calling the helper's `compute()`
  (which throws `LogicException` on a foreign alphabet); the constructor validates the result again.
- Every enum docblock says cases may be added in a minor; `get()` returns `non-empty-string|null` everywhere.
