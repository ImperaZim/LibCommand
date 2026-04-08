<?php

declare(strict_types = 1);

namespace imperazim\command\dynamic;

use Closure;
use pocketmine\permission\DefaultPermissions;
use imperazim\command\Command;
use imperazim\command\SubCommand;
use imperazim\command\argument\Argument;
use imperazim\command\constraint\Constraint;
use imperazim\command\result\CommandResult;
use imperazim\command\result\CommandFailure;

/**
* Builder class for creating dynamic subcommands programmatically
*
* Allows fluent configuration of subcommand properties, arguments, and callbacks
*/
final class DynamicSubCommand extends SubCommand {
    
    /**
    * @var array Subcommand configuration structure
    *
    * Structure:
    * - 'name': string Subcommand name
    * - 'description': string Subcommand description
    * - 'aliases': string[] Subcommand aliases
    * - 'permission': string Required permission
    * - 'constraints': Constraint[] Validation constraints
    * - 'arguments': Argument[] Subcommand arguments
    * - 'subcommands': SubCommand[] Child subcommands
    */
    private array $config = [
        'name' => '',
        'description' => '',
        'aliases' => [],
        'permission' => DefaultPermissions::ROOT_USER,
        'constraints' => [],
        'arguments' => [],
        'subcommands' => [],
    ];

    /** @var Closure|null Success execution callback */
    private ?Closure $executeCallback = null;

    /** @var Closure|null Failure handling callback */
    private ?Closure $failureCallback = null;

    /**
    * Constructs a dynamic subcommand
    *
    * @param Command|SubCommand $parent Parent command or subcommand
    * @param string $name Subcommand name
    */
    public function __construct(Command|SubCommand $parent, string $name) {
        $this->config['name'] = $name;
        parent::__construct($parent);
    }

    /**
    * Factory method for subcommand creation
    *
    * @param Command|SubCommand $parent Parent command instance
    * @param string $name Subcommand name
    * @return self New subcommand instance
    */
    public static function create(Command|SubCommand $parent, string $name): self {
        return new self($parent, $name);
    }

    /**
    * Sets the subcommand description
    *
    * @param string $description Subcommand description
    * @return $this
    */
    public function setDescription(string $description): self {
        $this->config['description'] = $description;
        return $this;
    }

    /**
    * Sets subcommand aliases
    *
    * @param string[] $aliases Alternative subcommand names
    * @return $this
    */
    public function setAliases(array $aliases): self {
        $this->config['aliases'] = $aliases;
        return $this;
    }

    /**
    * Sets required permission
    *
    * @param string $permission Permission node
    * @return $this
    */
    public function setPermission(string $permission): self {
        $this->config['permission'] = $permission;
        return $this;
    }

    /**
    * Adds a validation constraint
    *
    * @param Constraint $constraint Constraint instance
    * @return $this
    */
    public function addConstraint(Constraint $constraint): self {
        $this->config['constraints'][] = $constraint;
        parent::addConstraint($constraint);
        return $this;
    }

    /**
    * Adds a subcommand argument
    *
    * @param Argument $argument Argument instance
    * @return $this
    */
    public function addArgument(Argument $argument): self {
        $this->config['arguments'][] = $argument;
        parent::addArgument($argument);
        return $this;
    }

    /**
    * Adds a child subcommand
    *
    * @param SubCommand $subCommand Subcommand instance
    * @return $this
    */
    public function addSubCommand(SubCommand $subCommand): self {
        $this->config['subcommands'][] = $subCommand;
        return $this;
    }

    /**
    * Sets the success execution handler
    *
    * @param Closure $callback Callback with signature `function(CommandResult $result): void`
    * @return $this
    */
    public function setOnExecute(Closure $callback): self {
        $this->executeCallback = $callback;
        return $this;
    }

    /**
    * Sets the failure handler
    *
    * @param Closure $callback Callback with signature `function(CommandFailure $failure): void`
    * @return $this
    */
    public function setOnFailure(Closure $callback): self {
        $this->failureCallback = $callback;
        return $this;
    }

    /**
    * Builds the final subcommand configuration
    *
    * @return array Subcommand configuration structure
    */
    public function onBuild(): array {
        return $this->config;
    }

    /**
    * Executes the subcommand success handler
    *
    * @param CommandResult $result Command execution result
    */
    public function onExecute(CommandResult $result): void {
        if ($this->executeCallback !== null) {
            ($this->executeCallback)($result);
        }
    }

    /**
    * Executes the subcommand failure handler
    *
    * @param CommandFailure $failure Command failure details
    */
    public function onFailure(CommandFailure $failure): void {
        if ($this->failureCallback !== null) {
            ($this->failureCallback)($failure);
        }
    }
}