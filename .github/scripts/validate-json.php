<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$skip = [
    DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR,
];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$failed = false;
$count = 0;

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || strtolower($file->getExtension()) !== 'json') {
        continue;
    }

    $path = $file->getPathname();
    foreach ($skip as $part) {
        if (str_contains($path, $part)) {
            continue 2;
        }
    }

    $count++;
    json_decode((string) file_get_contents($path), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $failed = true;
        echo $path . ': ' . json_last_error_msg() . PHP_EOL;
    }
}

if ($failed) {
    exit(1);
}

echo 'JSON validation OK (' . $count . " files).\n";
