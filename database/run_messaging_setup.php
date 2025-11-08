<?php
// Run messaging system setup
require_once __DIR__ . '/../config/database.php';

try {
    // Read and execute the messaging setup SQL
    $sql = file_get_contents(__DIR__ . '/setup_messaging.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "Running messaging system setup...\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Messaging system setup completed successfully!\n";
    echo "✓ Created tables: conversations, conversation_participants, messages, message_status\n";
    echo "✓ Added sample project conversations\n";
    
} catch (Exception $e) {
    echo "✗ Error setting up messaging system: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>