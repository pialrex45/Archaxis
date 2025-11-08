<?php
// Database Connection Test and Fix
echo "<h2>Database Connection Diagnostic</h2>";

// Test different connection configurations
$configs = [
    ['localhost', 'Construction_management', 'root', ''],
    ['127.0.0.1', 'Construction_management', 'root', ''],
    ['localhost', 'construction_management', 'root', ''],
    ['127.0.0.1', 'construction_management', 'root', ''],
];

foreach ($configs as $i => $config) {
    list($host, $dbname, $user, $pass) = $config;
    echo "<h4>Test " . ($i + 1) . ": $host / $dbname</h4>";
    
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ MySQL connection successful!<br>";
        
        // Check if database exists
        $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Database '$dbname' exists!<br>";
            
            // Connect to the specific database
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            
            // Check tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "✓ Connected to database! Tables found: " . implode(', ', $tables) . "<br>";
            
            // Check users table specifically
            if (in_array('users', $tables)) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                $count = $stmt->fetchColumn();
                echo "✓ Users table has $count records<br>";
            }
            
            // Check messages table
            if (in_array('messages', $tables)) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
                $count = $stmt->fetchColumn();
                echo "✓ Messages table has $count records<br>";
            }
            
            echo "<div class='alert alert-success'>This configuration works! Using it...</div>";
            
            // Save working config to a file
            $configContent = "<?php
// Working database configuration
\$db_host = '$host';
\$db_name = '$dbname';
\$db_user = '$user';
\$db_pass = '$pass';

try {
    \$pdo = new PDO(\"mysql:host=\$db_host;dbname=\$db_name;charset=utf8\", \$db_user, \$db_pass);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}
?>";
            
            file_put_contents('config_working.php', $configContent);
            echo "✓ Saved working config to config_working.php<br>";
            
            break; // Use this config
            
        } else {
            echo "✗ Database '$dbname' does not exist<br>";
            
            // Try to create the database
            echo "Attempting to create database...<br>";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ Database '$dbname' created!<br>";
        }
        
    } catch (PDOException $e) {
        echo "✗ Connection failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

// If we have a working config, create sample data
if (file_exists('config_working.php')) {
    include 'config_working.php';
    
    echo "<h3>Creating Sample Data</h3>";
    
    // Create users table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','supervisor','client','project_manager','site_manager','site_engineer','logistic_officer','sub_contractor') DEFAULT 'supervisor',
        approved TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create messages table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    )");
    
    echo "✓ Tables created<br>";
    
    // Insert sample users
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
    
    $users = [
        [1, 'John Admin', 'john@ironroot.com', password_hash('password', PASSWORD_DEFAULT), 'admin'],
        [2, 'Jane Supervisor', 'jane@ironroot.com', password_hash('password', PASSWORD_DEFAULT), 'supervisor'],
        [3, 'Bob Manager', 'bob@ironroot.com', password_hash('password', PASSWORD_DEFAULT), 'project_manager'],
        [4, 'Alice Client', 'alice@ironroot.com', password_hash('password', PASSWORD_DEFAULT), 'client'],
        [5, 'Mike Engineer', 'mike@ironroot.com', password_hash('password', PASSWORD_DEFAULT), 'site_engineer'],
    ];
    
    foreach ($users as $user) {
        $stmt->execute($user);
        echo "✓ Created user: {$user[1]} ({$user[4]})<br>";
    }
    
    // Insert sample messages
    $stmt = $pdo->prepare("INSERT IGNORE INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
    
    $messages = [
        [1, 2, "Hello Jane! How's the project going?"],
        [2, 1, "Hi John! Everything is on track. The team is working well."],
        [1, 3, "Bob, can you review the project timeline for me?"],
        [3, 1, "Sure John, I'll take a look at it today."],
        [2, 4, "Alice, the materials have arrived. Ready for inspection."],
        [4, 2, "Great! I'll be there this afternoon to check them."],
    ];
    
    foreach ($messages as $msg) {
        $stmt->execute($msg);
        echo "✓ Created message from user {$msg[0]} to user {$msg[1]}<br>";
    }
    
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $message_count = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✓ Setup Complete!</h4>";
    echo "Total users: $user_count<br>";
    echo "Total messages: $message_count<br>";
    echo "<a href='simple_messaging_fixed.php' class='btn btn-primary mt-2'>Go to Messaging System</a>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup & Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <div class="card">
        <div class="card-body">
            <!-- PHP output appears above -->
        </div>
    </div>
</body>
</html>