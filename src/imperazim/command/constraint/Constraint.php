<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\command\CommandSender;

/**
* Abstract base class for command execution constraints
*
* Defines the contract for command constraints that can validate execution context
*/
abstract class Constraint {
    /**
    * Called when constraint is successfully satisfied
    *
    * @param CommandSender $sender Command executor
    */
    public function onSuccess(CommandSender $sender): void {}

    /**
    * Called when constraint fails validation
    *
    * @param CommandSender $sender Command executor
    */
    abstract public function onFailure(CommandSender $sender): void;

    /**
    * Determines if the constraint is satisfied by the sender
    *
    * @param CommandSender $sender Command executor
    * @return bool True if satisfied, false otherwise
    */
    abstract public function isSatisfiedBy(CommandSender $sender): bool;

    /**
    * Gets a human-readable description of the constraint
    *
    * @return string Constraint description
    */
    abstract public function getDescription(): string;
}