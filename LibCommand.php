<?php

declare(strict_types = 1);

namespace imperazim\command;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\EventPriority;
use pocketmine\utils\SingletonTrait;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use imperazim\packet\LibPacket;
use imperazim\packet\handler\PacketHandlerInterface;

/**
* Main library for advanced command system.
* Handles command registration and packet interception for dynamic command UI.
*/
class LibCommand extends PluginBase {
    use SingletonTrait;

    /** @var bool Flag indicating if interceptor is registered */
    protected static bool $registered = false;

    /**
    * Called when the plugin loads.
    * Sets the singleton instance.
    */
    protected function onLoad(): void {
        self::setInstance($this);
    }

    /**
    * Called when the plugin enables.
    * Registers the interceptor and test commands.
    */
    protected function onEnable(): void {
        $this->registerInterceptor($this);

        $cmdMap = $this->getServer()->getCommandMap();
        $namespace = $this->getDescription()->getName();

        // Register test commands
        $cmdMap->register($namespace, new \imperazim\tests\InfoCommand($this));
        $cmdMap->register($namespace, new \imperazim\tests\AdvancedTestCommand($this));
        $cmdMap->register($namespace, new \imperazim\tests\AdminCommand($this));
    }

    /**
    * Registers the packet interceptor for dynamic command UI.
    *
    * @param PluginBase $plugin The plugin instance
    *
    * @throws \Exception If interceptor is already registered
    */
    public function registerInterceptor(PluginBase $plugin): void {
        if (self::$registered) {
            throw new \Exception("Command interceptor already registered.");
        }

        $priority = EventPriority::HIGHEST;
        $interceptor = LibPacket::createInterceptor($plugin, $priority);
        $interceptor->registerOutgoing(new LibCommandInterceptor());

        self::$registered = true;
    }
}