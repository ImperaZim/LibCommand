<?php

declare(strict_types = 1);

namespace imperazim\command\traits;

use imperazim\command\SubCommand;

trait SubCommandTrait {

    /**
    * @var SubCommand[] Array of registered subcommands
    */
    private array $subcommands = [];

    /**
    * Registers a new subcommand
    *
    * @param SubCommand $subcommand The subcommand instance to register
    * @return $this
    */
    public function addSubCommand(SubCommand $subcommand): self {
        $this->subcommands[] = $subcommand;
        return $this;
    }

    /**
    * Gets all registered subcommands
    *
    * @return SubCommand[] Array of subcommand instances
    */
    public function getSubCommands(): array {
        return $this->subcommands;
    }

    /**
    * Retrieves a subcommand by its name
    *
    * @param string $name Name of the subcommand to search for
    * @return SubCommand|null Subcommand instance if found, null otherwise
    */
    public function getSubCommand(string $name): ?SubCommand {
        foreach ($this->subcommands as $subcommand) {
            if ($subcommand->getName() === $name) {
                return $subcommand;
            }
        }
        return null;
    }

}