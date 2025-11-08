<!DOCTYPE html>
<html>
<head>
    <title>Messaging System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Messaging System Database Setup</h1>
    
    <?php
    require_once '../config/database.php';
    
    try {
        echo "<h2>Creating Messaging System Tables...</h2>";
        
        $sql = file_get_contents('../database/migrations/create_messaging_system.sql');
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $table_name = '';
                    if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                        $table_name = $matches[1];
                        echo "<div class='success'>✓ Created table: {$table_name}</div>";
                    } else {
                        echo "<div class='success'>✓ Executed statement</div>";
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<div class='info'>ℹ Table already exists (skipping)</div>";
                    } else {
                        throw $e;
                    }
                }
            }
        }
        
        echo "<h2 class='success'>✓ Messaging System Setup Complete!</h2>";
        echo "<p>The following features are now available:</p>";
        echo "<ul>";
        echo "<li>Direct messaging between users</li>";
        echo "<li>Project-based group channels</li>";
        echo "<li>Role-based message permissions</li>";
        echo "<li>Real-time messaging widget</li>";
        echo "</ul>";
        
        // Verify tables were created
        echo "<h3>Verifying Tables:</h3>";
        $tables = ['conversations', 'conversation_participants', 'messages', 'project_channels'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✓ {$table} table exists</div>";
            } else {
                echo "<div class='error'>✗ {$table} table missing</div>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
        echo "<h3>Error Details:</h3>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    ?>
    
    <hr>
    <p><strong>Next Steps:</strong></p>
    <ul>
        <li>The messaging widget is now integrated into all authenticated pages</li>
        <li>Users can access messaging through the floating chat icon</li>
        <li>Project managers can create project channels</li>
        <li>All roles can send direct messages to each other</li>
    </ul>
    
    <p><a href="../">&larr; Return to Dashboard</a></p>
</body>
</html>