<?php

declare(strict_types = 1);

namespace imperazim\command;

use pocketmine\scheduler\AsyncTask;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use imperazim\command\result\CommandResult;
use imperazim\command\result\CommandFailure;

/**
* Base class for commands that perform async operations (DB queries, HTTP, etc).
*
* Subclasses implement onAsyncRun() which executes on an async worker thread,
* and onAsyncComplete() which runs back on the main thread with the result.
*/
abstract class AsyncCommand extends Command {

    /**
    * Executes the command by dispatching an async task.
    */
    final public function onExecute(CommandResult $result): void {
        $sender = $result->getSender();
        $args = $result->getArgumentsList();
        $label = $result->getLabel();

        $senderName = $sender->getName();
        $isPlayer = $sender instanceof Player;

        $task = new class($this, $senderName, $isPlayer, $args->toArray(), $label) extends AsyncTask {
            public function __construct(
                private AsyncCommand $command,
                private string $senderName,
                private bool $isPlayer,
                private array $args,
                private string $label
            ) {}

            public function onRun(): void {
                $result = $this->command->onAsyncRun($this->args);
                $this->setResult($result);
            }

            public function onCompletion(): void {
                $sender = null;
                if ($this->isPlayer) {
                    $sender = Server::getInstance()->getPlayerExact($this->senderName);
                } else {
                    $sender = Server::getInstance()->getConsoleSender();
                }

                if ($sender === null) {
                    return;
                }

                $this->command->onAsyncComplete($sender, $this->getResult(), $this->label);
            }
        };

        Server::getInstance()->getAsyncPool()->submitTask($task);
    }

    /**
    * Runs on async worker thread. Must not access main-thread APIs.
    *
    * @param array $args Parsed arguments as key-value pairs
    * @return mixed Result to pass to onAsyncComplete
    */
    abstract public function onAsyncRun(array $args): mixed;

    /**
    * Called on main thread when async operation completes.
    *
    * @param CommandSender $sender The original sender (re-resolved)
    * @param mixed $result The result from onAsyncRun
    * @param string $label The command label
    */
    abstract public function onAsyncComplete(CommandSender $sender, mixed $result, string $label): void;
}
