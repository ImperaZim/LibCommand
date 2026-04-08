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
use imperazim\command\HelpGenerator;

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

  /** @var float|null Command cooldown in seconds */
  private ?float $cooldown = null;

  /** @var array Last usage timestamps per sender */
  private static array $cooldowns = [];

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

    // Store cooldown if provided
    $this->cooldown = $config["cooldown"] ?? null;

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
   * Checks if sender is on cooldown.
   *
   * @param CommandSender $sender The sender to check
   * @return bool True if on cooldown
   */
  private function isOnCooldown(CommandSender $sender): bool {
    if ($this->cooldown === null) {
      return false;
    }

    $name = $sender->getName();
    $key = $this->getName() . ":" . $name;

    if (!isset(self::$cooldowns[$key])) {
      return false;
    }

    $elapsed = microtime(true) - self::$cooldowns[$key];
    return $elapsed < $this->cooldown;
  }

  /**
   * Gets remaining cooldown time.
   *
   * @param CommandSender $sender The sender to check
   * @return float Remaining seconds
   */
  private function getRemainingCooldown(CommandSender $sender): float {
    if ($this->cooldown === null) {
      return 0.0;
    }

    $name = $sender->getName();
    $key = $this->getName() . ":" . $name;

    if (!isset(self::$cooldowns[$key])) {
      return 0.0;
    }

    $elapsed = microtime(true) - self::$cooldowns[$key];
    return max(0.0, $this->cooldown - $elapsed);
  }

  /**
   * Updates sender's cooldown.
   *
   * @param CommandSender $sender The sender
   */
  private function updateCooldown(CommandSender $sender): void {
    if ($this->cooldown === null) {
      return;
    }

    $name = $sender->getName();
    $key = $this->getName() . ":" . $name;
    self::$cooldowns[$key] = microtime(true);
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
    // Check cooldown
    if ($this->isOnCooldown($sender)) {
      $remaining = $this->getRemainingCooldown($sender);
      $this->onFailure(
        new CommandFailure($sender, CommandFailure::COOLDOWN, [
          "remaining" => $remaining,
        ])
      );
      return;
    }
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
      // Update cooldown before execution
      $this->updateCooldown($sender);
      
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
