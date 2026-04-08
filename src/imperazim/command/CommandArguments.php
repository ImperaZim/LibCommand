<?php

declare(strict_types=1);

namespace imperazim\command;

use ArrayAccess;
use IteratorAggregate;
use Countable;
use ArrayIterator;
use Traversable;
use InvalidArgumentException;
use pocketmine\command\CommandSender;

/**
 * Represents parsed command arguments with array-like access.
 * Implements ArrayAccess, IteratorAggregate, and Countable for easy manipulation.
 */
final class CommandArguments implements ArrayAccess, IteratorAggregate, Countable {
    
  /** @var CommandSender The command sender */
  private readonly CommandSender $sender;

  /** @var array The parsed arguments */
  private array $arguments = [];

  /**
   * Constructor.
   *
   * @param CommandSender $sender The command sender
   * @param array $arguments The parsed arguments
   */
  public function __construct(CommandSender $sender, array $arguments = []) {
    $this->sender = $sender;
    $this->arguments = $arguments;
  }

  /**
   * Gets the command sender.
   *
   * @return CommandSender
   */
  public function getSender(): CommandSender {
    return $this->sender;
  }

  /**
   * Checks if an argument exists.
   *
   * @param mixed $offset Argument name
   * @return bool True if exists, false otherwise
   */
  public function offsetExists(mixed $offset): bool {
    return isset($this->arguments[$offset]);
  }

  /**
   * Gets an argument value.
   *
   * @param mixed $offset Argument name
   * @return mixed Argument value or null
   */
  public function offsetGet(mixed $offset): mixed {
    return $this->arguments[$offset] ?? null;
  }

  /**
   * Sets an argument value.
   *
   * @param mixed $offset Argument name
   * @param mixed $value Argument value
   *
   * @throws InvalidArgumentException If offset is null
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    if ($offset === null) {
      throw new InvalidArgumentException("Argument must have a name");
    }
    $this->arguments[$offset] = $value;
  }

  /**
   * Removes an argument.
   *
   * @param mixed $offset Argument name
   */
  public function offsetUnset(mixed $offset): void {
    unset($this->arguments[$offset]);
  }

  /**
   * Gets an iterator for the arguments.
   *
   * @return Traversable Argument iterator
   */
  public function getIterator(): Traversable {
    return new ArrayIterator($this->arguments);
  }

  /**
   * Counts the number of arguments.
   *
   * @return int Number of arguments
   */
  public function count(): int {
    return count($this->arguments);
  }

  /**
   * Converts arguments to an array.
   *
   * @return array Arguments as array
   */
  public function toArray(): array {
    return $this->arguments;
  }

  /**
   * Gets an argument value with default fallback.
   *
   * @param string $key Argument name
   * @param mixed $default Default value if not found
   * @return mixed Argument value or default
   */
  public function get(string $key, mixed $default = null): mixed {
    return $this->arguments[$key] ?? $default;
  }
}
