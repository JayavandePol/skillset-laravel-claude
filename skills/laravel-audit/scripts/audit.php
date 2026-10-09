<?php

declare(strict_types=1);

/**
 * Laravel skill-library audit — static checks for rules from the laravel-* skills.
 *
 * Usage:
 *   php audit.php [project-path] [--format=md|json] [--min=info|warning|error]
 *
 * No dependencies; read-only. Heuristic (regex) checks: every finding needs a human/agent look
 * before fixing. Exit code 1 when at least one "error" finding exists.
 */

const SEVERITY_ORDER = ['info' => 0, 'warning' => 1, 'error' => 2];

$args = array_slice($argv, 1);
$root = getcwd();
$format = 'md';
$min = 'info';

foreach ($args as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    } elseif (str_starts_with($arg, '--min=')) {
        $min = substr($arg, 6);
    } elseif (! str_starts_with($arg, '--')) {
        $root = $arg;
    }
}

$root = rtrim(realpath($root) ?: $root, '/\\');

if (! is_file($root.'/artisan') || ! is_file($root.'/composer.json')) {
    fwrite(STDERR, "Not a Laravel project (no artisan/composer.json): {$root}\n");
    exit(2);
}

$findings = [];

function add(array &$findings, string $severity, string $rule, string $message, ?string $file = null, ?int $line = null): void
{
    $findings[] = compact('severity', 'rule', 'message', 'file', 'line');
}

function rel(string $root, string $path): string
{
    return str_replace('\\', '/', ltrim(substr($path, strlen($root)), '/\\'));
}

/** @return list<string> */
function files(string $root, string $dir, array $extensions): array
{
    $base = $root.'/'.$dir;
    if (! is_dir($base)) {
        return [];
    }

    $skip = ['vendor', 'node_modules', 'storage', '.git', 'build', 'cache', 'ssr'];
    $result = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            fn (SplFileInfo $f) => ! ($f->isDir() && in_array($f->getFilename(), $skip, true)),
        ),
    );

    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        foreach ($extensions as $ext) {
            if (str_ends_with($file->getFilename(), $ext)) {
                $result[] = $file->getPathname();
                break;
            }
        }
    }

    sort($result);

    return $result;
}

/** Scan each line of each file with a regex; report matches. */
function scan(array &$findings, string $root, array $paths, string $pattern, string $severity, string $rule, string $message, ?string $unless = null): void
{
    foreach ($paths as $path) {
        $lines = @file($path) ?: [];
        foreach ($lines as $i => $text) {
            $trim = ltrim($text);
            if (str_starts_with($trim, '//') || str_starts_with($trim, '*') || str_starts_with($trim, '#')) {
                continue;
            }
            if (preg_match($pattern, $text) && ($unless === null || ! preg_match($unless, $text))) {
                add($findings, $severity, $rule, $message, rel($root, $path), $i + 1);
            }
        }
    }
}

// ---------------------------------------------------------------- composer / tooling
$composer = json_decode((string) file_get_contents($root.'/composer.json'), true) ?: [];
$require = $composer['require'] ?? [];
$requireDev = $composer['require-dev'] ?? [];
$all = $require + $requireDev;

$framework = $require['laravel/framework'] ?? 'unknown';
$phpConstraint = $require['php'] ?? 'unknown';
add($findings, 'info', 'INFO', "laravel/framework {$framework}, php {$phpConstraint}", 'composer.json');

if (preg_match('/\^?(\d+)/', $framework, $m) && (int) $m[1] < 12) {
    add($findings, 'warning', 'STD-00', "Laravel {$m[1]} — consider upgrading (12 or 13 are supported in 2026).", 'composer.json');
}

foreach (['laravel/pint' => 'STD-60', 'larastan/larastan' => 'STD-61', 'pestphp/pest' => 'TST-01'] as $package => $rule) {
    if (! isset($all[$package])) {
        add($findings, 'warning', $rule, "{$package} is not installed.", 'composer.json');
    }
}
if (! isset($all['rector/rector'])) {
    add($findings, 'info', 'STD-62', 'rector/rector not installed (recommended for upgrades and dead-code removal).', 'composer.json');
}
foreach (['laravel/telescope', 'barryvdh/laravel-debugbar', 'barryvdh/laravel-ide-helper', 'laravel/boost'] as $devOnly) {
    if (isset($require[$devOnly])) {
        add($findings, 'error', 'SEC-60', "{$devOnly} is in \"require\" — move it to \"require-dev\".", 'composer.json');
    }
}
if (isset($require['laravel/breeze']) || isset($requireDev['laravel/breeze'])) {
    add($findings, 'info', 'AUTH-01', 'Laravel Breeze detected — legacy scaffolding; see laravel-auth/references/legacy-breeze-jetstream.md.', 'composer.json');
}
if (isset($require['laravel/jetstream'])) {
    add($findings, 'info', 'AUTH-01', 'Laravel Jetstream detected — no longer actively developed; check compatibility before framework upgrades.', 'composer.json');
}

$scripts = $composer['scripts'] ?? [];
if (! isset($scripts['check'])) {
    add($findings, 'info', 'STD-65', 'No "check" composer script (lint + analyse + test).', 'composer.json');
}

if (! is_file($root.'/pint.json')) {
    add($findings, 'info', 'STD-60', 'No pint.json — default preset only (template in laravel-standards/assets).');
}
if (! is_file($root.'/phpstan.neon') && ! is_file($root.'/phpstan.neon.dist')) {
    add($findings, 'warning', 'STD-61', 'No phpstan.neon(.dist) — static analysis not configured.');
}
if (! is_file($root.'/tests/ArchTest.php') && ! is_file($root.'/tests/Arch.php') && ! is_dir($root.'/tests/Arch')) {
    add($findings, 'warning', 'STD-63', 'No architecture tests (tests/ArchTest.php).');
}
if (! is_file($root.'/CLAUDE.md') && ! is_file($root.'/AGENTS.md')) {
    add($findings, 'info', 'STD-66', 'No CLAUDE.md/AGENTS.md with project conventions.');
}
if (! is_dir($root.'/.github/workflows') && ! is_file($root.'/.gitlab-ci.yml') && ! is_file($root.'/bitbucket-pipelines.yml')) {
    add($findings, 'warning', 'DEP-10', 'No CI pipeline found.');
}

// ---------------------------------------------------------------- git: committed secrets
if (is_dir($root.'/.git')) {
    $tracked = @shell_exec('git -C '.escapeshellarg($root).' ls-files .env .env.production .env.local 2>'.(PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'));
    if (is_string($tracked) && trim($tracked) !== '') {
        add($findings, 'error', 'SEC-61', 'Environment file(s) tracked in git: '.str_replace("\n", ', ', trim($tracked)));
    }
}

// ---------------------------------------------------------------- PHP source checks
$appPhp = files($root, 'app', ['.php']);
$phpForStrict = array_merge($appPhp, files($root, 'routes', ['.php']), files($root, 'database', ['.php']), files($root, 'tests', ['.php']));
$nonConfigPhp = array_merge($appPhp, files($root, 'routes', ['.php']), files($root, 'database', ['.php']), files($root, 'resources/views', ['.php']));

foreach ($phpForStrict as $path) {
    $head = (string) @file_get_contents($path, false, null, 0, 400);
    if (! str_contains($head, 'strict_types=1')) {
        add($findings, 'warning', 'STD-10', 'Missing declare(strict_types=1).', rel($root, $path), 1);
    }
}

scan($findings, $root, $nonConfigPhp, '/(?<![\w>:$])env\s*\(/', 'error', 'STD-20', 'env() outside config/ returns null when config is cached — use config().');
scan($findings, $root, array_merge($appPhp, files($root, 'routes', ['.php'])), '/(?<![\w>:$])(dd|dump|ray|var_dump|print_r)\s*\(|\bdie\s*[;(]|\bexit\s*[;(]/', 'error', 'STD-14', 'Debug/exit statement in committed code.');
scan($findings, $root, $appPhp, '/(\$request|request\(\))\s*->\s*all\s*\(\s*\)/', 'warning', 'SEC-02', 'Request->all() — pass validated() / a DTO instead (check it never reaches create/update/fill).');
scan($findings, $root, $appPhp, '/(->|::)\s*(create|update|fill|forceFill|forceCreate|insert|updateOrCreate|firstOrCreate|make)\s*\(\s*(\$request|request\(\))\s*->\s*(all|input|except|post)\s*\(/', 'error', 'SEC-02', 'Unvalidated request data passed to a model write (mass assignment).');
scan($findings, $root, $appPhp, '/Model::unguard\s*\(|protected\s+\$guarded\s*=\s*\[\s*\]/', 'error', 'SEC-04', 'Mass-assignment protection disabled.');
scan($findings, $root, $appPhp, '/(whereRaw|orWhereRaw|selectRaw|orderByRaw|havingRaw|groupByRaw|DB::raw|DB::statement|DB::select|DB::unprepared|DB::update|DB::delete|DB::insert)\s*\(\s*"[^"]*\{?\$/', 'error', 'SEC-21', 'Variable interpolated into raw SQL — use bindings.');
scan($findings, $root, $appPhp, '/(whereRaw|orWhereRaw|selectRaw|orderByRaw|havingRaw|DB::raw|DB::statement|DB::select|DB::unprepared)\s*\([^)]*\.\s*\$/', 'error', 'SEC-21', 'String concatenation into raw SQL — use bindings.');
scan($findings, $root, $appPhp, '/->\s*(orderBy|orderByDesc|groupBy|select|addSelect|where|whereColumn)\s*\(\s*(\$request\s*->|request\s*\()/', 'warning', 'SEC-21', 'Column name taken from request — allowlist it.');
scan($findings, $root, $appPhp, '/(?<![\w>:$])(eval|unserialize|shell_exec|passthru|system|proc_open|popen)\s*\(|(?<![\w>:$])exec\s*\(/', 'warning', 'SEC-23', 'Dangerous function — verify no user input reaches it.');
scan($findings, $root, $appPhp, '/redirect\s*\(\s*(\$request\s*->|request\s*\()/', 'warning', 'SEC-22', 'Redirect target from request — possible open redirect.');
scan($findings, $root, files($root, 'app/Http/Controllers', ['.php']), '/\bDB::/', 'warning', 'STD-02', 'DB facade in a controller — move to an Action/query object.');
scan($findings, $root, files($root, 'app/Http/Controllers', ['.php']), '/->(validate)\s*\(\s*\[/', 'info', 'SEC-01', 'Inline validation in controller — prefer a FormRequest.');

// Livewire: id-like public properties without #[Locked]
foreach (array_merge(files($root, 'app/Livewire', ['.php']), files($root, 'resources/views', ['.blade.php'])) as $path) {
    $lines = @file($path) ?: [];
    foreach ($lines as $i => $text) {
        if (preg_match('/public\s+\??(int|string)\s+\$(\w*[iI]d|\w*_id|price\w*|amount\w*|role\w*|tenant\w*)\b/', $text, $m, PREG_OFFSET_CAPTURE)) {
            $prev = $lines[$i - 1] ?? '';
            $before = substr($text, 0, $m[0][1]);
            if (! str_contains($prev, 'Locked') && ! str_contains($before, 'Locked')) {
                add($findings, 'warning', 'SEC-13', 'Livewire public property looks sensitive and is not #[Locked].', rel($root, $path), $i + 1);
            }
        }
    }
}

// Policies for models
$models = array_map(fn ($p) => basename($p, '.php'), files($root, 'app/Models', ['.php']));
$models = array_filter($models, fn ($m) => ! in_array($m, ['User'], true) && ! str_contains($m, 'Concerns'));
foreach ($models as $model) {
    if (! is_file("{$root}/app/Policies/{$model}Policy.php")) {
        add($findings, 'info', 'SEC-11', "No {$model}Policy — required if {$model} is exposed to users.", "app/Models/{$model}.php");
    }
}

// Models without fillable/guarded
foreach (files($root, 'app/Models', ['.php']) as $path) {
    $code = (string) file_get_contents($path);
    if (str_contains($code, 'extends Model') || str_contains($code, 'extends Authenticatable') || str_contains($code, 'extends Pivot')) {
        if (! preg_match('/\$fillable|\$guarded|#\[Fillable|#\[Guarded|Unguarded/', $code)) {
            add($findings, 'info', 'DB-11', 'Model defines neither $fillable nor $guarded (Laravel guards all by default — fine if never mass-assigned).', rel($root, $path));
        }
    }
}

// ---------------------------------------------------------------- migrations
foreach (files($root, 'database/migrations', ['.php']) as $path) {
    $code = (string) file_get_contents($path);
    // split into statements
    foreach (preg_split('/;\s*\n/', $code) ?: [] as $statement) {
        if (preg_match('/->foreign(Id|Ulid|Uuid)\(\s*[\'"](\w+)[\'"]/', $statement, $m)) {
            $line = substr_count(substr($code, 0, (int) strpos($code, $statement)), "\n") + 1;
            if (! str_contains($statement, 'constrained') && ! str_contains($statement, '->references(')) {
                add($findings, 'warning', 'DB-01', "Foreign key column '{$m[2]}' without constrained() — no FK constraint.", rel($root, $path), $line);
            } elseif (! preg_match('/(cascadeOnDelete|nullOnDelete|restrictOnDelete|noActionOnDelete|onDelete\()/', $statement)) {
                add($findings, 'info', 'DB-01', "Foreign key '{$m[2]}' has no explicit delete strategy.", rel($root, $path), $line);
            }
        }
    }
}

// ---------------------------------------------------------------- views / frontend
scan($findings, $root, files($root, 'resources/views', ['.blade.php']), '/\{!!\s*(?!(__|trans|trans_choice|csrf_field|method_field|\$slot|\$attributes|Vite::|json_encode)\b)/', 'warning', 'SEC-20', 'Unescaped Blade output — only for sanitised HTML.');
scan($findings, $root, files($root, 'resources/js', ['.tsx', '.jsx', '.ts', '.js']), '/dangerouslySetInnerHTML/', 'warning', 'SEC-20', 'dangerouslySetInnerHTML — only for server-sanitised HTML.');
scan($findings, $root, files($root, 'resources/js', ['.vue']), '/\sv-html=/', 'warning', 'SEC-20', 'v-html — only for server-sanitised HTML.');
scan($findings, $root, files($root, 'resources/js', ['.svelte']), '/\{@html/', 'warning', 'SEC-20', '{@html} — only for server-sanitised HTML.');
scan($findings, $root, files($root, 'resources/js', ['.tsx', '.ts', '.vue', '.svelte']), '/:\s*any\b|as\s+any\b|<any>/', 'info', 'FE-11', 'TypeScript any.');
scan($findings, $root, files($root, 'resources/js', ['.tsx', '.jsx', '.vue', '.svelte']), '/href=\{?["\'`]\/(?!\/)[\w\-\/]+["\'`]/', 'info', 'FE-03', 'Hard-coded internal URL — use Wayfinder/route helpers.');

// ---------------------------------------------------------------- config & bootstrap
$bootstrap = (string) @file_get_contents($root.'/bootstrap/app.php');
if ($bootstrap !== '') {
    if (preg_match('/validateCsrfTokens\s*\(\s*except:\s*\[([^\]]*)\]/s', $bootstrap, $m)) {
        add($findings, 'info', 'SEC-30', 'CSRF exclusions: '.trim(preg_replace('/\s+/', ' ', $m[1])).' — each must verify a signature.', 'bootstrap/app.php');
    }
    if (! str_contains($bootstrap, 'trustProxies')) {
        add($findings, 'info', 'DEP-05', 'trustProxies not configured — needed behind Dokploy/Traefik/NPM/load balancers.', 'bootstrap/app.php');
    }
}

$providers = (string) @file_get_contents($root.'/app/Providers/AppServiceProvider.php');
if ($providers !== '') {
    if (! preg_match('/shouldBeStrict|preventLazyLoading/', $providers)) {
        add($findings, 'warning', 'DB-10', 'Model strictness / lazy-loading prevention not enabled.', 'app/Providers/AppServiceProvider.php');
    }
    if (! str_contains($providers, 'Password::defaults')) {
        add($findings, 'info', 'SEC-40', 'Password::defaults() not configured.', 'app/Providers/AppServiceProvider.php');
    }
}

$envExample = (string) @file_get_contents($root.'/.env.example');
if ($envExample === '') {
    add($findings, 'warning', 'STD-21', 'No .env.example.');
} elseif (preg_match('/^(\w*(SECRET|PASSWORD|KEY|TOKEN)\w*)=(?!\s*$|null|""|\$\{|secret|password|changeme|your-)(\S{12,})/mi', $envExample, $m)) {
    add($findings, 'warning', 'STD-21', "Possible real secret in .env.example ({$m[1]}).", '.env.example');
}

$session = (string) @file_get_contents($root.'/config/session.php');
if ($session !== '' && preg_match("/'secure'\s*=>\s*false/", $session)) {
    add($findings, 'error', 'SEC-31', "Session 'secure' hard-coded to false.", 'config/session.php');
}

// ---------------------------------------------------------------- tests presence
$featureTests = count(files($root, 'tests/Feature', ['.php']));
$browserTests = count(files($root, 'tests/Browser', ['.php']));
if ($featureTests === 0) {
    add($findings, 'error', 'TST-01', 'No feature tests.');
}
if ($browserTests === 0) {
    add($findings, 'info', 'TST-04', 'No browser tests (tests/Browser).');
}

// ---------------------------------------------------------------- output
$findings = array_values(array_filter($findings, fn ($f) => SEVERITY_ORDER[$f['severity']] >= (SEVERITY_ORDER[$min] ?? 0)));
usort($findings, fn ($a, $b) => [SEVERITY_ORDER[$b['severity']], $a['rule'], $a['file'] ?? '', $a['line'] ?? 0] <=> [SEVERITY_ORDER[$a['severity']], $b['rule'], $b['file'] ?? '', $b['line'] ?? 0]);

$counts = ['error' => 0, 'warning' => 0, 'info' => 0];
foreach ($findings as $f) {
    $counts[$f['severity']]++;
}

if ($format === 'json') {
    echo json_encode(['root' => $root, 'counts' => $counts, 'findings' => $findings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
} else {
    echo "# Laravel audit — {$root}\n\n";
    echo "Errors: {$counts['error']} · Warnings: {$counts['warning']} · Info: {$counts['info']}\n\n";
    echo "| Severity | Rule | Location | Finding |\n|---|---|---|---|\n";
    foreach ($findings as $f) {
        $loc = $f['file'] ? $f['file'].($f['line'] ? ':'.$f['line'] : '') : '—';
        echo "| {$f['severity']} | {$f['rule']} | `{$loc}` | ".str_replace('|', '\|', $f['message'])." |\n";
    }
    echo "\nHeuristic checks — verify each finding before fixing. Rule details: laravel-* skills.\n";
}

exit($counts['error'] > 0 ? 1 : 0);
