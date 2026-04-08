<?php

declare(strict_types = 1);

namespace imperazim\command\result;

use pocketmine\command\CommandSender;
use imperazim\command\constraint\Constraint;

/**
* Represents a command execution failure with detailed error information
*
* Contains failure reason, contextual data, and formatted messages
*/
final class CommandFailure {

    /** Failure type: Invalid argument provided */
    public const INVALID_ARGUMENT = 0;

    /** Failure type: Required argument missing */
    public const MISSING_ARGUMENT = 1;

    /** Failure type: Constraint check failed */
    public const CONSTRAINT_FAILED = 2;

    /** Failure type: Runtime execution error */
    public const EXECUTION_ERROR = 3;

    /** Failure type: Command is on cooldown */
    public const COOLDOWN = 4;

    /**
    * @var int[] Valid failure type identifiers
    */
    public const ERROR_TYPES = [
        self::INVALID_ARGUMENT,
        self::MISSING_ARGUMENT,
        self::CONSTRAINT_FAILED,
        self::EXECUTION_ERROR,
        self::COOLDOWN
    ];

    /**
    * Constructs a command failure result
    *
    * @param CommandSender $sender Command executor
    * @param int $reasonId Failure type identifier (use class constants)
    * @param array $data Contextual error data (structure depends on failure type)
    *
    * @throws \InvalidArgumentException When invalid reason ID is provided
    */
    public function __construct(
        private readonly CommandSender $sender,
        private readonly int $reasonId,
        private readonly array $data = []
    ) {
        if (!in_array($reasonId, self::ERROR_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid failure reason ID: $reasonId");
        }
    }

    /**
    * Gets the command sender that triggered the failure
    *
    * @return CommandSender Command executor
    */
    public function getSender(): CommandSender {
        return $this->sender;
    }

    /**
    * Gets the failure reason identifier
    *
    * @return int One of the class failure constants
    */
    public function getReason(): int {
        return $this->reasonId;
    }

    /**
    * Gets the failure reason as a human-readable name
    *
    * @return string Failure type name
    */
    public function getReasonName(): string {
        return match($this->reasonId) {
            self::INVALID_ARGUMENT => 'INVALID_ARGUMENT',
            self::MISSING_ARGUMENT => 'MISSING_ARGUMENT',
            self::CONSTRAINT_FAILED => 'CONSTRAINT_FAILED',
            self::EXECUTION_ERROR => 'EXECUTION_ERROR',
            self::COOLDOWN => 'COOLDOWN',
            default => 'UNKNOWN_ERROR'
        };
    }

    /**
    * Gets contextual failure data
    *
    * Structure varies by failure type:
    * - INVALID_ARGUMENT: May contain 'argument_errors'
    * - MISSING_ARGUMENT: May contain 'missing_arguments'
    * - CONSTRAINT_FAILED: May contain 'failed_constraints'
    * - EXECUTION_ERROR: May contain exception or error details
    *
    * @return array Failure-specific contextual data
    */
    public function getData(): array {
        return $this->data;
    }

    /**
    * Gets failed constraints (for CONSTRAINT_FAILED errors)
    *
    * @return Constraint[] Array of failed constraint objects
    */
    public function getFailedConstraints(): array {
        return $this->data['failed_constraints'] ?? [];
    }

    /**
    * Gets the failure message
    *
    * Returns custom message if provided in data, otherwise default message
    *
    * @return string Human-readable error message
    */
    public function getMessage(): string {
        return $this->data['message'] ?? $this->getDefaultMessage();
    }

    /**
    * Generates a default message based on failure type
    *
    * @return string Default error message for the failure type
    */
    private function getDefaultMessage(): string {
        return match($this->reasonId) {
            self::INVALID_ARGUMENT => 'Invalid argument provided',
            self::MISSING_ARGUMENT => 'Missing required arguments',
            self::CONSTRAINT_FAILED => 'Command constraints not satisfied',
            self::EXECUTION_ERROR => 'An error occurred during command execution',
            self::COOLDOWN => 'Command is on cooldown',
            default => 'Unknown command error'
        };
    }
}