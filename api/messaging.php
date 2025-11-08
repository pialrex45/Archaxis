<?php
// Modern Messaging API Controller - Adapted for existing database structure
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
    case 'conversations':
        if ($method === 'GET') {
            getConversations($user_id);
        }
        break;
        
    case 'messages':
        if ($method === 'GET') {
            getMessages($_GET['conversation_id'] ?? 0, $_GET['type'] ?? 'direct', $user_id);
        } elseif ($method === 'POST') {
            sendMessage($user_id);
        }
        break;
        
    case 'send':
        if ($method === 'POST') {
            sendMessage($user_id);
        }
        break;
        
    case 'mark_read':
        if ($method === 'POST') {
            markMessagesRead($_POST['conversation_id'] ?? 0, $_POST['type'] ?? 'direct', $user_id);
        }
        break;
        
    case 'test':
        if ($method === 'GET') {
            echo json_encode([
                'status' => 'API is working',
                'user_id' => $user_id,
                'user_role' => $user_role,
                'time' => date('Y-m-d H:i:s')
            ]);
        }
        break;
        
    case 'debug':
        if ($method === 'GET') {
            debugInfo($user_id);
        }
        break;
        
    case 'users':
        if ($method === 'GET') {
            getAvailableUsers($user_id, $user_role);
        }
        break;
        
    case 'projects':
        if ($method === 'GET') {
            getUserProjects($user_id, $user_role);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function getConversations($user_id) {
    global $pdo;
    
    try {
        // For simplicity, let's get all users except current user as potential conversations
        // This will show available people to message even if no messages exist yet
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
            // Extract other user ID from conversation_id (format: user_123)
            $other_user_id = (int)str_replace('user_', '', $conversation_id);
            
            $sql = "SELECT 
                        m.id,
                        m.message_text,
                        m.file_path,
                        m.reply_to,
                        m.created_at,
                        m.sender_id,
                        u.name as sender_name,
                        u.role,
                        0 as read_count
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                    ORDER BY m.created_at ASC
                    LIMIT 100";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            
        } else if ($type === 'project') {
            // Extract project ID from conversation_id (format: project_123)
            $project_id = (int)str_replace('project_', '', $conversation_id);
            
            $sql = "SELECT 
                        pm.id,
                        pm.body as message_text,
                        pm.channel,
                        pm.created_at,
                        pm.sender_id,
                        u.name as sender_name,
                        u.role,
                        0 as read_count
                    FROM project_messages pm
                    JOIN users u ON pm.sender_id = u.id
                    WHERE pm.project_id = ?
                    ORDER BY pm.created_at ASC
                    LIMIT 100";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$project_id]);
        }
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
    $reply_to_id = $_POST['reply_to_id'] ?? null;
    
    if (empty($message_text) || empty($conversation_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message text and conversation ID required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        if ($type === 'direct') {
            // Extract other user ID
            $receiver_id = (int)str_replace('user_', '', $conversation_id);
            
            // Insert direct message
            $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, reply_to) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $receiver_id, $message_text, $reply_to_id]);
            $message_id = $pdo->lastInsertId();
            
            // Create message status for receiver if table exists
            try {
                $status_sql = "INSERT INTO message_status (message_id, user_id, status) VALUES (?, ?, 'delivered')";
                $status_stmt = $pdo->prepare($status_sql);
                $status_stmt->execute([$message_id, $receiver_id]);
            } catch (Exception $e) {
                // Message status table might not exist, continue without it
            }
            
        } else if ($type === 'project') {
            // Extract project ID
            $project_id = (int)str_replace('project_', '', $conversation_id);
            
            // Insert project message
            $sql = "INSERT INTO project_messages (project_id, sender_id, body, channel) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$project_id, $user_id, $message_text, null]);
            $message_id = $pdo->lastInsertId();
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message_id' => $message_id,
            'message' => 'Message sent successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
    }
}

function getAvailableUsers($user_id, $user_role) {
    global $pdo;
    
    try {
        // First try a simple query to see if basic users loading works
        $sql = "SELECT id, name, email, role FROM users WHERE id != ? ORDER BY name LIMIT 20";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add project info in a separate query to avoid complex JOINs for now
        foreach ($users as &$user) {
            try {
                $project_sql = "SELECT DISTINCT p.name 
                               FROM projects p 
                               WHERE p.owner_id = ? OR p.id IN (
                                   SELECT t.project_id FROM tasks t WHERE t.assigned_to = ?
                               ) 
                               LIMIT 3";
                $project_stmt = $pdo->prepare($project_sql);
                $project_stmt->execute([$user['id'], $user['id']]);
                $projects = $project_stmt->fetchAll(PDO::FETCH_COLUMN);
                $user['project_names'] = implode(', ', $projects);
            } catch (Exception $e) {
                $user['project_names'] = '';
            }
        }
        
        echo json_encode(['users' => $users, 'total' => count($users)]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error loading users: ' . $e->getMessage()]);
    }
}

function getUserProjects($user_id, $user_role) {
    global $pdo;
    
    $sql = "SELECT DISTINCT p.id, p.name, p.status
            FROM projects p
            WHERE p.owner_id = ? 
            OR p.id IN (
                SELECT DISTINCT t.project_id 
                FROM tasks t 
                WHERE t.assigned_to = ?
            )
            ORDER BY p.name";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['projects' => $projects]);
}

function markMessagesRead($conversation_id, $type, $user_id) {
    global $pdo;
    
    try {
        if ($type === 'direct') {
            $other_user_id = (int)str_replace('user_', '', $conversation_id);
            
            $sql = "UPDATE message_status ms
                    JOIN messages m ON ms.message_id = m.id
                    SET ms.status = 'read'
                    WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                    AND ms.user_id = ? AND ms.status != 'read'";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$other_user_id, $user_id, $user_id, $other_user_id, $user_id]);
        }
    } catch (Exception $e) {
        // Message status table might not exist, continue without error
    }
    
    echo json_encode(['success' => true]);
}

function debugInfo($user_id) {
    global $pdo;
    
    try {
        // Check current user
        $user_sql = "SELECT id, name, email, role FROM users WHERE id = ?";
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([$user_id]);
        $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check all users
        $users_sql = "SELECT id, name, email, role FROM users LIMIT 10";
        $users_stmt = $pdo->prepare($users_sql);
        $users_stmt->execute();
        $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check messages
        $messages_sql = "SELECT COUNT(*) as count FROM messages";
        $messages_stmt = $pdo->prepare($messages_sql);
        $messages_stmt->execute();
        $message_count = $messages_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'current_user' => $current_user,
            'all_users' => $all_users,
            'message_count' => $message_count,
            'user_id_from_session' => $user_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Debug error: ' . $e->getMessage()]);
    }
}
?>