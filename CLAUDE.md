# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

`msstc4php/vo-finance`: value objects for securities identifiers (ISIN, LEI, UPI, CFI, FISN, MIC, FIGI, CUSIP, SEDOL,
WKN, Valor), payment card numbers and payment QR payloads (EMVCo merchant-presented QR and national profiles, EPC QR,
Swiss QR-bill, GOST R 56042, SBP, UPI). PHP >= 8.4. Namespace `MSSTC4PHP\ValueObject\Financial\`. Framework-agnostic;
Doctrine storage is the separate `vo-finance-doctrine` bridge. Requirements and the plan live in `../vo-base/docs/`
(plan `2026-11-20-001-feat-finance-wave-plan.md`); the source notes, the authority for every rule, are in
`docs/sources/FINANCE.md`.

## Common commands

Local install against the sibling `../vo-base`: `COMPOSER=composer-local.json composer update
--ignore-platform-req=php`. In CI the install is `composer-ci.json`.

`composer-local.json` is gitignored. Create it from `composer-ci.json`: copy the file and replace the VCS repository
with `{"type": "path", "url": "../vo-base", "options": {"symlink": true}}`, and require `msstc4php/vo-base` as
`dev-main as 1.4.0`.

- `COMPOSER=composer-local.json make check`, `make test`, `make infection` (`php -d memory_limit=-1` if the reporter
  runs out of memory), `make fix`.

`composer-ci.lock` (written by `make install-ci`) is not ignored, because `.gitignore` is a byte-identical template;
delete it after a CI-profile install and never commit it.

## Standard

Tool configuration is byte-identical to the `php84` templates of `msstc4symfony/bundle-standard`; CI's `templates` job
enforces it. Do not edit `.php-cs-fixer.dist.php`, `rector.php`, `phpstan*.neon`, `phpunit.xml.dist`,
`infection.json5`, `Makefile` or `.gitignore` here. The hex layout of that standard does not apply.

A check digit or structural rule is enforced only when `docs/sources/FINANCE.md` quotes an official source for it; no
bcmath (digit-by-digit integer arithmetic). Exception messages never contain the input or any part of it, not even its
length; never call `InvalidValueObjectException::excerpt()`.

Released as 1.x: the README section "Backward compatibility" is binding. A minor never refuses a value an earlier 1.x
accepted (add a `hasValid…()` instead), never changes a factory candidate set, an enum backed value or a `MAX_LENGTH`.
