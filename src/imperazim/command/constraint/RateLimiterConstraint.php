<?php

declare(strict_types = 1);

namespace imperazim\command\constraint;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

/**
* Constraint that limits command usage to X executions per time window.
*
* Unlike CooldownConstraint (fixed delay between uses), this allows bursts
* but caps total usage within a sliding window.
*
* Example: maxUses=3, windowSeconds=60 => max 3 uses per minute
*/
class RateLimiterConstraint extends Constraint {

    /** @var array<string, list<float>> Usage timestamps per sender */
    private static array $usageLog = [];

    /**
    * @param int $maxUses Maximum allowed executions within the window
    * @param float $windowSeconds Time window in seconds
    * @param string|null $customMessage Optional failure message ({remaining} placeholder)
    * @param string|null $customDescription Optional constraint description
    */
    public function __construct(
        private int $maxUses,
        private float $windowSeconds,
        private ?string $customMessage = null,
        private ?string $customDescription = null
    ) {}

    public function onSuccess(CommandSender $sender): void {
        $key = $this->getSenderKey($sender);
        if (!isset(self::$usageLog[$key])) {
            self::$usageLog[$key] = [];
        }
        self::$usageLog[$key][] = microtime(true);
    }

    public function onFailure(CommandSender $sender): void {
        $remaining = $this->getWindowRemaining($sender);
        if ($this->customMessage !== null) {
            $message = str_replace('{remaining}', (string) round($remaining, 1), $this->customMessage);
            $sender->sendMessage($message);
        } else {
            $sender->sendMessage(
                TextFormat::RED . "Rate limit reached ({$this->maxUses} uses per {$this->windowSeconds}s). " .
                "Try again in " . round($remaining, 1) . "s"
            );
        }
    }

    public function isSatisfiedBy(CommandSender $sender): bool {
        $key = $this->getSenderKey($sender);
        $now = microtime(true);
        $cutoff = $now - $this->windowSeconds;

        if (!isset(self::$usageLog[$key])) {
            return true;
        }

        // Prune expired entries
        self::$usageLog[$key] = array_values(array_filter(
            self::$usageLog[$key],
            fn(float $ts) => $ts > $cutoff
        ));

        return count(self::$usageLog[$key]) < $this->maxUses;
    }

    public function getDescription(): string {
        return $this->customDescription ?? "Maximum {$this->maxUses} uses per {$this->windowSeconds} seconds";
    }

    private function getSenderKey(CommandSender $sender): string {
        return $sender instanceof Player ? $sender->getUniqueId()->toString() : 'console';
    }

    private function getWindowRemaining(CommandSender $sender): float {
        $key = $this->getSenderKey($sender);
        $entries = self::$usageLog[$key] ?? [];
        if (empty($entries)) {
            return 0.0;
        }
        $oldest = $entries[0];
        return max(0.0, ($oldest + $this->windowSeconds) - microtime(true));
    }
}
