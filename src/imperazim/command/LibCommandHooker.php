<?php

declare(strict_types = 1);

namespace imperazim\command;

use imperazim\packet\LibPacket;
use pocketmine\event\EventPriority;
use pocketmine\plugin\PluginBase;
use Exception;

/**
* Load and start libcommand hooks
*/
final class LibCommandHooker {

    /** @var bool Flag indicating if interceptor is registered */
    protected static bool $registered = false;

    /**
    * Registers the packet interceptor for dynamic command UI.
    *
    * @param PluginBase $plugin The plugin instance
    *
    * @throws \Exception If interceptor is already registered
    */
    public static function registerInterceptor(PluginBase $plugin): void {
        if (self::$registered) {
            throw new Exception("Command interceptor already registered.");
        }

        $priority = EventPriority::HIGHEST;
        $interceptor = LibPacket::createInterceptor($plugin, $priority);
        $interceptor->registerOutgoing(new LibCommandInterceptor());

        self::$registered = true;
    }
}