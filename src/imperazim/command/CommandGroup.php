<?php

declare(strict_types = 1);

namespace imperazim\command;

use pocketmine\plugin\Plugin;
use pocketmine\Server;

/**
* Utility for batch-registering groups of commands.
*
* Usage:
*   CommandGroup::register($plugin, [
*       new MyCommand($plugin),
*       new OtherCommand($plugin),
*   ]);
*/
final class CommandGroup {

    /**
    * Registers multiple commands at once.
    *
    * @param Plugin $plugin The owning plugin
    * @param Command[] $commands Commands to register
    */
    public static function register(Plugin $plugin, array $commands): void {
        $cmdMap = $plugin->getServer()->getCommandMap();
        $namespace = $plugin->getDescription()->getName();

        foreach ($commands as $command) {
            $cmdMap->register($namespace, $command);
        }
    }

    /**
    * Unregisters multiple commands at once.
    *
    * @param Plugin $plugin The owning plugin
    * @param Command[] $commands Commands to unregister
    */
    public static function unregister(Plugin $plugin, array $commands): void {
        $cmdMap = $plugin->getServer()->getCommandMap();

        foreach ($commands as $command) {
            $cmdMap->unregister($command);
        }
    }

    /**
    * Registers commands from class names. Instantiates each with the plugin.
    *
    * @param Plugin $plugin The owning plugin
    * @param class-string<Command>[] $classes Fully qualified class names
    */
    public static function registerClasses(Plugin $plugin, array $classes): void {
        $commands = [];
        foreach ($classes as $class) {
            if (!is_subclass_of($class, Command::class)) {
                $plugin->getLogger()->warning("Skipping {$class}: not a Command subclass");
                continue;
            }
            $commands[] = new $class($plugin);
        }
        self::register($plugin, $commands);
    }
}
