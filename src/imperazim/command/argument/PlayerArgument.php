<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for online player names
*
* Matches players by name prefix (case-insensitive)
*/
final class PlayerArgument extends Argument {
    /**
    * Constructs a player argument
    *
    * @param string $name Argument name
    * @param bool $optional Whether argument is optional
    * @param mixed $default Default value (only for optional arguments)
    * @param string $description Argument description for help
    * @param array $aliases Alternative names for this argument
    * @param callable|null $validator Custom validation function
    */
    public function __construct(
        string $name,
        bool $optional = false,
        mixed $default = null,
        string $description = '',
        array $aliases = [],
        ?callable $validator = null
    ) {
        parent::__construct($name, $optional, $default, $description, $aliases, $validator);
    }

    /**
    * Gets the human-readable type name
    *
    * @return string "player"
    */
    public function getTypeName(): string {
        return "player";
    }

    /**
    * Gets the network type flag
    *
    * @return int ARG_TYPE_TARGET constant
    */
    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_TARGET;
    }

    /**
    * Checks if input matches an online player's name (prefix)
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if matches an online player
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        return Server::getInstance()->getPlayerByPrefix($testString) !== null;
    }

    /**
    * Parses input into Player object
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return Player Parsed player instance
    *
    * @throws ArgumentException If player not found
    */
    public function parse(string $value, CommandSender $sender): mixed {
        $player = Server::getInstance()->getPlayerByPrefix($value);
        if ($player === null) {
            throw new ArgumentException("Player '{$value}' not found");
        }

        return $player;
    }
}