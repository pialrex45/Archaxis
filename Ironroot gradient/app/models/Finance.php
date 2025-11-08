<?php
// Finance model for managing project finances

require_once __DIR__ . '/../../config/database.php';

class Finance {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Log income or expense
     * 
     * @param int $projectId
     * @param string $type
     * @param float $amount
     * @param string $description
     * @return array
     */
    public function logTransaction($projectId, $type, $amount, $description = null) {
        try {
            // Validate type
            if (!in_array($type, ['income', 'expense'])) {
                return ['success' => false, 'message' => 'Invalid transaction type. Must be "income" or "expense".'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO finance (project_id, type, amount, description, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([$projectId, $type, $amount, $description]);
            
            if ($result) {
                $financeId = $this->pdo->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Transaction logged successfully',
                    'finance_id' => $financeId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to log transaction'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Transaction logging error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get transaction by ID
     * 
     * @param int $financeId
     * @return array|bool
     */
    public function getById($financeId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, p.name as project_name 
                FROM finance f 
                JOIN projects p ON f.project_id = p.id 
                WHERE f.id = ?
            ");
            $stmt->execute([$financeId]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get all transactions
     * 
     * @return array|bool
     */
    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, p.name as project_name 
                FROM finance f 
                JOIN projects p ON f.project_id = p.id 
                ORDER BY f.created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get transactions by project
     * 
     * @param int $projectId
     * @return array|bool
     */
    public function getByProject($projectId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, p.name as project_name 
                FROM finance f 
                JOIN projects p ON f.project_id = p.id 
                WHERE f.project_id = ?
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$projectId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get transactions by type
     * 
     * @param string $type
     * @return array|bool
     */
    public function getByType($type) {
        try {
            // Validate type
            if (!in_array($type, ['income', 'expense'])) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT f.*, p.name as project_name 
                FROM finance f 
                JOIN projects p ON f.project_id = p.id 
                WHERE f.type = ?
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$type]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get transactions within date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @param int $projectId
     * @return array|bool
     */
    public function getByDateRange($startDate, $endDate, $projectId = null) {
        try {
            $sql = "
                SELECT f.*, p.name as project_name 
                FROM finance f 
                JOIN projects p ON f.project_id = p.id 
                WHERE f.created_at BETWEEN ? AND ?
            ";
            
            $params = [$startDate, $endDate];
            
            if ($projectId) {
                $sql .= " AND f.project_id = ?";
                $params[] = $projectId;
            }
            
            $sql .= " ORDER BY f.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update transaction
     * 
     * @param int $financeId
     * @param array $data
     * @return array
     */
    public function update($financeId, $data) {
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
            
            $values[] = $financeId; // Add finance ID for WHERE clause
            
            $sql = "UPDATE finance SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Transaction updated successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'No changes made to transaction'];
            }
            
            return ['success' => false, 'message' => 'Failed to update transaction'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Transaction update error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete transaction
     * 
     * @param int $financeId
     * @return array
     */
    public function delete($financeId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM finance WHERE id = ?");
            $result = $stmt->execute([$financeId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Transaction deleted successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete transaction'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Transaction deletion error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate financial summary for a project
     * 
     * @param int $projectId
     * @return array|bool
     */
    public function getSummary($projectId = null) {
        try {
            $sql = "
                SELECT 
                    f.type,
                    SUM(f.amount) as total
                FROM finance f
            ";
            
            $params = [];
            
            if ($projectId) {
                $sql .= " WHERE f.project_id = ?";
                $params[] = $projectId;
            }
            
            $sql .= " GROUP BY f.type";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll();
            
            // Convert to associative array for easier access
            $summary = [
                'income' => 0,
                'expense' => 0,
                'balance' => 0
            ];
            
            foreach ($result as $row) {
                $summary[$row['type']] = (float)$row['total'];
            }
            
            $summary['balance'] = $summary['income'] - $summary['expense'];
            
            return $summary;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get total income
     * 
     * @param int $projectId
     * @return float|bool
     */
    public function getTotalIncome($projectId = null) {
        try {
            $sql = "SELECT SUM(amount) as total FROM finance WHERE type = 'income'";
            $params = [];
            
            if ($projectId) {
                $sql .= " AND project_id = ?";
                $params[] = $projectId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result ? (float)$result['total'] : 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get total expenses
     * 
     * @param int $projectId
     * @return float|bool
     */
    public function getTotalExpenses($projectId = null) {
        try {
            $sql = "SELECT SUM(amount) as total FROM finance WHERE type = 'expense'";
            $params = [];
            
            if ($projectId) {
                $sql .= " AND project_id = ?";
                $params[] = $projectId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result ? (float)$result['total'] : 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}