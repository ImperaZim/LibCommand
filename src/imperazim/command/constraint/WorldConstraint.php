<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that restricts command usage to specific worlds
*/
class WorldConstraint extends Constraint {
    /**
    * @var string[] Allowed world names
    */
    private array $allowedWorlds;

    /**
    * @param string|string[] $worldNames Single world name or array of allowed worlds
    * @param string|null $customMessage Optional custom failure message
    * @param string|null $customDescription Optional custom description
    */
    public function __construct(
        string|array $worldNames,
        private ?string $customMessage = null,
        private ?string $customDescription = null
    ) {
        $this->allowedWorlds = (array) $worldNames;
    }

    /**
    * Notifies sender about invalid world
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        if ($this->customMessage !== null) {
            $sender->sendMessage($this->customMessage);
        } else {
            $worlds = implode(', ', $this->allowedWorlds);
            $sender->sendMessage(TextFormat::RED . "You must be in one of these worlds: $worlds");
        }
    }

    /**
    * Checks if sender is in allowed world
    *
    * @param CommandSender $sender Command executor
    * @return bool True if in allowed world, false otherwise
    */
    public function isSatisfiedBy(CommandSender $sender): bool {
        return ($sender instanceof Player) &&
        in_array($sender->getWorld()->getFolderName(), $this->allowedWorlds, true);
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
        $worlds = implode(', ', $this->allowedWorlds);
        return "You must be in one of these worlds: $worlds";
    }
}