<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use imperazim\command\exception\ArgumentException;

/**
* Argument type for numeric ranges (e.g., "1..10", "5", "-3..3")
*
* Parses input like "min..max" into [min, max] array or single number into [n, n]
*/
final class RangeArgument extends Argument {

    /**
    * @param string $name Argument name
    * @param bool $optional Whether argument is optional
    * @param mixed $default Default value (only for optional arguments)
    * @param string $description Argument description
    * @param array $aliases Alternative names
    * @param callable|null $validator Custom validation function
    * @param int|null $absoluteMin Minimum allowed value for range bounds
    * @param int|null $absoluteMax Maximum allowed value for range bounds
    */
    public function __construct(
        string $name,
        bool $optional = false,
        mixed $default = null,
        string $description = '',
        array $aliases = [],
        ?callable $validator = null,
        private ?int $absoluteMin = null,
        private ?int $absoluteMax = null
    ) {
        parent::__construct($name, $optional, $default, $description, $aliases, $validator);
    }

    public function getTypeName(): string {
        return "range";
    }

    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_STRING;
    }

    public function canParse(string $testString, CommandSender $sender): bool {
        if (is_numeric($testString)) {
            $value = (int) $testString;
            return $this->areBoundsValid($value, $value);
        }

        if (preg_match('/^(-?\d+)\.\.(-?\d+)$/', $testString, $matches)) {
            $min = (int) $matches[1];
            $max = (int) $matches[2];
            return $min <= $max && $this->areBoundsValid($min, $max);
        }

        return false;
    }

    private function areBoundsValid(int $min, int $max): bool {
        if ($this->absoluteMin !== null && $min < $this->absoluteMin) {
            return false;
        }
        if ($this->absoluteMax !== null && $max > $this->absoluteMax) {
            return false;
        }
        return true;
    }

    /**
    * @return array{int, int} [min, max] range values
    */
    public function parse(string $argument, CommandSender $sender): mixed {
        if (is_numeric($argument)) {
            $value = (int) $argument;
            $this->validateBounds($value, $value);
            return [$value, $value];
        }

        if (preg_match('/^(-?\d+)\.\.(-?\d+)$/', $argument, $matches)) {
            $min = (int) $matches[1];
            $max = (int) $matches[2];

            if ($min > $max) {
                throw new ArgumentException("Range minimum ($min) cannot exceed maximum ($max)");
            }

            $this->validateBounds($min, $max);
            return [$min, $max];
        }

        throw new ArgumentException("Invalid range format: '$argument'. Use 'min..max' or a single number");
    }

    private function validateBounds(int $min, int $max): void {
        if ($this->absoluteMin !== null && $min < $this->absoluteMin) {
            throw new ArgumentException("Range minimum must be at least {$this->absoluteMin}");
        }
        if ($this->absoluteMax !== null && $max > $this->absoluteMax) {
            throw new ArgumentException("Range maximum must be at most {$this->absoluteMax}");
        }
    }
}
