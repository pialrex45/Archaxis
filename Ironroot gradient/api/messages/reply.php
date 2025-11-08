<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/controllers/MessageController.php';
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

try {
    // Get current user ID
    $senderId = getCurrentUserId();
    
    // Get reply_to from query parameter
    $replyToId = isset($_GET['reply_to']) ? (int)$_GET['reply_to'] : 0;
    
    // Get POST data
    $messageText = isset($_POST['message_text']) ? sanitize($_POST['message_text']) : '';
    
    // Validate input
    if (empty($replyToId)) {
        jsonResponse(['success' => false, 'message' => 'Reply to ID is required'], 400);
    }
    
    if (empty($messageText)) {
        jsonResponse(['success' => false, 'message' => 'Message text is required'], 400);
    }
    
    // Handle file upload if present
    $filePath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['attachment']);
        if (!$uploadResult['success']) {
            jsonResponse(['success' => false, 'message' => $uploadResult['message']], 400);
        }
        $filePath = $uploadResult['file_path'];
    }
    
    // Reply to message
    $messageController = new MessageController();
    $result = $messageController->reply($replyToId, [
        'message_text' => $messageText,
        'file_path' => $filePath
    ]);
    
    // Return result
    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'message' => 'Reply sent successfully',
            'data' => $result
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => $result['message']], 400);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error sending reply: ' . $e->getMessage()], 500);
}

/**
 * Handle file upload with validation
 * 
 * @param array $file File data from $_FILES
 * @return array Result with success status and file path or error message
 */
function handleFileUpload($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Validate file size
    $maxFileSize = env('MAX_FILE_SIZE', 5242880); // Default 5MB
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
    }
    
    // Validate file type
    $allowedTypes = explode(',', env('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,pdf,doc,docx'));
    $fileExtension = getFileExtension($file['name']);
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $uniqueFilename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
    $uploadPath = __DIR__ . '/../../public/uploads/files/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Move uploaded file
    $destination = $uploadPath . $uniqueFilename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Return relative path for database storage
        return [
            'success' => true,
            'file_path' => '/uploads/files/' . $uniqueFilename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}