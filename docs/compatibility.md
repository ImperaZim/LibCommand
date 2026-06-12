
# Compatibility

This document records the expected compatibility targets for `LibCommand`.

| Field | Value |
|---|---|
| Repository | `LibCommand` |
| Composer package | `imperazim/libcommand` |
| Current version | `2.0.0` |
| PHP | `^8.2` |
| PocketMine-MP | `5.x` |
| Standalone usage | Yes |
| Embedded in EasyLibrary | Yes |

## Notes

- Prefer the latest tagged release for production servers.
- Development branches may include unstable APIs and migration work.
- When using this with PocketMine-MP, always test on the exact PMMP version used by the target server.
- If both a standalone library and EasyLibrary are installed, the standalone runtime should be treated as authoritative when that handoff is supported.
