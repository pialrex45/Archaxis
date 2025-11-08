<?php
// File-based Messaging System (No MySQL Required)
session_start();

// Simple authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default user
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_name'] = 'John Admin';
}

// File paths
$users_file = 'data/users.json';
$messages_file = 'data/messages.json';

// Create data directory if it doesn't exist
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

// Initialize data files if they don't exist
if (!file_exists($users_file)) {
    $default_users = [
        ['id' => 1, 'name' => 'John Admin', 'email' => 'john@ironroot.com', 'role' => 'admin'],
        ['id' => 2, 'name' => 'Jane Supervisor', 'email' => 'jane@ironroot.com', 'role' => 'supervisor'],
        ['id' => 3, 'name' => 'Bob Manager', 'email' => 'bob@ironroot.com', 'role' => 'project_manager'],
        ['id' => 4, 'name' => 'Alice Client', 'email' => 'alice@ironroot.com', 'role' => 'client'],
        ['id' => 5, 'name' => 'Mike Engineer', 'email' => 'mike@ironroot.com', 'role' => 'site_engineer']
    ];
    file_put_contents($users_file, json_encode($default_users, JSON_PRETTY_PRINT));
}

if (!file_exists($messages_file)) {
    $default_messages = [
        ['id' => 1, 'sender_id' => 1, 'receiver_id' => 2, 'message_text' => 'Hello Jane! How is the project going?', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'sender_id' => 2, 'receiver_id' => 1, 'message_text' => 'Hi John! Everything is on track.', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 3, 'sender_id' => 1, 'receiver_id' => 3, 'message_text' => 'Bob, can you check the timeline?', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 4, 'sender_id' => 3, 'receiver_id' => 1, 'message_text' => 'Sure, I will review it today.', 'created_at' => date('Y-m-d H:i:s')]
    ];
    file_put_contents($messages_file, json_encode($default_messages, JSON_PRETTY_PRINT));
}

// Load data
function loadUsers() {
    global $users_file;
    return json_decode(file_get_contents($users_file), true);
}

function loadMessages() {
    global $messages_file;
    return json_decode(file_get_contents($messages_file), true);
}

function saveMessages($messages) {
    global $messages_file;
    return file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
}

$current_user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle AJAX requests
if (!empty($action)) {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'get_users':
            $users = loadUsers();
            $filtered_users = array_filter($users, function($user) use ($current_user_id) {
                return $user['id'] != $current_user_id;
            });
            echo json_encode(['success' => true, 'users' => array_values($filtered_users)]);
            break;
            
        case 'get_conversations':
            $users = loadUsers();
            $messages = loadMessages();
            $conversations = [];
            
            // Group messages by conversation partner
            $partners = [];
            foreach ($messages as $msg) {
                if ($msg['sender_id'] == $current_user_id) {
                    $partner_id = $msg['receiver_id'];
                } elseif ($msg['receiver_id'] == $current_user_id) {
                    $partner_id = $msg['sender_id'];
                } else {
                    continue;
                }
                
                if (!isset($partners[$partner_id])) {
                    $partners[$partner_id] = [];
                }
                $partners[$partner_id][] = $msg;
            }
            
            foreach ($partners as $partner_id => $partner_messages) {
                $partner = array_values(array_filter($users, function($u) use ($partner_id) {
                    return $u['id'] == $partner_id;
                }))[0] ?? null;
                
                if ($partner) {
                    $last_message = end($partner_messages);
                    $conversations[] = [
                        'other_user_id' => $partner_id,
                        'user_name' => $partner['name'],
                        'user_role' => $partner['role'],
                        'message_count' => count($partner_messages),
                        'last_message_time' => $last_message['created_at']
                    ];
                }
            }
            
            // Sort by last message time
            usort($conversations, function($a, $b) {
                return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
            });
            
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;
            
        case 'get_messages':
            $other_user_id = (int)$_GET['user_id'];
            $users = loadUsers();
            $messages = loadMessages();
            
            // Get user names for display
            $user_names = [];
            foreach ($users as $user) {
                $user_names[$user['id']] = $user['name'];
            }
            
            // Filter messages for this conversation
            $conversation_messages = array_filter($messages, function($msg) use ($current_user_id, $other_user_id) {
                return ($msg['sender_id'] == $current_user_id && $msg['receiver_id'] == $other_user_id) ||
                       ($msg['sender_id'] == $other_user_id && $msg['receiver_id'] == $current_user_id);
            });
            
            // Add sender names
            foreach ($conversation_messages as &$msg) {
                $msg['sender_name'] = $user_names[$msg['sender_id']] ?? 'Unknown';
            }
            
            // Sort by time
            usort($conversation_messages, function($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
            
            echo json_encode(['success' => true, 'messages' => array_values($conversation_messages)]);
            break;
            
        case 'send_message':
            $receiver_id = (int)$_POST['receiver_id'];
            $message_text = trim($_POST['message_text']);
            
            if ($receiver_id && $message_text) {
                $messages = loadMessages();
                
                // Generate new ID
                $max_id = 0;
                foreach ($messages as $msg) {
                    if ($msg['id'] > $max_id) $max_id = $msg['id'];
                }
                
                $new_message = [
                    'id' => $max_id + 1,
                    'sender_id' => $current_user_id,
                    'receiver_id' => $receiver_id,
                    'message_text' => $message_text,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $messages[] = $new_message;
                
                if (saveMessages($messages)) {
                    echo json_encode(['success' => true, 'message' => 'Message sent']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to save message']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing data']);
            }
            break;
            
        case 'get_stats':
            $users = loadUsers();
            $messages = loadMessages();
            
            $my_messages = array_filter($messages, function($msg) use ($current_user_id) {
                return $msg['sender_id'] == $current_user_id || $msg['receiver_id'] == $current_user_id;
            });
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_users' => count($users),
                    'total_messages' => count($messages),
                    'my_messages' => count($my_messages)
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// Get current user info
$users = loadUsers();
$current_user = array_values(array_filter($users, function($u) use ($current_user_id) {
    return $u['id'] == $current_user_id;
}))[0] ?? ['name' => 'Test User', 'role' => 'admin'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ironroot Messaging System - File Based</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container { 
            height: 450px; 
            overflow-y: auto; 
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .message-bubble { 
            max-width: 75%; 
            margin-bottom: 15px; 
            padding: 12px 18px;
            border-radius: 20px;
            position: relative;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .message-sent { 
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white; 
            margin-left: auto; 
            text-align: right;
        }
        .message-received { 
            background: white;
            border: 1px solid #e9ecef;
        }
        .conversation-item { 
            cursor: pointer; 
            transition: all 0.3s ease;
            border-radius: 10px;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
        }
        .conversation-item:hover { 
            background-color: #e3f2fd; 
            transform: translateX(8px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .conversation-item.active { 
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white; 
            transform: translateX(12px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .success-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: bold;
        }
        .status-online {
            color: #28a745;
            font-weight: bold;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-comments me-2"></i>Ironroot Messaging System</h4>
                                <small><span class="success-badge">FILE-BASED • NO DATABASE REQUIRED</span></small>
                                <br><small>Logged in as: <?php echo htmlspecialchars($current_user['name']); ?> (<?php echo htmlspecialchars($current_user['role']); ?>)</small>
                            </div>
                            <div id="statsDisplay" class="text-end">
                                <div class="success-badge">
                                    <i class="fas fa-check-circle"></i> System Ready
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>System Status:</strong> <span class="status-online">ONLINE</span> • 
                            File-based storage active • No MySQL connection required
                        </div>
                        
                        <div class="row">
                            <!-- Users & Conversations -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Start New Chat</h6>
                                    </div>
                                    <div class="card-body">
                                        <select id="userSelect" class="form-select mb-3">
                                            <option value="">Loading users...</option>
                                        </select>
                                        <button class="btn btn-success btn-sm w-100" onclick="refreshData()">
                                            <i class="fas fa-sync me-2"></i>Refresh All
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="card mt-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Active Conversations</h6>
                                    </div>
                                    <div class="card-body p-2">
                                        <div id="conversationsList" style="max-height: 320px; overflow-y: auto;">
                                            Loading conversations...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Chat Area -->
                            <div class="col-md-8">
                                <div id="chatHeader" class="d-none mb-3 p-3 bg-light rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3" id="chatAvatar">U</div>
                                        <div>
                                            <h5 class="mb-0" id="chatUserName">Select a user</h5>
                                            <small class="text-muted" id="chatUserRole">Role</small>
                                        </div>
                                        <div class="ms-auto">
                                            <span class="badge bg-success">
                                                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>Online
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="messagesContainer" class="chat-container mb-3">
                                    <div class="text-center mt-5">
                                        <i class="fas fa-comments fa-4x mb-3 text-primary opacity-75"></i>
                                        <h4 class="text-primary">Welcome to Ironroot Messaging</h4>
                                        <p class="text-muted">Select a user from the list to start a conversation</p>
                                        <div class="success-badge d-inline-block">
                                            <i class="fas fa-database me-2"></i>File-based system ready
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="messageForm" class="d-none">
                                    <div class="input-group input-group-lg">
                                        <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." maxlength="500">
                                        <button class="btn btn-primary btn-lg px-4" onclick="sendMessage()">
                                            <i class="fas fa-paper-plane me-2"></i>Send
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="text-muted">Press Enter to send message</small>
                                        <small class="text-muted"><span id="charCount">0</span>/500 characters</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentChatUserId = null;
        let messageRefreshInterval = null;
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing file-based messaging system...');
            refreshData();
            loadStats();
            
            // Auto-refresh every 15 seconds
            setInterval(() => {
                loadConversations();
                if (currentChatUserId) {
                    loadMessages(currentChatUserId);
                }
            }, 15000);
            
            // Character counter
            document.getElementById('messageInput').addEventListener('input', function() {
                document.getElementById('charCount').textContent = this.value.length;
            });
        });
        
        function refreshData() {
            loadUsers();
            loadConversations();
            if (currentChatUserId) {
                loadMessages(currentChatUserId);
            }
            loadStats();
        }
        
        function loadUsers() {
            console.log('Loading users...');
            fetch('?action=get_users')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('userSelect');
                        select.innerHTML = '<option value="">Choose someone to message...</option>';
                        
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = `${user.name} (${user.role})`;
                            select.appendChild(option);
                        });
                        console.log(`✓ Loaded ${data.users.length} users`);
                    } else {
                        console.error('Failed to load users:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                });
        }
        
        function loadConversations() {
            fetch('?action=get_conversations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('conversationsList');
                        
                        if (data.conversations.length === 0) {
                            container.innerHTML = `
                                <div class="text-center p-4">
                                    <i class="fas fa-comment-dots fa-2x text-muted mb-2"></i>
                                    <div class="text-muted">No conversations yet</div>
                                    <small>Start messaging someone!</small>
                                </div>
                            `;
                        } else {
                            let html = '';
                            data.conversations.forEach(conv => {
                                const isActive = conv.other_user_id == currentChatUserId ? 'active' : '';
                                const avatar = conv.user_name.charAt(0).toUpperCase();
                                html += `
                                    <div class="conversation-item p-3 ${isActive}" onclick="openChat(${conv.other_user_id}, '${conv.user_name}', '${conv.user_role}')">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3" style="width: 35px; height: 35px; font-size: 14px;">${avatar}</div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">${conv.user_name}</div>
                                                <small class="opacity-75">${conv.message_count} messages • ${new Date(conv.last_message_time).toLocaleDateString()}</small>
                                            </div>
                                            <div class="text-end">
                                                <i class="fas fa-chevron-right"></i>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        }
                        console.log(`✓ Loaded ${data.conversations.length} conversations`);
                    }
                })
                .catch(error => {
                    console.error('Error loading conversations:', error);
                });
        }
        
        function openChat(userId, userName, userRole) {
            console.log(`Opening chat with ${userName} (ID: ${userId})`);
            currentChatUserId = userId;
            
            // Update UI
            document.getElementById('chatUserName').textContent = userName;
            document.getElementById('chatUserRole').textContent = userRole;
            document.getElementById('chatAvatar').textContent = userName.charAt(0).toUpperCase();
            document.getElementById('chatHeader').classList.remove('d-none');
            document.getElementById('messageForm').classList.remove('d-none');
            
            // Update conversations list
            loadConversations();
            
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
                                    <h5>No messages yet</h5>
                                    <p class="text-muted">Start the conversation by sending a message!</p>
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
                                        <div class="mb-1">${msg.message_text}</div>
                                        <small class="opacity-75">
                                            <i class="fas fa-user me-1"></i>${msg.sender_name} • 
                                            <i class="fas fa-clock me-1"></i>${time}
                                        </small>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        }
                        
                        // Scroll to bottom
                        container.scrollTop = container.scrollHeight;
                        console.log(`✓ Loaded ${data.messages.length} messages`);
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }
        
        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const messageText = messageInput.value.trim();
            
            if (!messageText || !currentChatUserId) {
                return;
            }
            
            if (messageText.length > 500) {
                alert('Message is too long. Maximum 500 characters.');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', currentChatUserId);
            formData.append('message_text', messageText);
            
            // Disable input while sending
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
                    loadConversations();
                    loadStats();
                    console.log('✓ Message sent successfully');
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                messageInput.disabled = false;
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            });
        }
        
        function loadStats() {
            fetch('?action=get_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.stats;
                        document.getElementById('statsDisplay').innerHTML = `
                            <div class="success-badge">
                                <i class="fas fa-users me-1"></i>${stats.total_users} Users • 
                                <i class="fas fa-comments me-1"></i>${stats.total_messages} Messages • 
                                <i class="fas fa-user-check me-1"></i>${stats.my_messages} Your Messages
                            </div>
                        `;
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }
        
        // Handle Enter key
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.id === 'messageInput' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Handle user selection
        document.getElementById('userSelect').addEventListener('change', function() {
            const userId = this.value;
            if (userId) {
                const userName = this.options[this.selectedIndex].text.split(' (')[0];
                const userRole = this.options[this.selectedIndex].text.match(/\(([^)]+)\)/)[1];
                openChat(parseInt(userId), userName, userRole);
                this.value = '';
            }
        });
    </script>
</body>
</html>