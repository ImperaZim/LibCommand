<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for position coordinates (e.g., "100 64 200", "~ ~1 ~", "~5 ~ ~-3")
*
* Supports absolute coordinates and relative (~) notation.
* Consumes 3 tokens from the argument list.
*/
final class PositionArgument extends Argument {

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

    public function getTypeName(): string {
        return "x y z";
    }

    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_POSITION;
    }

    public function canParse(string $testString, CommandSender $sender): bool {
        $parts = explode(' ', $testString);
        if (count($parts) !== 3) {
            return false;
        }

        foreach ($parts as $part) {
            if (!$this->isValidCoordinate($part)) {
                return false;
            }
        }

        return true;
    }

    /**
    * @return Vector3 Parsed position
    */
    public function parse(string $argument, CommandSender $sender): mixed {
        $parts = explode(' ', $argument);
        if (count($parts) !== 3) {
            throw new ArgumentException("Position requires exactly 3 coordinates (x y z), got " . count($parts));
        }

        $origin = ($sender instanceof Player) ? $sender->getPosition() : new Vector3(0, 0, 0);

        $x = $this->parseCoordinate($parts[0], $origin->getX());
        $y = $this->parseCoordinate($parts[1], $origin->getY());
        $z = $this->parseCoordinate($parts[2], $origin->getZ());

        return new Vector3($x, $y, $z);
    }

    private function isValidCoordinate(string $value): bool {
        if ($value === '~') {
            return true;
        }
        if (str_starts_with($value, '~')) {
            return is_numeric(substr($value, 1));
        }
        return is_numeric($value);
    }

    private function parseCoordinate(string $value, float $origin): float {
        if ($value === '~') {
            return $origin;
        }
        if (str_starts_with($value, '~')) {
            $offset = (float) substr($value, 1);
            return $origin + $offset;
        }
        return (float) $value;
    }

    public function getUsageFormatted(): string {
        $name = $this->getName();
        return $this->isOptional() ? "[{$name}: x y z]" : "<{$name}: x y z>";
    }
}
