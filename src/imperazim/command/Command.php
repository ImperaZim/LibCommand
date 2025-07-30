<?php

declare(strict_types=1);

namespace imperazim\command;

use pocketmine\plugin\Plugin;
use pocketmine\command\Command as PMMPCommand;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use imperazim\command\traits\SubCommandTrait;
use imperazim\command\traits\ArgumentableTrait;
use imperazim\command\traits\ConstraintableTrait;
use imperazim\command\argument\Argument;
use imperazim\command\constraint\Constraint;
use imperazim\command\constraint\PermissionConstraint;
use imperazim\command\result\CommandResult;
use imperazim\command\result\CommandFailure;

/**
 * Abstract base class for custom commands.
 * Extends PocketMine's base command with advanced features like constraints,
 * subcommands, and argument parsing.
 */
abstract class Command extends PMMPCommand {
  use SubCommandTrait;
  use ArgumentableTrait;
  use ConstraintableTrait;

  /** @var Plugin The plugin that owns this command */
  private readonly Plugin $plugin;

  /**
   * Constructor.
   *
   * @param Plugin $plugin The plugin that owns this command
   */
  public function __construct(Plugin $plugin) {
    $this->plugin = $plugin;
    $config = $this->onBuild();

    $name = $config["name"] ?? "command";
    $description = $config["description"] ?? "";
    $aliases = $config["aliases"] ?? [];
    $permission = $config["permission"] ?? DefaultPermissions::ROOT_USER;

    parent::__construct(
      $name,
      $description,
      $this->buildUsage($config),
      $aliases
    );

    // Add permission constraint if none provided
    $config["constraints"][] = new PermissionConstraint($permission);

    // Register constraints
    foreach ($config["constraints"] ?? [] as $constraint) {
      $this->addConstraint($constraint);
    }

    // Register subcommands
    foreach ($config["subcommands"] ?? [] as $subcommand) {
      $this->addSubCommand($subcommand);
    }

    // Register arguments
    foreach ($config["arguments"] ?? [] as $argument) {
      $this->addArgument($argument);
    }

    $this->setPermission($permission);
  }

  /**
   * Gets the plugin that owns this command.
   *
   * @return Plugin The owning plugin
   */
  public function getPlugin(): Plugin {
    return $this->plugin;
  }

  /**
   * Executes the command.
   *
   * @param CommandSender $sender The command sender
   * @param string $label The command label used
   * @param array $rawArgs Raw arguments passed to the command
   */
  public function execute(
    CommandSender $sender,
    string $label,
    array $rawArgs
  ): void {
    // Handle subcommands
    if (!empty($rawArgs)) {
      $key = strtolower(array_shift($rawArgs));
      foreach ($this->getSubCommands() as $sub) {
        $name = $sub->getName();
        $aliases = $sub->getAliases();
        if ($key === $name || in_array($key, $aliases, true)) {
          $sub->execute($sender, $label . " " . $name, $rawArgs);
          return;
        }
      }
    }
      
    // Check constraints
    $constraintResult = $this->testConstraints($sender);
    if (!$constraintResult["success"]) {
      $this->onFailure(
        new CommandFailure($sender, CommandFailure::CONSTRAINT_FAILED, [
          "failed_constraints" => $constraintResult["failed_constraints"],
        ])
      );
      return;
    }

    // Parse and validate arguments
    $processedArgs = $this->parseRawArgs($rawArgs);
    $validation = $this->validateArguments($sender, $processedArgs);
    if (!$validation["valid"]) {
      $this->onFailure(
        new CommandFailure($sender, $validation["type"], [
          "message" => $validation["message"],
          "argument_errors" => $validation["argument_errors"] ?? [],
        ])
      );
      return;
    }

    $parsedArgs = $this->parseArguments($sender, $rawArgs);

    try {
      // Execute command logic
      $this->onExecute(new CommandResult($sender, $parsedArgs, $label));
    } catch (\Throwable $e) {
      try {
        $this->onFailure(
          new CommandFailure($sender, CommandFailure::EXECUTION_ERROR, [
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTraceAsString(),
          ])
        );
      } catch (\Throwable $e) {
        $this->plugin->getLogger()->warning("Fatal error: " . $e->getMessage());
        $this->plugin
          ->getLogger()
          ->warning("Location: {$e->getFile()}:{$e->getLine()}");
      }
    }
  }

  /**
   * Builds the command configuration.
   * Must return an array with command settings.
   *
   * @return array Command configuration
   */
  abstract public function onBuild(): array;

  /**
   * Executes the command logic.
   *
   * @param CommandResult $result The command execution result
   */
  abstract public function onExecute(CommandResult $result): void;

  /**
   * Handles command execution failures.
   *
   * @param CommandFailure $failure The failure details
   */
  abstract public function onFailure(CommandFailure $failure): void;
}
