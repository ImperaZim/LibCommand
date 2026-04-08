<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for boolean values
*
* Accepts various truthy/falsy representations:
* - true: 'true', 'yes', '1'
* - false: 'false', 'no', '0'
*/
final class BooleanArgument extends Argument {
    /** @var string[] Valid truthy/falsy representations */
    private const VALID_VALUES = [
        'true',
        'false',
        'yes',
        'no',
        '1',
        '0'
    ];

    /**
    * Constructs a boolean argument
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
        '0'
    ];

    /**
    * Gets the human-readable type name
    *
    * @return string "bool"
    */
    public function getTypeName(): string {
        return "bool";
    }

    /**
    * Gets the network type flag
    *
    * @return int ARG_TYPE_VALUE constant
    */
    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_VALUE;
    }

    /**
    * Checks if input is a valid boolean representation
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if valid boolean string
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        return in_array(strtolower($testString), self::VALID_VALUES, true);
    }

    /**
    * Parses input into boolean value
    *
    * @param string $value Input string to parse
    * @param CommandSender $sender Command executor
    * @return bool Parsed boolean value
    *
    * @throws ArgumentException If input is not a valid boolean representation
    */
    public function parse(string $value, CommandSender $sender): mixed {
        $normalized = strtolower($value);
        if (!in_array($normalized, self::VALID_VALUES)) {
            throw new ArgumentException("Invalid boolean value");
        }
        return in_array($normalized, ['true', 'yes', '1']);
    }
}