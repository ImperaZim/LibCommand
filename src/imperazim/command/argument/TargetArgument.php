<?php

declare(strict_types = 1);

namespace imperazim\command\argument;

use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use imperazim\command\exception\ArgumentException;

/**
 * Argument type for entity/player targets
 * 
 * Supports:
 * - Player names (exact or prefix)
 * - Entity selectors (@p, @a, @e, etc.)
 * - Entity IDs
 * - Entity name tags
 */
final class TargetArgument extends Argument {

  /**
   * Gets the human-readable type name
   * 
   * @return string "target"
   */
  public function getTypeName(): string {
    return "target";
  }

  /**
   * Gets the network type flag
   * 
   * @return int ARG_TYPE_TARGET constant
   */
  public function getNetworkType(): int {
    return AvailableCommandsPacket::ARG_TYPE_TARGET;
  }

  /**
   * Checks if input is a valid target selector
   * 
   * @param string $testString Input string to test
   * @param CommandSender $sender Command executor
   * @return bool True if valid selector or entity identifier
   */
  public function canParse(string $testString, CommandSender $sender): bool {
    return preg_match('/^@[aeprs]($|\[)/', $testString) ||
    $this->isValidEntityIdentifier($testString);
  }

  /**
   * Parses input into Entity or array of entities
   * 
   * @param string $value Input string to parse
   * @param CommandSender $sender Command executor
   * @return Entity|Entity[]|null Parsed entity/entities
   * 
   * @throws ArgumentException If target not found
   */
  public function parse(string $value, CommandSender $sender): mixed {
    if (preg_match('/^@[aeprs]($|\[)/', $value)) {
      return $this->parseSelector($value, $sender);
    }

    $entity = $this->findEntity($value, $sender);
    if ($entity === null) {
      throw new ArgumentException("Target '{$value}' not found");
    }

    return $entity;
  }

  /**
   * Checks if input is a valid entity identifier
   * 
   * @param string $testString Input string to test
   * @return bool True if valid identifier (1-16 word characters)
   */
  private function isValidEntityIdentifier(string $testString): bool {
    return preg_match('/^\w{1,16}$/', $testString);
  }

  /**
   * Parses entity selector into entity/entities
   * 
   * @param string $selector Selector string (@p, @a, etc.)
   * @param CommandSender $sender Command executor
   * @return Entity|Entity[]|null Parsed entity/entities
   * 
   * @throws ArgumentException For unknown selector types
   */
  private function parseSelector(string $selector, CommandSender $sender): mixed {
    $type = $selector[1];
    $args = [];

    // Extract arguments if present [arg=value]
    if (strpos($selector, '[') !== false) {
      preg_match('/\[(.*?)\]/', $selector, $matches);
      if (isset($matches[1])) {
        parse_str(str_replace(',', '&', $matches[1]), $args);
      }
    }

    switch ($type) {
      case 'a': // @a - All players
        return $this->getAllPlayers($args);

      case 'e': // @e - All entities
        return $this->getAllEntities($args);

      case 'p': // @p - Nearest player
        return $this->getNearestPlayer($sender, $args);

      case 'r': // @r - Random player
        return $this->getRandomPlayer($args);

      case 's': // @s - The sender
        return $sender instanceof Entity ? $sender : null;

      default:
        throw new ArgumentException("Unknown selector type: @{$type}");
    }
  }

  /**
   * Gets all players matching filter criteria
   * 
   * @param array $args Selector arguments (e.g., ['world' => 'world_name'])
   * @return Player[] Filtered players
   */
  private function getAllPlayers(array $args): array {
    $players = Server::getInstance()->getOnlinePlayers();

    // Filter by world if specified
    if (isset($args['world'])) {
      $worldName = $args['world'];
      $players = array_filter($players, fn(Player $p) =>
        $p->getWorld()->getFolderName() === $worldName
      );
    }

    return array_values($players);
  }

  /**
   * Gets all entities matching filter criteria
   * 
   * @param array $args Selector arguments
   * @return Entity[] Filtered entities
   */
  private function getAllEntities(array $args): array {
    $entities = [];

    foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
      // Filter by world if specified
      if (isset($args['world']) && $world->getFolderName() !== $args['world']) {
        continue;
      }

      foreach ($world->getEntities() as $entity) {
        // Filter by type if specified
        if (isset($args['type']) && get_class($entity) !== $args['type']) {
          continue;
        }

        $entities[] = $entity;
      }
    }

    return $entities;
  }

  /**
   * Gets nearest player to sender
   * 
   * @param CommandSender $sender Command executor
   * @param array $args Selector arguments
   * @return Player|null Nearest player or sender if none found
   */
  private function getNearestPlayer(CommandSender $sender, array $args): ?Player {
    if (!$sender instanceof Player) {
      return null;
    }

    $nearest = null;
    $minDistance = PHP_INT_MAX;
    $senderPos = $sender->getPosition();

    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
      // Filter by world
      if (isset($args['world']) &&
        $player->getWorld()->getFolderName() !== $args['world']) {
        continue;
      }

      $distance = $player->getPosition()->distance($senderPos);
      if ($distance < $minDistance && $player !== $sender) {
        $minDistance = $distance;
        $nearest = $player;
      }
    }

    return $nearest ?? $sender;
  }

  /**
   * Gets random player
   * 
   * @param array $args Selector arguments
   * @return Player|null Random player or null if none available
   */
  private function getRandomPlayer(array $args): ?Player {
    $players = Server::getInstance()->getOnlinePlayers();

    // Filter by world if specified
    if (isset($args['world'])) {
      $players = array_filter($players, fn(Player $p) =>
        $p->getWorld()->getFolderName() === $args['world']
      );
    }

    return count($players) > 0 ? $players[array_rand($players)] : null;
  }

  /**
   * Finds entity by identifier
   * 
   * @param string $identifier Entity identifier (name or ID)
   * @param CommandSender $sender Command executor
   * @return Entity|null Found entity or null
   */
  private function findEntity(string $identifier, CommandSender $sender): ?Entity {
    $server = Server::getInstance();

    // 1. Try to find player by exact name
    $player = $server->getPlayerExact($identifier);
    if ($player !== null) {
      return $player;
    }

    // 2. Try to find player by prefix
    $player = $server->getPlayerByPrefix($identifier);
    if ($player !== null) {
      return $player;
    }

    // 3. Search all entities in the same world as sender
    if ($sender instanceof Player) {
      $world = $sender->getWorld();
      foreach ($world->getEntities() as $entity) {
        if ($entity instanceof Player) continue;

        if (strtolower($entity->getNameTag()) === strtolower($identifier) ||
          $entity->getId() === (int)$identifier) {
          return $entity;
        }
      }
    }

    // 4. Search all entities globally
    foreach ($server->getWorldManager()->getWorlds() as $world) {
      foreach ($world->getEntities() as $entity) {
        if ($entity instanceof Player) continue;

        if (strtolower($entity->getNameTag()) === strtolower($identifier) ||
          $entity->getId() === (int)$identifier) {
          return $entity;
        }
      }
    }

    return null;
  }
}