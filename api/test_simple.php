<?php
// Simple API test endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

try {
    // Test database connection
    require_once __DIR__ . '/../../config/database.php';
    
    $action = $_GET['action'] ?? 'test';
    
    switch ($action) {
        case 'test':
            echo json_encode([
                'status' => 'success',
                'message' => 'API is working',
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION
            ]);
            break;
            
        case 'db_test':
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch()['count'];
            echo json_encode([
                'status' => 'success',
                'message' => 'Database connection working',
                'user_count' => $count
            ]);
            break;
            
        case 'users_simple':
            $stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY name LIMIT 10");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'status' => 'success',
                'users' => $users,
                'count' => count($users)
            ]);
            break;
            
        default:
            echo json_encode(['error' => 'Unknown action: ' . $action]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>