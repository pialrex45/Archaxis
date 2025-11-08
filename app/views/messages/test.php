<?php
require_once __DIR__ . '/../../app/core/auth.php';
require_once __DIR__ . '/../../app/core/helpers.php';

requireAuth();

$pageTitle = 'Messaging System Test';
$currentPage = 'messages';

include_once __DIR__ . '/../../app/views/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Messaging System Test</h1>
            
            <div class="alert alert-info">
                <strong>Testing the new messaging system!</strong><br>
                This page is accessible to all authenticated users to test the messaging functionality.
            </div>
            
            <!-- Full Messaging System -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>Complete Messaging System
                    </h5>
                </div>
                <div class="card-body p-0">
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layouts/footer.php'; ?>