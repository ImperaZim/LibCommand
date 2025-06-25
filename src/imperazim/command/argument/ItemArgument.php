<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\LegacyStringToItemParser;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for item identifiers
*
* Supports both modern and legacy item identifiers
*/
final class ItemArgument extends Argument {

    /**
    * Gets the human-readable type name
    *
    * @return string "item"
    */
    public function getTypeName(): string {
        return "item";
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
    * Checks if input matches a known item identifier
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if valid item identifier
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        $item = StringToItemParser::getInstance()->parse($testString) ?? LegacyStringToItemParser::getInstance()->parse($testString);
        return $item !== null;
    }

    /**
    * Parses input into Item object
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return Item Parsed item instance
    *
    * @throws ArgumentException If item identifier is unknown
    */
    public function parse(string $value, CommandSender $sender): mixed {
        $item = StringToItemParser::getInstance()->parse($value) ?? LegacyStringToItemParser::getInstance()->parse($value);

        if ($item === null) {
            throw new ArgumentException("Unknown item '{$value}'");
        }

        return $item;
    }
}