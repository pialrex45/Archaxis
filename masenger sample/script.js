document.addEventListener('DOMContentLoaded', function() {
    // Handle project button clicks
    const projectButtons = document.querySelectorAll('.project-btn');
    const projectNameDisplay = document.querySelector('.project-name');
    
    projectButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all project buttons
            projectButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update project name display
            const projectNumber = this.dataset.project;
            projectNameDisplay.textContent = `Project ${projectNumber}`;
            
            // Clear chat area (simulate switching projects)
            const chatArea = document.querySelector('.chat-area');
            chatArea.innerHTML = `<p class="placeholder-text">Project ${projectNumber} messaging system activated. Start your conversation here.</p>`;
        });
    });

    // Handle role button clicks
    const roleButtons = document.querySelectorAll('.role-btn');
    
    roleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all role buttons
            roleButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update chat area based on selected role
            const role = this.dataset.role;
            const chatArea = document.querySelector('.chat-area');
            const roleName = this.textContent;
            
            chatArea.innerHTML = `<p class="placeholder-text">Just as when you press the ${roleName} button, the ${roleName}'s messaging system is activated, similarly, when you activate the rest, the Admin's messaging system is activated along with them.</p>`;
            
            // Update info boxes based on role
            updateInfoBoxes(role);
        });
    });

    // Handle message input and send functionality
    const messageInput = document.querySelector('.message-input');
    const sendButton = document.querySelector('.send-btn');
    const chatArea = document.querySelector('.chat-area');

    function sendMessage() {
        const message = messageInput.value.trim();
        if (message) {
            // Create message element
            const messageElement = document.createElement('div');
            messageElement.className = 'message user-message';
            messageElement.innerHTML = `
                <div class="message-content">
                    <span class="message-text">${message}</span>
                    <span class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
            `;
            
            // Remove placeholder text if it exists
            const placeholder = chatArea.querySelector('.placeholder-text');
            if (placeholder) {
                placeholder.remove();
            }
            
            // Add message to chat area
            chatArea.appendChild(messageElement);
            
            // Clear input
            messageInput.value = '';
            
            // Scroll to bottom
            chatArea.scrollTop = chatArea.scrollTop + messageElement.offsetHeight + 10;
            
            // Simulate response after 1 second
            setTimeout(() => {
                const activeRole = document.querySelector('.role-btn.active');
                const roleName = activeRole ? activeRole.textContent : 'System';
                
                const responseElement = document.createElement('div');
                responseElement.className = 'message system-message';
                responseElement.innerHTML = `
                    <div class="message-content">
                        <span class="message-sender">${roleName}:</span>
                        <span class="message-text">Thank you for your message. This is a simulated response from ${roleName}.</span>
                        <span class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                `;
                
                chatArea.appendChild(responseElement);
                chatArea.scrollTop = chatArea.scrollTop + responseElement.offsetHeight + 10;
            }, 1000);
        }
    }

    // Send message on button click
    sendButton.addEventListener('click', sendMessage);

    // Send message on Enter key press
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Function to update info boxes based on selected role
    function updateInfoBoxes(role) {
        const infoBoxes = document.querySelectorAll('.info-content');
        const roleData = {
            'client': {
                manager: 'Client Representative: John Smith\nEmail: john.smith@client.com\nPhone: +1 234-567-8901\nDepartment: Operations',
                project: 'Client Requirements:\n• Budget: $500,000\n• Timeline: 6 months\n• Quality Standards: ISO 9001\n• Regular Updates Required'
            },
            'project-manager': {
                manager: 'Project Manager: Sarah Johnson\nEmail: sarah.j@company.com\nPhone: +1 234-567-8902\nExperience: 8 years\nCertification: PMP',
                project: 'Project Status:\n• Phase: Development\n• Progress: 65%\n• Team Size: 12 members\n• Next Milestone: Dec 15'
            },
            'site-engineer': {
                manager: 'Site Engineer: Mike Chen\nEmail: mike.chen@company.com\nPhone: +1 234-567-8903\nSpecialty: Civil Engineering\nYears: 5',
                project: 'Site Information:\n• Location: Downtown Area\n• Site Status: Active\n• Safety Record: Excellent\n• Equipment: Operational'
            },
            'site-manager': {
                manager: 'Site Manager: Anna Rodriguez\nEmail: anna.r@company.com\nPhone: +1 234-567-8904\nShift: Day Shift\nTeam: 15 workers',
                project: 'Site Management:\n• Daily Reports: Current\n• Safety Inspections: Weekly\n• Resource Status: Adequate\n• Weather Impact: Minimal'
            },
            'sub-contractor': {
                manager: 'Sub Contractor: Robert Wilson\nCompany: Wilson Construction\nEmail: rob@wilsonconst.com\nPhone: +1 234-567-8905',
                project: 'Contract Details:\n• Scope: Electrical Work\n• Duration: 3 months\n• Progress: On Schedule\n• Payment: Current'
            },
            'supervisor': {
                manager: 'Supervisor: Lisa Brown\nEmail: lisa.brown@company.com\nPhone: +1 234-567-8906\nShift: Morning\nTeam Size: 8',
                project: 'Supervision Areas:\n• Quality Control\n• Safety Compliance\n• Team Coordination\n• Progress Monitoring'
            },
            'logistics-officer': {
                manager: 'Logistics Officer: David Kumar\nEmail: david.k@company.com\nPhone: +1 234-567-8907\nDepartment: Supply Chain',
                project: 'Logistics Status:\n• Material Delivery: On Time\n• Inventory: Well Stocked\n• Transportation: Available\n• Warehousing: Organized'
            },
            'finance-officer': {
                manager: 'Finance Officer: Jennifer Lee\nEmail: jennifer.lee@company.com\nPhone: +1 234-567-8908\nDepartment: Finance',
                project: 'Financial Status:\n• Budget Used: 60%\n• Remaining: $200,000\n• Cash Flow: Positive\n• Payments: Up to Date'
            }
        };

        if (roleData[role]) {
            infoBoxes[0].textContent = roleData[role].manager;
            infoBoxes[1].textContent = roleData[role].project;
        }
    }

    // Initialize with default role data
    updateInfoBoxes('client');

    // Add smooth scrolling behavior
    document.querySelectorAll('.project-btn, .role-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Add a small delay for visual feedback
            setTimeout(() => {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            }, 50);
        });
    });
});