<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for fixed set of string choices
*
* Represents an enumeration of predefined string values
*/
class EnumArgument extends Argument {

    /** @var string[] Available choices */
    protected array $choices = [];

    /**
    * Constructs an enum argument
    *
    * @param string $name Argument name
    * @param bool $optional Whether argument is optional
    * @param string[] $choices Available enum choices
    */
    public function __construct(
        string $name,
        bool $optional = false,
        array $choices = []
    ) {
        $this->choices = $choices;
        parent::__construct($name, $optional);
    }

    /**
    * Gets the human-readable type name
    *
    * @return string "enum"
    */
    public function getTypeName(): string {
        return "enum";
    }

    /**
    * Gets the network type flag
    *
    * @return int Combination of ARG_FLAG_ENUM and ARG_TYPE_STRING
    */
    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_FLAG_ENUM | AvailableCommandsPacket::ARG_TYPE_STRING;
    }

    /**
    * Gets the network parameter data with enum definition
    *
    * @return CommandParameter Network parameter data with enum
    */
    public function getParameterData(): CommandParameter {
        return CommandParameter::enum($name, new CommandHardEnum("", $this->getChoices()), 0, $this->optional);
    }

    /**
    * Checks if input is in the predefined choices
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if input matches an enum choice
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        return in_array($testString, $this->choices, true);
    }

    /**
    * Returns the input string if valid
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return string The input value
    *
    * @throws ArgumentException If input is not a valid choice
    */
    public function parse(string $value, CommandSender $sender): mixed {
        if (!in_array($value, $this->choices, true)) {
            throw new ArgumentException("Invalid choice. Valid options: " . implode(", ", $this->choices));
        }
        return $value;
    }

    /**
    * Gets available enum choices
    *
    * @return string[] Array of valid choices
    */
    public function getChoices(): array {
        return $this->choices;
    }
}