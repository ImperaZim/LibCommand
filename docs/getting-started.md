
# Getting Started

`LibCommand` is framework standalone de comandos, argumentos, subcommands, constraints e autocomplete para PocketMine-MP.

## Requirements

- PHP `^8.2`
- PocketMine-MP 5.x when this repository is used as a PMMP plugin/library
- Composer for development workflows

## Development setup

```bash
composer validate --strict
composer install
composer run quality
```

## Package

```text
imperazim/libcommand
```

Read `docs/standalone-vs-embedded.md` before mixing standalone plugins with EasyLibrary package-backed providers.
