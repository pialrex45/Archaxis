<?php
// Simple database setup for messaging
try {
    $pdo = new PDO("mysql:host=localhost;dbname=Construction_management;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Database Setup</h3>";
    
    // Check if users exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    echo "Users in database: $user_count<br>";
    
    if ($user_count < 3) {
        echo "Creating sample users...<br>";
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, password_hash, role, approved) VALUES (?, ?, ?, ?, ?, 1)");
        
        $users = [
            [1, 'John Admin', 'john@test.com', password_hash('password', PASSWORD_DEFAULT), 'admin'],
            [2, 'Jane Supervisor', 'jane@test.com', password_hash('password', PASSWORD_DEFAULT), 'supervisor'],
            [3, 'Bob Manager', 'bob@test.com', password_hash('password', PASSWORD_DEFAULT), 'project_manager'],
            [4, 'Alice Client', 'alice@test.com', password_hash('password', PASSWORD_DEFAULT), 'client']
        ];
        
        foreach ($users as $user) {
            $stmt->execute($user);
            echo "Created: {$user[1]} ({$user[4]})<br>";
        }
    }
    
    // Check messages table
    $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
    $message_count = $stmt->fetchColumn();
    echo "Messages in database: $message_count<br>";
    
    if ($message_count < 3) {
        echo "Creating sample messages...<br>";
        
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, created_at) VALUES (?, ?, ?, NOW())");
        
        $messages = [
            [1, 2, "Hello Jane! How is the project going?"],
            [2, 1, "Hi John! Everything is on track."],
            [1, 3, "Bob, can you check the timeline?"],
            [3, 1, "Sure, I'll review it today."]
        ];
        
        foreach ($messages as $msg) {
            $stmt->execute($msg);
            echo "Created message from user {$msg[0]} to user {$msg[1]}<br>";
        }
    }
    
    echo "<br><strong>✓ Setup complete!</strong><br>";
    echo "Total users: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "<br>";
    echo "Total messages: " . $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn() . "<br>";
    
    echo "<br><a href='simple_messaging_working.php' class='btn btn-primary'>Go to Messaging System</a>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card">
        <div class="card-body">
            <!-- PHP output appears here -->
        </div>
    </div>
</body>
</html>