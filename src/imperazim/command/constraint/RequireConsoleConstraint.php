<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that restricts command usage to console only
*/
final class RequireConsoleConstraint extends Constraint {
    /**
    * Notifies player about console-only restriction
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        $sender->sendMessage(TextFormat::RED . 'You need to be in the console to use this command.');
    }

    /**
    * Checks if sender is console (not player)
    *
    * @param CommandSender $sender Command executor
    * @return bool True if console, false if player
    */
    public function isSatisfiedBy(CommandSender $sender): bool {
        return (!$sender instanceof Player);
    }

    /**
    * Gets a human-readable description of the constraint
    *
    * @return string Constraint description
    */
    public function getDescription(): string {
        return 'Command can only be used from console';
    }
}