<?php
// Fixed Simple Messaging System
session_start();

// Simple authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default to admin user
    $_SESSION['user_role'] = 'admin';
}

// Try to use working config, fallback to default
if (file_exists('config_working.php')) {
    include 'config_working.php';
} else {
    // Fallback database connection
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=Construction_management;charset=utf8", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;dbname=construction_management;charset=utf8", 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e2) {
            die("<div class='alert alert-danger'>Database connection failed. Please run <a href='fix_database.php'>fix_database.php</a> first.</div>");
        }
    }
}

$current_user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle AJAX requests
if (!empty($action)) {
    header('Content-Type: application/json');
    
    try {
        switch ($action) {
            case 'get_users':
                $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id != ? ORDER BY name");
                $stmt->execute([$current_user_id]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'users' => $users]);
                break;
                
            case 'get_conversations':
                $stmt = $pdo->prepare("
                    SELECT DISTINCT
                        CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
                        u.name as user_name,
                        u.role as user_role,
                        MAX(m.created_at) as last_message_time,
                        COUNT(*) as message_count
                    FROM messages m
                    JOIN users u ON (
                        (m.sender_id = ? AND u.id = m.receiver_id) OR 
                        (m.receiver_id = ? AND u.id = m.sender_id)
                    )
                    WHERE m.sender_id = ? OR m.receiver_id = ?
                    GROUP BY other_user_id, u.name, u.role
                    ORDER BY last_message_time DESC
                ");
                $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
                $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'conversations' => $conversations]);
                break;
                
            case 'get_messages':
                $other_user_id = (int)$_GET['user_id'];
                $stmt = $pdo->prepare("
                    SELECT m.*, u.name as sender_name
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE 
                        (m.sender_id = ? AND m.receiver_id = ?) OR
                        (m.sender_id = ? AND m.receiver_id = ?)
                    ORDER BY m.created_at ASC
                ");
                $stmt->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'messages' => $messages]);
                break;
                
            case 'send_message':
                $receiver_id = (int)$_POST['receiver_id'];
                $message_text = trim($_POST['message_text']);
                
                if ($receiver_id && $message_text) {
                    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$current_user_id, $receiver_id, $message_text]);
                    echo json_encode(['success' => true, 'message' => 'Message sent', 'id' => $pdo->lastInsertId()]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Missing receiver or message text']);
                }
                break;
                
            case 'get_stats':
                $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $message_count = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
                $my_messages = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? OR receiver_id = ?");
                $my_messages->execute([$current_user_id, $current_user_id]);
                $my_message_count = $my_messages->fetchColumn();
                
                echo json_encode([
                    'success' => true, 
                    'stats' => [
                        'total_users' => $user_count,
                        'total_messages' => $message_count,
                        'my_messages' => $my_message_count
                    ]
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get current user info
try {
    $stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = ?");
    $stmt->execute([$current_user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$current_user) {
        $current_user = ['name' => 'Unknown User', 'role' => 'admin'];
    }
} catch (Exception $e) {
    $current_user = ['name' => 'Test User', 'role' => 'admin'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ironroot Messaging System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container { 
            height: 450px; 
            overflow-y: auto; 
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        .message-bubble { 
            max-width: 75%; 
            margin-bottom: 15px; 
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
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
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .conversation-item { 
            cursor: pointer; 
            transition: all 0.2s;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        .conversation-item:hover { 
            background-color: #e9ecef; 
            transform: translateX(5px);
        }
        .conversation-item.active { 
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white; 
            transform: translateX(10px);
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .online-status {
            width: 12px;
            height: 12px;
            background: #28a745;
            border-radius: 50%;
            border: 2px solid white;
            position: absolute;
            bottom: 0;
            right: 0;
        }
        .stats-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-comments me-2"></i>Ironroot Messaging System</h4>
                                <small>Logged in as: <?php echo htmlspecialchars($current_user['name']); ?> (<?php echo htmlspecialchars($current_user['role']); ?>)</small>
                            </div>
                            <div id="statsDisplay" class="text-end">
                                <small>Loading stats...</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Users & Conversations -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Start New Chat</h6>
                                    </div>
                                    <div class="card-body">
                                        <select id="userSelect" class="form-select mb-3">
                                            <option value="">Loading users...</option>
                                        </select>
                                        <button class="btn btn-success btn-sm w-100" onclick="refreshData()">
                                            <i class="fas fa-refresh me-2"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Conversations</h6>
                                    </div>
                                    <div class="card-body p-2">
                                        <div id="conversationsList" style="max-height: 300px; overflow-y: auto;">
                                            Loading conversations...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Chat Area -->
                            <div class="col-md-8">
                                <div id="chatHeader" class="d-none mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3" id="chatAvatar">U</div>
                                        <div>
                                            <h5 class="mb-0" id="chatUserName">Select a user</h5>
                                            <small class="text-muted" id="chatUserRole">Role</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="messagesContainer" class="chat-container mb-3">
                                    <div class="text-muted text-center mt-5">
                                        <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
                                        <h5>Welcome to Ironroot Messaging</h5>
                                        <p>Select a user from the list to start a conversation</p>
                                    </div>
                                </div>
                                
                                <div id="messageForm" class="d-none">
                                    <div class="input-group">
                                        <input type="text" id="messageInput" class="form-control form-control-lg" placeholder="Type your message..." maxlength="500">
                                        <button class="btn btn-primary btn-lg" onclick="sendMessage()">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                    <div class="text-muted small mt-2">
                                        Press Enter to send • <span id="charCount">0</span>/500 characters
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
            console.log('Initializing messaging system...');
            refreshData();
            loadStats();
            
            // Auto-refresh every 30 seconds
            setInterval(loadConversations, 30000);
            
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
                        console.log(`Loaded ${data.users.length} users`);
                    } else {
                        console.error('Failed to load users:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    document.getElementById('userSelect').innerHTML = '<option value="">Error loading users</option>';
                });
        }
        
        function loadConversations() {
            fetch('?action=get_conversations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('conversationsList');
                        
                        if (data.conversations.length === 0) {
                            container.innerHTML = '<div class="text-muted small text-center p-3">No conversations yet<br>Start messaging someone!</div>';
                        } else {
                            let html = '';
                            data.conversations.forEach(conv => {
                                const isActive = conv.other_user_id == currentChatUserId ? 'active' : '';
                                const avatar = conv.user_name.charAt(0).toUpperCase();
                                html += `
                                    <div class="conversation-item p-2 ${isActive}" onclick="openChat(${conv.other_user_id}, '${conv.user_name}', '${conv.user_role}')">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 12px;">${avatar}</div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small">${conv.user_name}</div>
                                                <div class="text-muted small">${conv.message_count} messages • ${new Date(conv.last_message_time).toLocaleDateString()}</div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        }
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
            
            // Start auto-refresh for active chat
            if (messageRefreshInterval) clearInterval(messageRefreshInterval);
            messageRefreshInterval = setInterval(() => loadMessages(userId), 10000);
        }
        
        function loadMessages(userId) {
            fetch(`?action=get_messages&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('messagesContainer');
                        
                        if (data.messages.length === 0) {
                            container.innerHTML = '<div class="text-muted text-center mt-5">No messages yet. Start the conversation!</div>';
                        } else {
                            let html = '';
                            data.messages.forEach(msg => {
                                const isOwn = msg.sender_id == <?php echo $current_user_id; ?>;
                                const bubbleClass = isOwn ? 'message-sent ms-auto' : 'message-received';
                                const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                                
                                html += `
                                    <div class="message-bubble ${bubbleClass}">
                                        <div class="mb-1">${msg.message_text}</div>
                                        <small class="opacity-75">${msg.sender_name} • ${time}</small>
                                    </div>
                                `;
                            });
                            container.innerHTML = html;
                        }
                        
                        // Scroll to bottom
                        container.scrollTop = container.scrollHeight;
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
                            <div class="small">
                                Users: ${stats.total_users} • 
                                Total Messages: ${stats.total_messages} • 
                                Your Messages: ${stats.my_messages}
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