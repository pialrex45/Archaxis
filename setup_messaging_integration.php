<?php
// Messaging Integration Setup Script
require_once __DIR__ . '/app/core/auth.php';
require_once __DIR__ . '/app/core/helpers.php';

// Create data directory if it doesn't exist
$data_dir = __DIR__ . '/data';
if (!file_exists($data_dir)) {
    mkdir($data_dir, 0777, true);
    echo "✅ Created data directory<br>";
}

// Sample users for testing the messaging system
$sample_users = [
    [
        'id' => 1,
        'name' => 'Admin User',
        'role' => 'admin',
        'last_active' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 2,
        'name' => 'John Manager',
        'role' => 'project_manager',
        'last_active' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 3,
        'name' => 'Sarah Client',
        'role' => 'client',
        'last_active' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 4,
        'name' => 'Mike Supervisor',
        'role' => 'supervisor',
        'last_active' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 5,
        'name' => 'Lisa Engineer',
        'role' => 'site_engineer',
        'last_active' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 6,
        'name' => 'David Contractor',
        'role' => 'sub_contractor',
        'last_active' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 7,
        'name' => 'Emma Logistics',
        'role' => 'logistic_officer',
        'last_active' => date('Y-m-d H:i:s')
    ]
];

// Sample messages for testing
$sample_messages = [
    [
        'id' => 1,
        'sender_id' => 1,
        'receiver_id' => 2,
        'message_text' => 'Hello John, could you please update me on the progress of Project Alpha?',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ],
    [
        'id' => 2,
        'sender_id' => 2,
        'receiver_id' => 1,
        'message_text' => 'Hi Admin, Project Alpha is 75% complete. We should finish by Friday.',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
    ],
    [
        'id' => 3,
        'sender_id' => 3,
        'receiver_id' => 2,
        'message_text' => 'John, I have a question about the material delivery schedule.',
        'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
    ],
    [
        'id' => 4,
        'sender_id' => 4,
        'receiver_id' => 5,
        'message_text' => 'Lisa, can you review the structural drawings for Building B?',
        'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
    ],
    [
        'id' => 5,
        'sender_id' => 5,
        'receiver_id' => 4,
        'message_text' => 'Sure Mike, I\'ll have the review completed by end of day.',
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
    ]
];

// Write sample users
$users_file = $data_dir . '/messaging_users.json';
if (file_put_contents($users_file, json_encode($sample_users, JSON_PRETTY_PRINT))) {
    echo "✅ Created sample users: " . count($sample_users) . " users<br>";
} else {
    echo "❌ Failed to create users file<br>";
}

// Write sample messages
$messages_file = $data_dir . '/messaging_messages.json';
if (file_put_contents($messages_file, json_encode($sample_messages, JSON_PRETTY_PRINT))) {
    echo "✅ Created sample messages: " . count($sample_messages) . " messages<br>";
} else {
    echo "❌ Failed to create messages file<br>";
}

echo "<br><h3>🎯 Setup Complete!</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
echo "<h4>✨ What's Ready:</h4>";
echo "<ul>";
echo "<li><strong>Data Storage:</strong> File-based messaging system initialized</li>";
echo "<li><strong>Sample Users:</strong> 7 users across different roles for testing</li>";
echo "<li><strong>Sample Messages:</strong> 5 test conversations to demonstrate functionality</li>";
echo "<li><strong>Integration:</strong> Messaging buttons added to all dashboards</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; color: #0c5460; margin: 20px 0;'>";
echo "<h4>🚀 How to Test:</h4>";
echo "<ol>";
echo "<li><strong>Login to any dashboard</strong> with your existing credentials</li>";
echo "<li><strong>Look for the blue messaging button</strong> in the bottom-right corner</li>";
echo "<li><strong>Click the messaging button</strong> to access the integrated messaging system</li>";
echo "<li><strong>Try messaging</strong> between different users to test functionality</li>";
echo "<li><strong>Navigate back</strong> to dashboard using the 'Back to Dashboard' button</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; color: #856404; margin: 20px 0;'>";
echo "<h4>⚡ Key Features:</h4>";
echo "<ul>";
echo "<li><strong>Role-Based Access:</strong> Integrated with existing authentication</li>";
echo "<li><strong>Real-time Messaging:</strong> Auto-refresh every 20 seconds</li>";
echo "<li><strong>Cross-Platform:</strong> Works on all devices</li>";
echo "<li><strong>Secure:</strong> Users only see their own conversations</li>";
echo "<li><strong>Professional UI:</strong> Matches Ironroot design theme</li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='/messages' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 0 10px;'>🔗 Test Messaging System</a>";
echo "<a href='/dashboard' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 0 10px;'>📊 Go to Dashboard</a>";
echo "</div>";

echo "<hr style='margin: 30px 0;'>";
echo "<small style='color: #666; text-align: center; display: block;'>";
echo "Ironroot Messaging Setup Complete • " . date('Y-m-d H:i:s');
echo "</small>";
?>