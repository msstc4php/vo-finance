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
