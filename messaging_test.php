<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging Test - Ironroot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php
    session_start();
    // Simulate logged in user for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-comments me-2"></i>Messaging System Test</h3>
                    </div>
                    <div class="card-body text-center">
                        <p>Click the button below to test the messaging system.</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#messagesModal">
                            <i class="fas fa-comments me-2"></i>Open Messages
                        </button>
                        
                        <hr>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5>Test API Endpoints</h5>
                                <button class="btn btn-outline-info btn-sm me-2" onclick="testAPISimple('debug')">Test Debug</button>
                                <button class="btn btn-outline-info btn-sm me-2" onclick="testAPISimple('users')">Test Users</button>
                                <button class="btn btn-outline-info btn-sm" onclick="testAPISimple('conversations')">Test Conversations</button>
                            </div>
                            <div class="col-md-6">
                                <h5>Test Results</h5>
                                <div id="testResults" class="small text-start border p-2" style="height: 200px; overflow-y: auto;">
                                    <div class="text-muted">Click test buttons to see API responses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Simple Messaging Component -->
    <?php include 'app/views/components/simple_messaging.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Simple API test function
    async function testAPISimple(action) {
        const resultsDiv = document.getElementById('testResults');
        resultsDiv.innerHTML = `<div class="text-info">Testing ${action}...</div>`;
        
        try {
            const response = await fetch(`/Ironroot/api/messaging_working.php?action=${action}`);
            const data = await response.json();
            
            resultsDiv.innerHTML = `
                <div class="text-success">✓ ${action} API Response:</div>
                <pre class="bg-light p-2 small">${JSON.stringify(data, null, 2)}</pre>
            `;
        } catch (error) {
            resultsDiv.innerHTML = `
                <div class="text-danger">✗ ${action} API Error:</div>
                <div class="text-danger small">${error.message}</div>
            `;
        }
    }
    
    // Test basic JavaScript
    console.log('JavaScript working');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded');
        document.getElementById('testResults').innerHTML = '<div class="text-success">JavaScript is working! Click test buttons above.</div>';
    });
    </script>
</body>
</html>