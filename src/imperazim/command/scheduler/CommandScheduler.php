<?php

declare(strict_types = 1);

namespace imperazim\command\scheduler;

use pocketmine\Server;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\ClosureTask;
use pocketmine\plugin\PluginBase;

/**
* Schedule command execution in the future.
*
* Usage:
*   CommandScheduler::init($plugin);
*   CommandScheduler::delay($sender, "broadcast The game starts!", 100); // 5 seconds
*   CommandScheduler::repeat($sender, "say tick!", 20, "heartbeat"); // every second
*   CommandScheduler::cancel("heartbeat");
*/
final class CommandScheduler {

    private static ?PluginBase $plugin = null;

    /** @var array<string, int> id => taskHandlerId for cancellation */
    private static array $tasks = [];

    /**
    * Initializes the scheduler with a plugin reference.
    *
    * @param PluginBase $plugin Plugin for scheduler access
    */
    public static function init(PluginBase $plugin): void {
        self::$plugin = $plugin;
    }

    /**
    * Schedules a command to run after a delay.
    *
    * @param CommandSender $sender Who executes the command
    * @param string $command Command string (without /)
    * @param int $delayTicks Delay in ticks (20 = 1 second)
    * @param string|null $id Optional identifier for cancellation
    * @return string Task id
    */
    public static function delay(CommandSender $sender, string $command, int $delayTicks, ?string $id = null): string {
        $id ??= 'sched_' . spl_object_id((object) []) . '_' . mt_rand();

        $taskId = $id;
        $handler = self::getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($sender, $command, $taskId): void {
                Server::getInstance()->dispatchCommand($sender, $command);
                unset(self::$tasks[$taskId]);
            }),
            $delayTicks
        );

        self::$tasks[$id] = $handler->getTaskId();
        return $id;
    }

    /**
    * Schedules a command to run repeatedly.
    *
    * @param CommandSender $sender Who executes the command
    * @param string $command Command string (without /)
    * @param int $intervalTicks Interval in ticks
    * @param string|null $id Optional identifier for cancellation
    * @param int $maxRuns Maximum executions (0 = unlimited)
    * @return string Task id
    */
    public static function repeat(
        CommandSender $sender,
        string $command,
        int $intervalTicks,
        ?string $id = null,
        int $maxRuns = 0
    ): string {
        $id ??= 'sched_' . spl_object_id((object) []) . '_' . mt_rand();

        $runs = 0;
        $taskId = $id;
        $handler = self::getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function () use ($sender, $command, $taskId, $maxRuns, &$runs): void {
                Server::getInstance()->dispatchCommand($sender, $command);
                $runs++;
                if ($maxRuns > 0 && $runs >= $maxRuns) {
                    self::cancel($taskId);
                }
            }),
            $intervalTicks
        );

        self::$tasks[$id] = $handler->getTaskId();
        return $id;
    }

    /**
    * Cancels a scheduled task.
    *
    * @param string $id Task identifier
    * @return bool False if not found
    */
    public static function cancel(string $id): bool {
        if (!isset(self::$tasks[$id])) return false;

        self::getScheduler()->cancelTask(self::$tasks[$id]);
        unset(self::$tasks[$id]);
        return true;
    }

    /**
    * Cancels all scheduled tasks.
    */
    public static function cancelAll(): void {
        foreach (array_keys(self::$tasks) as $id) {
            self::cancel($id);
        }
    }

    /**
    * Checks if a task is active.
    *
    * @param string $id Task identifier
    * @return bool
    */
    public static function isActive(string $id): bool {
        return isset(self::$tasks[$id]);
    }

    /**
    * Gets all active task ids.
    *
    * @return string[]
    */
    public static function getActiveTasks(): array {
        return array_keys(self::$tasks);
    }

    private static function getScheduler(): \pocketmine\scheduler\TaskScheduler {
        if (self::$plugin === null) {
            throw new \RuntimeException("CommandScheduler not initialized. Call CommandScheduler::init(\$plugin) first.");
        }
        return self::$plugin->getScheduler();
    }
}
