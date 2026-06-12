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
use imperazim\command\constraint\PermissionConstraint;
use imperazim\command\constraint\CooldownConstraint;
use imperazim\command\result\CommandResult;
use imperazim\command\result\CommandFailure;
use imperazim\command\HelpGenerator;
use Throwable;

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

  /** @var array Custom messages for command failures */
  private array $messages = [];

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

    // Store custom messages
    $this->messages = $config["messages"] ?? [];

    parent::__construct(
      $name,
      $description,
      $this->buildUsage($config),
      $aliases
    );

    // Register constraints
    $constraints = $config["constraints"] ?? [];
    $constraints[] = new PermissionConstraint($permission);
    if (isset($config["cooldown"]) && is_numeric($config["cooldown"])) {
      $constraints[] = new CooldownConstraint((float) $config["cooldown"]);
    }
    foreach ($constraints as $constraint) {
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
   * Gets a custom message by key.
   *
   * @param string $key The message key
   * @param string $default Default message if key not found
   * @return string The message
   */
  public function getMessage(string $key, string $default = ""): string {
    return $this->messages[$key] ?? $default;
  }

  /**
   * Sets a custom message.
   *
   * @param string $key The message key
   * @param string $message The message text
   */
  public function setMessage(string $key, string $message): void {
    $this->messages[$key] = $message;
  }

  /**
   * Generates help text for this command.
   *
   * @param string $format The format style (detailed, compact, usage)
   * @return string The help text
   */
  public function getHelp(string $format = "detailed"): string {
    return HelpGenerator::generate($this, $format);
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
    if (!empty($rawArgs) && !empty($this->getSubCommands())) {
      $key = strtolower(array_shift($rawArgs));
      foreach ($this->getSubCommands() as $sub) {
        $name = $sub->getName();
        $aliases = $sub->getAliases();
        if ($key === $name || in_array($key, $aliases, true)) {
          $sub->execute($sender, $label . " " . $name, $rawArgs);
          return;
        }
      }
      array_unshift($rawArgs, $key);
    }
      
    // Check constraints
    $constraintResult = $this->testConstraints($sender);
    if (!$constraintResult["success"]) {
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

    $parsedArgs = $this->parseArguments($sender, $processedArgs);

    try {
      // Record in history
      CommandHistory::record($sender->getName(), $label, $rawArgs);

      // Execute command logic
      $this->onExecute(new CommandResult($sender, $parsedArgs, $label, array_values($rawArgs)));
    } catch (Throwable $e) {
      try {
        $this->onFailure(
          new CommandFailure($sender, CommandFailure::EXECUTION_ERROR, [
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTraceAsString(),
          ])
        );
      } catch (Throwable $innerEx) {
        $this->plugin->getLogger()->warning("Fatal error: " . $innerEx->getMessage());
        $this->plugin
          ->getLogger()
          ->warning("Location: {$innerEx->getFile()}:{$innerEx->getLine()}");
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
