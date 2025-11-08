<?php
// Setup sample data for messaging test
require_once 'config/database.php';

try {
    echo "Setting up messaging test data...\n\n";
    
    // Check if users exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    echo "Current users in database: $user_count\n";
    
    if ($user_count < 2) {
        echo "Creating sample users...\n";
        
        $users = [
            ['John Doe', 'john@test.com', 'admin'],
            ['Jane Smith', 'jane@test.com', 'supervisor'],
            ['Bob Wilson', 'bob@test.com', 'project_manager'],
            ['Alice Brown', 'alice@test.com', 'client']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, approved) VALUES (?, ?, ?, ?, 1)");
        
        foreach ($users as $user) {
            $stmt->execute([$user[0], $user[1], password_hash('password123', PASSWORD_DEFAULT), $user[2]]);
            echo "Created user: {$user[0]} ({$user[2]})\n";
        }
    }
    
    // Check if messages exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
    $message_count = $stmt->fetchColumn();
    echo "Current messages in database: $message_count\n";
    
    if ($message_count < 5) {
        echo "Creating sample messages...\n";
        
        $messages = [
            [1, 2, "Hello Jane! How's the project going?"],
            [2, 1, "Hi John! Everything is on track. The team is working hard."],
            [1, 3, "Bob, can you review the project timeline?"],
            [3, 1, "Sure, I'll take a look at it today."],
            [2, 4, "Alice, the materials have arrived. Ready for inspection."]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, created_at) VALUES (?, ?, ?, NOW())");
        
        foreach ($messages as $msg) {
            $stmt->execute($msg);
            echo "Created message from user {$msg[0]} to user {$msg[1]}\n";
        }
    }
    
    echo "\n✓ Database setup complete!\n";
    echo "✓ Users: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
    echo "✓ Messages: " . $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn() . "\n";
    
    echo "\nYou can now test the messaging system.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>