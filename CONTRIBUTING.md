# Contributing

All pull requests should target the `development` branch.

## Core Contributions

1. Keep public APIs backward compatible unless a breaking release is explicitly planned.
2. Add focused tests for new argument types, constraints, subcommand nesting, or packet interception features.
3. Update `README.md` and the current changelog for user-visible changes.
4. Run linting:

```bash
find src -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Development Setup

```bash
composer install
```

Validate before submitting:

```bash
find src -name '*.php' -print0 | xargs -0 -n1 php -l
