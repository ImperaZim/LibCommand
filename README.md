# LibCommand
LibCommand is a small PocketMine-MP command library meant to simplify the process of creating commands while also enhancing the user experience. It provides an API for defining commands (and sub-commands) with rich argument types, permissions, and constraints, handling execution and failure cases automatically. LibCommand works with PocketMine servers and leverages network packet manipulation (via the **LibPacket** dependency) to, for example, enable client-side command suggestions and rendering. In practice, you integrate LibCommand into your plugin and use classes like `Command` and `SubCommand` to define command behavior in code, rather than writing separate entries in `plugin.yml`.

## Installation Instructions

* **Option 1 (Source / Development):** Download the LibCommand ZIP from GitHub and include its source code in your plugin’s project (for example, by putting the library files alongside your plugin code). You can use PocketMine’s *folder plugin* format (with DevTools) to load source folders directly. In that case, ensure the LibCommand files (with namespace `imperazim\command`) are in your plugin’s `src` folder. Then, in your plugin’s `onEnable()`, register LibCommand by calling:

  ```php
  \imperazim\command\LibCommand::getInstance()->registerInterceptor($this);
  ```

  This sets up LibCommand’s hooks for your plugin.

* **Option 2 (PHAR):** Download the `LibCommand.phar` file from the GitHub *Releases* page and place it in your server’s `plugins/` directory. The PHAR packaging bundles the entire library into one file. According to the PocketMine documentation, you simply drop the `.phar` into `plugins/` and restart the server to install it. After placing the PHAR, be sure to still register LibCommand in your plugin’s `onEnable()` (as shown above) so your plugin can intercept commands.

## Requirements
LibCommand depends on the **LibPacket** library. Make sure LibPacket is installed or available on your server (for example, by including it via EasyLibrary or its own PHAR). LibPacket handles the low-level packet work (such as sending command data to clients), so LibCommand will not function properly without it.
<p>
    <a href="https://github.com/ImperaZim/LibPacket" ><img align="center" src="https://github-readme-stats.vercel.app/api/pin/?username=imperazim&repo=LibPacket&show_icons=true&theme=radical&hide_border=true&include_all_commits=true&count_private=true" >
</a>
</p>

## Usage Examples

#### AdminCommand

This example defines a root command `/admin` with one sub-command (`user`). In the `onBuild()` method you return metadata (name, description, subcommands). Here’s a simplified snippet (the actual code may include more in `onExecute` and failure handling):

```php
public function onBuild(): array {
    return [
        'name' => 'admin',
        'description' => 'Root administrative command',
        'subcommands' => [
            new UserSubCommand($this)
        ]
    ];
}
```

This creates a command `/admin`. The `UserSubCommand` (below) handles the `/admin user ...` cases. In `onExecute()`, you might show usage or list available sub-commands:

```php
public function onExecute(CommandResult $result): void {
    $sender = $result->getSender();
    $available = implode(', ', array_map(fn($cmd) => $cmd->getName(), $this->getSubCommands()));
    $sender->sendMessage("Usage: /admin <{$available}>");
}
```

#### AdvancedTestCommand

This example demonstrates a command with various argument types and aliases. In `onBuild()` you specify name, aliases, description, permission, and a list of arguments:

```php
public function onBuild(): array {
    return [
        'name'        => 'advancedtest',
        'aliases'     => ['at', 'fulltest'],
        'description' => 'Demo command with all argument types',
        'permission'  => DefaultPermissions::ROOT_OPERATOR,
        'arguments'   => [
            new PlayerArgument('player', false),
            new ItemArgument('item', false),
            new IntegerArgument('quantity', false, min: 1, max: 64),
            new WorldArgument('world', true),
            new FloatArgument('damage', true, min: 0.0, max: 100.0),
            new StringArgument('message', true),
            new BooleanArgument('visible', true),
            new EnumArgument('color', true, ['red', 'blue', 'green']),
        ],
        'constraints' => [
            new InGameConstraint()
        ]
    ];
}
```

Here, `/advancedtest` (alias `/at`) accepts a player name, an item, an integer (1–64), optional world, optional float, optional string, optional boolean, and optional enum. In `onExecute()`, you would retrieve these arguments from `$result->getArgumentsList()` and perform your logic (the example sends a formatted message back to the sender).

#### PromoteSubCommand

This is a sub-command example under `/admin user`. It promotes a player to operator. In `onBuild()`, you give it a name, description, and arguments:

```php
public function onBuild(): array {
    return [
        'name'        => 'promote',
        'description' => 'Promote a player to operator',
        'arguments'   => [
            new PlayerArgument('player', false)
        ]
    ];
}
public function onExecute(CommandResult $result): void {
    $target = $result->getArgumentsList()->get('player');
    $result->getSender()->sendMessage("Promoting player {$target->getName()} to operator.");
}
```

If the player argument is missing or invalid, LibCommand automatically triggers `onFailure()`, where you can send a usage message. For example:

```php
public function onFailure(CommandFailure $failure): void {
    $failure->getSender()->sendMessage("Usage: /admin user promote <player>");
}
```

#### UserSubCommand

This sub-command groups user-related commands. Its `onBuild()` sets the name and adds `PromoteSubCommand` as a child:

```php
public function onBuild(): array {
    return [
        'name'        => 'user',
        'description' => 'Manage users',
        'subcommands' => [
            new PromoteSubCommand($this)
        ]
    ];
}
public function onExecute(CommandResult $result): void {
    // No subcommand given: show usage
    $result->getSender()->sendMessage("Usage: /admin user <promote>");
}
```

When the player types `/admin user promote <name>`, LibCommand will automatically dispatch to the `PromoteSubCommand`. If the player types `/admin user` without arguments or an unknown subcommand, `onExecute()` provides the usage string as shown.

## Getting Started

To use LibCommand in your plugin, first call its registration method in your main class (usually in `onEnable()`). For example:

```php
public function onEnable(): void {
    \imperazim\command\LibCommand::getInstance()->registerInterceptor($this);
    // Then register your commands with PocketMine:
    $this->getServer()->getCommandMap()->register($this->getName(), new AdminCommand());
}
```

The pattern is similar to other command libraries: you register the LibCommand interceptor for your plugin, then use `$server->getCommandMap()->register()` to register each command instance. After doing this, LibCommand will intercept those commands and handle argument parsing, sub-commands, and execution based on your `onBuild()`/`onExecute()` definitions.