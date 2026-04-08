<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for floating-point numbers
*
* Supports optional min/max value constraints
*/
final class FloatArgument extends Argument {

    /**
    * Constructs a float argument
    *
    * @param string $name Argument name
    * @param bool $optional Whether argument is optional
    * @param mixed $default Default value (only for optional arguments)
    * @param string $description Argument description for help
    * @param array $aliases Alternative names for this argument
    * @param callable|null $validator Custom validation function
    * @param float|null $min Minimum allowed value (inclusive)
    * @param float|null $max Maximum allowed value (inclusive)
    */
    public function __construct(
        string $name,
        bool $optional = false,
        mixed $default = null,
        string $description = '',
        array $aliases = [],
        ?callable $validator = null,
        private ?float $min = null,
        private ?float $max = null
    ) {
        parent::__construct($name, $optional, $default, $description, $aliases, $validator);
    }

    /**
    * Gets the human-readable type name
    *
    * @return string "float"
    */
    public function getTypeName(): string {
        return "float";
    }

    /**
    * Gets the network type flag
    *
    * @return int ARG_TYPE_FLOAT constant
    */
    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_FLOAT;
    }

    /**
    * Checks if input is a valid float within constraints
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if valid float within range
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        if (!is_numeric($testString)) {
            return false;
        }

        $value = (float)$testString;
        return ($this->min === null || $value >= $this->min) &&
        ($this->max === null || $value <= $this->max);
    }

    /**
    * Parses input into float value
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return float Parsed float value
    *
    * @throws ArgumentException If value is out of range
    */
    public function parse(string $value, CommandSender $sender): mixed {
        $floatValue = (float)$value;

        if ($this->min !== null && $floatValue < $this->min) {
            throw new ArgumentException("Value must be at least {$this->min}");
        }

        if ($this->max !== null && $floatValue > $this->max) {
            throw new ArgumentException("Value must be at most {$this->max}");
        }

        return $floatValue;
    }
}