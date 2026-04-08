<?php

declare(strict_types=1);

namespace imperazim\command;

use imperazim\command\Command;
use imperazim\command\SubCommand;
use imperazim\command\argument\Argument;
use imperazim\command\constraint\Constraint;

/**
 * Generates automatic help text for commands.
 */
final class HelpGenerator {

  /**
   * Generates help text for a command.
   *
   * @param Command $command The command
   * @param string $format The format style (detailed, compact, usage)
   * @return string The help text
   */
  public static function generate(Command $command, string $format = "detailed"): string {
    return match ($format) {
      "compact" => self::generateCompact($command),
      "usage" => self::generateUsage($command),
      default => self::generateDetailed($command)
    };
  }

  /**
   * Generates detailed help text.
   *
   * @param Command $command The command
   * @return string The help text
   */
  private static function generateDetailed(Command $command): string {
    $lines = [];
    
    // Header
    $lines[] = "§e§l" . strtoupper($command->getName()) . " COMMAND";
    $lines[] = "§r§7" . $command->getDescription();
    $lines[] = "";

    // Usage
    $lines[] = "§eUsage:";
    $lines[] = "  §7/" . self::buildUsageLine($command);
    $lines[] = "";

    // Arguments
    if (!empty($command->getArguments())) {
      $lines[] = "§eArguments:";
      foreach ($command->getArguments() as $arg) {
        $name = $arg->getName();
        $type = $arg->getTypeName();
        $desc = $arg->getDescription();
        $optional = $arg->isOptional();
        $default = $arg->getDefault();
        
        $prefix = $optional ? "  §7[" : "  §7<";
        $suffix = $optional ? "]" : ">";
        $line = $prefix . $name . "§8:" . $type . $suffix;
        
        if ($desc !== "") {
          $line .= " §f- " . $desc;
        }
        
        if ($optional && $default !== null) {
          $line .= " §8(default: §7" . self::formatValue($default) . "§8)";
        }
        
        $lines[] = $line;
      }
      $lines[] = "";
    }

    // Subcommands
    if (!empty($command->getSubCommands())) {
      $lines[] = "§eSubcommands:";
      foreach ($command->getSubCommands() as $sub) {
        $name = $sub->getName();
        $desc = $sub->getDescription();
        $aliases = $sub->getAliases();
        
        $line = "  §7" . $name;
        
        if (!empty($aliases)) {
          $line .= " §8(" . implode(", ", $aliases) . ")";
        }
        
        if ($desc !== "") {
          $line .= " §f- " . $desc;
        }
        
        $lines[] = $line;
      }
      $lines[] = "";
    }

    // Constraints
    if (!empty($command->getConstraints())) {
      $lines[] = "§eRequirements:";
      foreach ($command->getConstraints() as $constraint) {
        $desc = $constraint->getDescription();
        if ($desc !== "") {
          $lines[] = "  §7• " . $desc;
        }
      }
      $lines[] = "";
    }

    return implode("\n", $lines);
  }

  /**
   * Generates compact help text.
   *
   * @param Command $command The command
   * @return string The help text
   */
  private static function generateCompact(Command $command): string {
    $lines = [];
    
    $lines[] = "§e/" . self::buildUsageLine($command);
    $lines[] = "§7" . $command->getDescription();
    
    if (!empty($command->getSubCommands())) {
      $subNames = array_map(fn($s) => $s->getName(), $command->getSubCommands());
      $lines[] = "§8Subcommands: §7" . implode("§8, §7", $subNames);
    }

    return implode("\n", $lines);
  }

  /**
   * Generates usage-only help text.
   *
   * @param Command $command The command
   * @return string The usage line
   */
  private static function generateUsage(Command $command): string {
    return "§7/" . self::buildUsageLine($command);
  }

  /**
   * Builds the usage line for a command.
   *
   * @param Command $command The command
   * @return string The usage line
   */
  private static function buildUsageLine(Command $command): string {
    $parts = [$command->getName()];

    if (!empty($command->getSubCommands())) {
      $subNames = array_map(fn($s) => $s->getName(), $command->getSubCommands());
      $parts[] = "<" . implode("|", $subNames) . ">";
    }

    foreach ($command->getArguments() as $arg) {
      $name = $arg->getName();
      $aliases = $arg->getAliases();
      
      if (!empty($aliases)) {
        $name .= "|" . implode("|", $aliases);
      }
      
      if ($arg->isOptional()) {
        $parts[] = "[" . $name . "]";
      } else {
        $parts[] = "<" . $name . ">";
      }
    }

    return implode(" ", $parts);
  }

  /**
   * Generates help text for a subcommand.
   *
   * @param SubCommand $subcommand The subcommand
   * @param string $parentName The parent command name
   * @param string $format The format style
   * @return string The help text
   */
  public static function generateSubCommand(
    SubCommand $subcommand,
    string $parentName,
    string $format = "detailed"
  ): string {
    $lines = [];
    
    if ($format === "detailed") {
      // Header
      $lines[] = "§e§l" . strtoupper($parentName . " " . $subcommand->getName());
      $lines[] = "§r§7" . $subcommand->getDescription();
      $lines[] = "";

      // Usage
      $lines[] = "§eUsage:";
      $lines[] = "  §7/" . self::buildSubCommandUsageLine($subcommand, $parentName);
      $lines[] = "";

      // Arguments
      if (!empty($subcommand->getArguments())) {
        $lines[] = "§eArguments:";
        foreach ($subcommand->getArguments() as $arg) {
          $name = $arg->getName();
          $type = $arg->getTypeName();
          $desc = $arg->getDescription();
          $optional = $arg->isOptional();
          
          $prefix = $optional ? "  §7[" : "  §7<";
          $suffix = $optional ? "]" : ">";
          $line = $prefix . $name . "§8:" . $type . $suffix;
          
          if ($desc !== "") {
            $line .= " §f- " . $desc;
          }
          
          $lines[] = $line;
        }
        $lines[] = "";
      }
    } else {
      $lines[] = "§7/" . self::buildSubCommandUsageLine($subcommand, $parentName);
      $lines[] = "§7" . $subcommand->getDescription();
    }

    return implode("\n", $lines);
  }

  /**
   * Builds the usage line for a subcommand.
   *
   * @param SubCommand $subcommand The subcommand
   * @param string $parentName The parent command name
   * @return string The usage line
   */
  private static function buildSubCommandUsageLine(
    SubCommand $subcommand,
    string $parentName
  ): string {
    $parts = [$parentName, $subcommand->getName()];

    foreach ($subcommand->getArguments() as $arg) {
      $name = $arg->getName();
      
      if ($arg->isOptional()) {
        $parts[] = "[" . $name . "]";
      } else {
        $parts[] = "<" . $name . ">";
      }
    }

    return implode(" ", $parts);
  }

  /**
   * Formats a value for display.
   *
   * @param mixed $value The value
   * @return string The formatted value
   */
  private static function formatValue($value): string {
    if (is_bool($value)) {
      return $value ? "true" : "false";
    }
    
    if (is_array($value)) {
      return "[" . implode(", ", array_map([self::class, "formatValue"], $value)) . "]";
    }
    
    if (is_string($value)) {
      return '"' . $value . '"';
    }
    
    return (string) $value;
  }
}
