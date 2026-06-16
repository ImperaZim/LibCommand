# LibCommand Changelog

All notable changes to `LibCommand` should be documented in this file.

The format is inspired by Keep a Changelog and uses semantic versioning where
possible.

## [2.0.0] - 2026-06-16

### Release Focus

LibCommand 2.0.0 is the command framework release for the 2.0 core library
line. It replaces simple command wrappers with a full command composition
system: typed arguments, nested subcommands, execution constraints, command
results, dynamic builders, macros, scheduling and Bedrock client command UI
integration through LibPacket.

The goal is to make large command trees predictable. Each command or subcommand
can own its parsing, constraints, permissions and help text instead of forcing
plugins to keep everything inside one long `execute()` method.

### Added

- Added class-based `Command` and `SubCommand` foundations with build-time
  configuration, permissions, aliases, typed arguments and nested subcommands.
- Added typed arguments for strings, numbers, booleans, players, targets, items,
  worlds, positions, ranges, enums and soft enums.
- Added `CommandResult`, `CommandFailure` and `CommandSuccess` response objects
  so commands can return structured outcomes instead of only sending messages.
- Added `CommandResult::getRawArguments()` for commands that need the original
  trailing text, such as parse, broadcast, execute or scripting commands.
- Added constraints for permissions, cooldowns, rate limits, sender type, world
  checks and custom predicates.
- Added dynamic command builders for runtime-created command surfaces.
- Added command macros, command scheduling, command history and interactive
  wizard helpers.
- Added automatic help generation in detailed, compact and usage-only modes.
- Added permission tree utilities for inspecting command access.
- Added batch registration through `CommandGroup`.
- Added LibPacket-powered `AvailableCommandsPacket` interception for dynamic
  Bedrock autocomplete and command suggestions.
- Added regression tests for interceptor ownership, enum argument names and raw
  argument handling.

### Changed

- Changed the recommended command structure to "one command or subcommand per
  class". This keeps large APIs such as LibPlaceholder and LibWorld readable and
  makes each permission/action easier to test.
- Changed enum argument assembly to always use stable non-empty parameter names,
  avoiding malformed client command metadata.
- Changed the interceptor lifecycle so it is explicitly owned by the plugin that
  registered it.
- Changed the standalone plugin to require `LibPacket`, because client command
  UI integration depends on packet interception.
- Changed sync workflows to stage untracked files, rebase on remote changes and
  retry pushes when EasyLibrary receives concurrent embedded updates.

### Fixed

- Fixed command interceptor shutdown so standalone and embedded hosts can unload
  cleanly during reload or plugin disable.
- Fixed stale interceptor ownership when EasyLibrary switches between embedded
  and standalone command hosts.
- Fixed enum and soft enum parameter naming before packet assembly.
- Fixed quality analysis stubs and Composer lock state for reproducible CI.

### Compatibility Notes

- Requires PocketMine-MP API `5.0.0+`.
- Requires PHP `8.2+`.
- Standalone `LibCommand` depends on `LibPacket`.
- EasyLibrary embeds the same `imperazim\command` namespace and can provide the
  command API when the standalone plugin is not installed.

### Migration Notes

- Prefer one file per command and subcommand:

```txt
src/myplugin/command/MainCommand.php
src/myplugin/command/subcommand/ReloadSubCommand.php
src/myplugin/command/subcommand/DebugSubCommand.php
```

- Register command groups from your plugin owner:

```php
use imperazim\command\CommandGroup;

CommandGroup::register($this, [
    new MainCommand($this),
]);
```

### Example

```php
use imperazim\command\Command;
use imperazim\command\argument\StringArgument;
use imperazim\command\result\CommandFailure;
use imperazim\command\result\CommandResult;

final class GreetCommand extends Command {
    public function onBuild(): array {
        return [
            'name' => 'greet',
            'description' => 'Send a greeting',
            'permission' => 'example.greet',
            'arguments' => [
                new StringArgument('name'),
            ],
        ];
    }

    public function onExecute(CommandResult $result): void {
        $name = (string) $result->getArgumentsList()->get('name');
        $result->getSender()->sendMessage('Hello ' . $name . '!');
    }

    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage('Command failed: ' . $failure->getReasonName());
    }
}
```
