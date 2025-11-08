<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/controllers/ProjectMessageController.php';
require_once __DIR__ . '/../../../app/core/auth.php';
require_once __DIR__ . '/../../../app/core/helpers.php';

header('Content-Type: application/json');
if (!isAuthenticated()) { jsonResponse(['success'=>false,'message'=>'User not authenticated'], 401); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['success'=>false,'message'=>'Invalid request method'], 405); }

$projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$body = isset($_POST['body']) ? sanitize($_POST['body']) : '';
$channel = isset($_POST['channel']) ? sanitize($_POST['channel']) : null;
// Handle attachments (optional, multiple)
$attachments = [];
if (!empty($_FILES['attachments'])) {
	$files = $_FILES['attachments'];
	$count = is_array($files['name']) ? count($files['name']) : 0;
	$uploadDir = __DIR__ . '/../../../public/uploads/project_messages/';
	if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
	$allowedExt = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar','7z','csv'];
	$maxSize = 10 * 1024 * 1024; // 10MB
	for ($i=0; $i<$count; $i++) {
		if ($files['error'][$i] !== UPLOAD_ERR_OK) { continue; }
		if ($files['size'][$i] > $maxSize) { continue; }
		$origName = $files['name'][$i];
		$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
		if ($ext && !in_array($ext, $allowedExt)) { continue; }
		$token = bin2hex(random_bytes(16));
		$safeName = $token . ($ext ? ('.' . $ext) : '');
		$dest = $uploadDir . $safeName;
		if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
			$attachments[] = [
				'name' => $origName,
				'path' => '/uploads/project_messages/' . $safeName,
				'token' => $token,
				'size' => (int)$files['size'][$i],
				'type' => $files['type'][$i] ?? null
			];
		}
	}
}

$metadata = null;
if (!empty($attachments)) {
	$metadata = [ 'attachments' => $attachments ];
}

$ctl = new ProjectMessageController();
$res = $ctl->send($projectId, $body, $channel, $metadata);
http_response_code($res['success'] ? 200 : 400);
echo json_encode($res);
