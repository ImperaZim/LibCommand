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
final class EnumArgument extends Argument {

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
        $choices = $this->getChoices();
        $enumName = 'enum:' . $this->getName() . ':' .
            substr(hash('sha256', serialize($choices)), 0, 16);
        return CommandParameter::enum(
            $this->getName(),
            new CommandHardEnum($enumName, $choices),
            0,
            $this->isOptional()
        );
    }

    /**
    * Checks if input is in the predefined choices
    *
    * @param string $testString Input string to test
    * @param CommandSender $sender Command executor
    * @return bool True if input matches an enum choice
    */
    public function canParse(string $testString, CommandSender $sender): bool {
        return in_array(strtolower($testString), array_map('strtolower', $this->choices), true);
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
        foreach ($this->choices as $choice) {
            if (strcasecmp($value, $choice) === 0) {
                return $choice;
            }
        }
        throw new ArgumentException("Invalid choice. Valid options: " . implode(", ", $this->choices));
    }

    /**
    * Gets available enum choices
    *
    * @return string[] Array of valid choices
    */
    public function getChoices(): array {
        return $this->choices;
    }

    /**
    * Returns enum choices matching the partial input.
    *
    * @param string $partial Partial input typed so far
    * @param CommandSender $sender Command executor
    * @return string[] Matching choices
    */
    public function getSuggestions(string $partial, CommandSender $sender): array {
        $partial = strtolower($partial);
        return array_values(array_filter(
            $this->choices,
            fn(string $c) => $partial === '' || str_starts_with(strtolower($c), $partial)
        ));
    }
}
