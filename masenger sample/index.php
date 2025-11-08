<?php
session_start();
require_once 'functions.php';

// Handle message submission
if ($_POST && isset($_POST['message']) && !empty(trim($_POST['message']))) {
    $currentProject = getCurrentProject();
    $currentRole = getCurrentRole();
    $message = trim($_POST['message']);
    
    addMessage($currentProject, $currentRole, $message, 'User');
    
    // Redirect to prevent form resubmission
    $redirect = buildQueryString();
    header("Location: index.php$redirect");
    exit;
}

// Get current state
$currentProject = getCurrentProject();
$currentRole = getCurrentRole();
$currentRoleDetails = getRoleDetails($currentRole);
$messages = getMessages($currentProject, $currentRole);
$defaultMessage = getDefaultChatMessage($currentProject, $currentRole);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Left Sidebar -->
        <aside class="left-sidebar">
            <div class="sidebar-header">
                <h2>Projects</h2>
            </div>
            <div class="project-list">
                <?php foreach ($projects as $id => $project): ?>
                    <button type="button" data-project="<?php echo $id; ?>" data-href="<?php echo buildQueryString(['project' => $id]); ?>" 
                       class="project-btn <?php echo ($id === $currentProject) ? 'active' : ''; ?>">
                        Project <?php echo $id; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Section -->
            <header class="top-section">
                <div class="project-info">
                    <div class="project-name"><?php echo htmlspecialchars($projects[$currentProject]['name']); ?></div>
                </div>
                <div class="role-buttons">
                    <?php foreach ($roles as $roleKey => $roleName): ?>
                        <button type="button" data-role="<?php echo $roleKey; ?>" data-href="<?php echo buildQueryString(['role' => $roleKey]); ?>" 
                           class="role-btn <?php echo ($roleKey === $currentRole) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($roleName); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </header>

            <!-- Chat/Message Area -->
            <section class="chat-section">
                <div class="chat-area">
                    <?php if (empty($messages)): ?>
                        <p class="placeholder-text"><?php echo htmlspecialchars($defaultMessage); ?></p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo ($msg['sender'] === 'User') ? 'user-message' : 'system-message'; ?>">
                                <div class="message-content">
                                    <?php if ($msg['sender'] !== 'User'): ?>
                                        <span class="message-sender"><?php echo htmlspecialchars($msg['sender']); ?>:</span>
                                    <?php endif; ?>
                                    <span class="message-text"><?php echo htmlspecialchars($msg['message']); ?></span>
                                    <span class="message-time"><?php echo htmlspecialchars($msg['timestamp']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Message Input Form -->
                <form method="POST" action="index.php<?php echo buildQueryString(); ?>" class="message-form">
                    <div class="message-input-container">
                        <input type="text" name="message" class="message-input" 
                               placeholder="Type your message..." required>
                        <button type="submit" class="send-btn">Send</button>
                    </div>
                </form>
            </section>
        </main>

        <!-- Right Sidebar -->
        <aside class="right-sidebar">
            <div class="info-box">
                <h3><?php echo strtolower($roles[$currentRole]); ?> details</h3>
                <div class="info-content">
                    <?php echo nl2br(htmlspecialchars($currentRoleDetails['manager_details'])); ?>
                </div>
            </div>
            <div class="info-box">
                <h3>project details</h3>
                <div class="info-content">
                    <?php echo nl2br(htmlspecialchars($currentRoleDetails['project_details'])); ?>
                </div>
            </div>
        </aside>
    </div>

    <!-- Enhanced JavaScript for dynamic interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Current state
            let currentProject = <?php echo json_encode($currentProject); ?>;
            let currentRole = <?php echo json_encode($currentRole); ?>;
            
            // Handle project button clicks
            const projectButtons = document.querySelectorAll('.project-btn');
            const projectNameDisplay = document.querySelector('.project-name');
            
            projectButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all project buttons
                    projectButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Update current project
                    currentProject = this.dataset.project;
                    projectNameDisplay.textContent = `Project ${currentProject}`;
                    
                    // Update URL without reload
                    const href = this.dataset.href;
                    history.pushState(null, '', 'index.php' + href);
                    
                    // Clear and reload chat area
                    loadChatArea();
                    
                    // Update info boxes
                    updateInfoBoxes();
                });
            });

            // Handle role button clicks
            const roleButtons = document.querySelectorAll('.role-btn');
            
            roleButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all role buttons
                    roleButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Update current role
                    currentRole = this.dataset.role;
                    
                    // Update URL without reload
                    const href = this.dataset.href;
                    history.pushState(null, '', 'index.php' + href);
                    
                    // Clear and reload chat area
                    loadChatArea();
                    
                    // Update info boxes
                    updateInfoBoxes();
                });
            });

            // Handle message form submission
            const messageForm = document.querySelector('.message-form');
            const messageInput = document.querySelector('.message-input');
            const sendButton = document.querySelector('.send-btn');
            const chatArea = document.querySelector('.chat-area');

            function sendMessage(e) {
                if (e) e.preventDefault();
                
                const message = messageInput.value.trim();
                if (!message) return;

                // Create and add user message immediately
                addMessageToChat(message, 'User');
                
                // Clear input
                messageInput.value = '';
                
                // Send via AJAX
                const formData = new FormData();
                formData.append('message', message);
                
                fetch('index.php' + buildCurrentQueryString(), {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        // Handle successful submission
                        setTimeout(() => {
                            const roleName = document.querySelector('.role-btn.active').textContent;
                            addMessageToChat(`Thank you for your message. This is a response from ${roleName}.`, roleName);
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                });
            }

            // Send message on button click
            sendButton.addEventListener('click', sendMessage);

            // Send message on Enter key press
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage(e);
                }
            });

            // Function to add message to chat area
            function addMessageToChat(message, sender) {
                const messageElement = document.createElement('div');
                messageElement.className = `message ${sender === 'User' ? 'user-message' : 'system-message'}`;
                
                const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                messageElement.innerHTML = `
                    <div class="message-content">
                        ${sender !== 'User' ? `<span class="message-sender">${sender}:</span>` : ''}
                        <span class="message-text">${message}</span>
                        <span class="message-time">${currentTime}</span>
                    </div>
                `;
                
                // Remove placeholder if exists
                const placeholder = chatArea.querySelector('.placeholder-text');
                if (placeholder) {
                    placeholder.remove();
                }
                
                chatArea.appendChild(messageElement);
                chatArea.scrollTop = chatArea.scrollHeight;
            }

            // Function to load chat area content
            function loadChatArea() {
                const placeholder = `Just as when you press the button, the messaging system is activated for Project ${currentProject} with ${document.querySelector('.role-btn.active').textContent} role.`;
                chatArea.innerHTML = `<p class="placeholder-text">${placeholder}</p>`;
            }

            // Function to update info boxes
            function updateInfoBoxes() {
                // This would typically fetch new data via AJAX
                // For now, we'll update with generic content
                const roleData = {
                    'client': {
                        manager: 'Client Representative: John Smith\\nEmail: john.smith@client.com\\nPhone: +1 234-567-8901\\nDepartment: Operations',
                        project: 'Client Requirements:\\n• Budget: $500,000\\n• Timeline: 6 months\\n• Quality Standards: ISO 9001\\n• Regular Updates Required'
                    },
                    'project-manager': {
                        manager: 'Project Manager: Sarah Johnson\\nEmail: sarah.j@company.com\\nPhone: +1 234-567-8902\\nExperience: 8 years\\nCertification: PMP',
                        project: 'Project Status:\\n• Phase: Development\\n• Progress: 65%\\n• Team Size: 12 members\\n• Next Milestone: Dec 15'
                    },
                    'site-engineer': {
                        manager: 'Site Engineer: Mike Chen\\nEmail: mike.chen@company.com\\nPhone: +1 234-567-8903\\nSpecialty: Civil Engineering\\nYears: 5',
                        project: 'Site Information:\\n• Location: Downtown Area\\n• Site Status: Active\\n• Safety Record: Excellent\\n• Equipment: Operational'
                    },
                    'site-manager': {
                        manager: 'Site Manager: Anna Rodriguez\\nEmail: anna.r@company.com\\nPhone: +1 234-567-8904\\nShift: Day Shift\\nTeam: 15 workers',
                        project: 'Site Management:\\n• Daily Reports: Current\\n• Safety Inspections: Weekly\\n• Resource Status: Adequate\\n• Weather Impact: Minimal'
                    },
                    'sub-contractor': {
                        manager: 'Sub Contractor: Robert Wilson\\nCompany: Wilson Construction\\nEmail: rob@wilsonconst.com\\nPhone: +1 234-567-8905',
                        project: 'Contract Details:\\n• Scope: Electrical Work\\n• Duration: 3 months\\n• Progress: On Schedule\\n• Payment: Current'
                    },
                    'supervisor': {
                        manager: 'Supervisor: Lisa Brown\\nEmail: lisa.brown@company.com\\nPhone: +1 234-567-8906\\nShift: Morning\\nTeam Size: 8',
                        project: 'Supervision Areas:\\n• Quality Control\\n• Safety Compliance\\n• Team Coordination\\n• Progress Monitoring'
                    },
                    'logistics-officer': {
                        manager: 'Logistics Officer: David Kumar\\nEmail: david.k@company.com\\nPhone: +1 234-567-8907\\nDepartment: Supply Chain',
                        project: 'Logistics Status:\\n• Material Delivery: On Time\\n• Inventory: Well Stocked\\n• Transportation: Available\\n• Warehousing: Organized'
                    },
                    'finance-officer': {
                        manager: 'Finance Officer: Jennifer Lee\\nEmail: jennifer.lee@company.com\\nPhone: +1 234-567-8908\\nDepartment: Finance',
                        project: 'Financial Status:\\n• Budget Used: 60%\\n• Remaining: $200,000\\n• Cash Flow: Positive\\n• Payments: Up to Date'
                    }
                };

                const infoBoxes = document.querySelectorAll('.info-content');
                if (roleData[currentRole]) {
                    infoBoxes[0].textContent = roleData[currentRole].manager.replace(/\\n/g, '\\n');
                    infoBoxes[1].textContent = roleData[currentRole].project.replace(/\\n/g, '\\n');
                }
            }

            // Function to build current query string
            function buildCurrentQueryString() {
                const params = new URLSearchParams();
                if (currentProject !== '0') params.append('project', currentProject);
                if (currentRole !== 'client') params.append('role', currentRole);
                return params.toString() ? '?' + params.toString() : '';
            }

            // Auto-scroll to bottom of chat area on load
            if (chatArea) {
                chatArea.scrollTop = chatArea.scrollHeight;
            }

            // Add smooth button press animations
            document.querySelectorAll('.project-btn, .role-btn').forEach(button => {
                button.addEventListener('click', function() {
                    setTimeout(() => {
                        this.style.transform = 'scale(0.98)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 100);
                    }, 50);
                });
            });
        });
    </script>
</body>
</html>