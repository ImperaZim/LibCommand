<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use imperazim\command\exception\ArgumentException;

/**
* Abstract base class for command arguments
*
* Provides the foundation for defining command arguments with automatic generation
* of network-compatible parameter data when not explicitly provided
*/
abstract class Argument {
    /**
    * Constructs a new command argument
    *
    * @param string $name Argument name (must be unique per command)
    * @param bool $optional Whether the argument is optional
    * @param CommandParameter|null $parameterData Pre-configured network parameter data
    *
    * @throws ArgumentException If argument name contains spaces
    */
    public function __construct(
        private string $name,
        private bool $optional = false,
        private ?CommandParameter $parameterData = null
    ) {
        if (str_contains($name, ' ')) {
            throw new ArgumentException("Argument name cannot contain spaces: '{$name}'");
        }

        if ($this->parameterData === null) {
            $param = new CommandParameter();
            $param->paramName = $this->name;
            $param->paramType = AvailableCommandsPacket::ARG_FLAG_VALID | $this->getNetworkType();
            $param->isOptional = $this->optional;
            $this->parameterData = $param;
        }
    }

    /**
    * Gets the argument name
    *
    * @return string Argument name
    */
    public function getName(): string {
        return $this->name;
    }

    /**
    * Checks if the argument is optional
    *
    * @return bool True if optional, false if required
    */
    public function isOptional(): bool {
        return $this->optional;
    }

    /**
    * Gets the network-ready parameter data
    *
    * @return CommandParameter Network command parameter data
    */
    public function getParameterData(): CommandParameter {
        return $this->parameterData;
    }

    /**
    * Returns the formatted representation for usage strings
    *
    * @return string Formatted usage representation
    */
    public function getUsageFormatted(): string {
        $name = $this->getName();
        $type = $this->getTypeName();
        return $this->optional ? "[{$name}: $type]" : "<{$name}: $type>";
    }

    /**
    * Gets the human-readable type name for documentation
    *
    * @return string Type name (e.g., "int", "string")
    */
    abstract public function getTypeName(): string;

    /**
    * Gets the network type flag for command registration
    *
    * @return int Network type constant from AvailableCommandsPacket
    */
    abstract public function getNetworkType(): int;

    /**
    * Validates if the argument can be parsed from input
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if parsable, false otherwise
    */
    abstract public function canParse(string $testString, CommandSender $sender): bool;

    /**
    * Parses the input string into the appropriate value type
    *
    * @param string $argument Input string to parse
    * @param CommandSender $sender Command executor
    * @return mixed Parsed value (type depends on implementation)
    *
    * @throws ArgumentException If parsing fails
    */
    abstract public function parse(string $argument, CommandSender $sender): mixed;
}