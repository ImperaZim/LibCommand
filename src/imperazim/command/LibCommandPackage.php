<?php

declare(strict_types=1);

namespace imperazim\command;

use pocketmine\plugin\PluginBase;

final class LibCommandPackage {

    public function __construct(private readonly PluginBase $host) {}

    public function boot(): void {}

    public function enable(): void {
        LibCommandHooker::registerInterceptor($this->host);
    }

    public function disable(): void {
        LibCommandHooker::shutdown($this->host);
    }

    public function reload(): void {
        $this->disable();
        $this->enable();
    }
}
