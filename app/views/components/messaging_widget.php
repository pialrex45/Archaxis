<?php
// Messaging Widget Component for Ironroot
// Include this in dashboard pages to add messaging functionality

$messagingUserId = getCurrentUserId();
$messagingUserRole = getCurrentUserRole();
?>

<!-- Messaging Widget Styles -->
<style>
.messaging-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    transform: translateY(100%);
    transition: transform 0.3s ease;
    overflow: hidden;
}

.messaging-widget.open {
    transform: translateY(0);
}

.messaging-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    transition: all 0.3s ease;
}

.messaging-toggle:hover {
    transform: scale(1.1);
}

.messaging-toggle.open {
    transform: rotate(45deg);
}

.messaging-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.messaging-body {
    height: calc(100% - 140px);
    display: flex;
    flex-direction: column;
}

.conversation-list {
    height: 150px;
    overflow-y: auto;
    border-bottom: 1px solid #eee;
}

.conversation-item {
    padding: 10px 15px;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;
    transition: background-color 0.2s;
}

.conversation-item:hover {
    background-color: #f8f9fa;
}

.conversation-item.active {
    background-color: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.chat-area {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    background-color: #fafafa;
}

.message {
    margin-bottom: 10px;
    padding: 8px 12px;
    border-radius: 12px;
    max-width: 80%;
    word-wrap: break-word;
}

.message.own {
    background-color: #2196f3;
    color: white;
    margin-left: auto;
    text-align: right;
}

.message.other {
    background-color: white;
    color: #333;
    border: 1px solid #eee;
}

.message-input-area {
    padding: 10px;
    border-top: 1px solid #eee;
    background: white;
}

.message-input-group {
    display: flex;
    gap: 8px;
}

.message-input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 8px 15px;
    outline: none;
}

.send-button {
    background: #2196f3;
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.project-selector {
    margin-bottom: 10px;
}

.project-selector select {
    width: 100%;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.hidden {
    display: none !important;
}
</style>

<!-- Messaging Toggle Button -->
<button class="messaging-toggle" id="messagingToggle">
    <i class="fas fa-comments"></i>
</button>

<!-- Messaging Widget -->
<div class="messaging-widget" id="messagingWidget">
    <div class="messaging-header">
        <span>Messages</span>
        <button type="button" id="closeMessaging" style="background: none; border: none; color: white; cursor: pointer;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="messaging-body">
        <!-- Project Selector -->
        <div class="project-selector">
            <select id="projectSelector" class="form-select form-select-sm">
                <option value="">Select Project...</option>
            </select>
        </div>
        
        <!-- Conversation List -->
        <div class="conversation-list" id="conversationList">
            <div class="empty-state">
                <i class="fas fa-comment-slash mb-2" style="font-size: 2em; opacity: 0.3;"></i>
                <p>Select a project to view conversations</p>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="empty-state">
                <i class="fas fa-comments mb-2" style="font-size: 2em; opacity: 0.3;"></i>
                <p>Select a conversation to start messaging</p>
            </div>
        </div>
        
        <!-- Message Input -->
        <div class="message-input-area" id="messageInputArea" style="display: none;">
            <div class="message-input-group">
                <input type="text" class="message-input" id="messageInput" placeholder="Type your message...">
                <button type="button" class="send-button" id="sendButton">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Messaging JavaScript -->
<script>
class MessagingWidget {
    constructor() {
        this.isOpen = false;
        this.currentConversationId = null;
        this.currentProjectId = null;
        this.userId = <?php echo json_encode($messagingUserId); ?>;
        this.userRole = <?php echo json_encode($messagingUserRole); ?>;
        this.refreshInterval = null;
        this.projectsLoaded = false;
        
        this.initializeElements();
        this.bindEvents();
        // Don't load projects immediately - wait for user interaction
    }
    
    initializeElements() {
        this.toggle = document.getElementById('messagingToggle');
        this.widget = document.getElementById('messagingWidget');
        this.closeBtn = document.getElementById('closeMessaging');
        this.projectSelector = document.getElementById('projectSelector');
        this.conversationList = document.getElementById('conversationList');
        this.chatArea = document.getElementById('chatArea');
        this.messageInput = document.getElementById('messageInput');
        this.sendButton = document.getElementById('sendButton');
        this.messageInputArea = document.getElementById('messageInputArea');
    }
    
    bindEvents() {
        this.toggle.addEventListener('click', () => this.toggleWidget());
        this.closeBtn.addEventListener('click', () => this.closeWidget());
        this.projectSelector.addEventListener('change', (e) => this.onProjectChange(e.target.value));
        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
    }
    
    toggleWidget() {
        this.isOpen = !this.isOpen;
        this.widget.classList.toggle('open', this.isOpen);
        this.toggle.classList.toggle('open', this.isOpen);
        
        if (this.isOpen) {
            // Load projects only when widget is first opened
            if (!this.projectsLoaded) {
                this.loadProjects();
                this.projectsLoaded = true;
            }
            this.startRefresh();
        } else {
            this.stopRefresh();
        }
    }
    
    closeWidget() {
        this.isOpen = false;
        this.widget.classList.remove('open');
        this.toggle.classList.remove('open');
        this.stopRefresh();
    }
    
    async loadProjects() {
        try {
            const response = await fetch(`${BASE_URL || '/'}api/projects/`);
            if (!response.ok) {
                console.warn('Projects API not available');
                return;
            }
            const result = await response.json();
            
            if (result.success) {
                this.populateProjectSelector(result.data);
            }
        } catch (error) {
            console.warn('Failed to load projects - messaging may be limited:', error.message);
            // Don't show error to user, just log it
        }
    }
    
    populateProjectSelector(projects) {
        this.projectSelector.innerHTML = '<option value="">Select Project...</option>';
        projects.forEach(project => {
            const option = document.createElement('option');
            option.value = project.id;
            option.textContent = project.name;
            this.projectSelector.appendChild(option);
        });
    }
    
    async onProjectChange(projectId) {
        this.currentProjectId = projectId;
        this.currentConversationId = null;
        this.messageInputArea.style.display = 'none';
        
        if (projectId) {
            await this.loadConversations(projectId);
            this.showEmptyChat();
        } else {
            this.showEmptyConversations();
            this.showEmptyChat();
        }
    }
    
    async loadConversations(projectId) {
        try {
            const response = await fetch(`${BASE_URL || '/'}api/messaging/?action=conversations&project_id=${projectId}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayConversations(result.data);
            }
        } catch (error) {
            console.error('Failed to load conversations:', error);
        }
    }
    
    displayConversations(conversations) {
        if (conversations.length === 0) {
            this.conversationList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comment-plus mb-2" style="font-size: 2em; opacity: 0.3;"></i>
                    <p>No conversations yet</p>
                    <button class="btn btn-sm btn-primary" onclick="messagingWidget.createNewConversation()">
                        Start Conversation
                    </button>
                </div>
            `;
            return;
        }
        
        this.conversationList.innerHTML = conversations.map(conv => `
            <div class="conversation-item" data-conversation-id="${conv.id}">
                <div style="font-weight: 600; font-size: 0.9em;">${conv.name}</div>
                <div style="font-size: 0.8em; color: #666; margin-top: 2px;">
                    ${conv.message_count} messages
                </div>
            </div>
        `).join('');
        
        // Add click handlers
        this.conversationList.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const conversationId = item.dataset.conversationId;
                this.selectConversation(conversationId);
            });
        });
    }
    
    async selectConversation(conversationId) {
        this.currentConversationId = conversationId;
        
        // Update UI
        this.conversationList.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.toggle('active', item.dataset.conversationId === conversationId);
        });
        
        this.messageInputArea.style.display = 'block';
        await this.loadMessages(conversationId);
    }
    
    async loadMessages(conversationId) {
        try {
            const response = await fetch(`${BASE_URL || '/'}api/messaging/?action=messages&conversation_id=${conversationId}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayMessages(result.data);
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
        }
    }
    
    displayMessages(messages) {
        this.chatArea.innerHTML = messages.map(msg => {
            const isOwn = msg.sender_id == this.userId;
            const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            return `
                <div class="message ${isOwn ? 'own' : 'other'}">
                    ${!isOwn ? `<div style="font-size: 0.8em; margin-bottom: 3px; opacity: 0.7;">${msg.sender_name}</div>` : ''}
                    <div>${msg.message_text}</div>
                    <div style="font-size: 0.7em; margin-top: 3px; opacity: 0.7;">${time}</div>
                </div>
            `;
        }).join('');
        
        this.chatArea.scrollTop = this.chatArea.scrollHeight;
    }
    
    async sendMessage() {
        if (!this.currentConversationId || !this.messageInput.value.trim()) return;
        
        const message = this.messageInput.value.trim();
        this.messageInput.value = '';
        
        try {
            const response = await fetch(`${BASE_URL || '/'}api/messaging/`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'send_message',
                    conversation_id: this.currentConversationId,
                    message: message
                })
            });
            
            const result = await response.json();
            if (result.success) {
                await this.loadMessages(this.currentConversationId);
            }
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    }
    
    showEmptyConversations() {
        this.conversationList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comment-slash mb-2" style="font-size: 2em; opacity: 0.3;"></i>
                <p>Select a project to view conversations</p>
            </div>
        `;
    }
    
    showEmptyChat() {
        this.chatArea.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comments mb-2" style="font-size: 2em; opacity: 0.3;"></i>
                <p>Select a conversation to start messaging</p>
            </div>
        `;
    }
    
    startRefresh() {
        if (this.refreshInterval) return;
        this.refreshInterval = setInterval(() => {
            if (this.currentConversationId) {
                this.loadMessages(this.currentConversationId);
            }
        }, 5000); // Refresh every 5 seconds
    }
    
    stopRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    async createNewConversation() {
        // Implement conversation creation modal
        const name = prompt('Enter conversation name:');
        if (!name || !this.currentProjectId) return;
        
        try {
            const response = await fetch(`${BASE_URL || '/'}api/messaging/`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'create_conversation',
                    project_id: this.currentProjectId,
                    name: name,
                    description: ''
                })
            });
            
            const result = await response.json();
            if (result.success) {
                await this.loadConversations(this.currentProjectId);
            }
        } catch (error) {
            console.error('Failed to create conversation:', error);
        }
    }
}

// Initialize messaging widget when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.messagingWidget = new MessagingWidget();
});
</script>