<?php
// Modern Messaging API - Simple version in messages directory
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/core/auth.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_users':
        if ($method === 'GET') {
            listUsers($user_id);
        }
        break;
        
    case 'conversations':
        if ($method === 'GET') {
            getConversations($user_id);
        }
        break;
        
    case 'get_messages':
        if ($method === 'GET') {
            getMessages($_GET['conversation_id'] ?? 0, $_GET['type'] ?? 'direct', $user_id);
        }
        break;
        
    case 'send_message':
        if ($method === 'POST') {
            sendMessage($user_id);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action: ' . $action]);
}

function listUsers($user_id) {
    global $pdo;
    
    try {
        $sql = "SELECT id, name, email, role FROM users WHERE id != ? ORDER BY name LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['users' => $users, 'total' => count($users)]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error loading users: ' . $e->getMessage()]);
    }
}

function getConversations($user_id) {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    u.id as other_user_id,
                    u.name as display_name,
                    u.role,
                    'direct' as type,
                    CONCAT('user_', u.id) as conversation_id,
                    0 as unread_count,
                    (SELECT m.message_text 
                     FROM messages m 
                     WHERE ((m.sender_id = ? AND m.receiver_id = u.id) OR (m.sender_id = u.id AND m.receiver_id = ?))
                     ORDER BY m.created_at DESC 
                     LIMIT 1) as last_message,
                    (SELECT m.created_at 
                     FROM messages m 
                     WHERE ((m.sender_id = ? AND m.receiver_id = u.id) OR (m.sender_id = u.id AND m.receiver_id = ?))
                     ORDER BY m.created_at DESC 
                     LIMIT 1) as last_message_time
                FROM users u
                WHERE u.id != ? 
                ORDER BY 
                    CASE WHEN last_message_time IS NULL THEN 1 ELSE 0 END,
                    last_message_time DESC,
                    u.name ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['conversations' => $conversations]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getMessages($conversation_id, $type, $user_id) {
    global $pdo;
    
    try {
        if ($type === 'direct') {
            $other_user_id = (int)str_replace('user_', '', $conversation_id);
            
            $sql = "SELECT 
                        m.id,
                        m.message_text,
                        m.file_path,
                        m.reply_to,
                        m.created_at,
                        m.sender_id,
                        u.name as sender_name,
                        u.role
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                    ORDER BY m.created_at ASC
                    LIMIT 100";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $messages = [];
        }
        
        echo json_encode(['messages' => $messages]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function sendMessage($user_id) {
    global $pdo;
    
    $conversation_id = $_POST['conversation_id'] ?? '';
    $type = $_POST['type'] ?? 'direct';
    $message_text = trim($_POST['message_text'] ?? '');
    
    if (empty($message_text) || empty($conversation_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message text and conversation ID required']);
        return;
    }
    
    try {
        if ($type === 'direct') {
            $receiver_id = (int)str_replace('user_', '', $conversation_id);
            
            $sql = "INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $receiver_id, $message_text]);
            $message_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message_id' => $message_id,
                'message' => 'Message sent successfully'
            ]);
        } else {
            echo json_encode(['error' => 'Project messaging not implemented yet']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
    }
}
?>