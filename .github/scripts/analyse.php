<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$configs = [
    $root . '/phpstan.neon',
    $root . '/phpstan.neon.dist',
    $root . '/phpstan.neon.dist.txt',
];

$config = null;
foreach ($configs as $candidate) {
    if (is_file($candidate)) {
        $config = $candidate;
        break;
    }
}

$phpstan = $root . '/vendor/bin/phpstan';
if (PHP_OS_FAMILY === 'Windows') {
    $phpstan .= '.bat';
}

if (!is_file($phpstan)) {
    echo "PHPStan is not installed; skipping analysis.\n";
    exit(0);
}

if ($config === null) {
    echo "No PHPStan config found; skipping analysis.\n";
    exit(0);
}

$cmd = escapeshellarg(PHP_BINARY) . ' -d memory_limit=512M ' . escapeshellarg($phpstan) . ' analyse --no-progress --configuration=' . escapeshellarg($config);
passthru($cmd, $code);
exit($code);
