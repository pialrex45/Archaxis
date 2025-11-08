<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../controllers/MessageController.php';

// Check if user is authenticated
if (!isAuthenticated()) {
    header('Location: /login');
    exit();
}

// Get message threads for the current user
$messageController = new MessageController();
$threadsResult = $messageController->getThreads();

$threads = [];
if ($threadsResult['success']) {
    $threads = $threadsResult['data'];
}
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Messages</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                        <span data-feather="refresh-cw"></span>
                        Refresh
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Left Panel - Message Threads -->
                <div class="col-md-4 border-end">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Inbox</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <span data-feather="plus"></span> New Message
                        </button>
                    </div>
                    
                    <div id="messageThreads">
                        <?php if (empty($threads)): ?>
                            <div class="text-center py-5">
                                <p class="text-muted">No messages yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($threads as $thread): ?>
                                <div class="card mb-2 thread-item" data-thread-id="<?= $thread['id'] ?>">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($thread['other_user_name']) ?></h6>
                                            <small class="text-muted"><?= timeAgo($thread['created_at']) ?></small>
                                        </div>
                                        <p class="mb-1 text-truncate"><?= htmlspecialchars(truncateText($thread['message_text'], 50)) ?></p>
                                        <?php if (!empty($thread['file_path'])): ?>
                                            <small class="text-muted">
                                                <span data-feather="paperclip"></span> Attachment
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Center Panel - Chat View -->
                <div class="col-md-8">
                    <div id="chatContainer" class="d-flex flex-column" style="height: calc(100vh - 200px);">
                        <div class="text-center py-5">
                            <p class="text-muted">Select a conversation to start messaging</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newMessageForm">
                    <div class="mb-3">
                        <label for="receiverId" class="form-label">To</label>
                        <select class="form-select" id="receiverId" name="receiver_id" required>
                            <option value="">Select recipient</option>
                            <!-- Users will be populated by JavaScript -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="messageText" class="form-label">Message</label>
                        <textarea class="form-control" id="messageText" name="message_text" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="attachment" class="form-label">Attachment (Optional)</label>
                        <input class="form-control" type="file" id="attachment" name="attachment">
                        <div class="form-text">Allowed file types: JPG, JPEG, PNG, PDF, DOC, DOCX. Max size: 5MB</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendMessageBtn">Send</button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 3 seconds
let refreshInterval = setInterval(fetchThreads, 3000);

// Refresh button click handler
document.getElementById('refreshBtn').addEventListener('click', function() {
    fetchThreads();
});

// Fetch message threads
function fetchThreads() {
    fetch('/api/messages/fetch.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateThreadsList(data.data);
            }
        })
        .catch(error => {
            console.error('Error fetching threads:', error);
        });
}

// Update threads list in UI
function updateThreadsList(threads) {
    const threadsContainer = document.getElementById('messageThreads');
    
    if (threads.length === 0) {
        threadsContainer.innerHTML = `
            <div class="text-center py-5">
                <p class="text-muted">No messages yet</p>
            </div>
        `;
        return;
    }
    
    let threadsHtml = '';
    threads.forEach(thread => {
        threadsHtml += `
            <div class="card mb-2 thread-item" data-thread-id="${thread.id}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-1">${thread.other_user_name}</h6>
                        <small class="text-muted">${timeAgo(thread.created_at)}</small>
                    </div>
                    <p class="mb-1 text-truncate">${truncateText(thread.message_text, 50)}</p>
                    ${thread.file_path ? '<small class="text-muted"><span data-feather="paperclip"></span> Attachment</small>' : ''}
                </div>
            </div>
        `;
    });
    
    threadsContainer.innerHTML = threadsHtml;
    
    // Reinitialize Feather icons
    feather.replace();
    
    // Add click handlers to thread items
    document.querySelectorAll('.thread-item').forEach(item => {
        item.addEventListener('click', function() {
            const threadId = this.getAttribute('data-thread-id');
            loadThread(threadId);
        });
    });
}

// Load thread messages
function loadThread(threadId) {
    fetch(`/api/messages/fetch.php?thread_id=${threadId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayThread(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading thread:', error);
        });
}

// Display thread messages
function displayThread(messages) {
    const chatContainer = document.getElementById('chatContainer');
    
    if (messages.length === 0) {
        chatContainer.innerHTML = `
            <div class="text-center py-5">
                <p class="text-muted">No messages in this conversation</p>
            </div>
        `;
        return;
    }
    
    let messagesHtml = '';
    messages.forEach(message => {
        messagesHtml += `
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-1">${message.sender_name}</h6>
                        <small class="text-muted">${formatDate(message.created_at)}</small>
                    </div>
                    <p class="mb-2">${message.message_text}</p>
                    ${message.file_path ? `
                        <div class="mt-2">
                            <a href="${message.file_path}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <span data-feather="paperclip"></span> View Attachment
                            </a>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    // Add reply form
    messagesHtml += `
        <div class="mt-auto">
            <form id="replyForm">
                <div class="mb-3">
                    <textarea class="form-control" id="replyText" name="message_text" rows="3" placeholder="Type your message..." required></textarea>
                </div>
                <div class="mb-3">
                    <input class="form-control" type="file" id="replyAttachment" name="attachment">
                </div>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
    `;
    
    chatContainer.innerHTML = messagesHtml;
    
    // Reinitialize Feather icons
    feather.replace();
    
    // Add submit handler for reply form
    document.getElementById('replyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        sendReply(messages[0].id); // Use the first message ID as thread ID
    });
}

// Send reply
function sendReply(threadId) {
    const formData = new FormData();
    formData.append('message_text', document.getElementById('replyText').value);
    formData.append('attachment', document.getElementById('replyAttachment').files[0]);
    
    fetch(`/api/messages/reply.php?reply_to=${threadId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('replyText').value = '';
            document.getElementById('replyAttachment').value = '';
            loadThread(threadId); // Reload the thread
        } else {
            alert('Error sending message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending reply:', error);
        alert('Error sending message');
    });
}

// Send new message
document.getElementById('sendMessageBtn').addEventListener('click', function() {
    const formData = new FormData(document.getElementById('newMessageForm'));
    
    fetch('/api/messages/send.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('newMessageForm').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('newMessageModal'));
            modal.hide();
            fetchThreads(); // Refresh threads
        } else {
            alert('Error sending message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Error sending message');
    });
});

// Helper functions
function timeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    let interval = Math.floor(seconds / 31536000);
    if (interval > 1) return interval + ' years ago';
    if (interval === 1) return '1 year ago';
    
    interval = Math.floor(seconds / 2592000);
    if (interval > 1) return interval + ' months ago';
    if (interval === 1) return '1 month ago';
    
    interval = Math.floor(seconds / 86400);
    if (interval > 1) return interval + ' days ago';
    if (interval === 1) return '1 day ago';
    
    interval = Math.floor(seconds / 3600);
    if (interval > 1) return interval + ' hours ago';
    if (interval === 1) return '1 hour ago';
    
    interval = Math.floor(seconds / 60);
    if (interval > 1) return interval + ' minutes ago';
    if (interval === 1) return '1 minute ago';
    
    return 'Just now';
}

function formatDate(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
}

function truncateText(text, length) {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>