<!-- Modern Messaging Component -->
<div class="messaging-container">
    <!-- Message Sidebar -->
    <div class="message-sidebar" id="messageSidebar">
        <div class="message-header">
            <h5 class="mb-0">
                <i class="fas fa-comments me-2"></i>Messages
            </h5>
            <button class="btn btn-sm btn-outline-primary" id="newMessageBtn">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        
        <div class="search-box">
            <input type="text" class="form-control" placeholder="Search conversations..." id="conversationSearch">
        </div>
        
        <div class="conversations-list" id="conversationsList">
            <!-- Conversations will be loaded here -->
        </div>
    </div>
    
    <!-- Message Chat Area -->
    <div class="message-chat" id="messageChat">
        <div class="chat-welcome" id="chatWelcome">
            <div class="text-center text-muted">
                <i class="fas fa-comments fa-3x mb-3"></i>
                <h5>Welcome to Messages</h5>
                <p>Select a conversation to start messaging</p>
            </div>
        </div>
        
        <div class="chat-area d-none" id="chatArea">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-info">
                    <h6 class="mb-0" id="chatTitle">Conversation</h6>
                    <small class="text-muted" id="chatParticipants">Participants</small>
                </div>
                <div class="chat-actions">
                    <button class="btn btn-sm btn-outline-secondary" id="chatOptionsBtn">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div class="messages-container" id="messagesContainer">
                <!-- Messages will be loaded here -->
            </div>
            
            <!-- Message Input -->
            <div class="message-input">
                <form id="messageForm" class="d-flex">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Type your message..." id="messageText" required>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- New Conversation Modal -->
<div class="modal fade" id="newConversationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="messaging.closeModal()"></button>
            </div>
            <div class="modal-body">
                <form id="newConversationForm">
                    <div class="mb-3">
                        <label class="form-label">Select Person to Message</label>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>All Users</h6>
                                <div class="participants-container" id="participantsContainer" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Users will be loaded here -->
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Your Projects</h6>
                                <div id="projectsContainer" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Projects will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Initial Message (Optional)</label>
                        <textarea class="form-control" id="initialMessage" rows="3" placeholder="Start the conversation..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="messaging.closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="createConversationBtn">Start Conversation</button>
            </div>
        </div>
    </div>
</div>

<style>
.messaging-container {
    display: flex;
    height: 600px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.message-sidebar {
    width: 320px;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
}

.message-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.search-box {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    padding: 1rem;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: background-color 0.2s;
}

.conversation-item:hover {
    background-color: #f8f9fa;
}

.conversation-item.active {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.conversation-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.conversation-preview {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.conversation-time {
    font-size: 0.75rem;
    color: #adb5bd;
}

.unread-badge {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 0.125rem 0.375rem;
    font-size: 0.75rem;
    min-width: 1.25rem;
    text-align: center;
}

.message-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-welcome {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
}

.chat-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.messages-container {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.message-bubble {
    max-width: 70%;
    padding: 0.75rem 1rem;
    border-radius: 18px;
    position: relative;
}

.message-bubble.sent {
    align-self: flex-end;
    background: #2196f3;
    color: white;
}

.message-bubble.received {
    align-self: flex-start;
    background: #f1f3f4;
    color: #333;
}

.message-info {
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
    opacity: 0.8;
}

.message-text {
    line-height: 1.4;
}

.message-time {
    font-size: 0.625rem;
    margin-top: 0.25rem;
    opacity: 0.7;
}

.message-input {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.participants-container {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.5rem;
}

.participant-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.participant-item:hover {
    background-color: #f8f9fa;
}

.participant-item.selected {
    background-color: #e3f2fd;
}

.participant-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    margin-right: 0.75rem;
}

.typing-indicator {
    padding: 0.5rem 1rem;
    color: #6c757d;
    font-style: italic;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .messaging-container {
        height: 500px;
    }
    
    .message-sidebar {
        width: 280px;
    }
    
    .message-bubble {
        max-width: 85%;
    }
}

/* Modal fallback styles if Bootstrap isn't loaded */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    width: 100%;
    height: 100%;
    overflow: hidden;
    outline: 0;
}

.modal.show {
    display: block !important;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 1.75rem;
    pointer-events: none;
}

.modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 0.3rem;
    outline: 0;
}

.project-item:hover {
    background-color: #f8f9fa !important;
}

.project-item.bg-light {
    background-color: #e9ecef !important;
}
</style>

<script>
// Modern Messaging System JavaScript
class MessagingSystem {
    constructor() {
        this.currentConversationId = null;
        this.conversations = [];
        this.messages = [];
        this.users = [];
        this.selectedParticipants = [];
        
        this.initializeEventListeners();
        this.loadConversations();
        this.loadUsers();
        
        // Auto-refresh messages every 10 seconds
        setInterval(() => {
            if (this.currentConversationId) {
                this.loadMessages(this.currentConversationId, this.currentConversationType || 'direct', false);
            }
        }, 10000);
    }
    
    initializeEventListeners() {
        // New message button
        document.getElementById('newMessageBtn').addEventListener('click', () => {
            this.showNewConversationModal();
        });
        
        // Message form submission
        document.getElementById('messageForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Create conversation button
        document.getElementById('createConversationBtn').addEventListener('click', () => {
            this.createConversation();
        });
        
        // Search conversations
        document.getElementById('conversationSearch').addEventListener('input', (e) => {
            this.filterConversations(e.target.value);
        });
    }
    
    async loadConversations() {
        try {
            const response = await fetch('/Ironroot/api/messages/modern.php?action=conversations');
            const data = await response.json();
            
            console.log('Conversations response:', data);
            
            if (data.conversations) {
                this.conversations = data.conversations;
                this.renderConversations();
            } else if (data.error) {
                console.error('Conversations error:', data.error);
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }
    
    async loadUsers() {
        try {
            const response = await fetch('/api/messaging?action=users');
            const data = await response.json();
            
            if (data.users) {
                this.users = data.users;
            }
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }
    
    async loadProjectUsers() {
        try {
            console.log('Loading users...');
            const response = await fetch('/Ironroot/api/messages/?action=users');
            console.log('Users response status:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Users API response:', data);
            
            if (data.users && Array.isArray(data.users)) {
                this.users = data.users;
                this.renderParticipants();
                console.log('Loaded', data.users.length, 'users');
            } else if (data.error) {
                console.error('Users API error:', data.error);
                this.showUserLoadError(data.error);
            } else {
                console.error('Unexpected response format:', data);
                this.showUserLoadError('Unexpected response format');
            }
        } catch (error) {
            console.error('Error loading project users:', error);
            this.showUserLoadError(error.message);
        }
    }
    
    showUserLoadError(errorMessage) {
        const container = document.getElementById('participantsContainer');
        container.innerHTML = `
            <div class="text-danger p-3">
                <small>Error loading users: ${errorMessage}</small>
                <br>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="messaging.loadProjectUsers()">
                    Try Again
                </button>
            </div>
        `;
    }
    
    renderConversations() {
        const container = document.getElementById('conversationsList');
        
        if (this.conversations.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted p-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>No conversations yet</p>
                    <small>Click the + button to start messaging someone</small>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.conversations.map(conv => `
            <div class="conversation-item ${conv.conversation_id === this.currentConversationId ? 'active' : ''}" 
                 onclick="messaging.selectConversation('${conv.conversation_id}', '${conv.type}')">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="conversation-name">${conv.display_name || conv.name || 'Unknown'}</div>
                        <div class="conversation-preview">${conv.last_message || 'No messages yet'}</div>
                        <div class="conversation-time">${this.formatTime(conv.last_message_time)}</div>
                    </div>
                    ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    async selectConversation(conversationId, type = 'direct') {
        this.currentConversationId = conversationId;
        this.currentConversationType = type;
        this.renderConversations(); // Update active state
        
        await this.loadMessages(conversationId, type);
        this.showChatArea();
        
        // Mark messages as read
        await this.markMessagesRead(conversationId, type);
    }
    
    async loadMessages(conversationId, type = 'direct', scrollToBottom = true) {
        try {
            const response = await fetch(`/Ironroot/api/messages/modern.php?action=get_messages&conversation_id=${conversationId}&type=${type}`);
            const data = await response.json();
            
            if (data.messages) {
                this.messages = data.messages;
                this.renderMessages();
                
                if (scrollToBottom) {
                    this.scrollToBottom();
                }
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }
    
    renderMessages() {
        const container = document.getElementById('messagesContainer');
        const currentUserId = <?php echo getCurrentUserId(); ?>;
        
        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted">
                    <p>No messages yet. Start the conversation!</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.messages.map(msg => {
            const isSent = msg.sender_id == currentUserId;
            const senderName = msg.sender_name || (msg.name || 'Unknown');
            return `
                <div class="message-bubble ${isSent ? 'sent' : 'received'}">
                    ${!isSent ? `<div class="message-info">${senderName}</div>` : ''}
                    <div class="message-text">${msg.message_text || msg.body || ''}</div>
                    <div class="message-time">${this.formatTime(msg.created_at)}</div>
                </div>
            `;
        }).join('');
    }
    
    showChatArea() {
        document.getElementById('chatWelcome').classList.add('d-none');
        document.getElementById('chatArea').classList.remove('d-none');
        
        // Update chat header
        const conversation = this.conversations.find(c => c.conversation_id === this.currentConversationId);
        if (conversation) {
            document.getElementById('chatTitle').textContent = conversation.display_name || conversation.name || 'Conversation';
        }
    }
    
    async sendMessage() {
        const messageText = document.getElementById('messageText').value.trim();
        
        if (!messageText || !this.currentConversationId) return;
        
        try {
            const formData = new FormData();
            formData.append('conversation_id', this.currentConversationId);
            formData.append('type', this.currentConversationType || 'direct');
            formData.append('message_text', messageText);
            
            const response = await fetch('/Ironroot/api/messages/modern.php?action=send_message', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('messageText').value = '';
                await this.loadMessages(this.currentConversationId, this.currentConversationType);
                await this.loadConversations(); // Refresh conversation list
            } else {
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message');
        }
    }
    
    showNewConversationModal() {
        this.selectedParticipants = [];
        this.loadProjectUsers(); // Load users based on projects
        this.loadProjects(); // Load user projects
        this.renderParticipants();
        
        // Check if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(document.getElementById('newConversationModal'));
            modal.show();
        } else {
            // Fallback if Bootstrap isn't loaded
            const modal = document.getElementById('newConversationModal');
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }
    
    closeModal() {
        const modal = document.getElementById('newConversationModal');
        if (typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        } else {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
    }
    
    async loadProjects() {
        try {
            const response = await fetch('/api/messaging?action=projects');
            const data = await response.json();
            
            if (data.projects) {
                this.projects = data.projects;
                this.renderProjects();
            }
        } catch (error) {
            console.error('Error loading projects:', error);
        }
    }
    
    renderProjects() {
        const container = document.getElementById('projectsContainer');
        
        if (!this.projects || this.projects.length === 0) {
            container.innerHTML = `
                <div class="text-muted p-3">
                    <small>No projects assigned</small>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.projects.map(project => `
            <div class="project-item p-2 border rounded mb-2" style="cursor: pointer;" onclick="messaging.selectProject(${project.id})">
                <div class="fw-medium">${project.name}</div>
                <small class="text-muted">${project.status}</small>
            </div>
        `).join('');
    }
    
    selectProject(projectId) {
        // For now, this could expand to show project team members
        // Currently, we'll just highlight the selected project
        const projectItems = document.querySelectorAll('.project-item');
        projectItems.forEach(item => item.classList.remove('bg-light'));
        
        event.target.closest('.project-item').classList.add('bg-light');
        
        // You could enhance this to filter users by project participation
        console.log('Selected project:', projectId);
    }
    
    renderParticipants() {
        const container = document.getElementById('participantsContainer');
        
        if (!this.users || this.users.length === 0) {
            container.innerHTML = `
                <div class="text-muted p-3">
                    <small>Loading users...</small>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.users.map(user => `
            <div class="participant-item ${this.selectedParticipants.includes(user.id) ? 'selected' : ''}"
                 onclick="messaging.toggleParticipant(${user.id})">
                <div class="d-flex align-items-center">
                    <div class="participant-avatar me-3">
                        ${user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-medium">${user.name || 'Unknown User'}</div>
                        <small class="text-muted d-block">${user.role}</small>
                        ${user.project_names ? `<small class="text-info">${user.project_names}</small>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    toggleParticipant(userId) {
        const index = this.selectedParticipants.indexOf(userId);
        if (index > -1) {
            this.selectedParticipants.splice(index, 1);
        } else {
            this.selectedParticipants.push(userId);
        }
        this.renderParticipants();
    }
    
    async createConversation() {
        if (this.selectedParticipants.length === 0) {
            alert('Please select at least one participant');
            return;
        }
        
        if (this.selectedParticipants.length === 1) {
            // Create direct conversation - just select the existing conversation with this user
            const otherUserId = this.selectedParticipants[0];
            const conversationId = `user_${otherUserId}`;
            
            // Close modal
            this.closeModal();
            
            // Send initial message if provided
            const initialMessage = document.getElementById('initialMessage').value.trim();
            if (initialMessage) {
                try {
                    const formData = new FormData();
                    formData.append('conversation_id', conversationId);
                    formData.append('type', 'direct');
                    formData.append('message_text', initialMessage);
                    
                    await fetch('/api/messaging?action=send', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Clear the textarea
                    document.getElementById('initialMessage').value = '';
                } catch (error) {
                    console.error('Error sending initial message:', error);
                }
            }
            
            // Find or create conversation in our list
            let conversation = this.conversations.find(c => c.conversation_id === conversationId);
            if (!conversation) {
                const user = this.users.find(u => u.id == otherUserId);
                conversation = {
                    conversation_id: conversationId,
                    type: 'direct',
                    display_name: user ? user.name : 'Unknown User',
                    last_message: initialMessage || null,
                    last_message_time: new Date().toISOString(),
                    unread_count: 0
                };
                this.conversations.unshift(conversation);
                this.renderConversations();
            }
            
            // Refresh conversations and select the conversation
            await this.loadConversations();
            this.selectConversation(conversationId, 'direct');
            
        } else {
            // For multiple participants, we'd need to implement project-based messaging
            alert('Multiple participant conversations are not yet implemented. Please select one participant for direct messaging.');
        }
    }
    
    async markMessagesRead(conversationId, type = 'direct') {
        try {
            const formData = new FormData();
            formData.append('conversation_id', conversationId);
            formData.append('type', type);
            
            await fetch('/api/messaging?action=mark_read', {
                method: 'POST',
                body: formData
            });
            
            // Refresh conversations to update unread counts
            await this.loadConversations();
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    }
    
    filterConversations(searchTerm) {
        const items = document.querySelectorAll('.conversation-item');
        
        items.forEach(item => {
            const name = item.querySelector('.conversation-name').textContent.toLowerCase();
            const preview = item.querySelector('.conversation-preview').textContent.toLowerCase();
            
            if (name.includes(searchTerm.toLowerCase()) || preview.includes(searchTerm.toLowerCase())) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }
    
    formatTime(timestamp) {
        if (!timestamp) return '';
        
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        // Less than a minute
        if (diff < 60000) {
            return 'Just now';
        }
        
        // Less than an hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes}m ago`;
        }
        
        // Less than a day
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours}h ago`;
        }
        
        // Same year
        if (date.getFullYear() === now.getFullYear()) {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
        
        // Different year
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }
}

// Initialize messaging system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.messaging = new MessagingSystem();
});
</script>