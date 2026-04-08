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
    * @param mixed $default Default value when argument is not provided (only for optional arguments)
    * @param string $description Human-readable description of the argument
    * @param array $aliases Alternative names for this argument
    * @param callable|null $validator Custom validation function (receives value, returns bool)
    * @param CommandParameter|null $parameterData Pre-configured network parameter data
    *
    * @throws ArgumentException If argument name contains spaces or default provided for required argument
    */
    public function __construct(
        private string $name,
        private bool $optional = false,
        private mixed $default = null,
        private string $description = '',
        private array $aliases = [],
        private $validator = null,
        private ?CommandParameter $parameterData = null
    ) {
        if (str_contains($name, ' ')) {
            throw new ArgumentException("Argument name cannot contain spaces: '{$name}'");
        }

        if (!$optional && $default !== null) {
            throw new ArgumentException("Cannot set default value for required argument: '{$name}'");
        }

        if ($this->parameterData === null) {
            $this->parameterData = CommandParameter::standard($name, $this->getNetworkType(), 0, $this->isOptional());
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
    * Gets the default value for optional arguments
    *
    * @return mixed Default value or null
    */
    public function getDefault(): mixed {
        return $this->default;
    }

    /**
    * Gets the argument description
    *
    * @return string Description text
    */
    public function getDescription(): string {
        return $this->description;
    }

    /**
    * Gets the argument aliases
    *
    * @return array Array of alternative names
    */
    public function getAliases(): array {
        return $this->aliases;
    }

    /**
    * Checks if a name matches this argument (including aliases)
    *
    * @param string $name Name to check
    * @return bool True if matches
    */
    public function matchesName(string $name): bool {
        return $name === $this->name || in_array($name, $this->aliases, true);
    }

    /**
    * Validates value using custom validator if provided
    *
    * @param mixed $value Value to validate
    * @return bool True if valid
    */
    public function validate(mixed $value): bool {
        if ($this->validator !== null) {
            return ($this->validator)($value);
        }
        return true;
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