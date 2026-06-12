<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$testRunner = $root . '/tests/run.php';

if (!is_file($testRunner)) {
    echo "No tests configured yet.\n";
    exit(0);
}

require $testRunner;
