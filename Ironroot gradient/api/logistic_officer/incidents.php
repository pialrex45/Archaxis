<?php
// API: /api/logistic_officer/incidents.php
// GET: list incidents filtered by reporter (current user) or by related_po_id
// POST action=report: create a delivery-related incident

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/core/auth.php';
require_once dirname(__DIR__, 2) . '/app/core/helpers.php';

requireLogisticOfficer();

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    if ($method === 'GET') {
        $mine = isset($_GET['mine']) ? (int)$_GET['mine'] : 1; // default list own
        $relatedPoId = isset($_GET['related_po_id']) ? (int)$_GET['related_po_id'] : null;
        $limit = min(max((int)($_GET['limit'] ?? 50), 1), 200);

        $sql = "SELECT i.* FROM incidents i WHERE 1=1";
        $params = [];
        if ($mine === 1) { $sql .= " AND i.reported_by = :uid"; $params[':uid'] = getCurrentUserId(); }
        if ($relatedPoId) { $sql .= " AND i.description LIKE :po"; $params[':po'] = '%PO#'.$relatedPoId.'%'; }
        $sql .= " ORDER BY i.created_at DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST' && $action === 'report') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $type = trim($body['type'] ?? 'incident');
        $description = trim($body['description'] ?? '');
        $relatedPoId = isset($body['related_po_id']) ? (int)$body['related_po_id'] : null;
        if ($description === '') {
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>'Description is required']);
            exit;
        }
        // Encode type and related info into title/description for now (schema already has incidents table)
        $title = 'Delivery ' . ($type ?: 'incident');
        if ($relatedPoId) {
            // Optional validation
            $exists = $pdo->prepare('SELECT 1 FROM purchase_orders WHERE id = ?');
            $exists->execute([$relatedPoId]);
            if (!$exists->fetchColumn()) { throw new Exception('Related PO not found'); }
        }
        $stmt = $pdo->prepare('INSERT INTO incidents (reported_by, project_id, task_id, title, description, severity, status) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            getCurrentUserId(),
            null, // not tied to a specific project mandatorily
            null,
            $title,
            $description . ($relatedPoId ? (' [PO#'.$relatedPoId.']') : ''),
            'medium',
            'open'
        ]);
        echo json_encode(['success'=>true,'message'=>'Incident reported']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method/Action not supported']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
