<?php

declare(strict_types = 1);

namespace imperazim\command\traits;

use imperazim\command\CommandArguments;
use imperazim\command\argument\Argument;
use imperazim\command\argument\StringArgument;
use imperazim\command\argument\PositionArgument;
use imperazim\command\result\CommandFailure;
use pocketmine\command\CommandSender;

trait ArgumentableTrait {
    
    /**
    * @var Argument[] Array of command arguments
    */
    private array $arguments = [];

    /**
    * Adds an argument to the command
    *
    * @param Argument $argument Argument instance to add
    * @return $this
    */
    public function addArgument(Argument $argument): self {
        $this->arguments[] = $argument;
        return $this;
    }

    /**
    * Gets all registered arguments
    *
    * @return Argument[]
    */
    public function getArguments(): array {
        return $this->arguments;
    }

    /**
    * Retrieves an argument by its name
    *
    * @param string $name Name of the argument to find
    * @return Argument|null Argument instance or null if not found
    */
    public function getArgument(string $name): ?Argument {
        foreach ($this->arguments as $argument) {
            if ($argument->getName() === $name) {
                return $argument;
            }
        }
        return null;
    }

    /**
    * Builds the command usage string from configuration
    *
    * @param array $config Command configuration array
    * @return string Formatted usage string
    */
    private function buildUsage(array $config): string {
        $parts = ['/' . ($config['name'] ?? 'command')];

        foreach ($config['arguments'] ?? [] as $argument) {
            $parts[] = $argument->getUsageFormatted();
        }

        return implode(' ', $parts);
    }

    /**
    * Parses raw command arguments according to defined argument types
    *
    * Handles special cases like StringArgument consuming multiple tokens
    *
    * @param string[] $rawArgs Raw arguments from command input
    * @return array Processed arguments with correct grouping
    */
    private function parseRawArgs(array $rawArgs): array {
        $processed = [];
        $argDefs = $this->arguments;
        $rawCount = count($rawArgs);
        $argCount = count($argDefs);
        $i = 0;
        $j = 0;

        while ($i < $argCount && $j < $rawCount) {
            $argDef = $argDefs[$i];
            $remainingRaw = $rawCount - $j;
            $remainingArgs = $argCount - $i;

            // PositionArgument consumes exactly 3 tokens (x y z)
            if ($argDef instanceof PositionArgument && $remainingRaw >= 3) {
                $value = implode(' ', array_slice($rawArgs, $j, 3));
                $processed[] = $value;
                $j += 3;
                $i++;
                continue;
            }

            if ($argDef instanceof StringArgument && $remainingRaw > $remainingArgs) {
                $toConsume = $remainingRaw - ($remainingArgs - 1);
                $value = implode(' ', array_slice($rawArgs, $j, $toConsume));
                $processed[] = $value;
                $j += $toConsume;
                $i++;
                continue;
            }

            $processed[] = $rawArgs[$j];
            $j++;
            $i++;
        }

        return $processed;
    }

    /**
    * Validates command arguments against defined requirements
    *
    * @param CommandSender $sender Command executor
    * @param string[] $rawArgs Raw arguments from command input
    * @return array Validation result with status and potential errors
    */
    private function validateArguments(CommandSender $sender, array $rawArgs): array {
        $providedCount = count($rawArgs);
        $requiredCount = count($this->getRequiredArguments());

        if ($providedCount < $requiredCount) {
            return $this->getMissingArgsErr($requiredCount, $providedCount);
        }

        if ($providedCount > count($this->arguments)) {
            return $this->getExcessArgsErr();
        }

        return $this->validateArgumentTypes($sender, $rawArgs);
    }

    /**
    * Gets all required (non-optional) arguments
    *
    * @return Argument[] Required arguments
    */
    private function getRequiredArguments(): array {
        $arguments = [];
        foreach ($this->arguments as $argument) {
            if (!$argument->isOptional()) {
                $arguments[] = $argument;
            }
        }
        return $arguments;
    }

    /**
    * Generates missing arguments error structure
    *
    * @param int $required Number of required arguments
    * @param int $provided Number of provided arguments
    * @return array Error details including missing argument names
    */
    private function getMissingArgsErr(int $required, int $provided): array {
        $missing = [];
        for ($i = $provided; $i < $required; $i++) {
            $missing[] = $this->arguments[$i]->getName();
        }
        return [
            'valid' => false,
            'type' => CommandFailure::MISSING_ARGUMENT,
            'message' => 'Missing required arguments: ' . implode(', ', $missing),
            'missing_arguments' => $missing
        ];
    }

    /**
    * Generates excess arguments error structure
    *
    * @return array Error details including maximum allowed arguments
    */
    private function getExcessArgsErr(): array {
        return [
            'valid' => false,
            'type' => CommandFailure::INVALID_ARGUMENT,
            'message' => 'Too many arguments (max: ' . count($this->arguments) . ')',
            'max_arguments' => count($this->arguments)
        ];
    }

    /**
    * Validates argument values against their defined types
    *
    * @param CommandSender $sender Command executor
    * @param string[] $rawArgs Processed raw arguments
    * @return array Validation result with potential argument errors
    */
    private function validateArgumentTypes(CommandSender $sender, array $rawArgs): array {
        $errors = [];

        foreach ($rawArgs as $index => $value) {
            if (!isset($this->arguments[$index])) continue;
            $argument = $this->arguments[$index];

            if (!$argument->canParse($value, $sender)) {
                $errors[] = [
                    'argument' => $argument->getName(),
                    'value' => $value,
                    'message' => "Invalid value for argument '{$argument->getName()}'"
                ];
            }
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'type' => CommandFailure::INVALID_ARGUMENT,
                'message' => 'Invalid arguments provided',
                'argument_errors' => $errors
            ];
        }

        return ['valid' => true];
    }

    /**
    * Parses and packages arguments into a CommandArguments object
    *
    * @param CommandSender $sender Command executor
    * @param string[] $rawArgs Raw arguments from command input
    * @return CommandArguments Structured argument container
    */
    private function parseArguments(CommandSender $sender, array $rawArgs): CommandArguments {
        $processedArgs = $this->parseRawArgs($rawArgs);

        $parsed = [];
        foreach ($this->arguments as $index => $argDef) {
            $rawValue = $processedArgs[$index] ?? null;
            $parsed[$argDef->getName()] = $rawValue === null ? null : $argDef->parse($rawValue, $sender);
        }

        return new CommandArguments($sender, $parsed);
    }
}