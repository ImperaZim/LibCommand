<?php

declare(strict_types = 1);

namespace imperazim\command\permission;

use pocketmine\Server;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use imperazim\command\Command;
use imperazim\command\SubCommand;
use imperazim\command\constraint\PermissionConstraint;

/**
* Generates a visual permission tree showing command hierarchies and access levels.
*
* Usage:
*   $tree = CommandPermissionTree::generate($commands);
*   CommandPermissionTree::sendTo($player, $commands);
*/
final class CommandPermissionTree {

    /**
    * Generates a permission tree string from registered commands.
    *
    * @param Command[] $commands Commands to analyze
    * @param CommandSender|null $viewer If provided, colors based on viewer's permissions
    * @return string Formatted tree string
    */
    public static function generate(array $commands, ?CommandSender $viewer = null): string {
        $lines = ["§l§eCommand Permission Tree§r\n"];

        foreach ($commands as $command) {
            $permission = $command->getPermission() ?? "none";
            $hasAccess = $viewer !== null && $viewer->hasPermission($permission);
            $color = $hasAccess ? "§a" : "§c";

            $lines[] = "{$color}/" . $command->getName() . " §7[{$permission}]";

            // Subcommands
            foreach ($command->getSubCommands() as $sub) {
                self::buildSubTree($sub, $lines, $viewer, "  ");
            }
        }

        return implode("\n", $lines);
    }

    /**
    * Sends the permission tree to a player.
    *
    * @param Player $player Target player
    * @param Command[] $commands Commands to display
    */
    public static function sendTo(Player $player, array $commands): void {
        $tree = self::generate($commands, $player);
        $player->sendMessage($tree);
    }

    /**
    * Builds a tree for a specific command, showing its structure.
    *
    * @param Command $command Single command
    * @param CommandSender|null $viewer If provided, colors based on access
    * @return string Formatted tree for this command
    */
    public static function forCommand(Command $command, ?CommandSender $viewer = null): string {
        $lines = [];
        $permission = $command->getPermission() ?? "none";
        $hasAccess = $viewer !== null && $viewer->hasPermission($permission);
        $color = $hasAccess ? "§a" : "§c";

        $lines[] = "{$color}/" . $command->getName() . " §7[{$permission}]";

        // Arguments
        foreach ($command->getArguments() as $arg) {
            $opt = $arg->isOptional() ? "§7(optional)" : "§6(required)";
            $lines[] = "  §f├─ §b<" . $arg->getName() . ": " . $arg->getTypeName() . "> {$opt}";
        }

        // Subcommands
        foreach ($command->getSubCommands() as $sub) {
            self::buildSubTree($sub, $lines, $viewer, "  ");
        }

        // Constraints
        foreach ($command->getConstraints() as $constraint) {
            $lines[] = "  §f├─ §d⚙ " . $constraint->getDescription();
        }

        return implode("\n", $lines);
    }

    private static function buildSubTree(SubCommand $sub, array &$lines, ?CommandSender $viewer, string $indent): void {
        $subPermission = self::getSubCommandPermission($sub);
        $hasAccess = $viewer !== null && ($subPermission === null || $viewer->hasPermission($subPermission));
        $color = $hasAccess ? "§a" : "§c";

        $lines[] = "{$indent}{$color}├─ " . $sub->getName() . " §7[" . ($subPermission ?? "inherited") . "]";

        // Arguments
        foreach ($sub->getArguments() as $arg) {
            $opt = $arg->isOptional() ? "§7(optional)" : "§6(required)";
            $lines[] = "{$indent}  §f│  §b<" . $arg->getName() . ": " . $arg->getTypeName() . "> {$opt}";
        }

        // Nested subcommands
        foreach ($sub->getSubCommands() as $childSub) {
            self::buildSubTree($childSub, $lines, $viewer, $indent . "  ");
        }
    }

    private static function getSubCommandPermission(SubCommand $sub): ?string {
        $constraints = $sub->getConstraints();
        foreach ($constraints as $constraint) {
            if ($constraint instanceof PermissionConstraint) {
                return $constraint->getDescription();
            }
        }
        return null;
    }
}
