<?php
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../controllers/MessageController.php';

// Check if user is authenticated
if (!isAuthenticated()) {
    header('Location: /login');
    exit();
}

// Get thread ID from URL parameter
$threadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($threadId <= 0) {
    header('Location: /messages');
    exit();
}

// Get messages for this thread
$messageController = new MessageController();
$messagesResult = $messageController->getConversation($threadId);

$messages = [];
if ($messagesResult['success']) {
    $messages = $messagesResult['data'];
}

// Get the other user in the conversation
$otherUser = null;
if (!empty($messages)) {
    $currentUserId = getCurrentUserId();
    $otherUser = $messages[0]['sender_id'] == $currentUserId ? 
        ['id' => $messages[0]['receiver_id'], 'name' => $messages[0]['receiver_name']] : 
        ['id' => $messages[0]['sender_id'], 'name' => $messages[0]['sender_name']];
}
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?php if ($otherUser): ?>
                        Conversation with <?= htmlspecialchars($otherUser['name']) ?>
                    <?php else: ?>
                        Message Thread
                    <?php endif; ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                        <span data-feather="refresh-cw"></span>
                        Refresh
                    </button>
                    <a href="/messages" class="btn btn-sm btn-outline-primary ms-2">
                        <span data-feather="inbox"></span>
                        Back to Inbox
                    </a>
                </div>
            </div>

            <?php if (empty($messages)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">No messages in this conversation</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Messages Container -->
                    <div class="col-12">
                        <div id="messagesContainer" class="d-flex flex-column" style="height: calc(100vh - 300px); overflow-y: auto;">
                            <?php foreach ($messages as $message): ?>
                                <div class="card mb-3 <?= $message['sender_id'] == getCurrentUserId() ? 'bg-light' : '' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($message['sender_name']) ?></h6>
                                            <small class="text-muted"><?= formatDate($message['created_at']) ?></small>
                                        </div>
                                        <p class="mb-2"><?= nl2brSafe($message['message_text']) ?></p>
                                        <?php if (!empty($message['file_path'])): ?>
                                            <div class="mt-2">
                                                <?php
                                                $fileExtension = getFileExtension($message['file_path']);
                                                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']);
                                                $isPdf = $fileExtension === 'pdf';
                                                ?>
                                                
                                                <?php if ($isImage): ?>
                                                    <div class="mb-2">
                                                        <img src="<?= htmlspecialchars($message['file_path']) ?>" alt="Attachment" class="img-thumbnail" style="max-height: 200px;">
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($isPdf): ?>
                                                    <div class="mb-2">
                                                        <iframe src="<?= htmlspecialchars($message['file_path']) ?>" width="100%" height="300px"></iframe>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <a href="<?= htmlspecialchars($message['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <span data-feather="paperclip"></span> 
                                                    <?= $isImage ? 'View Image' : ($isPdf ? 'View PDF' : 'Download Attachment') ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Reply Form -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Reply</h5>
                                <form id="replyForm">
                                    <div class="mb-3">
                                        <textarea class="form-control" id="replyText" name="message_text" rows="3" placeholder="Type your message..." required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="attachment" class="form-label">Attachment (Optional)</label>
                                        <input class="form-control" type="file" id="attachment" name="attachment">
                                        <div class="form-text">Allowed file types: JPG, JPEG, PNG, PDF, DOC, DOCX. Max size: 5MB</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Send Reply</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Auto-refresh every 3 seconds
let refreshInterval = setInterval(fetchMessages, 3000);

// Refresh button click handler
document.getElementById('refreshBtn').addEventListener('click', function() {
    fetchMessages();
});

// Fetch messages for this thread
function fetchMessages() {
    fetch(`/api/messages/fetch.php?thread_id=<?= $threadId ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMessagesList(data.data);
            }
        })
        .catch(error => {
            console.error('Error fetching messages:', error);
        });
}

// Update messages list in UI
function updateMessagesList(messages) {
    const messagesContainer = document.getElementById('messagesContainer');
    
    if (messages.length === 0) {
        messagesContainer.innerHTML = `
            <div class="text-center py-5">
                <p class="text-muted">No messages in this conversation</p>
            </div>
        `;
        return;
    }
    
    let messagesHtml = '';
    messages.forEach(message => {
        const isCurrentUser = message.sender_id == <?= getCurrentUserId() ?>;
        const cardClass = isCurrentUser ? 'bg-light' : '';
        
        messagesHtml += `
            <div class="card mb-3 ${cardClass}">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-1">${message.sender_name}</h6>
                        <small class="text-muted">${formatDate(message.created_at)}</small>
                    </div>
                    <p class="mb-2">${message.message_text}</p>
                    ${message.file_path ? `
                        <div class="mt-2">
                            ${getFilePreview(message.file_path)}
                            <a href="${message.file_path}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <span data-feather="paperclip"></span> 
                                ${getFileActionText(message.file_path)}
                            </a>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });
    
    messagesContainer.innerHTML = messagesHtml;
    
    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Reinitialize Feather icons
    feather.replace();
}

// Get file preview based on file type
function getFilePreview(filePath) {
    const extension = filePath.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(extension);
    const isPdf = extension === 'pdf';
    
    if (isImage) {
        return `<div class="mb-2"><img src="${filePath}" alt="Attachment" class="img-thumbnail" style="max-height: 200px;"></div>`;
    } else if (isPdf) {
        return `<div class="mb-2"><iframe src="${filePath}" width="100%" height="300px"></iframe></div>`;
    }
    
    return '';
}

// Get file action text based on file type
function getFileActionText(filePath) {
    const extension = filePath.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(extension);
    const isPdf = extension === 'pdf';
    
    if (isImage) return 'View Image';
    if (isPdf) return 'View PDF';
    return 'Download Attachment';
}

// Handle reply form submission
document.getElementById('replyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('message_text', document.getElementById('replyText').value);
    formData.append('attachment', document.getElementById('attachment').files[0]);
    
    fetch('/api/messages/reply.php?reply_to=<?= $threadId ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('replyText').value = '';
            document.getElementById('attachment').value = '';
            fetchMessages(); // Refresh messages
        } else {
            alert('Error sending reply: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error sending reply:', error);
        alert('Error sending reply');
    });
});

// Helper function to format date
function formatDate(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>