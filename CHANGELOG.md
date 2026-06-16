
# Changelog

All notable changes to `LibCommand` should be documented in this file.

The format is inspired by Keep a Changelog and uses semantic versioning where possible.

## [Unreleased]

### Added
- `CommandResult::getRawArguments()` for commands that need the original trailing text.
- Added a lifecycle-safe command interceptor shutdown path for plugin reloads and embedded unloading.
- Added regression tests for interceptor ownership and enum parameter naming.


### Added

- Community health files and GitHub templates.
- Quality workflow scaffolding.
- Compatibility and standalone/embedded documentation.

### Fixed

- Fixed command interceptor state so standalone and embedded hosts can unload cleanly.
- Fixed enum parameter names to be stable and non-empty before packet assembly.
- Fixed the sync workflow to avoid missed untracked files and retry/rebase when EasyLibrary receives concurrent sync pushes.

## [2.0.0] - TBD

### Notes

- Current repository baseline.
