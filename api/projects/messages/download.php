<?php
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/core/policies/MessagingPolicy.php';

// Secure download for project message attachments using token lookup in metadata
if (!isAuthenticated()) { http_response_code(401); echo 'Unauthorized'; exit; }

$token = isset($_GET['token']) ? preg_replace('/[^a-f0-9]/i','', $_GET['token']) : '';
if (!$token) { http_response_code(400); echo 'Bad request'; exit; }

try {
    $db = new Database();
    $pdo = $db->connect();
    // Find message that references this token in metadata JSON
    $stmt = $pdo->prepare("SELECT pm.project_id, pm.metadata FROM project_messages pm WHERE pm.metadata IS NOT NULL AND JSON_SEARCH(pm.metadata, 'one', :token, NULL, '$.attachments[*].token') IS NOT NULL LIMIT 1");
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); echo 'Not found'; exit; }

    $projectId = (int)$row['project_id'];
    $meta = json_decode($row['metadata'], true);
    $file = null;
    if (isset($meta['attachments']) && is_array($meta['attachments'])){
        foreach ($meta['attachments'] as $att){ if (($att['token'] ?? '') === $token) { $file = $att; break; } }
    }
    if (!$file) { http_response_code(404); echo 'Not found'; exit; }

    // Authorization: user must be allowed to view/post messages in this project
    $uid = getCurrentUserId();
    $policy = new MessagingPolicy();
    if (!$policy->canPostProject($uid, $projectId)) { http_response_code(403); echo 'Forbidden'; exit; }

    $absPath = realpath(__DIR__ . '/../../../public' . $file['path']);
    $baseDir = realpath(__DIR__ . '/../../../public/uploads/project_messages');
    if (!$absPath || !$baseDir || strpos($absPath, $baseDir) !== 0 || !is_file($absPath)) { http_response_code(404); echo 'Not found'; exit; }

    // Serve file
    $name = $file['name'] ?? 'file';
    $mime = mime_content_type($absPath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($name) . '"');
    header('Content-Length: ' . filesize($absPath));
    readfile($absPath);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error';
    exit;
}
