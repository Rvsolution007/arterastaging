<?php
/**
 * Server State Diagnostic Script
 * Run on both staging and production to compare environments.
 * Usage: https://your-domain.com/debug_server_state.php
 * 
 * DELETE THIS FILE AFTER DEBUGGING!
 */

header('Content-Type: application/json');

$results = [];

// ── 1. SERVER IDENTITY ──
$results['server'] = [
    'hostname' => gethostname(),
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s T'),
    'timezone' => date_default_timezone_get(),
];

// ── 2. GIT STATUS ──
$gitDir = dirname(__DIR__);
$gitCommit = trim(shell_exec("cd \"$gitDir\" && git rev-parse HEAD 2>&1") ?? 'unknown');
$gitBranch = trim(shell_exec("cd \"$gitDir\" && git branch --show-current 2>&1") ?? 'unknown');
$gitLog5 = trim(shell_exec("cd \"$gitDir\" && git log --oneline -5 2>&1") ?? 'unknown');
$gitDirty = trim(shell_exec("cd \"$gitDir\" && git status --porcelain 2>&1") ?? '');

$results['git'] = [
    'current_commit' => $gitCommit,
    'current_branch' => $gitBranch,
    'last_5_commits' => explode("\n", $gitLog5),
    'is_dirty' => !empty($gitDirty),
    'dirty_files' => empty($gitDirty) ? [] : explode("\n", $gitDirty),
];

// ── 3. KEY FILE CHECKSUMS ──
// Compare these between staging and production to see if code is identical
$keyFiles = [
    'app/Http/Controllers/Api/HomeApi.php',
    'app/Http/Controllers/Admin/TemplateBuilderController.php',
    'app/Http/Controllers/MainController.php',
    'config/app.php',
    'config/database.php',
    'config/filesystems.php',
    'routes/api.php',
    'routes/web.php',
    '.env',
];

$checksums = [];
foreach ($keyFiles as $file) {
    $fullPath = $gitDir . '/' . $file;
    if (file_exists($fullPath)) {
        $checksums[$file] = [
            'md5' => md5_file($fullPath),
            'size' => filesize($fullPath),
            'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
        ];
    } else {
        $checksums[$file] = 'FILE_NOT_FOUND';
    }
}
$results['file_checksums'] = $checksums;

// ── 4. .ENV COMPARISON (safe keys only, no passwords) ──
$envPath = $gitDir . '/.env';
$envSafe = [];
if (file_exists($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $safeKeys = [
        'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
        'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE',
        'CACHE_DRIVER', 'SESSION_DRIVER', 'QUEUE_CONNECTION',
        'FILESYSTEM_DISK', 'FILESYSTEM_DRIVER',
        'MAIL_MAILER',
        'STORAGE_TYPE',
    ];
    foreach ($envLines as $line) {
        if (strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && in_array(trim($parts[0]), $safeKeys)) {
            $envSafe[trim($parts[0])] = trim($parts[1]);
        }
    }
}
$results['env_config'] = $envSafe;

// ── 5. LARAVEL CACHE STATUS ──
try {
    require $gitDir . '/vendor/autoload.php';
    $app = require_once $gitDir . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Check if config is cached
    $results['laravel'] = [
        'config_cached' => file_exists($gitDir . '/bootstrap/cache/config.php'),
        'routes_cached' => file_exists($gitDir . '/bootstrap/cache/routes-v7.php') || file_exists($gitDir . '/bootstrap/cache/routes.php'),
        'views_cached' => is_dir($gitDir . '/storage/framework/views') ? count(glob($gitDir . '/storage/framework/views/*.php')) : 0,
        'app_env' => env('APP_ENV', 'unknown'),
        'app_debug' => env('APP_DEBUG', false),
        'app_url' => env('APP_URL', 'unknown'),
        'cache_driver' => env('CACHE_DRIVER', 'unknown'),
        'storage_type' => env('STORAGE_TYPE', env('FILESYSTEM_DISK', 'unknown')),
    ];
    
    // Check Redis connection
    try {
        $cacheDriver = env('CACHE_DRIVER', 'file');
        if ($cacheDriver === 'redis') {
            $redisOk = \Illuminate\Support\Facades\Redis::ping();
            $results['laravel']['redis_connected'] = true;
        } else {
            $results['laravel']['redis_connected'] = 'N/A (driver: ' . $cacheDriver . ')';
        }
    } catch (\Exception $e) {
        $results['laravel']['redis_connected'] = false;
        $results['laravel']['redis_error'] = $e->getMessage();
    }
    
    // Check DB connection
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $results['laravel']['db_connected'] = true;
        
        // Check specific tables exist
        $results['laravel']['tables'] = [
            'poster_makers' => \Illuminate\Support\Facades\Schema::hasTable('poster_makers'),
            'editor_templates' => \Illuminate\Support\Facades\Schema::hasTable('editor_templates'),
            'business_custom_frames' => \Illuminate\Support\Facades\Schema::hasTable('business_custom_frames'),
        ];
    } catch (\Exception $e) {
        $results['laravel']['db_connected'] = false;
        $results['laravel']['db_error'] = $e->getMessage();
    }
    
} catch (\Exception $e) {
    $results['laravel'] = [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ];
}

// ── 6. STORAGE PERMISSIONS ──
$storageDirs = [
    'storage/logs',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'public/uploads',
    'public/uploads/template',
];
$permissions = [];
foreach ($storageDirs as $dir) {
    $fullDir = $gitDir . '/' . $dir;
    $permissions[$dir] = [
        'exists' => is_dir($fullDir),
        'writable' => is_writable($fullDir),
        'permissions' => is_dir($fullDir) ? substr(sprintf('%o', fileperms($fullDir)), -4) : 'N/A',
    ];
}
$results['permissions'] = $permissions;

// ── 7. LATEST LARAVEL LOG ERRORS (last 50 lines) ──
$logFile = $gitDir . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    // Read last 10KB of log file
    $fp = fopen($logFile, 'r');
    $readBytes = min($logSize, 10240);
    fseek($fp, -$readBytes, SEEK_END);
    $logTail = fread($fp, $readBytes);
    fclose($fp);
    
    // Extract ERROR lines
    $logLines = explode("\n", $logTail);
    $errorLines = array_filter($logLines, function($line) {
        return strpos($line, '.ERROR:') !== false || strpos($line, 'ARTERA_DEBUG') !== false;
    });
    
    $results['recent_errors'] = [
        'log_file_size_bytes' => $logSize,
        'last_modified' => date('Y-m-d H:i:s', filemtime($logFile)),
        'recent_error_lines' => array_values(array_slice($errorLines, -20)),
    ];
} else {
    $results['recent_errors'] = 'No laravel.log file found';
}

// ── 8. TEMPLATE SAMPLE TEST ──
// Try to load a specific template to see if the pipeline works
try {
    if (isset($app)) {
        $kernel->handle(
            $request = Illuminate\Http\Request::capture()
        );
        
        // Test resolveTemplateJson by checking a sample poster
        $samplePoster = \App\Models\PosterMaker::first();
        if ($samplePoster) {
            $results['sample_test'] = [
                'poster_id' => $samplePoster->id,
                'zip_name' => $samplePoster->zip_name,
                'has_layers_json' => !empty($samplePoster->layers_json),
                'layers_json_type' => gettype($samplePoster->layers_json),
                'render_version' => $samplePoster->render_version ?? 'not_set',
            ];
            
            // Check if the template directory exists on disk
            $templateDir = $gitDir . '/public/uploads/template/' . $samplePoster->zip_name;
            $results['sample_test']['disk_dir_exists'] = is_dir($templateDir);
            if (is_dir($templateDir)) {
                $results['sample_test']['disk_contents'] = scandir($templateDir);
            }
            
            // Check if layers_json has valid structure
            if (!empty($samplePoster->layers_json)) {
                $parsed = is_string($samplePoster->layers_json) 
                    ? json_decode($samplePoster->layers_json, true) 
                    : $samplePoster->layers_json;
                $results['sample_test']['json_valid'] = ($parsed !== null);
                if ($parsed) {
                    $results['sample_test']['has_layers_key'] = isset($parsed['layers']);
                    $results['sample_test']['layer_count'] = isset($parsed['layers']) ? count($parsed['layers']) : 0;
                    $results['sample_test']['has_render_version'] = isset($parsed['render_version']);
                    $results['sample_test']['json_render_version'] = $parsed['render_version'] ?? 'not_set';
                }
            }
        } else {
            $results['sample_test'] = 'No PosterMaker records found';
        }
    }
} catch (\Exception $e) {
    $results['sample_test'] = [
        'error' => $e->getMessage(),
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (isset($_GET["action"]) && $_GET["action"] === "log") {
    $log = file_get_contents(storage_path("logs/laravel.log"));
    $lines = explode("\n", $log);
    $debugLines = array_filter($lines, function($line) { return strpos($line, "ARTERA_DEBUG") !== false; });
    echo implode("\n", array_slice($debugLines, -100));
    exit;
}


if (isset($_GET["action"]) && $_GET["action"] === "test_frame") {
    $dir = public_path("uploads/template/Frame_PEWA_1111_2/skins/Frame_PEWA_1111_2");
    $files = glob($dir . "/*");
    $res = [];
    foreach($files as $f) {
        $res[basename($f)] = [
            "type" => filetype($f),
            "is_link" => is_link($f),
            "perms" => substr(sprintf("%o", fileperms($f)), -4),
            "owner" => fileowner($f)
        ];
    }
    echo json_encode($res, JSON_PRETTY_PRINT);
    exit;
}

