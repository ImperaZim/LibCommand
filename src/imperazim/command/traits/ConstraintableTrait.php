<?php

declare(strict_types = 1);

namespace imperazim\command\traits;

use pocketmine\command\CommandSender;
use imperazim\command\constraint\Constraint;

/**
* Provides constraint management functionality for commands and subcommands.
* Allows adding constraints, retrieving them, and testing against a command sender.
*/
trait ConstraintableTrait {
    
    /** @var Constraint[] Constraints applied to the command */
    private array $constraints = [];

    /**
    * Adds a constraint to the command.
    *
    * @param Constraint $constraint The constraint to add
    * @return self Returns the instance for method chaining
    */
    public function addConstraint(Constraint $constraint): self {
        $this->constraints[] = $constraint;
        return $this;
    }

    /**
    * Gets all constraints applied to the command.
    *
    * @return Constraint[] Array of constraints
    */
    public function getConstraints(): array {
        return $this->constraints;
    }

    /**
    * Gets constraints of a specific type.
    *
    * @param string $type The class name of the constraint type
    * @return Constraint[] Constraints of the specified type
    */
    public function getConstraintsByType(string $type): array {
        return array_filter($this->constraints, fn($c) => $c instanceof $type);
    }

    /**
    * Tests all constraints against a command sender.
    *
    * @param CommandSender $sender The command sender to test
    * @return array Test results with keys:
    *               - 'success': Whether all constraints were satisfied
    *               - 'failed_constraints': Array of failed constraints
    */
    private function testConstraints(CommandSender $sender): array {
        $failed = [];
        $passed = [];

        foreach ($this->constraints as $constraint) {
            if (!$constraint->isSatisfiedBy($sender)) {
                $failed[] = $constraint;
            } else {
                $passed[] = $constraint;
            }
        }

        if (empty($failed)) {
            foreach ($passed as $constraint) {
                $constraint->onSuccess($sender);
            }
        } else {
            foreach ($failed as $constraint) {
                $constraint->onFailure($sender);
            }
        }

        return [
            'success' => empty($failed),
            'failed_constraints' => $failed
        ];
    }
}