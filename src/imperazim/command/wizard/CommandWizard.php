<?php

declare(strict_types = 1);

namespace imperazim\command\wizard;

use Closure;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\PluginBase;

/**
* Interactive step-by-step command wizard via chat.
*
* Usage:
*   $wizard = new CommandWizard($plugin, $player, "Setup Warp");
*   $wizard->step("What is the warp name?", function(Player $p, string $answer, CommandWizard $w) {
*       $w->setData("name", $answer);
*       return true; // proceed to next step
*   });
*   $wizard->step("Is it public? (yes/no)", function(Player $p, string $answer, CommandWizard $w) {
*       if (!in_array(strtolower($answer), ['yes', 'no'])) {
*           $p->sendMessage("§cPlease answer yes or no");
*           return false; // repeat this step
*       }
*       $w->setData("public", strtolower($answer) === 'yes');
*       return true;
*   });
*   $wizard->onComplete(function(Player $p, array $data) {
*       $p->sendMessage("§aWarp '{$data['name']}' created!");
*   });
*   $wizard->onCancel(function(Player $p) {
*       $p->sendMessage("§cWizard cancelled.");
*   });
*   $wizard->start();
*/
final class CommandWizard implements Listener {

    /** @var list<array{question: string, handler: Closure}> */
    private array $steps = [];

    private int $currentStep = 0;

    /** @var array<string, mixed> Collected data */
    private array $data = [];

    private ?Closure $onComplete = null;
    private ?Closure $onCancel = null;

    private bool $running = false;

    /** Cancel keyword. */
    public const CANCEL_WORD = "cancel";

    /** @var array<string, CommandWizard> playerName => active wizard */
    private static array $activeWizards = [];

    public function __construct(
        private PluginBase $plugin,
        private Player $player,
        private string $title = "Wizard"
    ) {}

    /**
    * Adds a step to the wizard.
    *
    * @param string $question Question to ask the player
    * @param Closure $handler fn(Player, string $answer, CommandWizard): bool — return true to advance, false to repeat
    * @return static
    */
    public function step(string $question, Closure $handler): static {
        $this->steps[] = ['question' => $question, 'handler' => $handler];
        return $this;
    }

    /**
    * Sets the completion callback.
    *
    * @param Closure $callback fn(Player, array $data): void
    * @return static
    */
    public function onComplete(Closure $callback): static {
        $this->onComplete = $callback;
        return $this;
    }

    /**
    * Sets the cancel callback.
    *
    * @param Closure $callback fn(Player): void
    * @return static
    */
    public function onCancel(Closure $callback): static {
        $this->onCancel = $callback;
        return $this;
    }

    /**
    * Stores data in the wizard context.
    *
    * @param string $key Data key
    * @param mixed $value Data value
    */
    public function setData(string $key, mixed $value): void {
        $this->data[$key] = $value;
    }

    /**
    * Gets data from the wizard context.
    *
    * @param string $key Data key
    * @param mixed $default Default value
    * @return mixed
    */
    public function getData(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    /**
    * Gets all collected data.
    *
    * @return array<string, mixed>
    */
    public function getAllData(): array {
        return $this->data;
    }

    /**
    * Starts the wizard.
    */
    public function start(): void {
        // Cancel any existing wizard for this player
        $name = $this->player->getName();
        if (isset(self::$activeWizards[$name])) {
            self::$activeWizards[$name]->cancel();
        }

        $this->running = true;
        $this->currentStep = 0;
        self::$activeWizards[$name] = $this;

        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        $this->askCurrent();
    }

    /**
    * Cancels the wizard.
    */
    public function cancel(): void {
        if (!$this->running) return;
        $this->running = false;
        unset(self::$activeWizards[$this->player->getName()]);

        if ($this->onCancel !== null && $this->player->isConnected()) {
            ($this->onCancel)($this->player);
        }
    }

    /**
    * Chat event handler — captures player responses.
    */
    public function onPlayerChat(PlayerChatEvent $event): void {
        if (!$this->running) return;
        if ($event->getPlayer()->getName() !== $this->player->getName()) return;

        $event->cancel();
        $message = trim($event->getMessage());

        // Check for cancel
        if (strtolower($message) === self::CANCEL_WORD) {
            $this->cancel();
            return;
        }

        // Process current step
        $step = $this->steps[$this->currentStep] ?? null;
        if ($step === null) return;

        $proceed = ($step['handler'])($this->player, $message, $this);
        if ($proceed === true) {
            $this->currentStep++;
            if ($this->currentStep >= count($this->steps)) {
                $this->complete();
            } else {
                $this->askCurrent();
            }
        } else {
            // Repeat the question
            $this->askCurrent();
        }
    }

    /**
    * Checks if a player has an active wizard.
    *
    * @param Player $player Target player
    * @return bool
    */
    public static function hasActiveWizard(Player $player): bool {
        return isset(self::$activeWizards[$player->getName()]);
    }

    /**
    * Cleans up wizard on player quit.
    *
    * @param Player $player Disconnecting player
    */
    public static function cleanup(Player $player): void {
        $name = $player->getName();
        if (isset(self::$activeWizards[$name])) {
            self::$activeWizards[$name]->running = false;
            unset(self::$activeWizards[$name]);
        }
    }

    private function askCurrent(): void {
        if (!$this->player->isConnected() || !isset($this->steps[$this->currentStep])) return;

        $step = $this->steps[$this->currentStep];
        $progress = ($this->currentStep + 1) . "/" . count($this->steps);
        $this->player->sendMessage("§e[{$this->title}] §7({$progress}) §f" . $step['question']);
        $this->player->sendMessage("§7Type '§ccancel§7' to abort.");
    }

    private function complete(): void {
        $this->running = false;
        unset(self::$activeWizards[$this->player->getName()]);

        if ($this->onComplete !== null && $this->player->isConnected()) {
            ($this->onComplete)($this->player, $this->data);
        }
    }
}
