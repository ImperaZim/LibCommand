<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that requires command sender to be an in-game player
*/
class InGameConstraint extends Constraint {
    /**
    * Notifies sender about console usage restriction
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        $sender->sendMessage(TextFormat::RED . 'This command can only be used in-game');
    }

    /**
    * Checks if sender is an in-game player
    *
    * @param CommandSender $sender Command executor
    * @return bool True if player, false if console
    */
    public function isSatisfiedBy(CommandSender $sender): bool {
        return $sender instanceof Player;
    }
}