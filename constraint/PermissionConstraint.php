<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that enforces permission requirements
*/
class PermissionConstraint extends Constraint {
    /**
    * @param string $permission Required permission node
    */
    public function __construct(private string $permission) {}

    /**
    * Notifies sender about missing permission
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
    }

    /**
    * Checks if sender has required permission
    *
    * @param CommandSender $sender Command executor
    * @return bool True if permission granted, false otherwise
    */
    public function isSatisfiedBy(CommandSender $sender): bool {
        return $sender->hasPermission($this->permission);
    }
}