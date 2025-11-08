<?php
// Simple PHP test file
echo "PHP is working!<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP version: " . phpversion() . "<br>";
echo "Current directory: " . __DIR__ . "<br>";

// Test if required files exist
$required_files = [
    '/app/core/auth.php',
    '/app/core/helpers.php',
    '/app/views/layouts/header.php',
    '/app/views/layouts/footer.php'
];

echo "<h3>File Check:</h3>";
foreach ($required_files as $file) {
    $full_path = __DIR__ . $file;
    $exists = file_exists($full_path);
    echo $file . ": " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "<br>";
}

// Test if we can include auth.php
echo "<h3>Include Test:</h3>";
try {
    require_once __DIR__ . '/app/core/auth.php';
    echo "✅ auth.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading auth.php: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/app/core/helpers.php';
    echo "✅ helpers.php loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading helpers.php: " . $e->getMessage() . "<br>";
}
?>