<?php
// Front-controller for worker attendance
// This file is directly accessible from the web at /worker/attendance.php

// Include necessary files
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Include the worker attendance view
include_once __DIR__ . '/../../app/views/worker/attendance.php';
