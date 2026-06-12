<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$directories = array_values(array_filter([
    $root . '/src',
    $root . '/tests',
], static fn(string $dir): bool => is_dir($dir)));

$standaloneFiles = array_values(array_filter([
    $root . '/Library.php',
    $root . '/LibCommand.php',
    $root . '/LibCustom.php',
    $root . '/LibDB.php',
    $root . '/LibEnchantment.php',
    $root . '/LibForm.php',
    $root . '/LibHud.php',
    $root . '/LibPacket.php',
    $root . '/LibPlaceholder.php',
    $root . '/LibSerializer.php',
    $root . '/LibTrigger.php',
    $root . '/LibWindow.php',
    $root . '/LibWorld.php',
], static fn(string $file): bool => is_file($file)));

$files = $standaloneFiles;
foreach ($directories as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

sort($files);
if ($files === []) {
    echo "No PHP files found.\n";
    exit(0);
}

$failed = false;
foreach ($files as $file) {
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file);
    exec($cmd, $output, $code);
    if ($code !== 0) {
        $failed = true;
        echo implode(PHP_EOL, $output) . PHP_EOL;
    }
}

if ($failed) {
    exit(1);
}

echo 'PHP lint OK (' . count($files) . " files).\n";
