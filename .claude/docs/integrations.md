# Integrations

- `msstc4php/vo-base ^1.2` (`dev-main as 1.2.0` in `composer-local.json`); no vo-base 1.3 dependency.
- Bridge: `msstc4php/vo-finance-doctrine` (types `fin_<snake_name>`, from Task 8).
- Source documents are read through `curl` for allowed hosts and WebFetch; the Internet Archive needs curl, and was
  offline for a while on 2026-11-20.
