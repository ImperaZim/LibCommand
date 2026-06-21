# Standalone vs Package-Backed

`LibCommand` can be used as an independent PocketMine-MP plugin/library or as an official EasyLibrary package-backed provider.

EasyLibrary 3.x no longer embeds official library source. The supported provider choices are now:

| Provider | Where it lives | Who owns runtime |
|---|---|---|
| Standalone | `plugins/LibCommand.phar` or `plugins/LibCommand/` | PocketMine-MP plugin manager |
| Package-backed | `plugin_data/EasyLibrary/packages/libcommand/<version>/` plus a generated EasyLibrary proxy | EasyLibrary package manager |

## Use the standalone library when

- another plugin has a hard `depend` on `LibCommand` and you want to manage it manually;
- you want this library to be updated, removed or replaced independently from EasyLibrary;
- the server does not use the EasyLibrary package manager flow.

## Use the EasyLibrary package when

- you want EasyLibrary to install and track official packages under `plugin_data/EasyLibrary/packages/`;
- you want restart-safe package install, update, remove, rollback and repair operations;
- you want `/easylibrary packages doctor` and proxy diagnostics for official libs.

## Important

Do not intentionally run two active providers for the same runtime. During migration, if the standalone plugin is present, it wins and the internal EasyLibrary package should report as shadowed.

Validate provider ownership with:

```txt
/easylibrary packages doctor
elprobe run libcommand
```
