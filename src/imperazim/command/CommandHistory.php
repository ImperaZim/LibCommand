<?php

declare(strict_types = 1);

namespace imperazim\command;

/**
* Tracks command execution history per sender.
*
* Stores the last N commands executed by each player/console for debugging and auditing.
*/
final class CommandHistory {

    /** @var array<string, list<array{command: string, args: string[], timestamp: float}>> */
    private static array $history = [];

    /** @var int Maximum entries per sender */
    private static int $maxEntries = 50;

    /** @var int Maximum tracked senders */
    private static int $maxSenders = 200;

    /**
    * Sets the maximum number of history entries per sender.
    *
    * @param int $max Maximum entries (minimum 1)
    */
    public static function setMaxEntries(int $max): void {
        self::$maxEntries = max(1, $max);
    }

    /**
    * Records a command execution.
    *
    * @param string $senderName The sender's name
    * @param string $command The command name
    * @param string[] $args The raw arguments
    */
    public static function record(string $senderName, string $command, array $args): void {
        if (!isset(self::$history[$senderName])) {
            self::$history[$senderName] = [];
        }

        self::$history[$senderName][] = [
            'command' => $command,
            'args' => $args,
            'timestamp' => microtime(true),
        ];

        if (count(self::$history[$senderName]) > self::$maxEntries) {
            array_shift(self::$history[$senderName]);
        }

        // Evict oldest sender if limit exceeded
        if (count(self::$history) > self::$maxSenders) {
            array_shift(self::$history);
        }
    }

    /**
    * Gets the command history for a sender.
    *
    * @param string $senderName The sender's name
    * @param int $limit Maximum entries to return (0 = all)
    * @return list<array{command: string, args: string[], timestamp: float}>
    */
    public static function get(string $senderName, int $limit = 0): array {
        $entries = self::$history[$senderName] ?? [];
        if ($limit > 0) {
            return array_slice($entries, -$limit);
        }
        return $entries;
    }

    /**
    * Gets the last command executed by a sender.
    *
    * @param string $senderName The sender's name
    * @return array{command: string, args: string[], timestamp: float}|null
    */
    public static function getLast(string $senderName): ?array {
        $entries = self::$history[$senderName] ?? [];
        return empty($entries) ? null : end($entries);
    }

    /**
    * Clears history for a sender.
    *
    * @param string $senderName The sender's name
    */
    public static function clear(string $senderName): void {
        unset(self::$history[$senderName]);
    }

    /**
    * Clears all history.
    */
    public static function clearAll(): void {
        self::$history = [];
    }
}
