<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Messaging Test - Ironroot</title>
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
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-comments me-2"></i>Messaging System Test - Debug Version</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Direct API Tests</h5>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="testBasicAPI()">Test Basic API</button>
                                    <button class="btn btn-outline-info" onclick="testUsersAPI()">Test Users API</button>
                                    <button class="btn btn-outline-warning" onclick="testConversationsAPI()">Test Conversations API</button>
                                    <button class="btn btn-outline-success" onclick="testDebugAPI()">Test Debug API</button>
                                </div>
                                
                                <hr>
                                
                                <h5>Messaging System</h5>
                                <button class="btn btn-primary btn-lg" onclick="openMessaging()">
                                    <i class="fas fa-comments me-2"></i>Open Messages Modal
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h5>Test Results</h5>
                                <div id="testOutput" class="border p-3 bg-light" style="height: 400px; overflow-y: auto;">
                                    <div class="text-muted">Click test buttons to see results...</div>
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
    // Add some debugging
    console.log('JavaScript loaded successfully');
    
    function log(message) {
        const output = document.getElementById('testOutput');
        const time = new Date().toLocaleTimeString();
        output.innerHTML += `<div class="small text-muted">[${time}] ${message}</div>`;
        output.scrollTop = output.scrollHeight;
        console.log(message);
    }
    
    function clearLog() {
        document.getElementById('testOutput').innerHTML = '';
    }
    
    async function testBasicAPI() {
        clearLog();
        log('Testing basic API connectivity...');
        
        try {
            const response = await fetch('/Ironroot/api/messaging_working.php?action=debug');
            log(`Response status: ${response.status} ${response.statusText}`);
            
            const text = await response.text();
            log(`Response text: ${text.substring(0, 200)}...`);
            
            const data = JSON.parse(text);
            log(`✓ API is working! User ID: ${data.user_id}`);
            log(`✓ Tables found: ${data.tables.join(', ')}`);
            log(`✓ Users in DB: ${data.user_count}`);
            
        } catch (error) {
            log(`✗ API Error: ${error.message}`);
        }
    }
    
    async function testUsersAPI() {
        clearLog();
        log('Testing users API...');
        
        try {
            const response = await fetch('/Ironroot/api/messaging_working.php?action=users');
            log(`Response status: ${response.status}`);
            
            const data = await response.json();
            log(`✓ Users API response: ${JSON.stringify(data, null, 2)}`);
            
            if (data.users) {
                log(`✓ Found ${data.users.length} users`);
                data.users.forEach(user => {
                    log(`  - ${user.name} (${user.role})`);
                });
            }
            
        } catch (error) {
            log(`✗ Users API Error: ${error.message}`);
        }
    }
    
    async function testConversationsAPI() {
        clearLog();
        log('Testing conversations API...');
        
        try {
            const response = await fetch('/Ironroot/api/messaging_working.php?action=conversations');
            log(`Response status: ${response.status}`);
            
            const data = await response.json();
            log(`✓ Conversations API response: ${JSON.stringify(data, null, 2)}`);
            
        } catch (error) {
            log(`✗ Conversations API Error: ${error.message}`);
        }
    }
    
    async function testDebugAPI() {
        clearLog();
        log('Testing debug API...');
        
        try {
            const response = await fetch('/Ironroot/api/messaging_working.php?action=debug');
            const data = await response.json();
            
            log('=== DEBUG INFO ===');
            log(`User ID: ${data.user_id}`);
            log(`Session: ${JSON.stringify(data.session)}`);
            log(`Tables: ${data.tables.join(', ')}`);
            log(`User Count: ${data.user_count}`);
            log(`Message Count: ${data.message_count}`);
            
        } catch (error) {
            log(`✗ Debug API Error: ${error.message}`);
        }
    }
    
    function openMessaging() {
        log('Opening messaging modal...');
        try {
            const modal = new bootstrap.Modal(document.getElementById('messagesModal'));
            modal.show();
            log('✓ Modal opened successfully');
        } catch (error) {
            log(`✗ Modal error: ${error.message}`);
        }
    }
    
    // Test if everything loaded
    document.addEventListener('DOMContentLoaded', function() {
        log('Page loaded successfully');
        log('Bootstrap version: ' + (typeof bootstrap !== 'undefined' ? 'Loaded' : 'Not loaded'));
        log('Font Awesome: ' + (document.querySelector('.fa-comments') ? 'Loaded' : 'Not loaded'));
        
        // Test if messaging component loaded
        const messagingModal = document.getElementById('messagesModal');
        if (messagingModal) {
            log('✓ Messaging component loaded');
        } else {
            log('✗ Messaging component not found');
        }
    });
    </script>
</body>
</html>