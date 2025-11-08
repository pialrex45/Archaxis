<?php
// Load environment variables from .env-like files
function loadEnv($filePath) {
    if (!file_exists($filePath)) return;
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // comment
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }
}

// Prefer project .env, then .env.local as a fallback
$envRoot = __DIR__ . '/../';
loadEnv($envRoot . '.env');
loadEnv($envRoot . '.env.local');

// Define APP_KEY if not already defined
if (!defined('APP_KEY')) {
    define('APP_KEY', getenv('APP_KEY') ?: 'fallback_key_for_development');
}

// Helper function to get environment variables
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Handle boolean values
    switch (strtolower($value)) {
        case 'true':
            return true;
        case 'false':
            return false;
        case 'null':
            return null;
    }
    
    return $value;
}