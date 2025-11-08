<?php
// Working Messaging API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Start session and set up basic auth simulation
session_start();

// For testing, simulate logged in user if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
}

try {
    require_once __DIR__ . '/../../config/database.php';
    
    $user_id = $_SESSION['user_id'];
    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($action) {
        case 'users':
            if ($method === 'GET') {
                getUsers($pdo, $user_id);
            }
            break;
            
        case 'conversations':
            if ($method === 'GET') {
                getConversations($pdo, $user_id);
            }
            break;
            
        case 'messages':
            if ($method === 'GET') {
                getMessages($pdo, $_GET['conversation_id'] ?? 0, $user_id);
            }
            break;
            
        case 'send':
            if ($method === 'POST') {
                sendMessage($pdo, $user_id);
            }
            break;
            
        case 'debug':
            debugInfo($pdo, $user_id);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

function getUsers($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id != ? ORDER BY name");
        $stmt->execute([$user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load users: ' . $e->getMessage()]);
    }
}

function getConversations($pdo, $user_id) {
    try {
        // For simplicity, get recent message partners as conversations
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END as other_user_id,
                u.name as display_name,
                u.role,
                MAX(m.created_at) as last_message_time,
                SUBSTRING(MAX(CONCAT(m.created_at, '|', m.message_text)), 20) as last_message
            FROM messages m
            JOIN users u ON (
                (m.sender_id = ? AND u.id = m.receiver_id) OR 
                (m.receiver_id = ? AND u.id = m.sender_id)
            )
            WHERE m.sender_id = ? OR m.receiver_id = ?
            GROUP BY other_user_id, u.name, u.role
            ORDER BY last_message_time DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add conversation IDs
        foreach ($conversations as &$conv) {
            $conv['id'] = 'user_' . $conv['other_user_id'];
            $conv['name'] = $conv['display_name'];
        }
        
        echo json_encode([
            'success' => true,
            'conversations' => $conversations
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load conversations: ' . $e->getMessage()]);
    }
}

function getMessages($pdo, $conversation_id, $user_id) {
    try {
        // Extract user ID from conversation_id (format: user_123)
        $other_user_id = (int)str_replace('user_', '', $conversation_id);
        
        $stmt = $pdo->prepare("
            SELECT 
                m.id,
                m.sender_id,
                m.message_text,
                m.created_at,
                u.name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE 
                (m.sender_id = ? AND m.receiver_id = ?) OR
                (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load messages: ' . $e->getMessage()]);
    }
}

function sendMessage($pdo, $user_id) {
    try {
        $conversation_id = $_POST['conversation_id'] ?? '';
        $recipient_id = $_POST['recipient_id'] ?? '';
        $message_text = $_POST['message_text'] ?? '';
        
        if (empty($message_text)) {
            throw new Exception('Message text is required');
        }
        
        // Determine recipient
        if (!empty($conversation_id) && strpos($conversation_id, 'user_') === 0) {
            $recipient_id = (int)str_replace('user_', '', $conversation_id);
        } elseif (!empty($recipient_id)) {
            $recipient_id = (int)$recipient_id;
        } else {
            throw new Exception('Recipient is required');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message_text, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $recipient_id, $message_text]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully',
            'message_id' => $pdo->lastInsertId()
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
    }
}

function debugInfo($pdo, $user_id) {
    try {
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch()) {
            $tables[] = $row[0];
        }
        
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $message_count = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        
        echo json_encode([
            'debug' => true,
            'user_id' => $user_id,
            'tables' => $tables,
            'user_count' => $user_count,
            'message_count' => $message_count,
            'session' => $_SESSION
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Debug failed: ' . $e->getMessage()]);
    }
}
?>