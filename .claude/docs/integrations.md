# Integrations

- `msstc4php/vo-base ^1.4` (`dev-main as 1.4.0` in `composer-local.json`); `ext-bcmath` required (vo-base
  `Decimal::compareTo()` calls `bccomp()`, and vo-base does not require the extension itself). The source code here
  never calls bcmath.
- Bridge: `msstc4php/vo-finance-doctrine` (types `fin_<snake_name>`); its column lengths read the public `MAX_LENGTH`
  constants (frozen for 1.x), never private or `@internal` ones.
- Source documents are read through `curl` for allowed hosts and WebFetch; the Internet Archive needs curl, and was
  offline for a while on 2026-11-20.
- ext-mbstring (composer `suggest`, present in CI's EXTENSIONS) backs `fromBytes()` via `Internal\Transcoder`; without it `fromBytes()` throws `LogicException`. vo-base `Bic` and `Iban` are used through `tryFrom()` only, since their messages quote the input.
