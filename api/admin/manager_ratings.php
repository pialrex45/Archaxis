<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/models/Project.php'; // still loaded in case other includes expect it

if(!headers_sent()) header('Content-Type: application/json');
try {
  requireAuth();
  if(!hasRole('admin')){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'Access denied']); exit; }
  // Ratings feature has been disabled. Always return message.
  http_response_code(410); // Gone
  echo json_encode(['success'=>false,'message'=>'Project manager ratings feature disabled']);
  exit;
} catch(Throwable $e){ http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']); }
