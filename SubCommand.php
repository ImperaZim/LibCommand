<?php

declare(strict_types = 1);

namespace imperazim\command;

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use imperazim\command\result\CommandResult;
use imperazim\command\result\CommandFailure;
use imperazim\command\traits\ArgumentableTrait;
use imperazim\command\traits\ConstraintableTrait;
use imperazim\command\constraint\PermissionConstraint;

/**
* Represents a subcommand within a main command.
* Supports nested subcommands, constraints, and argument parsing.
*/
abstract class SubCommand {
    use ArgumentableTrait;
    use ConstraintableTrait;

    /** @var array Subcommand configuration */
    protected array $config;

    /** @var Command|SubCommand The parent command or subcommand */
    protected Command|SubCommand $parent;

    /**
    * Constructor.
    *
    * @param Command|SubCommand $parent The parent command or subcommand
    */
    public function __construct(Command|SubCommand $parent) {
        $this->parent = $parent;
        $this->config = $this->onBuild();

        $permission = $this->config['permission'] ?? DefaultPermissions::ROOT_USER;
        $this->config['constraints'][] = new PermissionConstraint($permission);

        // Register constraints
        foreach ($this->config['constraints'] ?? [] as $constraint) {
            $this->addConstraint($constraint);
        }

        // Register arguments
        foreach ($this->config['arguments'] ?? [] as $argument) {
            $this->addArgument($argument);
        }

        // Inherit parent constraints
        foreach ($parent->getConstraints() as $constraint) {
            $this->addConstraint($constraint);
        }
    }

    /**
    * Gets the owning plugin.
    *
    * @return Plugin The plugin instance
    */
    public function getPlugin(): Plugin {
        return $this->parent->getPlugin();
    }

    /**
    * Gets the subcommand name.
    *
    * @return string The subcommand name
    */
    public function getName(): string {
        return $this->config['name'] ?? 'subcommand';
    }

    /**
    * Gets the subcommand description.
    *
    * @return string The subcommand description
    */
    public function getDescription(): string {
        return $this->config['description'] ?? '...';
    }

    /**
    * Gets the subcommand aliases.
    *
    * @return array Subcommand aliases
    */
    public function getAliases(): array {
        return $this->config['aliases'] ?? [];
    }

    /**
    * Gets child subcommands.
    *
    * @return array Child subcommands
    */
    public function getSubCommands(): array {
        return $this->config['subcommands'] ?? [];
    }

    /**
    * Executes the subcommand.
    *
    * @param CommandSender $sender The command sender
    * @param string $label The command label used
    * @param array $rawArgs Raw arguments passed to the subcommand
    */
    public function execute(CommandSender $sender, string $label, array $rawArgs): void {
        // Check constraints
        $res = $this->testConstraints($sender);
        if (!$res['success']) {
            $this->onFailure(new CommandFailure(
                $sender,
                CommandFailure::CONSTRAINT_FAILED,
                ['failed_constraints' => $res['failed_constraints']]
            ));
            return;
        }

        // Handle child subcommands
        if (!empty($this->getSubCommands()) && !empty($rawArgs)) {
            $key = strtolower(array_shift($rawArgs));
            foreach ($this->getSubCommands() as $sub) {
                $name = $sub->getName();
                $aliases = $sub->getAliases();
                if ($key === $name || in_array($key, $aliases, true)) {
                    $sub->execute($sender, $label . ' ' . $name, $rawArgs);
                    return;
                }
            }
        }

        // Parse and validate arguments
        $processed = $this->parseRawArgs($rawArgs);
        $valid = $this->validateArguments($sender, $processed);
        if (!$valid['valid']) {
            $this->onFailure(new CommandFailure(
                $sender,
                $valid['type'],
                ['message' => $valid['message']]
            ));
            return;
        }

        $parsed = $this->parseArguments($sender, $rawArgs);

        try {
            // Execute subcommand logic
            $this->onExecute(new CommandResult($sender, $parsed, $label));
        } catch (\Throwable $e) {
            $this->onFailure(new CommandFailure(
                $sender,
                CommandFailure::EXECUTION_ERROR,
                ['error' => $e->getMessage()]
            ));
        }
    }

    /**
    * Builds the subcommand configuration.
    * Must return an array with subcommand settings.
    *
    * @return array Subcommand configuration
    */
    abstract public function onBuild(): array;

    /**
    * Executes the subcommand logic.
    *
    * @param CommandResult $result The command execution result
    */
    abstract public function onExecute(CommandResult $result): void;

    /**
    * Handles subcommand execution failures.
    *
    * @param CommandFailure $failure The failure details
    */
    abstract public function onFailure(CommandFailure $failure): void;
}