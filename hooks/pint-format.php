<?php

declare(strict_types=1);

/*
 * PostToolUse hook: run the project's Laravel Pint on a PHP file Claude just edited.
 * Reads the hook payload from stdin. Always exits 0 so it never blocks Claude.
 * Disable with the environment variable LARAVEL_SKILLS_NO_PINT=1.
 */

if (getenv('LARAVEL_SKILLS_NO_PINT') === '1') {
    exit(0);
}

$payload = json_decode((string) stream_get_contents(STDIN), true);
$file = $payload['tool_input']['file_path'] ?? null;

if (! is_string($file) || ! str_ends_with($file, '.php') || ! is_file($file)) {
    exit(0);
}

// Walk up to the Laravel project root (artisan + vendor/bin/pint).
$dir = dirname(realpath($file) ?: $file);
$root = null;
for ($i = 0; $i < 12 && $dir !== dirname($dir); $i++, $dir = dirname($dir)) {
    if (is_file($dir.'/artisan') && (is_file($dir.'/vendor/bin/pint') || is_file($dir.'/vendor/laravel/pint/builds/pint'))) {
        $root = $dir;
        break;
    }
}

if ($root === null) {
    exit(0);
}

// Skip vendor and generated paths.
$relative = str_replace('\\', '/', substr(realpath($file) ?: $file, strlen($root) + 1));
foreach (['vendor/', 'storage/', 'bootstrap/cache/', 'node_modules/'] as $skip) {
    if (str_starts_with($relative, $skip)) {
        exit(0);
    }
}

$pint = is_file($root.'/vendor/laravel/pint/builds/pint')
    ? $root.'/vendor/laravel/pint/builds/pint'
    : $root.'/vendor/bin/pint';

$command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($pint).' --quiet '.escapeshellarg($file);
$descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$process = proc_open($command, $descriptor, $pipes, $root);

if (is_resource($process)) {
    stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
}

exit(0);
