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
*
* Important: onAsyncRun() receives serializable data only — no main-thread objects.
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
        $argsArray = $args->toArray();

        // Prepare serializable data for the worker thread
        $asyncData = $this->prepareAsyncData($argsArray);

        $command = $this;
        $task = new class($senderName, $isPlayer, $argsArray, $label, $asyncData) extends AsyncTask {
            public function __construct(
                private string $senderName,
                private bool $isPlayer,
                private array $args,
                private string $label,
                private mixed $asyncData
            ) {}

            public function onRun(): void {
                // AsyncCommand stores the actual run callback via storeLocal (main-thread only)
                // We pass the serializable data to onCompletion where it can be processed
                $this->setResult($this->asyncData);
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

                $callback = $this->fetchLocal('asyncCompleteCallback');
                $callback($sender, $this->getResult(), $this->args, $this->label);
            }
        };

        $task->storeLocal('asyncCompleteCallback', function(CommandSender $s, mixed $data, array $args, string $l) use ($command) {
            $command->onAsyncComplete($s, $data, $args, $l);
        });

        Server::getInstance()->getAsyncPool()->submitTask($task);
    }

    /**
    * Prepares serializable data for the async worker thread.
    * Override this to include data that onAsyncComplete needs to process.
    *
    * @param array $args Parsed arguments as key-value pairs
    * @return mixed Serializable data for the worker (must not contain Closures or non-serializable objects)
    */
    protected function prepareAsyncData(array $args): mixed {
        return $args;
    }

    /**
    * Called on main thread when async operation completes.
    * Process the data here using main-thread APIs.
    *
    * @param CommandSender $sender The original sender (re-resolved)
    * @param mixed $data The serializable data from prepareAsyncData
    * @param array $args The original parsed arguments
    * @param string $label The command label
    */
    abstract public function onAsyncComplete(CommandSender $sender, mixed $data, array $args, string $label): void;
}
