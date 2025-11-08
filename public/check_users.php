<?php
require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT id, email, role FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available user accounts:\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']} | Email: {$user['email']} | Role: {$user['role']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>