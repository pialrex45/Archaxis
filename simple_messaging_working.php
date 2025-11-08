<?php
// Simple working messaging system
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default user for testing
    $_SESSION['user_role'] = 'admin';
}

// Database connection
try {
    $host = 'localhost';
    $dbname = 'Construction_management';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$current_user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle AJAX requests
if (!empty($action)) {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'get_users':
            $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id != ? ORDER BY name");
            $stmt->execute([$current_user_id]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            exit;
            
        case 'get_conversations':
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
                    u.name as user_name,
                    MAX(m.created_at) as last_message_time
                FROM messages m
                JOIN users u ON (
                    (m.sender_id = ? AND u.id = m.receiver_id) OR 
                    (m.receiver_id = ? AND u.id = m.sender_id)
                )
                WHERE m.sender_id = ? OR m.receiver_id = ?
                GROUP BY other_user_id, u.name
                ORDER BY last_message_time DESC
            ");
            $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            exit;
            
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
            exit;
            
        case 'send_message':
            $receiver_id = (int)$_POST['receiver_id'];
            $message_text = trim($_POST['message_text']);
            
            if ($receiver_id && $message_text) {
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$current_user_id, $receiver_id, $message_text]);
                echo json_encode(['success' => true, 'message' => 'Message sent']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing data']);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Messaging System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container { height: 500px; overflow-y: auto; }
        .message-bubble { max-width: 80%; margin-bottom: 10px; }
        .message-sent { background-color: #007bff; color: white; margin-left: auto; }
        .message-received { background-color: #f8f9fa; }
        .conversation-item { cursor: pointer; }
        .conversation-item:hover { background-color: #f8f9fa; }
        .conversation-item.active { background-color: #007bff; color: white; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-comments me-2"></i>Simple Messaging System</h3>
                        <small class="text-muted">Logged in as User ID: <?php echo $current_user_id; ?></small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Users List -->
                            <div class="col-md-4">
                                <h5>Start New Conversation</h5>
                                <div class="mb-3">
                                    <select id="userSelect" class="form-select">
                                        <option value="">Loading users...</option>
                                    </select>
                                </div>
                                
                                <h6>Recent Conversations</h6>
                                <div id="conversationsList" class="border rounded p-2" style="height: 200px; overflow-y: auto;">
                                    Loading conversations...
                                </div>
                            </div>
                            
                            <!-- Chat Area -->
                            <div class="col-md-8">
                                <div id="chatHeader" class="d-none mb-3">
                                    <h6>Chat with: <span id="chatUserName">Select a user</span></h6>
                                </div>
                                
                                <div id="messagesContainer" class="border rounded p-3 chat-container mb-3">
                                    <div class="text-muted text-center">Select a user to start messaging</div>
                                </div>
                                
                                <div id="messageForm" class="d-none">
                                    <div class="input-group">
                                        <input type="text" id="messageInput" class="form-control" placeholder="Type your message...">
                                        <button class="btn btn-primary" onclick="sendMessage()">
                                            <i class="fas fa-paper-plane"></i> Send
                                        </button>
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
        let currentUserId = null;
        let currentChatUserId = null;
        
        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            loadConversations();
            
            // Auto-refresh conversations every 30 seconds
            setInterval(loadConversations, 30000);
        });
        
        function loadUsers() {
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
                            container.innerHTML = '<div class="text-muted small">No conversations yet</div>';
                        } else {
                            let html = '';
                            data.conversations.forEach(conv => {
                                html += `
                                    <div class="conversation-item p-2 border-bottom" onclick="openChat(${conv.other_user_id}, '${conv.user_name}')">
                                        <div class="fw-bold">${conv.user_name}</div>
                                        <div class="small text-muted">${new Date(conv.last_message_time).toLocaleDateString()}</div>
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
        
        function openChat(userId, userName) {
            currentChatUserId = userId;
            
            // Update UI
            document.getElementById('chatUserName').textContent = userName;
            document.getElementById('chatHeader').classList.remove('d-none');
            document.getElementById('messageForm').classList.remove('d-none');
            
            // Highlight active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
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
                            container.innerHTML = '<div class="text-muted text-center">No messages yet. Start the conversation!</div>';
                        } else {
                            let html = '';
                            data.messages.forEach(msg => {
                                const isOwn = msg.sender_id == <?php echo $current_user_id; ?>;
                                const bubbleClass = isOwn ? 'message-sent ms-auto' : 'message-received';
                                
                                html += `
                                    <div class="message-bubble ${bubbleClass} p-2 rounded">
                                        <div>${msg.message_text}</div>
                                        <small class="opacity-75">${msg.sender_name} - ${new Date(msg.created_at).toLocaleTimeString()}</small>
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
                alert('Please enter a message and select a user');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', currentChatUserId);
            formData.append('message_text', messageText);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadMessages(currentChatUserId);
                    loadConversations();
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message');
            });
        }
        
        // Handle Enter key in message input
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.id === 'messageInput') {
                sendMessage();
            }
        });
        
        // Handle user selection from dropdown
        document.getElementById('userSelect').addEventListener('change', function() {
            const userId = this.value;
            const userName = this.options[this.selectedIndex].text;
            
            if (userId) {
                openChat(parseInt(userId), userName.split(' (')[0]);
                this.value = ''; // Reset dropdown
            }
        });
    </script>
</body>
</html>