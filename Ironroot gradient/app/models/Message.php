<?php
// Message model for handling user messaging with attachments

require_once __DIR__ . '/../../config/database.php';

class Message {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Send a new message
     * 
     * @param int $senderId
     * @param int $receiverId
     * @param string $messageText
     * @param int $replyTo
     * @param string $filePath
     * @return array
     */
    public function send($senderId, $receiverId, $messageText, $replyTo = null, $filePath = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, message_text, reply_to, file_path, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([$senderId, $receiverId, $messageText, $replyTo, $filePath]);
            
            if ($result) {
                $messageId = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'message_id' => $messageId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to send message'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Message sending error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get message by ID
     * 
     * @param int $messageId
     * @return array|bool
     */
    public function getById($messageId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, s.name as sender_name, r.name as receiver_name 
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.receiver_id = r.id 
                WHERE m.id = ?
            ");
            $stmt->execute([$messageId]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get conversation between two users
     * 
     * @param int $userId1
     * @param int $userId2
     * @return array|bool
     */
    public function getConversation($userId1, $userId2) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, s.name as sender_name, r.name as receiver_name 
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.receiver_id = r.id 
                WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all messages for a user
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getForUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, s.name as sender_name, r.name as receiver_name 
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.receiver_id = r.id 
                WHERE m.receiver_id = ? OR m.sender_id = ?
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$userId, $userId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get message threads for a user
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getThreads($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.*, 
                    s.name as sender_name, 
                    r.name as receiver_name,
                    CASE 
                        WHEN m.sender_id = ? THEN r.id 
                        ELSE s.id 
                    END as other_user_id,
                    CASE 
                        WHEN m.sender_id = ? THEN r.name 
                        ELSE s.name 
                    END as other_user_name
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.receiver_id = r.id 
                WHERE m.id IN (
                    SELECT MAX(id) 
                    FROM messages 
                    WHERE sender_id = ? OR receiver_id = ? 
                    GROUP BY 
                        CASE 
                            WHEN sender_id = ? THEN receiver_id 
                            ELSE sender_id 
                        END,
                        CASE 
                            WHEN sender_id = ? THEN sender_id 
                            ELSE receiver_id 
                        END
                )
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Reply to a message
     * 
     * @param int $replyToId
     * @param int $senderId
     * @param string $messageText
     * @param string $filePath
     * @return array
     */
    public function reply($replyToId, $senderId, $messageText, $filePath = null) {
        try {
            // Get the original message to determine receiver
            $originalMessage = $this->getById($replyToId);
            
            if (!$originalMessage) {
                return ['success' => false, 'message' => 'Original message not found'];
            }
            
            // Determine receiver (the other party in the conversation)
            $receiverId = $originalMessage['sender_id'] == $senderId ? $originalMessage['receiver_id'] : $originalMessage['sender_id'];
            
            // Send the reply
            return $this->send($senderId, $receiverId, $messageText, $replyToId, $filePath);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Message reply error: ' . $e->getMessage()];
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
            $stmt = $this->pdo->prepare("DELETE FROM messages WHERE id = ?");
            $result = $stmt->execute([$messageId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Message deleted successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Message not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete message'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Message deletion error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get messages after a specific timestamp for auto-refresh
     * 
     * @param int $userId
     * @param string $since ISO 8601 timestamp
     * @return array|bool
     */
    public function getMessagesSince($userId, $since) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, s.name as sender_name, r.name as receiver_name 
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.receiver_id = r.id 
                WHERE (m.receiver_id = ? OR m.sender_id = ?) 
                AND m.created_at > ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$userId, $userId, $since]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get message threads for a user with unread count
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getThreadsWithUnreadCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.*, 
                    s.name as sender_name, 
                    r.name as receiver_name,
                    CASE 
                        WHEN m.sender_id = ? THEN r.id 
                        ELSE s.id 
                    END as other_user_id,
                    CASE 
                        WHEN m.sender_id = ? THEN r.name 
                        ELSE s.name 
                    END as other_user_name,
                    COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 END) as unread_count
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.receiver_id = r.id 
                WHERE m.id IN (
                    SELECT MAX(id) 
                    FROM messages 
                    WHERE sender_id = ? OR receiver_id = ? 
                    GROUP BY 
                        CASE 
                            WHEN sender_id = ? THEN receiver_id 
                            ELSE sender_id 
                        END,
                        CASE 
                            WHEN sender_id = ? THEN sender_id 
                            ELSE receiver_id 
                        END
                )
                GROUP BY m.id, s.name, r.name
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
}