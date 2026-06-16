<?php

declare(strict_types = 1);

namespace imperazim\command;

use imperazim\packet\LibPacket;
use imperazim\packet\interceptor\PacketInterceptorInterface;
use pocketmine\event\EventPriority;
use pocketmine\plugin\PluginBase;

/**
* Load and start libcommand hooks
*/
final class LibCommandHooker {

    private static ?PacketInterceptorInterface $interceptor = null;
    private static ?LibCommandInterceptor $handler = null;
    private static ?PluginBase $owner = null;

    /**
    * Registers the packet interceptor for dynamic command UI.
    *
    * @param PluginBase $plugin The plugin instance
    *
    */
    public static function registerInterceptor(PluginBase $plugin): void {
        if (self::$interceptor !== null) {
            if (self::$owner === $plugin) {
                return;
            }
            throw new \LogicException(
                'Command interceptor is already registered by ' .
                (self::$owner?->getName() ?? 'another plugin') . '.'
            );
        }

        self::$owner = $plugin;
        self::$handler = new LibCommandInterceptor();
        self::$interceptor = LibPacket::createInterceptor(
            $plugin,
            EventPriority::HIGHEST
        );
        self::$interceptor->registerOutgoing(self::$handler);
    }

    public static function shutdown(PluginBase $plugin): bool {
        if (self::$owner !== $plugin) {
            return false;
        }

        if (self::$interceptor !== null && self::$handler !== null) {
            self::$interceptor->unregisterOutgoing(self::$handler);
        }

        self::$interceptor = null;
        self::$handler = null;
        self::$owner = null;
        return true;
    }

    public static function isRegistered(): bool {
        return self::$interceptor !== null;
    }

    public static function isHostedBy(PluginBase $plugin): bool {
        return self::$owner === $plugin;
    }
}
