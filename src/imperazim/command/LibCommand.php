<?php

declare(strict_types = 1);

namespace imperazim\command;

use pocketmine\plugin\PluginBase;

/**
* Main library for advanced command system.
* Handles command registration and packet interception for dynamic command UI.
*/
final class LibCommand extends PluginBase {

    /**
    * Called when the plugin enables.
    * Registers the interceptor and test commands.
    */
    protected function onEnable(): void {
        LibCommandHooker::registerInterceptor($this);
    }

}