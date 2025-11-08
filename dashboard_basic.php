<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ironroot Dashboard - Basic Version</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 30px 15px; }
        .dashboard-card { 
            background: rgba(255,255,255,0.95); 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        .feature-card { 
            background: white; 
            border-radius: 12px; 
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .feature-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .messaging-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .messaging-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0,123,255,0.5);
        }
        .stats-card { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .welcome-section { 
            background: linear-gradient(135deg, #6f42c1, #e83e8c); 
            color: white; 
            border-radius: 15px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Floating Messaging Button -->
    <button class="messaging-btn" onclick="openMessaging()" title="Open Messages">
        <i class="fas fa-comments fa-lg text-white"></i>
    </button>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section p-4 text-center">
            <h1 class="display-4 mb-3">
                <i class="fas fa-building me-3"></i>
                Ironroot Construction
            </h1>
            <p class="lead mb-0">Construction Management Dashboard</p>
            <small class="opacity-75">Basic Version - Simplified Access</small>
        </div>

        <!-- Main Dashboard -->
        <div class="dashboard-card p-4">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">Dashboard Overview</h3>
                            <p class="text-muted mb-0">Manage your construction projects efficiently</p>
                        </div>
                        <div class="badge bg-success fs-6">
                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                            System Online
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card feature-card stats-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-project-diagram fa-2x mb-3"></i>
                            <h4 class="mb-1">15</h4>
                            <p class="mb-0">Active Projects</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card feature-card" style="background: linear-gradient(135deg, #fd7e14, #ffc107); color: white;">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-3"></i>
                            <h4 class="mb-1">124</h4>
                            <p class="mb-0">Team Members</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card feature-card" style="background: linear-gradient(135deg, #dc3545, #e83e8c); color: white;">
                        <div class="card-body text-center">
                            <i class="fas fa-tasks fa-2x mb-3"></i>
                            <h4 class="mb-1">87</h4>
                            <p class="mb-0">Pending Tasks</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card feature-card" style="background: linear-gradient(135deg, #6f42c1, #6610f2); color: white;">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-2x mb-3"></i>
                            <h4 class="mb-1">92%</h4>
                            <p class="mb-0">Completion Rate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-comments fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Messaging System</h5>
                            <p class="card-text text-muted">Communicate with team members, clients, and contractors in real-time.</p>
                            <button class="btn btn-primary" onclick="openMessaging()">
                                <i class="fas fa-comments me-2"></i>Open Messages
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-project-diagram fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Project Management</h5>
                            <p class="card-text text-muted">Track project progress, assign tasks, and monitor deadlines.</p>
                            <button class="btn btn-outline-success" disabled>
                                <i class="fas fa-cog me-2"></i>Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">Team Management</h5>
                            <p class="card-text text-muted">Manage team members, roles, and permissions across projects.</p>
                            <button class="btn btn-outline-info" disabled>
                                <i class="fas fa-cog me-2"></i>Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                <i class="fas fa-bolt me-2 text-warning"></i>
                                Quick Actions
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="openMessaging()">
                                    <i class="fas fa-paper-plane me-1"></i>Send Message
                                </button>
                                <button class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>New Project
                                </button>
                                <button class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-user-plus me-1"></i>Add Team Member
                                </button>
                                <button class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-calendar me-1"></i>Schedule Meeting
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="runSystemTest()">
                                    <i class="fas fa-check-circle me-1"></i>System Test
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4">
            <div class="text-white">
                <small>
                    <i class="fas fa-shield-alt me-1"></i>
                    Ironroot Construction Management System • Basic Version • <?php echo date('Y-m-d H:i:s'); ?>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openMessaging() {
            // Open messaging in new window/tab
            window.open('basic_messaging.php', '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        }

        function runSystemTest() {
            // Open test page
            window.open('test_basic.php', '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Ironroot Dashboard loaded successfully');
            
            // Animate messaging button
            const messagingBtn = document.querySelector('.messaging-btn');
            setInterval(() => {
                messagingBtn.style.transform = messagingBtn.style.transform === 'scale(1.05)' ? 'scale(1)' : 'scale(1.05)';
            }, 2000);

            // Show welcome message
            setTimeout(() => {
                if (confirm('Welcome to Ironroot! Would you like to test the messaging system?')) {
                    openMessaging();
                }
            }, 1000);
        });

        // Add click effects to cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Add ripple effect
                const ripple = document.createElement('div');
                ripple.style.cssText = 'position:absolute;border-radius:50%;background:rgba(0,123,255,0.3);transform:scale(0);animation:ripple 0.6s linear;pointer-events:none;';
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
                
                this.style.position = 'relative';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add CSS animation for ripple
        const style = document.createElement('style');
        style.textContent = '@keyframes ripple { to { transform: scale(4); opacity: 0; } }';
        document.head.appendChild(style);
    </script>
</body>
</html>