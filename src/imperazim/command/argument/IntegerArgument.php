<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for integer values
*
* Supports optional min/max value constraints
*/
final class IntegerArgument extends Argument {
    /**
    * Constructs an integer argument
    *
    * @param string $name Argument name
    * @param bool $optional Whether argument is optional
    * @param mixed $default Default value (only for optional arguments)
    * @param string $description Argument description for help
    * @param array $aliases Alternative names for this argument
    * @param callable|null $validator Custom validation function
    * @param int|null $min Minimum allowed value (inclusive)
    * @param int|null $max Maximum allowed value (inclusive)
    */
    public function __construct(
        string $name,
        bool $optional = false,
        mixed $default = null,
        string $description = '',
        array $aliases = [],
        ?callable $validator = null,
        private ?int $min = null,
        private ?int $max = null
    ) {
        parent::__construct($name, $optional, $default, $description, $aliases, $validator);
    }

    /**
    * Gets the human-readable type name
    *
    * @return string "int"
    */
    public function getTypeName(): string {
        return "int";
    }

    /**
    * Gets the network type flag
    *
    * @return int ARG_TYPE_INT constant
    */
    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_INT;
    }

    /**
    * Checks if input is a valid integer within constraints
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if valid integer within range
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        if (!is_numeric($testString) || !ctype_digit($testString)) {
            return false;
        }

        $value = (int)$testString;
        return ($this->min === null || $value >= $this->min) &&
        ($this->max === null || $value <= $this->max);
    }

    /**
    * Parses input into integer value
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return int Parsed integer value
    *
    * @throws ArgumentException If value is out of range
    */
    public function parse(string $value, CommandSender $sender): mixed {
        $intValue = (int)$value;

        if ($this->min !== null && $intValue < $this->min) {
            throw new ArgumentException("Value must be at least {$this->min}");
        }

        if ($this->max !== null && $intValue > $this->max) {
            throw new ArgumentException("Value must be at most {$this->max}");
        }

        return $intValue;
    }
}