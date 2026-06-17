<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$pluginFile = $root . '/plugin.yml';
if (!is_file($pluginFile)) {
    fwrite(STDERR, "plugin.yml was not found.\n");
    exit(1);
}

$pluginYml = file_get_contents($pluginFile);
if ($pluginYml === false) {
    fwrite(STDERR, "Cannot read plugin.yml.\n");
    exit(1);
}

$name = readPluginValue($pluginYml, 'name');
$version = readPluginValue($pluginYml, 'version');
$safeName = sanitizeAssetPart($name);
$safeVersion = sanitizeAssetPart($version);
$dist = $root . '/dist';
if (!is_dir($dist) && !mkdir($dist, 0775, true) && !is_dir($dist)) {
    fwrite(STDERR, "Cannot create dist directory.\n");
    exit(1);
}

$pharPath = $dist . '/' . $safeName . '-' . $safeVersion . '.phar';
if (is_file($pharPath) && !unlink($pharPath)) {
    fwrite(STDERR, "Cannot remove previous PHAR: {$pharPath}\n");
    exit(1);
}

$phar = new Phar($pharPath);
$phar->startBuffering();
foreach ([
    'plugin.yml',
    'src',
    'resources',
    'icon.png',
    'icon.gif',
    'LICENSE',
] as $entry) {
    addPath($phar, $root, $root . '/' . $entry);
}
$phar->setStub("<?php __HALT_COMPILER();\n");
$phar->setSignatureAlgorithm(Phar::SHA256);
$phar->stopBuffering();

$hash = hash_file('sha256', $pharPath);
if (!is_string($hash)) {
    fwrite(STDERR, "Cannot calculate PHAR checksum.\n");
    exit(1);
}

$asset = basename($pharPath);
file_put_contents($dist . '/checksums.txt', $hash . '  ' . $asset . PHP_EOL);
file_put_contents(
    $dist . '/release.env',
    implode("\n", [
        'NAME=' . $safeName,
        'VERSION=' . $safeVersion,
        'TAG=v' . $safeVersion,
        'PHAR=dist/' . $asset,
    ]) . "\n"
);

echo "Built {$asset}\n";
echo "SHA-256 {$hash}\n";

function readPluginValue(string $pluginYml, string $key): string {
    if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*:\s*["\']?([^"\'#\r\n]+)["\']?/m', $pluginYml, $matches) !== 1) {
        fwrite(STDERR, "plugin.yml is missing {$key}.\n");
        exit(1);
    }

    return trim($matches[1]);
}

function sanitizeAssetPart(string $value): string {
    $value = trim($value);
    if ($value === '' || preg_match('/^[A-Za-z0-9_.-]+$/', $value) !== 1) {
        fwrite(STDERR, "Invalid release asset value: {$value}\n");
        exit(1);
    }

    return $value;
}

function addPath(Phar $phar, string $root, string $path): void {
    if (!file_exists($path)) {
        return;
    }
    if (is_file($path)) {
        $phar->addFile($path, relativePath($root, $path));
        return;
    }
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $phar->addFile($file->getPathname(), relativePath($root, $file->getPathname()));
        }
    }
}

function relativePath(string $root, string $path): string {
    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}
