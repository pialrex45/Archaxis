<?php
// Message controller for handling messaging operations

require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class MessageController {
    private $messageModel;
    
    public function __construct() {
        $this->messageModel = new Message();
    }
    
    /**
     * Send a new message
     * 
     * @param array $data
     * @return array
     */
    public function send($data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input data
            $receiverId = isset($data['receiver_id']) ? (int)$data['receiver_id'] : 0;
            $messageText = isset($data['message_text']) ? sanitize($data['message_text']) : '';
            $replyTo = isset($data['reply_to']) ? (int)$data['reply_to'] : null;
            $filePath = isset($data['file_path']) ? sanitize($data['file_path']) : null;
            
            // Validation
            if (empty($receiverId)) {
                return ['success' => false, 'message' => 'Receiver ID is required'];
            }
            
            if (empty($messageText)) {
                return ['success' => false, 'message' => 'Message text is required'];
            }
            
            // Get sender ID
            $senderId = getCurrentUserId();
            
            // Check if trying to send message to self
            if ($senderId == $receiverId) {
                return ['success' => false, 'message' => 'Cannot send message to yourself'];
            }
            
            // Send message
            $result = $this->messageModel->send($senderId, $receiverId, $messageText, $replyTo, $filePath);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error sending message: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get message by ID
     * 
     * @param int $messageId
     * @return array
     */
    public function get($messageId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate message ID
            if (empty($messageId)) {
                return ['success' => false, 'message' => 'Message ID is required'];
            }
            
            // Get message
            $message = $this->messageModel->getById($messageId);
            
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }
            
            // Check if user has permission to view this message
            $userId = getCurrentUserId();
            
            if ($message['sender_id'] != $userId && $message['receiver_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to view this message'];
            }
            
            return [
                'success' => true,
                'data' => $message
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching message: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get conversation between two users
     * 
     * @param int $userId
     * @return array
     */
    public function getConversation($userId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate user ID
            if (empty($userId)) {
                return ['success' => false, 'message' => 'User ID is required'];
            }
            
            // Get current user ID
            $currentUserId = getCurrentUserId();
            
            // Get conversation
            $messages = $this->messageModel->getConversation($currentUserId, $userId);
            
            return [
                'success' => true,
                'data' => $messages ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching conversation: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all messages for current user
     * 
     * @return array
     */
    public function getMessages() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get current user ID
            $userId = getCurrentUserId();
            
            // Get messages
            $messages = $this->messageModel->getForUser($userId);
            
            return [
                'success' => true,
                'data' => $messages ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching messages: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get message threads for current user
     * 
     * @return array
     */
    public function getThreads() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get current user ID
            $userId = getCurrentUserId();
            
            // Get threads
            $threads = $this->messageModel->getThreads($userId);
            
            return [
                'success' => true,
                'data' => $threads ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching message threads: ' . $e->getMessage()];
        }
    }
    
    /**
     * Reply to a message
     * 
     * @param int $replyToId
     * @param array $data
     * @return array
     */
    public function reply($replyToId, $data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate reply to ID
            if (empty($replyToId)) {
                return ['success' => false, 'message' => 'Reply to ID is required'];
            }
            
            // Validate input data
            $messageText = isset($data['message_text']) ? sanitize($data['message_text']) : '';
            $filePath = isset($data['file_path']) ? sanitize($data['file_path']) : null;
            
            // Validation
            if (empty($messageText)) {
                return ['success' => false, 'message' => 'Message text is required'];
            }
            
            // Get sender ID
            $senderId = getCurrentUserId();
            
            // Reply to message
            $result = $this->messageModel->reply($replyToId, $senderId, $messageText, $filePath);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error replying to message: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete message
     * 
     * @param int $messageId
     * @return array
     */
    public function delete($messageId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate message ID
            if (empty($messageId)) {
                return ['success' => false, 'message' => 'Message ID is required'];
            }
            
            // Get existing message
            $message = $this->messageModel->getById($messageId);
            
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }
            
            // Check if user has permission to delete this message
            $userId = getCurrentUserId();
            
            if ($message['sender_id'] != $userId && $message['receiver_id'] != $userId) {
                return ['success' => false, 'message' => 'Insufficient permissions to delete this message'];
            }
            
            // Delete message
            $result = $this->messageModel->delete($messageId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting message: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark message as read
     * 
     * @param int $messageId
     * @return array
     */
    public function markAsRead($messageId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate message ID
            if (empty($messageId)) {
                return ['success' => false, 'message' => 'Message ID is required'];
            }
            
            // Get existing message
            $message = $this->messageModel->getById($messageId);
            
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }
            
            // Check if user is the receiver
            $userId = getCurrentUserId();
            
            if ($message['receiver_id'] != $userId) {
                return ['success' => false, 'message' => 'Only the receiver can mark this message as read'];
            }
            
            // In a real application, we would have a 'read' column in the messages table
            // For now, we'll just return success
            return [
                'success' => true,
                'message' => 'Message marked as read'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error marking message as read: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get unread message count for current user
     * 
     * @return array
     */
    public function getUnreadCount() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get current user ID
            $userId = getCurrentUserId();
            
            // In a real application, we would query for unread messages
            // For now, we'll just return 0
            return [
                'success' => true,
                'data' => [
                    'unread_count' => 0
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching unread count: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get messages since a specific timestamp for auto-refresh
     * 
     * @param string $since ISO 8601 timestamp
     * @return array
     */
    public function getMessagesSince($since) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate timestamp
            if (empty($since)) {
                return ['success' => false, 'message' => 'Timestamp is required'];
            }
            
            // Get current user ID
            $userId = getCurrentUserId();
            
            // Get messages since timestamp
            $messages = $this->messageModel->getMessagesSince($userId, $since);
            
            return [
                'success' => true,
                'data' => $messages ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching messages: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get message threads with unread count for current user
     * 
     * @return array
     */
    public function getThreadsWithUnreadCount() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get current user ID
            $userId = getCurrentUserId();
            
            // Get threads with unread count
            $threads = $this->messageModel->getThreadsWithUnreadCount($userId);
            
            return [
                'success' => true,
                'data' => $threads ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching message threads: ' . $e->getMessage()];
        }
    }
}