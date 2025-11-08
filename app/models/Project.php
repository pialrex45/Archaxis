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
        // Attempt primary query including potential site_manager_id join
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
            // Fallback if site_manager_id (or sm join) not valid in this schema version
            try {
                    // Detect if project_assignments exists first
                    $hasAssignments = false;
                    try { $chk = $this->pdo->query("SHOW TABLES LIKE 'project_assignments'"); $hasAssignments = $chk && $chk->rowCount() > 0; } catch (PDOException $ignore) { $hasAssignments = false; }
                    if ($hasAssignments) {
                        $sql = "SELECT p.*, u.name AS owner_name,
                                    (SELECT uu.name FROM project_assignments pa JOIN users uu ON pa.user_id = uu.id
                                      WHERE pa.project_id = p.id AND pa.role = 'site_manager' LIMIT 1) AS site_manager_name,
                                    (SELECT pa.user_id FROM project_assignments pa WHERE pa.project_id = p.id AND pa.role='site_manager' LIMIT 1) AS site_manager_id
                                FROM projects p
                                LEFT JOIN users u ON p.owner_id = u.id
                                ORDER BY p.created_at DESC";
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute();
                        return $stmt->fetchAll();
                    } else {
                        $stmt = $this->pdo->prepare("SELECT p.*, u.name AS owner_name FROM projects p LEFT JOIN users u ON p.owner_id = u.id ORDER BY p.created_at DESC");
                        $stmt->execute();
                        return $stmt->fetchAll();
                    }
            } catch (PDOException $e2) {
                return false;
            }
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
     * Get projects visible to a client (currently identical to ownership)
     * Separate method allows future expansion (e.g., shared projects)
     * @param int $clientId
     * @return array|bool
     */
    public function getForClient($clientId) {
        return $this->getByOwner($clientId);
    }

    /**
     * Get projects for a manager: owner or site manager, optionally via project_assignments
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getByManager($userId) {
        // Full attempt: includes site_manager and project_assignments scope
        $baseFull = "
            SELECT p.*, 
                   u.name as owner_name,
                   sm.name as site_manager_name
            FROM projects p
            JOIN users u ON p.owner_id = u.id
            LEFT JOIN users sm ON p.site_manager_id = sm.id
        ";
        try {
            $sql = $baseFull . "
                LEFT JOIN project_assignments pa ON pa.project_id = p.id AND pa.user_id = :uid
                WHERE (p.owner_id = :uid OR p.site_manager_id = :uid OR pa.user_id IS NOT NULL)
                ORDER BY p.created_at DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':uid'=>$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Fallback 1: still assume site_manager_id exists but no project_assignments
            try {
                $sql = $baseFull . "
                    WHERE (p.owner_id = :uid OR p.site_manager_id = :uid)
                    ORDER BY p.created_at DESC
                ";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':uid'=>$userId]);
                return $stmt->fetchAll();
            } catch (PDOException $e2) {
                // Fallback 2: legacy schema without site_manager_id -> just return owned projects
                try {
                    $sql = "
                        SELECT p.*, u.name as owner_name
                        FROM projects p
                        JOIN users u ON p.owner_id = u.id
                        WHERE p.owner_id = :uid
                        ORDER BY p.created_at DESC
                    ";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([':uid'=>$userId]);
                    return $stmt->fetchAll();
                } catch (PDOException $e3) {
                    return false;
                }
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
            // Validate project exists first
            $projStmt = $this->pdo->prepare("SELECT id FROM projects WHERE id = ? LIMIT 1");
            $projStmt->execute([$projectId]);
            if ($projStmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Project not found'];
            }

            // Get site manager name first
            $stmtUser = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmtUser->execute([$siteManagerId]);
            $siteManagerName = $stmtUser->fetchColumn();
            if (!$siteManagerName) {
                return ['success' => false, 'message' => 'Site Manager user not found'];
            }
                $hasColumn = false;
                try {
                    $colStmt = $this->pdo->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'");
                    $hasColumn = ($colStmt && $colStmt->rowCount() > 0);
                } catch (PDOException $eCol) { $hasColumn = false; }

                $updated = false;
                if ($hasColumn) {
                    try {
                        $stmt = $this->pdo->prepare("UPDATE projects SET site_manager_id = ?, updated_at = NOW() WHERE id = ?");
                        $updated = $stmt->execute([$siteManagerId, $projectId]);
                    } catch (PDOException $eUpdate) {
                        // Ignore column failure; fallback to assignments table only
                    }
                }

                // Always attempt assignment entry (table may or may not exist)
                $assignmentInserted = false;
                try {
                    // Ensure table exists (defensive if migrations not yet run)
                    $this->pdo->exec("CREATE TABLE IF NOT EXISTS project_assignments (\n                        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,\n                        project_id INT UNSIGNED NOT NULL,\n                        user_id INT UNSIGNED NOT NULL,\n                        role VARCHAR(50) NOT NULL,\n                        assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n                        UNIQUE KEY uniq_project_user_role (project_id, user_id, role),\n                        KEY idx_project (project_id),\n                        KEY idx_user (user_id),\n                        KEY idx_role (role)\n                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $stmtA = $this->pdo->prepare("INSERT INTO project_assignments (project_id, user_id, role, assigned_at) VALUES (?, ?, 'site_manager', NOW()) ON DUPLICATE KEY UPDATE role='site_manager', assigned_at=NOW()");
                    $assignmentInserted = $stmtA->execute([$projectId,$siteManagerId]);
                } catch (PDOException $eA) { /* ignore */ }

                if ($updated || $assignmentInserted) {
                    return [
                        'success' => true,
                        'message' => 'Site manager assigned successfully',
                        'project_id' => $projectId,
                        'site_manager_id' => $siteManagerId,
                        'site_manager_name' => $siteManagerName
                    ];
                }

                return ['success' => false, 'message' => 'Assignment failed (no update performed)'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Site manager assignment error: ' . $e->getMessage()];
        }
    }

    /**
     * Add or update a manager rating aggregate for a project without new table.
     * Stores cumulative total and count in projects table (adds columns if missing).
     * @param int $projectId
     * @param int $rating (1-5)
     * @return bool
     */
    public function addManagerRating($projectId, $rating){
        $projectId=(int)$projectId; $rating=(int)$rating; if($projectId<=0||$rating<1||$rating>5) return false;
        // Ensure columns exist (idempotent ALTER checks)
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM projects LIKE 'manager_rating_total'");
            if(!$cols || $cols->rowCount()===0){
                $this->pdo->exec("ALTER TABLE projects ADD COLUMN manager_rating_total INT UNSIGNED NOT NULL DEFAULT 0, ADD COLUMN manager_rating_count INT UNSIGNED NOT NULL DEFAULT 0");
            } else {
                $cnt = $this->pdo->query("SHOW COLUMNS FROM projects LIKE 'manager_rating_count'");
                if(!$cnt || $cnt->rowCount()===0){
                    $this->pdo->exec("ALTER TABLE projects ADD COLUMN manager_rating_count INT UNSIGNED NOT NULL DEFAULT 0");
                }
            }
        } catch (Throwable $e){ /* ignore - best effort */ }
        // Update aggregate
        $sql="UPDATE projects SET manager_rating_total = manager_rating_total + :r, manager_rating_count = manager_rating_count + 1, updated_at=NOW() WHERE id=:id";
        $st=$this->pdo->prepare($sql); return $st->execute([':r'=>$rating, ':id'=>$projectId]);
    }

    /**
     * Assign a manager (site_manager) to a project ensuring columns/tables exist.
     */
    public function assignProjectManager($projectId,$managerUserId){
        $projectId=(int)$projectId; $managerUserId=(int)$managerUserId; if($projectId<=0||$managerUserId<=0) return false;
        // Ensure site_manager_id column
        try { $c=$this->pdo->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'"); if(!$c||$c->rowCount()===0){ $this->pdo->exec("ALTER TABLE projects ADD COLUMN site_manager_id INT UNSIGNED NULL"); } } catch(Throwable $e){ /* ignore */ }
        // Update project row
        $ok=false; try { $st=$this->pdo->prepare("UPDATE projects SET site_manager_id=:sm, updated_at=NOW() WHERE id=:id"); $ok=$st->execute([':sm'=>$managerUserId,':id'=>$projectId]); } catch(Throwable $e){ $ok=false; }
        // Ensure assignment table
        try { $this->pdo->exec("CREATE TABLE IF NOT EXISTS project_assignments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, role VARCHAR(50) NOT NULL, assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_proj_user_role(project_id,user_id,role), KEY idx_proj(project_id), KEY idx_user(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(Throwable $e){ }
        // Upsert assignment record
        try { $as=$this->pdo->prepare("INSERT INTO project_assignments (project_id,user_id,role,assigned_at) VALUES (?,?, 'site_manager', NOW()) ON DUPLICATE KEY UPDATE assigned_at=VALUES(assigned_at)"); $as->execute([$projectId,$managerUserId]); } catch(Throwable $e){ }
        return $ok;
    }

    /**
     * Get average manager rating for project (null if none).
     */
    public function getManagerRating($projectId){
        $projectId=(int)$projectId; if($projectId<=0) return null;
        try {
            $stmt=$this->pdo->prepare("SELECT manager_rating_total, manager_rating_count FROM projects WHERE id=? LIMIT 1");
            $stmt->execute([$projectId]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row) return null; $cnt=(int)($row['manager_rating_count']??0); if($cnt===0) return null; $tot=(int)($row['manager_rating_total']??0); return round($tot/$cnt,2);
        } catch (Throwable $e){ return null; }
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

    /**
     * Get projects assigned to a site manager (via column or project_assignments table)
     * Combines both sources and deduplicates.
     * @param int $siteManagerId
     * @return array
     */
    public function getBySiteManager($siteManagerId) {
        $siteManagerId = (int)$siteManagerId;
        if ($siteManagerId <= 0) return [];
        $rows = [];
        $seen = [];
        // Detect site_manager_id column
        $hasSmCol = false;
        try {
            $c = $this->pdo->query("SHOW COLUMNS FROM projects LIKE 'site_manager_id'");
            $hasSmCol = ($c && $c->rowCount() > 0);
        } catch (Throwable $e) { $hasSmCol = false; }
        // Detect assignments table
        $hasAssignments = false;
        try {
            $c2 = $this->pdo->query("SHOW TABLES LIKE 'project_assignments'");
            $hasAssignments = ($c2 && $c2->rowCount() > 0);
        } catch (Throwable $e) { $hasAssignments = false; }
        // 1. Column-based
        if ($hasSmCol) {
            try {
                $stmt = $this->pdo->prepare("SELECT id, name, status, start_date, end_date, site_manager_id FROM projects WHERE site_manager_id = :uid ORDER BY id DESC");
                $stmt->execute([':uid'=>$siteManagerId]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $rid = (int)$r['id']; if (isset($seen[$rid])) continue; $seen[$rid]=true; $rows[]=$r;
                }
            } catch (Throwable $e) { /* ignore */ }
        }
        // 2. Assignments-based
        if ($hasAssignments) {
            try {
                $stmt = $this->pdo->prepare("SELECT p.id, p.name, p.status, p.start_date, p.end_date, p.site_manager_id FROM projects p JOIN project_assignments pa ON pa.project_id = p.id WHERE pa.user_id = :uid ORDER BY p.id DESC");
                $stmt->execute([':uid'=>$siteManagerId]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $rid = (int)$r['id']; if (isset($seen[$rid])) continue; $seen[$rid]=true; $rows[]=$r;
                }
            } catch (Throwable $e) { /* ignore */ }
        }
        return $rows;
    }
}