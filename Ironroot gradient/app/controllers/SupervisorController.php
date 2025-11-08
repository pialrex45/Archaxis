<?php
// SupervisorController.php - Additive controller for Supervisor role
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../../config/database.php';

// Models (defensive includes)
require_once __DIR__ . '/../models/PurchaseOrder.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/TaskUpdate.php';

class SupervisorController {
    private $db;
    private $poModel;
    private $attendanceModel;
    private $taskModel;
    private $taskUpdateModel;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->poModel = class_exists('PurchaseOrder') ? new PurchaseOrder() : null;
        $this->attendanceModel = class_exists('Attendance') ? new Attendance() : null;
        $this->taskModel = class_exists('Task') ? new Task() : null;
        $this->taskUpdateModel = class_exists('TaskUpdate') ? new TaskUpdate() : null;
    }

    private function ensureSupervisor() {
        requireAnyRole(['supervisor','admin']);
    }

    private function currentUserId() { return getCurrentUserId(); }

    // ---- Tasks (assigned to the supervisor) ----
    public function tasksAssigned($limit = 100) {
        $this->ensureSupervisor();
        $uid = (int)$this->currentUserId();
        $lim = max(1, min(200, (int)$limit));
        try {
            $stmt = $this->db->prepare(
                "SELECT t.*, p.name AS project_name FROM tasks t JOIN projects p ON p.id=t.project_id WHERE t.assigned_to=? ORDER BY t.due_date IS NULL, t.due_date ASC, t.id DESC LIMIT {$lim}"
            );
            $stmt->execute([$uid]);
            return ['success'=>true,'data'=>$stmt->fetchAll() ?: []];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Failed to load tasks'];
        }
    }

    public function taskUpdateStatus($taskId, $status, $note = null, $photoPath = null) {
        $this->ensureSupervisor();
        $uid = (int)$this->currentUserId();
        $allowed = ['pending','in progress','completed'];
        if (!in_array($status, $allowed, true)) { return ['success'=>false,'message'=>'Invalid status']; }
        // Ensure the supervisor has the task assigned
        try {
            $chk = $this->db->prepare('SELECT 1 FROM tasks WHERE id = ? AND assigned_to = ?');
            $chk->execute([(int)$taskId, $uid]);
            if (!$chk->fetchColumn()) { return ['success'=>false,'message'=>'Forbidden']; }
        } catch (Throwable $e) { return ['success'=>false,'message'=>'Validation failed']; }
        if (!$this->taskModel) { return ['success'=>false,'message'=>'Task model not available']; }
        $res = $this->taskModel->updateStatus((int)$taskId, $status);
        if (!$res || !$res['success']) { return $res ?: ['success'=>false,'message'=>'Update failed']; }
        if ($this->taskUpdateModel) { $this->taskUpdateModel->create((int)$taskId, $uid, $status, $note, $photoPath); }
        return ['success'=>true,'message'=>'Status updated'];
    }

    public function taskAddProgress($taskId, $note = null, $status = null, $photoPath = null) {
        $this->ensureSupervisor();
        $uid = (int)$this->currentUserId();
        // Ensure access
        try {
            $chk = $this->db->prepare('SELECT 1 FROM tasks WHERE id = ? AND assigned_to = ?');
            $chk->execute([(int)$taskId, $uid]);
            if (!$chk->fetchColumn()) { return ['success'=>false,'message'=>'Forbidden']; }
        } catch (Throwable $e) { return ['success'=>false,'message'=>'Validation failed']; }
        if ($status && !in_array($status, ['pending','in progress','completed'], true)) {
            return ['success'=>false,'message'=>'Invalid status'];
        }
        if (!$this->taskUpdateModel) { return ['success'=>false,'message'=>'TaskUpdate model not available']; }
        return $this->taskUpdateModel->create((int)$taskId, $uid, $status, $note, $photoPath);
    }

    // ---- Purchase Orders ----
    public function listPOsByProject($projectId) {
        $this->ensureSupervisor();
        $pid = (int)$projectId;
        if ($pid <= 0) return ['success'=>false,'message'=>'Invalid project_id'];
        try {
            $stmt = $this->db->prepare(
                'SELECT po.*, s.name AS supplier_name, u.name AS created_by_name, p.name AS project_name
                 FROM purchase_orders po
                 JOIN suppliers s ON po.supplier_id = s.id
                 JOIN users u ON po.created_by = u.id
                 JOIN projects p ON po.project_id = p.id
                 WHERE po.project_id = :pid
                 ORDER BY po.created_at DESC'
            );
            $stmt->bindValue(':pid',$pid, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            return ['success'=>true,'data'=>$rows ?: []];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Failed to load purchase orders'];
        }
    }

    public function updatePOStatus($poId, $status, $note = null) {
        $this->ensureSupervisor();
        $id = (int)$poId;
        $newStatus = trim((string)$status);
        if ($id <= 0 || $newStatus === '') return ['success'=>false,'message'=>'Invalid parameters'];

        // Prefer model transition validation if available
        if ($this->poModel && method_exists($this->poModel,'updateStatus')) {
            $res = $this->poModel->updateStatus($id, $newStatus);
            // Optionally attach note if column exists (best-effort, additive)
            if ($note !== null) {
                try {
                    $stmt = $this->db->prepare('UPDATE purchase_orders SET note = :n WHERE id = :id');
                    $stmt->bindValue(':n', (string)$note);
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                } catch (Throwable $e) { /* ignore */ }
            }
            return $res;
        }

        // Fallback generic update without transition checks
        try {
            $stmt = $this->db->prepare('UPDATE purchase_orders SET status = :s, updated_at = NOW() WHERE id = :id');
            $stmt->bindValue(':s',$newStatus);
            $stmt->bindValue(':id',$id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            return ['success'=>(bool)$ok,'message'=>$ok?'Status updated':'Update failed'];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Update error'];
        }
    }

    // ---- Attendance ----
    public function viewAttendanceHistory($userId, $limit = 50) {
        $this->ensureSupervisor();
        $uid = (int)$userId; $lim = max(1, min(200, (int)$limit));
        if ($uid <= 0) return ['success'=>false,'message'=>'Invalid user_id'];
        try {
            $stmt = $this->db->prepare(
                'SELECT a.*, u.name AS user_name
                 FROM attendance a
                 JOIN users u ON a.user_id = u.id
                 WHERE a.user_id = :uid
                 ORDER BY a.date DESC, a.id DESC
                 LIMIT :lim'
            );
            $stmt->bindValue(':uid',$uid, PDO::PARAM_INT);
            $stmt->bindValue(':lim',$lim, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            return ['success'=>true,'data'=>$rows ?: []];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Failed to load attendance'];
        }
    }

    public function approveAttendance($attendanceId) {
        $this->ensureSupervisor();
        $aid = (int)$attendanceId;
        if ($aid <= 0) return ['success'=>false,'message'=>'Invalid attendance_id'];
        try {
            $stmt = $this->db->prepare('UPDATE attendance SET status = :s WHERE id = :id');
            $stmt->bindValue(':s','approved');
            $stmt->bindValue(':id',$aid, PDO::PARAM_INT);
            $ok = $stmt->execute();
            return ['success'=>(bool)$ok,'message'=>$ok?'Attendance approved':'Update failed'];
        } catch (Throwable $e) {
            return ['success'=>false,'message'=>'Approval error'];
        }
    }
}
