<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\Server;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\world\WorldManager;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for world names
*
* Matches loaded worlds by exact name
*/
final class WorldArgument extends Argument {

    /**
    * Gets the human-readable type name
    *
    * @return string "world"
    */
    public function getTypeName(): string {
        return "world";
    }

    /**
    * Gets the network type flag
    *
    * @return int ARG_TYPE_STRING constant
    */
    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_STRING;
    }

    /**
    * Checks if input matches a loaded world name
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if world exists
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        return $this->getWorldManager()->getWorldByName($testString) !== null;
    }

    /**
    * Parses input into World object
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return World Parsed world instance
    *
    * @throws ArgumentException If world not found
    */
    public function parse(string $value, CommandSender $sender): mixed {
        $world = $this->getWorldManager()->getWorldByName($value);

        if ($world === null) {
            $worlds = implode(', ', array_map(fn($w) => $w->getFolderName(), $this->getWorldManager()->getWorlds()));
            throw new ArgumentException("World '{$value}' not found. Available worlds: {$worlds}");
        }

        return $world;
    }

    /**
    * Gets WorldManager instance
    *
    * @return WorldManager Server world manager
    */
    private function getWorldManager(): WorldManager {
        return Server::getInstance()->getWorldManager();
    }
}