<?php
// Project model for managing construction projects

require_once __DIR__ . '/../../config/database.php';

class Project {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Create a new project
     * 
     * @param string $name
     * @param string $description
     * @param int $ownerId
     * @param string $status
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function create($name, $description, $ownerId, $status = 'planning', $startDate = null, $endDate = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO projects (name, description, owner_id, status, start_date, end_date, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $result = $stmt->execute([$name, $description, $ownerId, $status, $startDate, $endDate]);
            
            if ($result) {
                $projectId = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Project created successfully',
                    'project_id' => $projectId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create project'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Project creation error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get project by ID
     * 
     * @param int $projectId
     * @return array|bool
     */
    public function getById($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.name as owner_name 
                FROM projects p 
                JOIN users u ON p.owner_id = u.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all projects
     * 
     * @return array|bool
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       u.name as owner_name,
                       sm.name as site_manager_name
                FROM projects p 
                LEFT JOIN users u ON p.owner_id = u.id 
                LEFT JOIN users sm ON p.site_manager_id = sm.id
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get projects by owner
     * 
     * @param int $ownerId
     * @return array|bool
     */
    public function getByOwner($ownerId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.name as owner_name 
                FROM projects p 
                JOIN users u ON p.owner_id = u.id 
                WHERE p.owner_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$ownerId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get projects for a manager: owner or site manager, optionally via project_assignments
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getByManager($userId) {
        // Base select with joins
        $base = "
            SELECT p.*, 
                   u.name as owner_name,
                   sm.name as site_manager_name
            FROM projects p
            JOIN users u ON p.owner_id = u.id
            LEFT JOIN users sm ON p.site_manager_id = sm.id
        ";
        // Try including project_assignments if available
        try {
            $sql = $base . "
                LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = :uid
                WHERE (p.owner_id = :uid OR p.site_manager_id = :uid OR pa.user_id IS NOT NULL)
                ORDER BY p.created_at DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':uid'=>$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Fallback without project_assignments
            try {
                $sql = $base . "
                    WHERE (p.owner_id = :uid OR p.site_manager_id = :uid)
                    ORDER BY p.created_at DESC
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':uid'=>$userId]);
                return $stmt->fetchAll();
            } catch (PDOException $e2) {
                return false;
            }
        }
    }
    
    /**
     * Update project
     * 
     * @param int $projectId
     * @param array $data
     * @return array
     */
    public function update($projectId, $data) {
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
            
            $values[] = $projectId; // Add project ID for WHERE clause
            
            $sql = "UPDATE projects SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Project updated successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'No changes made to project'];
            }
            
            return ['success' => false, 'message' => 'Failed to update project'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Project update error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete project
     * 
     * @param int $projectId
     * @return array
     */
    public function delete($projectId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM projects WHERE id = ?");
            $result = $stmt->execute([$projectId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Project deleted successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete project'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Project deletion error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Assign project to user
     * 
     * @param int $projectId
     * @param int $userId
     * @return array
     */
    public function assign($projectId, $userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE projects SET owner_id = ? WHERE id = ?");
            $result = $stmt->execute([$userId, $projectId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Project assigned successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to assign project'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Project assignment error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update project status
     * 
     * @param int $projectId
     * @param string $status
     * @return array
     */
    public function updateStatus($projectId, $status) {
        try {
            $stmt = $this->pdo->prepare("UPDATE projects SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $projectId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Project status updated successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to update project status'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Project status update error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Assign site manager to project
     * 
     * @param int $projectId
     * @param int $siteManagerId
     * @return array
     */
    public function assignSiteManager($projectId, $siteManagerId) {
        try {
            // Get site manager name first
            $stmtUser = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtUser->execute([$siteManagerId]);
            $siteManagerName = $stmtUser->fetchColumn();
            
            // Schema uses 'id' as the primary key for projects
            $stmt = $this->pdo->prepare("UPDATE projects SET site_manager_id = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$siteManagerId, $projectId]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Also add entry to project_assignments table if it exists
                try {
                    $stmt = $this->pdo->prepare("INSERT INTO project_assignments (project_id, user_id, role, assigned_at) 
                                             VALUES (?, ?, 'site_manager', NOW()) 
                                             ON DUPLICATE KEY UPDATE role = 'site_manager', assigned_at = NOW()");
                    $stmt->execute([$projectId, $siteManagerId]);
                } catch (PDOException $e) {
                    // Silently ignore if project_assignments table doesn't exist
                }
                
                return [
                    'success' => true, 
                    'message' => 'Site manager assigned successfully',
                    'project_id' => $projectId,
                    'site_manager_id' => $siteManagerId,
                    'site_manager_name' => $siteManagerName
                ];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Project not found or no changes made'];
            }
            
            return ['success' => false, 'message' => 'Failed to assign site manager'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Site manager assignment error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all site managers
     * 
     * @return array|bool
     */
    public function getSiteManagers() {
        try {
            // Debug: Output the SQL query for troubleshooting
            $sql = "
                SELECT id, name, email 
                FROM users 
                WHERE role = 'site_manager'
                ORDER BY name ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug: Output the result
            error_log("Site managers query result: " . print_r($result, true));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error fetching site managers: " . $e->getMessage());
            return false;
        }
    }
}