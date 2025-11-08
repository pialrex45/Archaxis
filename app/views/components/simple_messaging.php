<?php
// Simple Messaging Component
?>

<!-- Simple Messages Modal -->
<div class="modal fade" id="messagesModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-comments me-2"></i>Messages
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0" style="height: 600px;">
                    <!-- Conversations List -->
                    <div class="col-4 border-end">
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Conversations</h6>
                                <button class="btn btn-sm btn-primary" onclick="simpleMessaging.showNewMessageForm()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="conversations-list" style="height: calc(100% - 80px); overflow-y: auto;">
                            <div id="conversationsList">
                                <div class="text-center text-muted p-4">
                                    <i class="fas fa-spinner fa-spin"></i> Loading conversations...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Area -->
                    <div class="col-8">
                        <div id="chatArea">
                            <!-- Welcome Screen -->
                            <div id="welcomeScreen" class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-muted">
                                    <i class="fas fa-comments fa-3x mb-3"></i>
                                    <h5>Select a conversation</h5>
                                    <p>Choose a conversation from the list to start messaging</p>
                                </div>
                            </div>
                            
                            <!-- Active Chat -->
                            <div id="activeChat" class="d-none h-100 d-flex flex-column">
                                <!-- Chat Header -->
                                <div class="p-3 border-bottom bg-light">
                                    <h6 class="mb-0" id="chatTitle">Select Conversation</h6>
                                    <small class="text-muted" id="chatInfo">No conversation selected</small>
                                </div>
                                
                                <!-- Messages -->
                                <div class="flex-grow-1 p-3" style="overflow-y: auto;" id="messagesContainer">
                                    <!-- Messages will appear here -->
                                </div>
                                
                                <!-- Message Input -->
                                <div class="p-3 border-top">
                                    <form id="messageForm" onsubmit="simpleMessaging.sendMessage(event)">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="messageInput" placeholder="Type a message..." required>
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newMessageForm" onsubmit="simpleMessaging.startNewConversation(event)">
                    <div class="mb-3">
                        <label class="form-label">Send message to:</label>
                        <select class="form-select" id="recipientSelect" required>
                            <option value="">Choose a person...</option>
                        </select>
                        <div id="recipientLoadError" class="text-danger mt-2 d-none"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message:</label>
                        <textarea class="form-control" id="newMessageText" rows="3" placeholder="Type your message..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
class SimpleMessaging {
    constructor() {
        this.currentConversationId = null;
        this.conversations = [];
        this.messages = [];
        this.users = [];
    }
    
    init() {
        console.log('Initializing Simple Messaging System');
        this.loadConversations();
    }
    
    async loadConversations() {
        try {
            console.log('Loading conversations...');
            const response = await fetch('/Ironroot/api/messaging_working.php?action=conversations');
            console.log('Conversations response:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Conversations data:', data);
            
            this.conversations = data.conversations || [];
            this.renderConversations();
            
        } catch (error) {
            console.error('Error loading conversations:', error);
            document.getElementById('conversationsList').innerHTML = `
                <div class="text-center text-danger p-4">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    Error loading conversations<br>
                    <small>${error.message}</small>
                </div>
            `;
        }
    }
    
    renderConversations() {
        const container = document.getElementById('conversationsList');
        
        if (this.conversations.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted p-4">
                    <i class="fas fa-inbox"></i><br>
                    No conversations yet<br>
                    <small>Start a new conversation</small>
                </div>
            `;
            return;
        }
        
        const html = this.conversations.map(conv => `
            <div class="conversation-item p-3 border-bottom" onclick="simpleMessaging.selectConversation(${conv.id})" style="cursor: pointer;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-bold">${conv.name || 'Conversation'}</div>
                        <div class="text-muted small">${conv.last_message || 'No messages yet'}</div>
                    </div>
                    <div class="text-muted small">
                        ${conv.last_message_time ? new Date(conv.last_message_time).toLocaleDateString() : ''}
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }
    
    async selectConversation(conversationId) {
        console.log('Selecting conversation:', conversationId);
        this.currentConversationId = conversationId;
        
        // Update UI
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('bg-primary', 'text-white');
        });
        event.currentTarget.classList.add('bg-primary', 'text-white');
        
        // Show chat area
        document.getElementById('welcomeScreen').classList.add('d-none');
        document.getElementById('activeChat').classList.remove('d-none');
        
        // Load messages
        await this.loadMessages(conversationId);
    }
    
    async loadMessages(conversationId) {
        try {
            console.log('Loading messages for conversation:', conversationId);
            const response = await fetch(`/Ironroot/api/messaging_working.php?action=messages&conversation_id=${conversationId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Messages data:', data);
            
            this.messages = data.messages || [];
            this.renderMessages();
            
            // Update chat title
            const conversation = this.conversations.find(c => c.id == conversationId);
            if (conversation) {
                document.getElementById('chatTitle').textContent = conversation.name || 'Conversation';
                document.getElementById('chatInfo').textContent = `${this.messages.length} messages`;
            }
            
        } catch (error) {
            console.error('Error loading messages:', error);
            document.getElementById('messagesContainer').innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i><br>
                    Error loading messages: ${error.message}
                </div>
            `;
        }
    }
    
    renderMessages() {
        const container = document.getElementById('messagesContainer');
        const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
        
        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-comment"></i><br>
                    No messages yet<br>
                    <small>Start the conversation!</small>
                </div>
            `;
            return;
        }
        
        const html = this.messages.map(msg => {
            const isOwn = msg.sender_id == currentUserId;
            return `
                <div class="mb-3 ${isOwn ? 'text-end' : 'text-start'}">
                    <div class="d-inline-block p-2 rounded ${isOwn ? 'bg-primary text-white' : 'bg-light'}" style="max-width: 70%;">
                        <div>${msg.message_text}</div>
                        <small class="opacity-75">
                            ${msg.sender_name} • ${new Date(msg.created_at).toLocaleTimeString()}
                        </small>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    }
    
    async sendMessage(event) {
        event.preventDefault();
        
        const messageInput = document.getElementById('messageInput');
        const messageText = messageInput.value.trim();
        
        if (!messageText || !this.currentConversationId) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('conversation_id', this.currentConversationId);
            formData.append('message_text', messageText);
            formData.append('type', 'direct');
            
            const response = await fetch('/Ironroot/api/messaging_working.php?action=send', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Send message response:', data);
            
            if (data.success) {
                messageInput.value = '';
                await this.loadMessages(this.currentConversationId);
                await this.loadConversations(); // Refresh conversations list
            } else {
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
            
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message: ' + error.message);
        }
    }
    
    async showNewMessageForm() {
        console.log('Showing new message form');
        await this.loadUsers();
        const modal = new bootstrap.Modal(document.getElementById('newMessageModal'));
        modal.show();
    }
    
    async loadUsers() {
        try {
            console.log('Loading users...');
            const response = await fetch('/Ironroot/api/messaging_working.php?action=users');
            console.log('Users response:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Users API error:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Users data:', data);
            
            if (data.users && Array.isArray(data.users)) {
                this.users = data.users;
                this.renderUserSelect();
                document.getElementById('recipientLoadError').classList.add('d-none');
            } else {
                throw new Error('Invalid response format');
            }
            
        } catch (error) {
            console.error('Error loading users:', error);
            document.getElementById('recipientLoadError').textContent = 'Error loading users: ' + error.message;
            document.getElementById('recipientLoadError').classList.remove('d-none');
        }
    }
    
    renderUserSelect() {
        const select = document.getElementById('recipientSelect');
        const options = this.users.map(user => 
            `<option value="${user.id}">${user.name} (${user.role})</option>`
        ).join('');
        
        select.innerHTML = '<option value="">Choose a person...</option>' + options;
    }
    
    async startNewConversation(event) {
        event.preventDefault();
        
        const recipientId = document.getElementById('recipientSelect').value;
        const messageText = document.getElementById('newMessageText').value.trim();
        
        if (!recipientId || !messageText) {
            alert('Please select a recipient and enter a message');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('recipient_id', recipientId);
            formData.append('message_text', messageText);
            formData.append('type', 'new');
            
            const response = await fetch('/Ironroot/api/messaging_working.php?action=send', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            console.log('New conversation response:', data);
            
            if (data.success) {
                // Clear form
                document.getElementById('newMessageForm').reset();
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('newMessageModal')).hide();
                
                // Refresh conversations
                await this.loadConversations();
                
                alert('Message sent successfully!');
            } else {
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
            
        } catch (error) {
            console.error('Error starting new conversation:', error);
            alert('Failed to send message: ' + error.message);
        }
    }
}

// Initialize messaging system
const simpleMessaging = new SimpleMessaging();

// Initialize when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const messagesModal = document.getElementById('messagesModal');
    if (messagesModal) {
        messagesModal.addEventListener('shown.bs.modal', function() {
            simpleMessaging.init();
        });
    }
});
</script>

<style>
.conversation-item:hover {
    background-color: #f8f9fa !important;
}

.conversation-item.active {
    background-color: #0d6efd !important;
    color: white !important;
}

#messagesContainer {
    max-height: 400px;
}
</style>