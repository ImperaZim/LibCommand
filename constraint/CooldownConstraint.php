<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that enforces a cooldown period between command uses
*
* Tracks last usage time per sender and prevents reuse until cooldown expires
*/
class CooldownConstraint extends Constraint {
    /**
    * @var float[] Last usage timestamps indexed by sender key
    */
    private static array $lastUsed = [];

    /**
    * @param float $cooldownSeconds Cooldown duration in seconds
    */
    public function __construct(private float $cooldownSeconds) {}

    /**
    * Records successful command execution time
    *
    * @param CommandSender $sender Command executor
    */
    public function onSuccess(CommandSender $sender): void {
        self::$lastUsed[$this->getSenderKey($sender)] = microtime(true);
    }

    /**
    * Notifies sender about remaining cooldown time
    *
    * @param CommandSender $sender Command executor
    */
    public function onFailure(CommandSender $sender): void {
        $remaining = $this->getRemainingCooldown($sender);
        $sender->sendMessage(TextFormat::RED . "Please wait {$remaining} seconds before using this command again");
    }

    /**
    * Checks if cooldown period has expired
    *
    * @param CommandSender $sender Command executor
    * @return bool True if cooldown expired, false otherwise
    */
    public function isSatisfiedBy(CommandSender $sender): bool {
        $key = $this->getSenderKey($sender);
        $currentTime = microtime(true);
        if (!isset(self::$lastUsed[$key])) {
            return true;
        }
        return ($currentTime - self::$lastUsed[$key]) >= $this->cooldownSeconds;
    }

    /**
    * Generates unique key for sender
    *
    * @param CommandSender $sender Command executor
    * @return string Unique identifier (UUID for players, 'console' otherwise)
    */
    private function getSenderKey(CommandSender $sender): string {
        return $sender instanceof Player ? $sender->getUniqueId()->toString() : 'console';
    }

    /**
    * Calculates remaining cooldown time
    *
    * @param CommandSender $sender Command executor
    * @return float Remaining cooldown in seconds (formatted to 1 decimal)
    */
    private function getRemainingCooldown(CommandSender $sender): float {
        $key = $this->getSenderKey($sender);
        $lastTime = self::$lastUsed[$key] ?? 0;
        $elapsed = microtime(true) - $lastTime;
        return max(0, number_format($this->cooldownSeconds - $elapsed, 1));
    }
}