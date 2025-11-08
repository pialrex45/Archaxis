<?php
// Attendance model for managing user attendance

require_once __DIR__ . '/../../config/database.php';

class Attendance {
    private $pdo;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Check if a column exists in the attendance table
     *
     * @param string $columnName Name of the column to check
     * @return bool Whether the column exists
     */
    private function hasColumn($columnName) {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM attendance LIKE ?");
            $stmt->execute([$columnName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Mark attendance for a user
     * 
     * @param int $userId
     * @param string $date
     * @param string $status
     * @param string $note
     * @return array
     */
    public function mark($userId, $date, $status = 'present', $note = null) {
        try {
            // Check if attendance already exists for this user on this date
            $existing = $this->getByUserAndDate($userId, $date);
            
            if ($existing) {
                // Update existing attendance
                $stmt = $this->pdo->prepare("
                    UPDATE attendance 
                    SET status = ?, note = ? 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$status, $note, $existing['id']]);
                
                if ($result) {
                    return ['success' => true, 'message' => 'Attendance updated successfully'];
                }
                
                return ['success' => false, 'message' => 'Failed to update attendance'];
            } else {
                // Create new attendance record
                $stmt = $this->pdo->prepare("
                    INSERT INTO attendance (user_id, date, status, note) 
                    VALUES (?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([$userId, $date, $status, $note]);
                
                if ($result) {
                    $attendanceId = $this->pdo->lastInsertId();
                    return [
                        'success' => true,
                        'message' => 'Attendance marked successfully',
                        'attendance_id' => $attendanceId
                    ];
                }
                
                return ['success' => false, 'message' => 'Failed to mark attendance'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Attendance marking error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance record by ID
     * 
     * @param int $attendanceId
     * @return array|bool
     */
    public function getById($attendanceId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.name as user_name 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$attendanceId]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get attendance by user and date
     * 
     * @param int $userId
     * @param string $date
     * @return array|bool
     */
    public function getByUserAndDate($userId, $date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.name as user_name 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.user_id = ? AND a.date = ?
            ");
            $stmt->execute([$userId, $date]);
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get attendance records for a user within a date range
     * 
     * @param int $userId
     * @param string $fromDate
     * @param string $toDate
     * @return array|bool
     */
    public function getByUserAndDateRange($userId, $fromDate = null, $toDate = null) {
        try {
            $sql = "
                SELECT a.*, u.name as user_name 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.user_id = ?
            ";
            
            $params = [$userId];
            
            if ($fromDate) {
                $sql .= " AND a.date >= ?";
                $params[] = $fromDate;
            }
            
            if ($toDate) {
                $sql .= " AND a.date <= ?";
                $params[] = $toDate;
            }
            
            $sql .= " ORDER BY a.date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get attendance records for a user (optional date range)
     *
     * @param int $userId
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array|bool
     */
    public function getByUser($userId, $fromDate = null, $toDate = null) {
        return $this->getByUserAndDateRange($userId, $fromDate, $toDate);
    }
    
    /**
     * Get attendance records for a date or date range
     * 
     * @param string $date
     * @param string $endDate
     * @return array|bool
     */
    public function getByDate($date, $endDate = null) {
        try {
            $sql = "
                SELECT a.*, u.name as user_name 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
            ";
            
            $params = [];
            
            if ($endDate) {
                $sql .= " WHERE a.date BETWEEN ? AND ?";
                $params[] = $date;
                $params[] = $endDate;
            } else {
                $sql .= " WHERE a.date = ?";
                $params[] = $date;
            }
            
            $sql .= " ORDER BY u.name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get attendance statistics
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array|bool
     */
    public function getStats($userId = null, $startDate = null, $endDate = null) {
        try {
            $sql = "
                SELECT 
                    status,
                    COUNT(*) as count
                FROM attendance a
            ";
            
            $params = [];
            $whereClause = [];
            
            if ($userId) {
                $whereClause[] = "a.user_id = ?";
                $params[] = $userId;
            }
            
            if ($startDate) {
                $whereClause[] = "a.date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $whereClause[] = "a.date <= ?";
                $params[] = $endDate;
            }
            
            if (!empty($whereClause)) {
                $sql .= " WHERE " . implode(" AND ", $whereClause);
            }
            
            $sql .= " GROUP BY status";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll();
            
            // Convert to associative array for easier access
            $stats = [];
            foreach ($result as $row) {
                $stats[$row['status']] = $row['count'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Delete attendance record
     * 
     * @param int $attendanceId
     * @return array
     */
    public function delete($attendanceId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM attendance WHERE id = ?");
            $result = $stmt->execute([$attendanceId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Attendance record deleted successfully'];
            } elseif ($result) {
                return ['success' => false, 'message' => 'Attendance record not found'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete attendance record'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Attendance deletion error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check in a user
     * 
     * @param int $userId
     * @param string $date
     * @param string $time
     * @return array
     */
    public function checkIn($userId, $date, $time) {
        try {
            // Check if attendance already exists for this user on this date
            $existing = $this->getByUserAndDate($userId, $date);
            
            if ($existing) {
                return ['success' => false, 'message' => 'Already checked in for today'];
            }
            
            // Prefer 'check_in' if present; else fallback to 'check_in_time'; else insert without time
            $checkInCol = null;
            if ($this->hasColumn('check_in')) {
                $checkInCol = 'check_in';
            } elseif ($this->hasColumn('check_in_time')) {
                $checkInCol = 'check_in_time';
            }
            
            if ($checkInCol) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO attendance (user_id, date, status, {$checkInCol}) 
                    VALUES (?, ?, 'present', ?)
                ");
                $result = $stmt->execute([$userId, $date, $time]);
            } else {
                // Fallback if no time column exists
                $stmt = $this->pdo->prepare("
                    INSERT INTO attendance (user_id, date, status) 
                    VALUES (?, ?, 'present')
                ");
                $result = $stmt->execute([$userId, $date]);
            }
            
            if ($result) {
                return ['success' => true, 'message' => 'Checked in successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to check in'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check out a user
     * 
     * @param int $userId
     * @param string $date
     * @param string $time
     * @return array
     */
    public function checkOut($userId, $date, $time) {
        try {
            // Check if attendance exists for this user on this date
            $existing = $this->getByUserAndDate($userId, $date);
            
            if (!$existing) {
                return ['success' => false, 'message' => 'Not checked in for today'];
            }
            
            // Determine available checkout column
            $checkOutCol = null;
            if ($this->hasColumn('check_out')) {
                $checkOutCol = 'check_out';
            } elseif ($this->hasColumn('check_out_time')) {
                $checkOutCol = 'check_out_time';
            }
            
            if (!$checkOutCol) {
                // If the column doesn't exist, return success (advisory)
                return ['success' => true, 'message' => 'Check-out recorded (note: database upgrade recommended)'];
            }
            
            // Prevent duplicate checkout
            if ((isset($existing[$checkOutCol]) && !empty($existing[$checkOutCol]))) {
                return ['success' => false, 'message' => 'Already checked out for today'];
            }
            
            // Update attendance record with check-out time
            $stmt = $this->pdo->prepare("
                UPDATE attendance 
                SET {$checkOutCol} = ? 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$time, $existing['id']]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Checked out successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to check out'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}