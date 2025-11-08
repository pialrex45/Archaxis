<?php
// Attendance controller for handling attendance operations

require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';

class AttendanceController {
    private $attendanceModel;
    private $userModel;
    
    public function __construct() {
        $this->attendanceModel = new Attendance();
        $this->userModel = new User();
    }
    
    /**
     * Mark attendance for a user
     * 
     * @param array $data
     * @return array
     */
    public function mark($data) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input data
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
            $date = isset($data['date']) ? $data['date'] : date('Y-m-d');
            $status = isset($data['status']) ? sanitize($data['status']) : 'present';
            $note = isset($data['note']) ? sanitize($data['note']) : null;
            
            // Validation
            if (empty($userId)) {
                return ['success' => false, 'message' => 'User ID is required'];
            }
            
            // Validate status
            $validStatuses = ['present', 'absent', 'leave', 'holiday'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid attendance status'];
            }
            
            // Validate date
            if (!strtotime($date)) {
                return ['success' => false, 'message' => 'Invalid date format'];
            }
            
            // Check if user has permission to mark attendance
            $currentUserId = getCurrentUserId();
            $currentUserRole = getCurrentUserRole();
            
            // Users can only mark their own attendance, or managers/admins can mark for others
            if ($userId != $currentUserId && !in_array($currentUserRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to mark attendance for this user'];
            }
            
            // Mark attendance
            $result = $this->attendanceModel->mark($userId, $date, $status, $note);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error marking attendance: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance by user and date
     * 
     * @param int $userId
     * @param string $date
     * @return array
     */
    public function getByUserAndDate($userId, $date) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate user ID
            if (empty($userId)) {
                return ['success' => false, 'message' => 'User ID is required'];
            }
            
            // Validate date
            if (!strtotime($date)) {
                return ['success' => false, 'message' => 'Invalid date format'];
            }
            
            // Get attendance record
            $attendance = $this->attendanceModel->getByUserAndDate($userId, $date);
            
            return ['success' => true, 'data' => $attendance];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting attendance: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance by user and date range
     * 
     * @param int $userId
     * @param string $fromDate
     * @param string $toDate
     * @return array
     */
    public function getByUserAndDateRange($userId, $fromDate, $toDate) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate user ID
            if (empty($userId)) {
                return ['success' => false, 'message' => 'User ID is required'];
            }
            
            // Validate dates
            if (!strtotime($fromDate) || !strtotime($toDate)) {
                return ['success' => false, 'message' => 'Invalid date format'];
            }
            
            // Get attendance records
            $attendanceRecords = $this->attendanceModel->getByUserAndDateRange($userId, $fromDate, $toDate);
            
            return ['success' => true, 'data' => $attendanceRecords];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error getting attendance records: ' . $e->getMessage()];
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
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input data
            if (empty($userId)) {
                return ['success' => false, 'message' => 'User ID is required'];
            }
            
            // Check if already checked in
            $existing = $this->attendanceModel->getByUserAndDate($userId, $date);
            
            if ($existing) {
                return ['success' => false, 'message' => 'Already checked in for today'];
            }
            
            // Create attendance record with check-in time
            $result = $this->attendanceModel->checkIn($userId, $date, $time);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error checking in: ' . $e->getMessage()];
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
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate input data
            if (empty($userId)) {
                return ['success' => false, 'message' => 'User ID is required'];
            }
            
            // Check if checked in
            $existing = $this->attendanceModel->getByUserAndDate($userId, $date);
            
            if (!$existing) {
                return ['success' => false, 'message' => 'Not checked in for today'];
            }
            
            if (isset($existing['check_out_time']) && !empty($existing['check_out_time'])) {
                return ['success' => false, 'message' => 'Already checked out for today'];
            }
            
            // Update attendance record with check-out time
            $result = $this->attendanceModel->checkOut($userId, $date, $time);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error checking out: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance record by ID
     * 
     * @param int $attendanceId
     * @return array
     */
    public function get($attendanceId) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate attendance ID
            if (empty($attendanceId)) {
                return ['success' => false, 'message' => 'Attendance ID is required'];
            }
            
            // Get attendance record
            $attendance = $this->attendanceModel->getById($attendanceId);
            
            if (!$attendance) {
                return ['success' => false, 'message' => 'Attendance record not found'];
            }
            
            // Check if user has permission to view this attendance record
            $userId = getCurrentUserId();
            $userRole = getCurrentUserRole();
            
            // Users can only view their own attendance, or managers/admins can view all
            if ($attendance['user_id'] != $userId && !in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view this attendance record'];
            }
            
            return [
                'success' => true,
                'data' => $attendance
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching attendance record: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance records for a user
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByUser($userId = null, $startDate = null, $endDate = null) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // If no user ID provided, use current user
            if (empty($userId)) {
                $userId = getCurrentUserId();
            }
            
            // Check if user has permission to view attendance records
            $currentUserId = getCurrentUserId();
            $currentUserRole = getCurrentUserRole();
            
            // Users can only view their own attendance, or managers/admins can view all
            if ($userId != $currentUserId && !in_array($currentUserRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view attendance records for this user'];
            }
            
            // Validate dates if provided
            if ($startDate && !strtotime($startDate)) {
                return ['success' => false, 'message' => 'Invalid start date format'];
            }
            
            if ($endDate && !strtotime($endDate)) {
                return ['success' => false, 'message' => 'Invalid end date format'];
            }
            
            if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
            
            // Get attendance records
            $attendance = $this->attendanceModel->getByUser($userId, $startDate, $endDate);
            
            return [
                'success' => true,
                'data' => $attendance ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching attendance records: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance records for a date or date range
     * 
     * @param string $date
     * @param string $endDate
     * @return array
     */
    public function getByDate($date, $endDate = null) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate dates
            if (empty($date)) {
                return ['success' => false, 'message' => 'Date is required'];
            }
            
            if (!strtotime($date)) {
                return ['success' => false, 'message' => 'Invalid date format'];
            }
            
            if ($endDate && !strtotime($endDate)) {
                return ['success' => false, 'message' => 'Invalid end date format'];
            }
            
            if ($endDate && strtotime($endDate) < strtotime($date)) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
            
            // Check if user has permission to view attendance records
            $userRole = getCurrentUserRole();
            
            if (!in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view attendance records by date'];
            }
            
            // Get attendance records
            $attendance = $this->attendanceModel->getByDate($date, $endDate);
            
            return [
                'success' => true,
                'data' => $attendance ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching attendance records: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance statistics
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStats($userId = null, $startDate = null, $endDate = null) {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // If no user ID provided, use current user
            if (empty($userId)) {
                $userId = getCurrentUserId();
            }
            
            // Check if user has permission to view attendance statistics
            $currentUserId = getCurrentUserId();
            $currentUserRole = getCurrentUserRole();
            
            // Users can only view their own attendance stats, or managers/admins can view all
            if ($userId != $currentUserId && !in_array($currentUserRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to view attendance statistics for this user'];
            }
            
            // Validate dates if provided
            if ($startDate && !strtotime($startDate)) {
                return ['success' => false, 'message' => 'Invalid start date format'];
            }
            
            if ($endDate && !strtotime($endDate)) {
                return ['success' => false, 'message' => 'Invalid end date format'];
            }
            
            if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
                return ['success' => false, 'message' => 'End date must be after start date'];
            }
            
            // Get attendance statistics
            $stats = $this->attendanceModel->getStats($userId, $startDate, $endDate);
            
            return [
                'success' => true,
                'data' => $stats ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching attendance statistics: ' . $e->getMessage()];
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
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Validate attendance ID
            if (empty($attendanceId)) {
                return ['success' => false, 'message' => 'Attendance ID is required'];
            }
            
            // Get existing attendance record
            $attendance = $this->attendanceModel->getById($attendanceId);
            
            if (!$attendance) {
                return ['success' => false, 'message' => 'Attendance record not found'];
            }
            
            // Check if user has permission to delete this attendance record
            $userId = getCurrentUserId();
            $userRole = getCurrentUserRole();
            
            // Users can only delete their own attendance, or managers/admins can delete all
            if ($attendance['user_id'] != $userId && !in_array($userRole, ['admin', 'manager'])) {
                return ['success' => false, 'message' => 'Insufficient permissions to delete this attendance record'];
            }
            
            // Delete attendance record
            $result = $this->attendanceModel->delete($attendanceId);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting attendance record: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get today's attendance status for current user
     * 
     * @return array
     */
    public function getTodaysAttendance() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get current user ID
            $userId = getCurrentUserId();
            
            // Get today's date
            $today = date('Y-m-d');
            
            // Get attendance record
            $attendance = $this->attendanceModel->getByUserAndDate($userId, $today);
            
            return [
                'success' => true,
                'data' => $attendance ?: null
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching today\'s attendance: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get attendance records for current week
     * 
     * @return array
     */
    public function getWeeklyAttendance() {
        try {
            // Check if user is authenticated
            if (!isAuthenticated()) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            // Get current user ID
            $userId = getCurrentUserId();
            
            // Get start and end of current week
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = date('Y-m-d', strtotime('sunday this week'));
            
            // Get attendance records
            $attendance = $this->attendanceModel->getByUser($userId, $startDate, $endDate);
            
            return [
                'success' => true,
                'data' => $attendance ?: []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching weekly attendance: ' . $e->getMessage()];
        }
    }
}