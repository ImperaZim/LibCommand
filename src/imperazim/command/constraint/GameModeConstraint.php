<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that requires sender to be in a specific game mode
*/
class GameModeConstraint extends Constraint {
    /**
    * @param GameMode $gameMode Required game mode
    * @param string|null $customMessage Optional custom failure message
    * @param string|null $customDescription Optional custom description
    */
    public function __construct(
        private GameMode $gameMode,
        private ?string $customMessage = null,
        private ?string $customDescription = null
    ) {}

    /**
    * Notifies sender about incorrect game mode
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        if ($this->customMessage !== null) {
            $sender->sendMessage($this->customMessage);
        } else {
            $modeName = $this->getModeName();
            $sender->sendMessage(TextFormat::RED . "You must be in {$modeName} mode to use this command");
        }
    }

    /**
    * Checks if sender is in required game mode
    *
    * @param CommandSender $sender Command executor
    * @return bool True if game mode matches, false otherwise
    */
    public function isSatisfiedBy(CommandSender $sender): bool {
        return ($sender instanceof Player) &&
        ($sender->getGamemode() === $this->gameMode);
    }

    /**
    * Gets human-readable game mode name
    *
    * @return string Mode name (Survival, Creative, etc.)
    */
    private function getModeName(): string {
        return match($this->gameMode->id()) {
            GameMode::SURVIVAL()->id() => "Survival",
            GameMode::CREATIVE()->id() => "Creative",
            GameMode::ADVENTURE()->id() => "Adventure",
            GameMode::SPECTATOR()->id() => "Spectator",
            default => "Unknown"
            };
        }

    /**
    * Gets description of this constraint
    *
    * @return string Constraint description
    */
    public function getDescription(): string {
        if ($this->customDescription !== null) {
            return $this->customDescription;
        }
        $modeName = $this->getModeName();
        return "You must be in {$modeName} mode";
    }
}