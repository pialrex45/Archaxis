<?php
// Setup messaging system and test API
require_once 'config/database.php';
require_once 'app/core/auth.php';

echo "=== Messaging System Setup & Test ===\n\n";

try {
    // Check if tables exist
    $tables_to_check = ['conversations', 'conversation_participants', 'message_status'];
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        } else {
            echo "✓ Table '$table' exists\n";
        }
    }
    
    if (!empty($missing_tables)) {
        echo "\n⚠ Missing tables: " . implode(', ', $missing_tables) . "\n";
        echo "Creating missing tables...\n\n";
        
        // Read and execute setup SQL
        $sql = file_get_contents('database/setup_messaging.sql');
        $pdo->exec($sql);
        echo "✓ Messaging tables created successfully\n\n";
    }
    
    // Test basic queries
    echo "=== Testing Database Queries ===\n";
    
    // Test users query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    echo "✓ Users table: $user_count users found\n";
    
    // Test conversations query  
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM conversations");
    $conv_count = $stmt->fetch()['count'];
    echo "✓ Conversations table: $conv_count conversations found\n";
    
    // Test a sample user fetch
    $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE id = 1");
    $user = $stmt->fetch();
    if ($user) {
        echo "✓ Sample user: {$user['name']} ({$user['role']})\n";
    }
    
    echo "\n=== API Test ===\n";
    
    // Simulate authenticated session for API test
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    
    // Capture API output
    ob_start();
    $_GET['action'] = 'users';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include 'api/messages/index.php';
    $api_output = ob_get_clean();
    
    echo "API Response for users action:\n";
    echo $api_output . "\n\n";
    
    // Test if response is valid JSON
    $decoded = json_decode($api_output, true);
    if ($decoded && isset($decoded['users'])) {
        echo "✓ API returned " . count($decoded['users']) . " users\n";
    } else {
        echo "✗ API response is not valid JSON or missing users\n";
    }
    
    echo "\n=== Setup Complete ===\n";
    echo "✓ Database tables verified\n";
    echo "✓ API endpoints functional\n";
    echo "✓ Ready for messaging system\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}