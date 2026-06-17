<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$pluginFile = $root . '/plugin.yml';
$packageFile = $root . '/package.yml';

foreach ([$pluginFile, $packageFile] as $requiredFile) {
    if (!is_file($requiredFile)) {
        fwrite(STDERR, basename($requiredFile) . " was not found.\n");
        exit(1);
    }
}

$pluginYml = (string) file_get_contents($pluginFile);
$packageYml = (string) file_get_contents($packageFile);
$name = readYamlScalar($pluginYml, 'name');
$version = readYamlScalar($pluginYml, 'version');
$packageId = readYamlScalar($packageYml, 'id');
$packageName = readYamlScalar($packageYml, 'name');
$packageVersion = readYamlScalar($packageYml, 'version');

if ($name !== $packageName) {
    fwrite(STDERR, "package.yml name {$packageName} does not match plugin.yml name {$name}.\n");
    exit(1);
}
if ($version !== $packageVersion) {
    fwrite(STDERR, "package.yml version {$packageVersion} does not match plugin.yml version {$version}.\n");
    exit(1);
}

$safeName = sanitizeAssetPart($name);
$safeVersion = sanitizeAssetPart($version);
$dist = $root . '/dist';
if (!is_dir($dist) && !mkdir($dist, 0775, true) && !is_dir($dist)) {
    fwrite(STDERR, "Cannot create dist directory.\n");
    exit(1);
}

$asset = $safeName . '-' . $safeVersion . '.easylib.zip';
$zipPath = $dist . '/' . $asset;
if (is_file($zipPath) && !unlink($zipPath)) {
    fwrite(STDERR, "Cannot remove previous package: {$zipPath}\n");
    exit(1);
}

$zip = new PharData($zipPath);
foreach ([
    'package.yml',
    'src',
    'resources',
    'README.md',
    'LICENSE',
] as $entry) {
    addPath($zip, $root, $root . '/' . $entry);
}

$manifestAsset = $dist . '/package.yml';
if (!copy($packageFile, $manifestAsset)) {
    fwrite(STDERR, "Cannot copy package.yml into dist.\n");
    exit(1);
}

$checksums = [];
foreach (glob($dist . '/*') ?: [] as $file) {
    if (!is_file($file) || basename($file) === 'checksums.txt' || basename($file) === 'release.env') {
        continue;
    }
    $hash = hash_file('sha256', $file);
    if (!is_string($hash)) {
        fwrite(STDERR, "Cannot calculate checksum for {$file}.\n");
        exit(1);
    }
    $checksums[] = $hash . '  ' . basename($file);
}
sort($checksums);
file_put_contents($dist . '/checksums.txt', implode(PHP_EOL, $checksums) . PHP_EOL);

$phar = $dist . '/' . $safeName . '-' . $safeVersion . '.phar';
$releaseEnv = [
    'NAME=' . $safeName,
    'VERSION=' . $safeVersion,
    'TAG=v' . $safeVersion,
    'PHAR=' . (is_file($phar) ? 'dist/' . basename($phar) : ''),
    'EASYLIB=dist/' . $asset,
    'PACKAGE_MANIFEST=dist/package.yml',
    'PACKAGE_ID=' . sanitizeAssetPart($packageId),
];
file_put_contents($dist . '/release.env', implode("\n", $releaseEnv) . "\n");

echo "Built {$asset}\n";
echo "Package manifest dist/package.yml\n";

function readYamlScalar(string $yaml, string $key): string {
    if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*:\s*["\']?([^"\'#\r\n]+)["\']?/m', $yaml, $matches) !== 1) {
        fwrite(STDERR, "YAML is missing {$key}.\n");
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

function addPath(PharData $zip, string $root, string $path): void {
    if (!file_exists($path)) {
        return;
    }
    if (is_file($path)) {
        $zip->addFile($path, relativePath($root, $path));
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
            $zip->addFile($file->getPathname(), relativePath($root, $file->getPathname()));
        }
    }
}

function relativePath(string $root, string $path): string {
    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}
