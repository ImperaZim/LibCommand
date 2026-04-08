<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that requires command sender to be an in-game player
*/
final class InGameConstraint extends Constraint {
    /**
    * @param string|null $customMessage Optional custom failure message
    * @param string|null $customDescription Optional custom description
    */
    public function __construct(
        private ?string $customMessage = null,
        private ?string $customDescription = null
    ) {}

    /**
    * Notifies sender about console usage restriction
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        $message = $this->customMessage ?? TextFormat::RED . 'This command can only be used in-game';
        $sender->sendMessage($message);
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

    /**
    * Gets description of this constraint
    *
    * @return string Constraint description
    */
    public function getDescription(): string {
        return $this->customDescription ?? "This command can only be used in-game";
    }
}