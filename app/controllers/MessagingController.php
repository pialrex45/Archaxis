<?php
// Messaging System Controller for Ironroot
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

class MessagingController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    // Get user's conversations in a project
    public function getUserConversations($userId, $projectId = null) {
        try {
            $sql = "SELECT DISTINCT c.*, p.name as project_name,
                           (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id) as message_count,
                           (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message_time
                    FROM conversations c
                    JOIN conversation_participants cp ON c.id = cp.conversation_id
                    LEFT JOIN projects p ON c.project_id = p.id
                    WHERE cp.user_id = :user_id";
            
            $params = [':user_id' => $userId];
            
            if ($projectId) {
                $sql .= " AND c.project_id = :project_id";
                $params[':project_id'] = $projectId;
            }
            
            $sql .= " ORDER BY last_message_time DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get conversations: ' . $e->getMessage()];
        }
    }
    
    // Get project channels user can access
    public function getProjectChannels($userId, $projectId) {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            $userRole = $user['role'];
            
            $sql = "SELECT * FROM project_channels 
                    WHERE project_id = :project_id 
                    AND JSON_CONTAINS(allowed_roles, JSON_QUOTE(:user_role))
                    ORDER BY channel_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_role' => $userRole
            ]);
            
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get channels: ' . $e->getMessage()];
        }
    }
    
    // Get messages from a conversation
    public function getMessages($conversationId, $userId, $limit = 50, $offset = 0) {
        try {
            // Check if user is participant
            if (!$this->isParticipant($conversationId, $userId)) {
                return ['success' => false, 'message' => 'Access denied'];
            }
            
            $sql = "SELECT m.*, u.name as sender_name, u.role as sender_role
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.conversation_id = :conversation_id
                    ORDER BY m.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            
            // Update last read timestamp
            $this->updateLastRead($conversationId, $userId);
            
            return ['success' => true, 'data' => $messages];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get messages: ' . $e->getMessage()];
        }
    }
    
    // Send a message
    public function sendMessage($conversationId, $senderId, $messageText, $messageType = 'text', $filePath = null, $replyToId = null) {
        try {
            // Check if user is participant
            if (!$this->isParticipant($conversationId, $senderId)) {
                return ['success' => false, 'message' => 'Access denied'];
            }
            
            $sql = "INSERT INTO messages (conversation_id, sender_id, message_text, message_type, file_path, reply_to_id) 
                    VALUES (:conversation_id, :sender_id, :message_text, :message_type, :file_path, :reply_to_id)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':conversation_id' => $conversationId,
                ':sender_id' => $senderId,
                ':message_text' => $messageText,
                ':message_type' => $messageType,
                ':file_path' => $filePath,
                ':reply_to_id' => $replyToId
            ]);
            
            if ($result) {
                $messageId = $this->db->lastInsertId();
                return ['success' => true, 'message_id' => $messageId];
            }
            
            return ['success' => false, 'message' => 'Failed to send message'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()];
        }
    }
    
    // Create a new conversation
    public function createConversation($projectId, $name, $description, $createdBy, $participants = []) {
        try {
            $this->db->beginTransaction();
            
            // Create conversation
            $sql = "INSERT INTO conversations (project_id, name, description, created_by) 
                    VALUES (:project_id, :name, :description, :created_by)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':project_id' => $projectId,
                ':name' => $name,
                ':description' => $description,
                ':created_by' => $createdBy
            ]);
            
            $conversationId = $this->db->lastInsertId();
            
            // Add creator as participant
            $this->addParticipant($conversationId, $createdBy);
            
            // Add other participants
            foreach ($participants as $userId) {
                if ($userId != $createdBy) {
                    $this->addParticipant($conversationId, $userId);
                }
            }
            
            $this->db->commit();
            return ['success' => true, 'conversation_id' => $conversationId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to create conversation: ' . $e->getMessage()];
        }
    }
    
    // Get users in a project (for creating conversations)
    public function getProjectUsers($projectId) {
        try {
            $sql = "SELECT DISTINCT u.id, u.name, u.role, u.email
                    FROM users u
                    LEFT JOIN project_assignments pa ON u.id = pa.user_id
                    LEFT JOIN tasks t ON u.id = t.assigned_to
                    WHERE pa.project_id = :project_id OR t.project_id = :project_id OR u.role = 'admin'
                    ORDER BY u.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':project_id' => $projectId]);
            
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get project users: ' . $e->getMessage()];
        }
    }
    
    // Private helper methods
    private function isParticipant($conversationId, $userId) {
        $sql = "SELECT 1 FROM conversation_participants WHERE conversation_id = :conversation_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conversation_id' => $conversationId, ':user_id' => $userId]);
        return $stmt->fetchColumn() !== false;
    }
    
    private function addParticipant($conversationId, $userId) {
        $sql = "INSERT IGNORE INTO conversation_participants (conversation_id, user_id) VALUES (:conversation_id, :user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conversation_id' => $conversationId, ':user_id' => $userId]);
    }
    
    private function updateLastRead($conversationId, $userId) {
        $sql = "UPDATE conversation_participants SET last_read_at = NOW() 
                WHERE conversation_id = :conversation_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':conversation_id' => $conversationId, ':user_id' => $userId]);
    }
    
    private function getUserById($userId) {
        $sql = "SELECT id, name, role, email FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>