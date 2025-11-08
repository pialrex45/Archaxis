<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/helpers.php';
requireAuth();
requireRole('client');

if (!isset($pageTitle)) { $pageTitle = 'Client Messages'; }
// Reuse the shared messages UI
include __DIR__ . '/../messages/index.php';
