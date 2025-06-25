<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

/**
* Argument type for string values
*
* Accepts any string input without validation
*/
final class StringArgument extends Argument {

    /**
    * Gets the human-readable type name
    *
    * @return string "string"
    */
    public function getTypeName(): string {
        return "string";
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
    * Always returns true since any string is valid
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool Always true
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        return true;
    }

    /**
    * Returns the input string unchanged
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return string The input value
    */
    public function parse(string $value, CommandSender $sender): mixed {
        return $value;
    }
}