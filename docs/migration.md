
# Migration Notes

Use this file to document migration steps between versions of `LibCommand`.

## Checklist for breaking changes

- Update namespace imports if an API moved.
- Check README examples against the new version.
- Run `composer run quality` before publishing a release.
- Mention any required PocketMine-MP or PHP version changes in release notes.

## EasyLibrary compatibility

When a standalone library is also embedded into EasyLibrary, keep public API changes synchronized between both distributions.
