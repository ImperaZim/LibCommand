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
    * @param string|null $customMessage Optional custom failure message
    * @param string|null $customDescription Optional custom description
    */
    public function __construct(
        private string $permission,
        private ?string $customMessage = null,
        private ?string $customDescription = null
    ) {}

    /**
    * Notifies sender about missing permission
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        $message = $this->customMessage ?? TextFormat::RED . "You don't have permission to use this command!";
        $sender->sendMessage($message);
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

    /**
    * Gets description of this constraint
    *
    * @return string Constraint description
    */
    public function getDescription(): string {
        return $this->customDescription ?? "Required permission: {$this->permission}";
    }
}