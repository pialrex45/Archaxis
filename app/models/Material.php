<?php
// Material model for managing construction materials

require_once __DIR__ . '/../../config/database.php';

class Material {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Request a material
     * 
     * @param int $projectId
     * @param int $requestedBy
     * @param string $materialName
     * @param int $quantity
     * @param string $status
     * @return array
     */
    public function request($projectId, $requestedBy, $materialName, $quantity, $status = 'requested') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO materials (project_id, requested_by, material_name, quantity, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$projectId, $requestedBy, $materialName, $quantity, $status]);
            
            if ($result) {
                $materialId = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Material request submitted successfully',
                    'material_id' => $materialId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to submit material request'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Material request error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get material by ID
     * 
     * @param int $materialId
     * @return array|bool
     */
    public function getById($materialId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, p.name as project_name, u.name as requested_by_name 
                FROM materials m 
                JOIN projects p ON m.project_id = p.id 
                JOIN users u ON m.requested_by = u.id 
                WHERE m.id = ?
            ");
            $stmt->execute([$materialId]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all materials
     * 
     * @return array|bool
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, p.name as project_name, u.name as requested_by_name 
                FROM materials m 
                JOIN projects p ON m.project_id = p.id 
                JOIN users u ON m.requested_by = u.id 
                ORDER BY m.created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get materials by project
     * 
     * @param int $projectId
     * @return array|bool
     */
    public function getByProject($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, p.name as project_name, u.name as requested_by_name 
                FROM materials m 
                JOIN projects p ON m.project_id = p.id 
                JOIN users u ON m.requested_by = u.id 
                WHERE m.project_id = ?
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$projectId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get materials requested by user
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getRequestedBy($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, p.name as project_name, u.name as requested_by_name 
                FROM materials m 
                JOIN projects p ON m.project_id = p.id 
                JOIN users u ON m.requested_by = u.id 
                WHERE m.requested_by = ?
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get materials by status
     * 
     * @param string $status
     * @return array|bool
     */
    public function getByStatus($status) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, p.name as project_name, u.name as requested_by_name 
                FROM materials m 
                JOIN projects p ON m.project_id = p.id 
                JOIN users u ON m.requested_by = u.id 
                WHERE m.status = ?
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$status]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update material request
     * 
     * @param int $materialId
     * @param array $data
     * @return array
     */
    public function update($materialId, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id') { // Don't update the ID
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return ['success' => false, 'message' => 'No data to update'];
            }
            
            $values[] = $materialId; // Add material ID for WHERE clause
            
            $sql = "UPDATE materials SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Material request updated successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'No changes made to material request'];
            }
            
            return ['success' => false, 'message' => 'Failed to update material request'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Material update error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Approve material request
     * 
     * @param int $materialId
     * @return array
     */
    public function approve($materialId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE materials SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$materialId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Material request approved successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Material request not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to approve material request'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Material approval error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Reject material request
     * 
     * @param int $materialId
     * @return array
     */
    public function reject($materialId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE materials SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$materialId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Material request rejected successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Material request not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to reject material request'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Material rejection error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark material as ordered
     * 
     * @param int $materialId
     * @return array
     */
    public function markOrdered($materialId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE materials SET status = 'ordered', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$materialId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Material marked as ordered successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Material request not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to mark material as ordered'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Material ordered error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark material as delivered
     * 
     * @param int $materialId
     * @return array
     */
    public function markDelivered($materialId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE materials SET status = 'delivered', updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$materialId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Material marked as delivered successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Material request not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to mark material as delivered'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Material delivery error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete material request
     * 
     * @param int $materialId
     * @return array
     */
    public function delete($materialId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM materials WHERE id = ?");
            $result = $stmt->execute([$materialId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Material request deleted successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Material request not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete material request'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Material deletion error: ' . $e->getMessage()];
        }
    }
}