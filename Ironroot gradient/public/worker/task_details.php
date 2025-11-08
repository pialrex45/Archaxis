<?php
// Front-controller for worker task details
// This file is directly accessible from the web at /worker/task_details.php

// Include necessary files
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Include the worker task_details view
include_once __DIR__ . '/../../app/views/worker/task_details.php';
