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
final class CooldownConstraint extends Constraint {
    /**
    * @var float[] Last usage timestamps indexed by compound key (instanceId:senderKey)
    */
    private static array $lastUsed = [];

    /** @var string Unique identifier for this constraint instance */
    private string $instanceId;

    /**
    * @param float $cooldownSeconds Cooldown duration in seconds
    * @param string|null $customMessage Optional custom failure message (use {time} placeholder for remaining time)
    * @param string|null $customDescription Optional custom description
    */
    public function __construct(
        private float $cooldownSeconds,
        private ?string $customMessage = null,
        private ?string $customDescription = null
    ) {
        $this->instanceId = (string) spl_object_id($this);
    }

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
        if ($this->customMessage !== null) {
            $message = str_replace('{time}', (string)$remaining, $this->customMessage);
            $sender->sendMessage($message);
        } else {
            $sender->sendMessage(TextFormat::RED . "Please wait {$remaining} seconds before using this command again");
        }
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

        // Cleanup expired entries
        foreach (self::$lastUsed as $k => $ts) {
            if (($currentTime - $ts) >= $this->cooldownSeconds) {
                unset(self::$lastUsed[$k]);
            }
        }

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
        $senderPart = $sender instanceof Player ? $sender->getUniqueId()->toString() : 'console';
        return $this->instanceId . ':' . $senderPart;
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
        return round(max(0.0, $this->cooldownSeconds - $elapsed), 1);
    }

    /**
    * Gets description of this constraint
    *
    * @return string Constraint description
    */
    public function getDescription(): string {
        return $this->customDescription ?? "Cooldown of {$this->cooldownSeconds} seconds between uses";
    }
}