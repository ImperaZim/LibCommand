<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use imperazim\command\enum\CommandEnumManager;
use imperazim\command\exception\ArgumentException;

/**
* Argument type using soft enums (dynamic, updateable at runtime).
*
* Unlike EnumArgument (hard enum), soft enum values can be modified after
* command registration via CommandEnumManager::updateEnum().
*
* Usage:
*   // Register soft enum first
*   CommandEnumManager::addEnum(new CommandSoftEnum("warps", ["spawn", "pvp", "shop"]));
*   // Use in argument
*   new SoftEnumArgument("warp", false, "warps");
*   // Update values later
*   CommandEnumManager::updateEnum("warps", ["spawn", "pvp", "shop", "arena"]);
*/
final class SoftEnumArgument extends Argument {

    /**
    * Constructs a soft enum argument.
    *
    * @param string $name Argument name
    * @param bool $optional Whether argument is optional
    * @param string $enumName Name of the soft enum registered in CommandEnumManager
    * @param string $description Argument description
    */
    public function __construct(
        string $name,
        bool $optional = false,
        private string $enumName = '',
        string $description = ''
    ) {
        parent::__construct(
            $name,
            $optional,
            default: null,
            description: $description,
            parameterData: CommandParameter::softEnum(
                $name,
                $this->getEnum() ?? new CommandSoftEnum($enumName, []),
                0,
                $optional
            )
        );
    }

    public function getTypeName(): string {
        return "softEnum";
    }

    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_FLAG_ENUM | AvailableCommandsPacket::ARG_TYPE_STRING;
    }

    /**
    * Gets the associated CommandSoftEnum from the manager.
    *
    * @return CommandSoftEnum|null
    */
    public function getEnum(): ?CommandSoftEnum {
        return CommandEnumManager::getEnumByName($this->enumName);
    }

    /**
    * Gets the current values from the soft enum.
    *
    * @return string[]
    */
    public function getValues(): array {
        $enum = $this->getEnum();
        return $enum !== null ? $enum->getValues() : [];
    }

    public function canParse(string $testString, CommandSender $sender): bool {
        $values = $this->getValues();
        if (empty($values)) return true; // Accept anything if no values defined
        return in_array(strtolower($testString), array_map('strtolower', $values), true);
    }

    public function parse(string $argument, CommandSender $sender): mixed {
        $values = $this->getValues();
        if (!empty($values)) {
            foreach ($values as $value) {
                if (strcasecmp($argument, $value) === 0) {
                    return $value;
                }
            }
            throw new ArgumentException("Invalid value. Valid options: " . implode(", ", $values));
        }
        return $argument;
    }

    /**
    * Returns soft enum values matching the partial input.
    *
    * @param string $partial Partial input typed so far
    * @param CommandSender $sender Command executor
    * @return string[] Matching values
    */
    public function getSuggestions(string $partial, CommandSender $sender): array {
        $partial = strtolower($partial);
        return array_values(array_filter(
            $this->getValues(),
            fn(string $v) => $partial === '' || str_starts_with(strtolower($v), $partial)
        ));
    }
}
