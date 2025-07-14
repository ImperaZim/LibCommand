# LibCommand

<p align="center">
  <img src="https://img.shields.io/badge/PocketMine--MP-5.0.0+-blue?style=flat-square" />
  <img src="https://img.shields.io/badge/PHP-8.2+-777bb4?style=flat-square" />
  <img src="https://img.shields.io/github/license/ImperaZim/LibCommand?style=flat-square" />
  <img src="https://img.shields.io/github/issues/ImperaZim/LibCommand?style=flat-square" />
  <img src="https://img.shields.io/github/stars/ImperaZim/LibCommand?style=flat-square" />
</p>

---

> **LibCommand** is an advanced library for creating modular, typed, and dynamic commands for PocketMine-MP plugins. Define commands, subcommands, arguments, and constraints programmatically, with full client UI integration via [LibPacket](https://github.com/ImperaZim/LibPacket).

---

## ✨ Technical Features

- Define commands and subcommands via code
- Typed arguments (string, int, float, enum, player, item, boolean, etc)
- Constraints (permission, cooldown, gamemode, world, console-only, etc)
- Support for aliases, dynamic usage, automatic error messages
- LibPacket integration for dynamic command UI
- Extensible: create your own arguments and constraints

---

## ⚡ Installation & Requirements

- **PocketMine-MP** API 5.0.0+
- **PHP** 8.2+
- **LibPacket** (required)

**Installation:**
- As a library: place `imperazim/command` in your `src/` and register the autoload.
- As a PHAR plugin: download the `.phar` and place it in `plugins/`.

---

## 🚀 Basic Integration

In your main plugin class:

```php
public function onEnable(): void {
    \imperazim\command\LibCommand::getInstance()->registerInterceptor($this);
    $this->getServer()->getCommandMap()->register($this->getName(), new MyCommand($this));
}
```

---

## 📚 Technical Examples

### 1. Command without arguments

```php
class PingCommand extends Command {
    public function onBuild(): array {
        return [
            'name' => 'ping',
            'description' => 'Shows your ping'
        ];
    }
    public function onExecute(CommandResult $result): void {
        $result->getSender()->sendMessage("Your ping: 42ms");
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}
```

---

### 2. Command with arguments

```php
class TeleportCommand extends Command {
    public function onBuild(): array {
        return [
            'name' => 'teleport',
            'description' => 'Teleport to a player',
            'arguments' => [
                new PlayerArgument('target', false)
            ]
        ];
    }
    public function onExecute(CommandResult $result): void {
        $target = $result->getArgumentsList()->get('target');
        $result->getSender()->sendMessage("Teleporting to {$target->getName()}");
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}
```

---

### 3. Command with subcommands

```php
class AdminCommand extends Command {
    public function onBuild(): array {
        return [
            'name' => 'admin',
            'description' => 'Administrative commands',
            'subcommands' => [
                new KickSubCommand($this),
                new BanSubCommand($this)
            ]
        ];
    }
    public function onExecute(CommandResult $result): void {
        $result->getSender()->sendMessage("Use /admin <kick|ban>");
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}
```

---

### 4. Command with arguments and subcommands

```php
class WarpCommand extends Command {
    public function onBuild(): array {
        return [
            'name' => 'warp',
            'description' => 'Manage warps',
            'arguments' => [
                new StringArgument('name', true)
            ],
            'subcommands' => [
                new SetWarpSubCommand($this),
                new DelWarpSubCommand($this)
            ]
        ];
    }
    public function onExecute(CommandResult $result): void {
        $name = $result->getArgumentsList()->get('name');
        if ($name) {
            $result->getSender()->sendMessage("Teleporting to warp: $name");
        } else {
            $result->getSender()->sendMessage("Use /warp <name> or /warp <set|del>");
        }
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}
```

---

### 5. Subcommand with arguments

```php
class KickSubCommand extends SubCommand {
    public function onBuild(): array {
        return [
            'name' => 'kick',
            'description' => 'Kick a player',
            'arguments' => [
                new PlayerArgument('target', false),
                new StringArgument('reason', true)
            ]
        ];
    }
    public function onExecute(CommandResult $result): void {
        $target = $result->getArgumentsList()->get('target');
        $reason = $result->getArgumentsList()->get('reason', 'No reason');
        $result->getSender()->sendMessage("Player {$target->getName()} kicked. Reason: $reason");
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}
```

---

### 6. Subcommand with subcommands

```php
class UserSubCommand extends SubCommand {
    public function onBuild(): array {
        return [
            'name' => 'user',
            'description' => 'Manage users',
            'subcommands' => [
                new PromoteSubCommand($this),
                new DemoteSubCommand($this)
            ]
        ];
    }
    public function onExecute(CommandResult $result): void {
        $result->getSender()->sendMessage("Use /admin user <promote|demote>");
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}
```

---

### 7. Command with arguments and subcommands (and subcommands with arguments)

```php
class GroupCommand extends Command {
    public function onBuild(): array {
        return [
            'name' => 'group',
            'description' => 'Manage groups',
            'arguments' => [
                new StringArgument('group', true)
            ],
            'subcommands' => [
                new AddUserSubCommand($this),
                new RemoveUserSubCommand($this)
            ]
        ];
    }
    public function onExecute(CommandResult $result): void {
        $group = $result->getArgumentsList()->get('group');
        if ($group) {
            $result->getSender()->sendMessage("Group info: $group");
        } else {
            $result->getSender()->sendMessage("Use /group <group> or /group <adduser|removeuser>");
        }
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}

class AddUserSubCommand extends SubCommand {
    public function onBuild(): array {
        return [
            'name' => 'adduser',
            'description' => 'Add user to group',
            'arguments' => [
                new PlayerArgument('user', false)
            ]
        ];
    }
    public function onExecute(CommandResult $result): void {
        $user = $result->getArgumentsList()->get('user');
        $result->getSender()->sendMessage("User {$user->getName()} added to group.");
    }
    public function onFailure(CommandFailure $failure): void {
        $failure->getSender()->sendMessage($failure->getMessage());
    }
}
```

---

## 🧑‍💻 Advanced Examples

### Custom Argument

```php
class UppercaseStringArgument extends Argument {
    public function getTypeName(): string { return 'upperstring'; }
    public function getNetworkType(): int { return AvailableCommandsPacket::ARG_TYPE_STRING; }
    public function canParse(string $testString, CommandSender $sender): bool {
        return strtoupper($testString) === $testString;
    }
    public function parse(string $argument, CommandSender $sender): mixed {
        return strtoupper($argument);
    }
}
// Usage:
new UppercaseStringArgument('shout', false);
```

### Custom Constraint

```php
class OnlyAtNightConstraint extends Constraint {
    public function isSatisfiedBy(CommandSender $sender): bool {
        // Example: only allow the command at night
        return (date('H') >= 18 || date('H') < 6);
    }
    public function onFailure(CommandSender $sender): void {
        $sender->sendMessage('This command can only be used at night!');
    }
    public function onSuccess(CommandSender $sender): void {}
}
// Usage:
new OnlyAtNightConstraint();
```

### Dynamic Enum

```php
$colors = ['red', 'blue', 'green', 'yellow'];
new EnumArgument('color', false, $colors);
```

### Advanced LibPacket Integration

- Use dynamic enums and argument suggestions for client-side autocomplete.
- Combine constraints for advanced permission logic.
- Create commands that change behavior based on player context.

---

## 🧩 Argument API

- **StringArgument($name, $optional)**
- **IntegerArgument($name, $optional, $min, $max)**
- **FloatArgument($name, $optional, $min, $max)**
- **BooleanArgument($name, $optional)**
- **EnumArgument($name, $optional, $values)**
- **PlayerArgument($name, $optional)**
- **ItemArgument($name, $optional)**
- **WorldArgument($name, $optional)**
- **TargetArgument($name, $optional)**

---

## 🔒 Constraint API

- **PermissionConstraint($permission)**
- **CooldownConstraint($seconds)**
- **InGameConstraint()**
- **RequireConsoleConstraint()**
- **WorldConstraint($world)**
- **GameModeConstraint($gamemode)**

---

## 💡 Advanced Tips

- Use subcommands to organize complex commands.
- Combine arguments and subcommands for maximum flexibility.
- Handle failures in `onFailure()` for clear user feedback.
- Create your own arguments and constraints by extending the base classes.
- Use dynamic enums for contextual suggestions in client autocomplete.

---

## 🤝 License & Contributing

MIT. Pull requests and suggestions are welcome!

---

## 🔗 Useful Links

- [PocketMine-MP](https://pmmp.io/)
- [LibPacket](https://github.com/ImperaZim/LibPacket)

---

Questions? Open an issue or contribute on GitHub!