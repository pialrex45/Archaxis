<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standalone Messaging Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php
    session_start();
    $_SESSION['user_id'] = 1; // Simulate logged in user
    $_SESSION['user_role'] = 'admin';
    ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bug me-2"></i>Standalone Messaging Test</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h5>API Tests</h5>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="testAPI('debug')">Test Debug</button>
                            <button class="btn btn-info" onclick="testAPI('users')">Test Users</button>
                            <button class="btn btn-warning" onclick="testAPI('conversations')">Test Conversations</button>
                        </div>
                        
                        <hr>
                        
                        <h5>Load Users</h5>
                        <button class="btn btn-success" onclick="loadUsers()">Load Users</button>
                        <select id="userSelect" class="form-select mt-2">
                            <option>Loading...</option>
                        </select>
                        
                        <hr>
                        
                        <h5>Send Message</h5>
                        <textarea id="messageText" class="form-control mb-2" placeholder="Type message..."></textarea>
                        <button class="btn btn-primary" onclick="sendMessage()">Send Message</button>
                    </div>
                    
                    <div class="col-md-8">
                        <h5>Output</h5>
                        <div id="output" class="border p-3 bg-light" style="height: 500px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                            Ready to test...<br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function log(message) {
        const output = document.getElementById('output');
        const time = new Date().toLocaleTimeString();
        output.innerHTML += `[${time}] ${message}<br>`;
        output.scrollTop = output.scrollHeight;
    }
    
    async function testAPI(action) {
        log(`Testing API action: ${action}`);
        
        try {
            const url = `/Ironroot/api/messaging_working.php?action=${action}`;
            log(`Making request to: ${url}`);
            
            const response = await fetch(url);
            log(`Response status: ${response.status} ${response.statusText}`);
            
            const data = await response.json();
            log(`Response data: ${JSON.stringify(data, null, 2)}`);
            
        } catch (error) {
            log(`ERROR: ${error.message}`);
            log(`Stack: ${error.stack}`);
        }
    }
    
    async function loadUsers() {
        log('Loading users for select dropdown...');
        
        try {
            const response = await fetch('/Ironroot/api/messaging_working.php?action=users');
            const data = await response.json();
            
            if (data.users) {
                const select = document.getElementById('userSelect');
                select.innerHTML = '<option value="">Choose a user...</option>';
                
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.name} (${user.role})`;
                    select.appendChild(option);
                });
                
                log(`✓ Loaded ${data.users.length} users into dropdown`);
            } else {
                log('No users found in response');
            }
            
        } catch (error) {
            log(`Error loading users: ${error.message}`);
        }
    }
    
    async function sendMessage() {
        const userId = document.getElementById('userSelect').value;
        const messageText = document.getElementById('messageText').value;
        
        if (!userId || !messageText) {
            log('Please select a user and enter a message');
            return;
        }
        
        log(`Sending message to user ${userId}: "${messageText}"`);
        
        try {
            const formData = new FormData();
            formData.append('recipient_id', userId);
            formData.append('message_text', messageText);
            
            const response = await fetch('/Ironroot/api/messaging_working.php?action=send', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            log(`Send response: ${JSON.stringify(data, null, 2)}`);
            
            if (data.success) {
                document.getElementById('messageText').value = '';
                log('✓ Message sent successfully!');
            }
            
        } catch (error) {
            log(`Error sending message: ${error.message}`);
        }
    }
    
    // Test basic functionality on load
    document.addEventListener('DOMContentLoaded', function() {
        log('Page loaded successfully');
        log('Bootstrap loaded: ' + (typeof bootstrap !== 'undefined'));
        log('Fetch API available: ' + (typeof fetch !== 'undefined'));
        
        // Auto-test debug API
        setTimeout(() => {
            log('Auto-testing debug API...');
            testAPI('debug');
        }, 1000);
    });
    </script>
</body>
</html>