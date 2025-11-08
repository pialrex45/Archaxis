<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/controllers/MessageController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    // Get current user ID
    $userId = getCurrentUserId();
    
    // Get query parameters
    $threadId = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;
    $since = isset($_GET['since']) ? $_GET['since'] : null;
    
    $messageController = new MessageController();
    
    // If thread_id is provided, fetch messages for that thread
    if ($threadId > 0) {
        $result = $messageController->getConversation($threadId);
        
        if ($result['success']) {
            // Format messages for response
            $messages = [];
            foreach ($result['data'] as $message) {
                $messages[] = [
                    'id' => (int)$message['id'],
                    'sender_id' => (int)$message['sender_id'],
                    'sender_name' => $message['sender_name'],
                    'receiver_id' => (int)$message['receiver_id'],
                    'receiver_name' => $message['receiver_name'],
                    'message_text' => $message['message_text'],
                    'file_path' => $message['file_path'],
                    'created_at' => $message['created_at']
                ];
            }
            
            jsonResponse([
                'success' => true,
                'data' => $messages
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => $result['message']], 400);
        }
    } else {
        // Fetch message threads for the user
        $result = $messageController->getThreads();
        
        if ($result['success']) {
            // Format threads for response
            $threads = [];
            foreach ($result['data'] as $thread) {
                $threads[] = [
                    'id' => (int)$thread['id'],
                    'sender_id' => (int)$thread['sender_id'],
                    'receiver_id' => (int)$thread['receiver_id'],
                    'other_user_id' => (int)$thread['other_user_id'],
                    'other_user_name' => $thread['other_user_name'],
                    'message_text' => $thread['message_text'],
                    'file_path' => $thread['file_path'],
                    'created_at' => $thread['created_at']
                ];
            }
            
            jsonResponse([
                'success' => true,
                'data' => $threads
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => $result['message']], 400);
        }
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error fetching messages: ' . $e->getMessage()], 500);
}