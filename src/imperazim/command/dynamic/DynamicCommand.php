<?php

declare(strict_types = 1);

namespace imperazim\command\dynamic;

use Closure;
use pocketmine\plugin\Plugin;
use pocketmine\permission\DefaultPermissions;
use imperazim\command\Command;
use imperazim\command\SubCommand;
use imperazim\command\argument\Argument;
use imperazim\command\constraint\Constraint;
use imperazim\command\result\CommandResult;
use imperazim\command\result\CommandFailure;

/**
* Builder class for creating dynamic commands programmatically
*
* Allows fluent configuration of command properties, arguments, subcommands, and callbacks
*/
class DynamicCommand extends Command {
    
    /**
    * @var array Command configuration structure
    *
    * Structure:
    * - 'name': string Command name
    * - 'description': string Command description
    * - 'aliases': string[] Command aliases
    * - 'permission': string Required permission
    * - 'constraints': Constraint[] Validation constraints
    * - 'arguments': Argument[] Command arguments
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
    * Constructs a dynamic command
    *
    * @param Plugin $plugin Owning plugin instance
    * @param string $name Command name
    */
    public function __construct(Plugin $plugin, string $name) {
        $this->config['name'] = $name;
        parent::__construct($plugin);
    }

    /**
    * Factory method for command creation
    *
    * @param Plugin $plugin Owning plugin instance
    * @param string $name Command name
    * @return self New command instance
    */
    public static function create(Plugin $plugin, string $name): self {
        return new self($plugin, $name);
    }

    /**
    * Sets the command description (fluent)
    *
    * @param string $description Command description
    * @return $this
    */
    public function withDescription(string $description): self {
        $this->config['description'] = $description;
        parent::setDescription($description);
        return $this;
    }

    /**
    * Sets command aliases (fluent)
    *
    * @param string[] $aliases Alternative command names
    * @return $this
    */
    public function withAliases(array $aliases): self {
        $this->config['aliases'] = $aliases;
        parent::setAliases($aliases);
        return $this;
    }

    /**
    * Sets required permission
    *
    * @param string $permission Permission node
    * @return $this
    */
    public function setPermission(?string $permission): void {
        $this->config['permission'] = $permission ?? DefaultPermissions::ROOT_USER;
        parent::setPermission($permission);
    }

    /**
    * Sets required permission (fluent)
    *
    * @param string $permission Permission node
    * @return $this
    */
    public function withPermission(string $permission): self {
        $this->config['permission'] = $permission;
        parent::setPermission($permission);
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
        return $this;
    }

    /**
    * Adds a command argument
    *
    * @param Argument $argument Argument instance
    * @return $this
    */
    public function addArgument(Argument $argument): self {
        $this->config['arguments'][] = $argument;
        return $this;
    }

    /**
    * Adds a subcommand
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
    * Builds the final command configuration
    *
    * @return array Command configuration structure
    */
    public function onBuild(): array {
        return $this->config;
    }

    /**
    * Executes the command success handler
    *
    * @param CommandResult $result Command execution result
    */
    public function onExecute(CommandResult $result): void {
        if ($this->executeCallback !== null) {
            ($this->executeCallback)($result);
        }
    }

    /**
    * Executes the command failure handler
    *
    * @param CommandFailure $failure Command failure details
    */
    public function onFailure(CommandFailure $failure): void {
        if ($this->failureCallback !== null) {
            ($this->failureCallback)($failure);
        }
    }

    /**
    * Registers the command with PocketMine's command map
    */
    public function registerCommand(): void {
        $cmdMap = $this->getPlugin()->getServer()->getCommandMap();
        $namespace = $this->getPlugin()->getDescription()->getName();
        $cmdMap->register($namespace, $this);
    }
}