<?php
// Error checking version
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "PHP working: " . phpversion() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";

// Test database connection
try {
    require_once 'config/database.php';
    echo "Database connected successfully<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Users in database: " . $count . "<br>";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Test API file
if (file_exists('api/messaging_working.php')) {
    echo "API file exists<br>";
} else {
    echo "API file NOT found<br>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Error Check</title>
    <script>
    function testClick() {
        alert('JavaScript is working!');
        console.log('Click test successful');
    }
    
    console.log('JavaScript loaded');
    </script>
</head>
<body>
    <h1>Error Check Page</h1>
    <button onclick="testClick()">Test JavaScript</button>
    
    <br><br>
    
    <button onclick="window.location.href='/Ironroot/api/messaging_working.php?action=debug'">
        Test API Direct
    </button>
</body>
</html>