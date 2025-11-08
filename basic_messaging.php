<?php
// Standalone Basic Messaging System - No Dependencies
session_start();

// Simple authentication simulation
if (!isset($_SESSION['user_id'])) {
    // Auto-login for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_role'] = 'admin';
}

$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'];
$current_user_role = $_SESSION['user_role'];

// File paths for messaging data
$data_dir = __DIR__ . '/data';
$users_file = $data_dir . '/simple_users.json';
$messages_file = $data_dir . '/simple_messages.json';

// Create data directory if it doesn't exist
if (!file_exists($data_dir)) {
    mkdir($data_dir, 0777, true);
}

// Helper functions
function loadUsers() {
    global $users_file;
    if (!file_exists($users_file)) {
        // Create default users
        $default_users = [
            ['id' => 1, 'name' => 'Admin User', 'role' => 'admin'],
            ['id' => 2, 'name' => 'John Manager', 'role' => 'manager'],
            ['id' => 3, 'name' => 'Sarah Client', 'role' => 'client'],
            ['id' => 4, 'name' => 'Mike Worker', 'role' => 'worker']
        ];
        file_put_contents($users_file, json_encode($default_users, JSON_PRETTY_PRINT));
        return $default_users;
    }
    return json_decode(file_get_contents($users_file), true) ?? [];
}

function loadMessages() {
    global $messages_file;
    if (!file_exists($messages_file)) {
        return [];
    }
    return json_decode(file_get_contents($messages_file), true) ?? [];
}

function saveMessages($messages) {
    global $messages_file;
    return file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
}

// Handle AJAX requests
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!empty($action)) {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'get_users':
            $users = loadUsers();
            $filtered = array_filter($users, function($u) use ($current_user_id) {
                return $u['id'] != $current_user_id;
            });
            echo json_encode(['success' => true, 'users' => array_values($filtered)]);
            break;
            
        case 'get_messages':
            $other_user_id = (int)$_GET['user_id'];
            $messages = loadMessages();
            
            $conversation = array_filter($messages, function($msg) use ($current_user_id, $other_user_id) {
                return ($msg['sender_id'] == $current_user_id && $msg['receiver_id'] == $other_user_id) ||
                       ($msg['sender_id'] == $other_user_id && $msg['receiver_id'] == $current_user_id);
            });
            
            usort($conversation, function($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
            
            echo json_encode(['success' => true, 'messages' => array_values($conversation)]);
            break;
            
        case 'send_message':
            $receiver_id = (int)$_POST['receiver_id'];
            $message_text = trim($_POST['message_text']);
            
            if ($receiver_id && $message_text) {
                $messages = loadMessages();
                
                $max_id = 0;
                foreach ($messages as $msg) {
                    if ($msg['id'] > $max_id) $max_id = $msg['id'];
                }
                
                $new_message = [
                    'id' => $max_id + 1,
                    'sender_id' => $current_user_id,
                    'receiver_id' => $receiver_id,
                    'message_text' => $message_text,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sender_name' => $current_user_name
                ];
                
                $messages[] = $new_message;
                
                if (saveMessages($messages)) {
                    echo json_encode(['success' => true, 'message' => 'Sent']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to save']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing data']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ironroot Messages - Basic Version</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .messaging-container { max-width: 1200px; margin: 20px auto; }
        .chat-container { 
            height: 400px; 
            overflow-y: auto; 
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            background: white;
        }
        .message-bubble { 
            max-width: 70%; 
            margin-bottom: 10px; 
            padding: 10px 15px;
            border-radius: 15px;
        }
        .message-sent { 
            background: #007bff;
            color: white; 
            margin-left: auto; 
        }
        .message-received { 
            background: #e9ecef;
        }
        .user-item { 
            cursor: pointer; 
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            border: 1px solid #dee2e6;
        }
        .user-item:hover { background-color: #e3f2fd; }
        .user-item.active { background: #007bff; color: white; }
        .status-badge { 
            position: fixed; 
            top: 10px; 
            right: 10px; 
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="status-badge">
        <span class="badge bg-success">
            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($current_user_name); ?> (<?php echo htmlspecialchars($current_user_role); ?>)
        </span>
    </div>

    <div class="messaging-container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-comments me-2"></i>
                    Ironroot Messaging System - Basic Version
                </h4>
                <small>Simple, standalone messaging without complex dependencies</small>
            </div>
        </div>

        <div class="row mt-3">
            <!-- Users List -->
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Select User to Message</h6>
                    </div>
                    <div class="card-body">
                        <div id="usersList">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div>Loading users...</div>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary btn-sm w-100 mt-2" onclick="loadUsers()">
                            <i class="fas fa-sync me-1"></i>Refresh Users
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="col-md-8">
                <div class="card shadow">
                    <div id="chatHeader" class="card-header d-none">
                        <h6 class="mb-0" id="chatTitle">Chat with User</h6>
                    </div>
                    
                    <div class="card-body p-0">
                        <div id="messagesContainer" class="chat-container">
                            <div class="text-center mt-5">
                                <i class="fas fa-comment fa-4x mb-3 text-muted"></i>
                                <h5>Welcome to Ironroot Messaging</h5>
                                <p class="text-muted">Select a user from the list to start messaging</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="messageForm" class="card-footer d-none">
                        <div class="input-group">
                            <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." maxlength="300">
                            <button class="btn btn-primary" onclick="sendMessage()">
                                <i class="fas fa-paper-plane me-1"></i>Send
                            </button>
                        </div>
                        <div class="text-end mt-1">
                            <small class="text-muted"><span id="charCount">0</span>/300</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <div class="badge bg-info fs-6">
                            <i class="fas fa-check-circle me-1"></i>Basic Messaging System - No Complex Dependencies
                        </div>
                        <div class="badge bg-success fs-6 ms-2">
                            <i class="fas fa-database me-1"></i>File-Based Storage
                        </div>
                        <div class="badge bg-warning fs-6 ms-2">
                            <i class="fas fa-user-shield me-1"></i>Auto-Login Enabled for Testing
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentChatUserId = null;
        
        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Loading basic messaging system...');
            loadUsers();
            
            // Character counter
            document.getElementById('messageInput').addEventListener('input', function() {
                document.getElementById('charCount').textContent = this.value.length;
            });
            
            // Enter key to send
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });

        function loadUsers() {
            fetch('?action=get_users')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('usersList');
                        if (data.users.length === 0) {
                            container.innerHTML = '<div class="text-muted text-center">No other users found</div>';
                        } else {
                            let html = '';
                            data.users.forEach(user => {
                                html += `
                                    <div class="user-item" onclick="selectUser(${user.id}, '${user.name}', '${user.role}')">
                                        <div class="fw-bold">${user.name}</div>
                                        <div class="small text-muted">${user.role}</div>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        }
                        console.log(`✓ Loaded ${data.users.length} users`);
                    } else {
                        document.getElementById('usersList').innerHTML = '<div class="text-danger">Failed to load users</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    document.getElementById('usersList').innerHTML = '<div class="text-danger">Error loading users</div>';
                });
        }

        function selectUser(userId, userName, userRole) {
            currentChatUserId = userId;
            
            // Update UI
            document.getElementById('chatTitle').textContent = `Chat with ${userName} (${userRole})`;
            document.getElementById('chatHeader').classList.remove('d-none');
            document.getElementById('messageForm').classList.remove('d-none');
            
            // Highlight selected user
            document.querySelectorAll('.user-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            // Load messages
            loadMessages(userId);
        }

        function loadMessages(userId) {
            fetch(`?action=get_messages&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('messagesContainer');
                        
                        if (data.messages.length === 0) {
                            container.innerHTML = `
                                <div class="text-center mt-5">
                                    <i class="fas fa-comment fa-3x text-muted mb-3"></i>
                                    <h6>No messages yet</h6>
                                    <p class="text-muted">Start the conversation!</p>
                                </div>
                            `;
                        } else {
                            let html = '';
                            data.messages.forEach(msg => {
                                const isOwn = msg.sender_id == <?php echo $current_user_id; ?>;
                                const bubbleClass = isOwn ? 'message-sent ms-auto' : 'message-received';
                                const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                                
                                html += `
                                    <div class="message-bubble ${bubbleClass}">
                                        <div>${msg.message_text}</div>
                                        <small class="opacity-75">${msg.sender_name || 'Unknown'} • ${time}</small>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        }
                        
                        container.scrollTop = container.scrollHeight;
                        console.log(`✓ Loaded ${data.messages.length} messages`);
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const messageText = messageInput.value.trim();
            
            if (!messageText || !currentChatUserId) return;
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', currentChatUserId);
            formData.append('message_text', messageText);
            
            messageInput.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageInput.disabled = false;
                
                if (data.success) {
                    messageInput.value = '';
                    document.getElementById('charCount').textContent = '0';
                    loadMessages(currentChatUserId);
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                messageInput.disabled = false;
                console.error('Error sending message:', error);
                alert('Error sending message');
            });
        }

        // Auto-refresh messages every 30 seconds
        setInterval(() => {
            if (currentChatUserId) {
                loadMessages(currentChatUserId);
            }
        }, 30000);
    </script>
</body>
</html>