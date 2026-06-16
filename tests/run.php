<?php

declare(strict_types=1);

require __DIR__ . '/phpstan-pmmp-stubs.php';
require dirname(__DIR__) . '/vendor/autoload.php';

use imperazim\command\argument\EnumArgument;
use imperazim\command\LibCommandHooker;
use pocketmine\plugin\PluginBase;

function expectSame(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . ': expected ' . var_export($expected, true) .
            ', got ' . var_export($actual, true)
        );
    }
}

$plugin = new class extends PluginBase {};
LibCommandHooker::registerInterceptor($plugin);
LibCommandHooker::registerInterceptor($plugin);
expectSame(true, LibCommandHooker::isHostedBy($plugin), 'hook must track its owner');
expectSame(true, LibCommandHooker::shutdown($plugin), 'owner must be able to stop hook');
expectSame(false, LibCommandHooker::isRegistered(), 'shutdown must reset hook state');

$argument = new EnumArgument('mode', false, ['one', 'two']);
$enumName = $argument->getParameterData()->enum?->getName();
expectSame(
    true,
    is_string($enumName) && str_starts_with($enumName, 'enum:mode:'),
    'enum arguments must use stable non-empty names'
);

echo "LibCommand tests passed.\n";
