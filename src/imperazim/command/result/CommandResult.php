<?php

declare(strict_types = 1);

namespace imperazim\command\result;

use pocketmine\command\CommandSender;
use imperazim\command\CommandArguments;

/**
* Represents a successful command execution result
*
* Contains execution context including sender, arguments, and command label
*/
final class CommandResult {
    
    /**
    * Constructs a command result
    *
    * @param CommandSender $sender Command executor
    * @param CommandArguments $arguments Parsed command arguments
    * @param string $label Actual command label used
    */
    /** @param list<string> $rawArguments */
    public function __construct(
        private readonly CommandSender $sender,
        private readonly CommandArguments $arguments,
        private readonly string $label,
        private readonly array $rawArguments = []
    ) {}

    /**
    * Gets the command sender that executed the command
    *
    * @return CommandSender Command executor
    */
    public function getSender(): CommandSender {
        return $this->sender;
    }

    /**
    * Gets the parsed command arguments
    *
    * @return CommandArguments Structured argument container
    */
    public function getArgumentsList(): CommandArguments {
        return $this->arguments;
    }

    /**
    * Gets the actual command label used
    *
    * @return string Command label (e.g., "help" in "/help")
    */
    public function getLabel(): string {
        return $this->label;
    }

    /** @return list<string> Raw arguments exactly as received from PMMP */
    public function getRawArguments(): array {
        return $this->rawArguments;
    }
}