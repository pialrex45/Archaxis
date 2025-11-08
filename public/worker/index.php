<?php
// Front-controller for worker index/dashboard
// This file is directly accessible from the web at /worker/index.php

// Include necessary files
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Check authentication and role
requireAuth();
if (!hasRole('worker')) {
    http_response_code(403);
    die('Access denied. Workers only.');
}

// Include the worker dashboard view
include_once __DIR__ . '/../../app/views/dashboards/worker.php';
