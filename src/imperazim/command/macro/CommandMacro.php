<?php

declare(strict_types = 1);

namespace imperazim\command\macro;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\command\CommandSender;

/**
* Allows players to create command aliases that execute multiple commands in sequence.
*
* Usage:
*   CommandMacro::create($player, "attack", ["/kit pvp", "/heal", "/tp arena"]);
*   CommandMacro::execute($player, "attack");
*   CommandMacro::remove($player, "attack");
*   CommandMacro::getList($player);
*/
final class CommandMacro {

    /** @var array<string, array<string, list<string>>> playerName => [macroName => commands[]] */
    private static array $macros = [];

    /** Maximum macros per player. */
    public const MAX_MACROS = 20;

    /** Maximum commands per macro. */
    public const MAX_COMMANDS = 10;

    /**
    * Creates or overwrites a macro for a player.
    *
    * @param Player $player Owner
    * @param string $name Macro name (case-insensitive)
    * @param string[] $commands Commands to execute (with or without leading /)
    * @return bool False if limits exceeded
    */
    public static function create(Player $player, string $name, array $commands): bool {
        $pName = $player->getName();
        $name = strtolower($name);

        if (!isset(self::$macros[$pName][$name]) && count(self::$macros[$pName] ?? []) >= self::MAX_MACROS) {
            return false;
        }
        if (count($commands) > self::MAX_COMMANDS) {
            return false;
        }

        // Normalize: ensure commands don't have leading /
        $commands = array_map(fn(string $cmd) => ltrim($cmd, '/'), $commands);

        self::$macros[$pName][$name] = $commands;
        return true;
    }

    /**
    * Executes a macro (runs all commands in sequence).
    *
    * @param Player $player Player executing the macro
    * @param string $name Macro name
    * @return bool False if macro not found
    */
    public static function execute(Player $player, string $name): bool {
        $commands = self::get($player, $name);
        if ($commands === null) return false;

        $server = Server::getInstance();
        foreach ($commands as $cmd) {
            $server->dispatchCommand($player, $cmd);
        }
        return true;
    }

    /**
    * Gets the commands for a macro.
    *
    * @param Player $player Owner
    * @param string $name Macro name
    * @return string[]|null Commands or null if not found
    */
    public static function get(Player $player, string $name): ?array {
        return self::$macros[$player->getName()][strtolower($name)] ?? null;
    }

    /**
    * Removes a macro.
    *
    * @param Player $player Owner
    * @param string $name Macro name
    * @return bool False if not found
    */
    public static function remove(Player $player, string $name): bool {
        $name = strtolower($name);
        if (!isset(self::$macros[$player->getName()][$name])) return false;
        unset(self::$macros[$player->getName()][$name]);
        return true;
    }

    /**
    * Lists all macros for a player.
    *
    * @param Player $player Owner
    * @return array<string, string[]> macroName => commands[]
    */
    public static function getList(Player $player): array {
        return self::$macros[$player->getName()] ?? [];
    }

    /**
    * Checks if a macro exists.
    *
    * @param Player $player Owner
    * @param string $name Macro name
    * @return bool
    */
    public static function exists(Player $player, string $name): bool {
        return isset(self::$macros[$player->getName()][strtolower($name)]);
    }

    /**
    * Cleans up macros on quit.
    *
    * @param Player $player Disconnecting player
    */
    public static function cleanup(Player $player): void {
        unset(self::$macros[$player->getName()]);
    }
}
