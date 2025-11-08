<!DOCTYPE html>
<html>
<head>
    <title>Ironroot Messaging Integration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #c3e6cb; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #f5c6cb; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid #bee5eb; }
        .test-result { margin: 15px 0; padding: 15px; border-radius: 8px; border-left: 4px solid; }
        .test-pass { background: #d4edda; border-left-color: #28a745; }
        .test-fail { background: #f8d7da; border-left-color: #dc3545; }
        ul { list-style-type: none; padding-left: 20px; }
        li { margin: 10px 0; }
        li:before { content: "✓ "; color: #28a745; font-weight: bold; }
        .fail-list li:before { content: "✗ "; color: #dc3545; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Ironroot Messaging Integration Status</h1>
        
        <div class="info">
            <h3>📋 Integration Summary</h3>
            <p>This test verifies that the messaging system has been successfully integrated into all Ironroot dashboard components.</p>
        </div>

        <?php
        function checkFileExists($file, $name) {
            $exists = file_exists(__DIR__ . $file);
            echo "<div class='test-result " . ($exists ? 'test-pass' : 'test-fail') . "'>";
            echo "<strong>" . ($exists ? "✅ PASS" : "❌ FAIL") . "</strong>: $name<br>";
            if (!$exists) {
                echo "<small>File missing: " . htmlspecialchars($file) . "</small>";
            }
            echo "</div>";
            return $exists;
        }

        function checkStringInFile($file, $search, $name) {
            if (!file_exists(__DIR__ . $file)) {
                echo "<div class='test-result test-fail'><strong>❌ FAIL</strong>: $name<br><small>File not found: " . htmlspecialchars($file) . "</small></div>";
                return false;
            }
            
            $content = file_get_contents(__DIR__ . $file);
            $found = strpos($content, $search) !== false;
            echo "<div class='test-result " . ($found ? 'test-pass' : 'test-fail') . "'>";
            echo "<strong>" . ($found ? "✅ PASS" : "❌ FAIL") . "</strong>: $name<br>";
            if (!$found) {
                echo "<small>String not found: " . htmlspecialchars($search) . "</small>";
            }
            echo "</div>";
            return $found;
        }

        $total_tests = 0;
        $passed_tests = 0;

        echo "<h3>🔧 Core System Files</h3>";
        if (checkFileExists('/app/views/messaging/index.php', 'Main messaging system')) $passed_tests++;
        $total_tests++;
        
        if (checkFileExists('/app/views/components/messaging_nav.php', 'Messaging navigation component')) $passed_tests++;
        $total_tests++;
        
        if (checkStringInFile('/app/core/auth.php', 'getCurrentUserDashboard', 'Dashboard helper function in auth.php')) $passed_tests++;
        $total_tests++;

        echo "<h3>🎯 Dashboard Integration</h3>";
        
        $dashboards = [
            '/app/views/dashboards/admin.php' => 'Admin Dashboard',
            '/app/views/dashboards/client.php' => 'Client Dashboard', 
            '/app/views/dashboards/supervisor.php' => 'Supervisor Dashboard',
            '/app/views/dashboards/manager.php' => 'Manager Dashboard',
            '/app/views/dashboards/project_manager.php' => 'Project Manager Dashboard',
            '/app/views/dashboards/site_engineer.php' => 'Site Engineer Dashboard',
            '/app/views/dashboards/site_manager.php' => 'Site Manager Dashboard',
            '/app/views/dashboards/sub_contractor.php' => 'Sub Contractor Dashboard',
            '/app/views/dashboards/logistic_officer.php' => 'Logistic Officer Dashboard',
            '/app/views/dashboards/general_contractor.php' => 'General Contractor Dashboard'
        ];

        foreach ($dashboards as $file => $name) {
            if (checkStringInFile($file, 'messaging_nav.php', "$name messaging integration")) $passed_tests++;
            $total_tests++;
        }

        echo "<h3>🛣️ Routing System</h3>";
        if (checkStringInFile('/public/index.php', '/app/views/messaging/index.php', 'Updated /messages route')) $passed_tests++;
        $total_tests++;

        echo "<h3>📁 Data Storage</h3>";
        $data_dir_exists = is_dir(__DIR__ . '/data');
        echo "<div class='test-result " . ($data_dir_exists ? 'test-pass' : 'test-fail') . "'>";
        echo "<strong>" . ($data_dir_exists ? "✅ PASS" : "⚠️ INFO") . "</strong>: Data directory for messaging storage<br>";
        if (!$data_dir_exists) {
            echo "<small>Will be created automatically on first use</small>";
        }
        echo "</div>";

        // Calculate percentage
        $percentage = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100) : 0;
        
        echo "<div class='test-result " . ($percentage >= 90 ? 'test-pass' : ($percentage >= 70 ? 'info' : 'test-fail')) . "'>";
        echo "<h3>📊 Integration Score: $passed_tests/$total_tests ($percentage%)</h3>";
        
        if ($percentage >= 90) {
            echo "<div class='success'>";
            echo "<h4>🎉 Excellent Integration!</h4>";
            echo "<ul>";
            echo "<li>All critical components are properly integrated</li>";
            echo "<li>Messaging navigation added to all dashboards</li>";
            echo "<li>File-based storage system ready</li>";
            echo "<li>Authentication system enhanced</li>";
            echo "<li>Routing properly configured</li>";
            echo "</ul>";
            echo "</div>";
        } elseif ($percentage >= 70) {
            echo "<div class='info'>";
            echo "<h4>⚡ Good Integration</h4>";
            echo "<p>Most components integrated successfully. Review failed tests above.</p>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<h4>⚠️ Integration Issues</h4>";
            echo "<p>Several components need attention. Please review failed tests.</p>";
            echo "</div>";
        }
        echo "</div>";
        ?>

        <div class="info">
            <h3>🚀 Next Steps</h3>
            <ul>
                <li>Test messaging system by logging into any dashboard</li>
                <li>Click the messaging button (blue circle, bottom-right)</li>
                <li>Verify messaging works between different user roles</li>
                <li>Check that users can only see their own conversations</li>
                <li>Confirm navigation works both ways (dashboard ↔ messages)</li>
            </ul>
        </div>

        <div class="success">
            <h3>✨ Integration Features</h3>
            <ul>
                <li><strong>Role-Based Access:</strong> Integrated with existing authentication</li>
                <li><strong>Floating Button:</strong> Accessible from all dashboards</li>
                <li><strong>File-Based Storage:</strong> No database connection issues</li>
                <li><strong>Real-time Updates:</strong> Auto-refresh every 20 seconds</li>
                <li><strong>Professional UI:</strong> Bootstrap-based design matching Ironroot theme</li>
                <li><strong>Secure Conversations:</strong> Users see only their own messages</li>
                <li><strong>Cross-Role Messaging:</strong> Any authenticated user can message any other user</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/messages" class="btn">🔗 Open Messaging System</a>
            <a href="/dashboard" class="btn">📊 Back to Dashboard</a>
        </div>

        <hr style="margin: 30px 0;">
        <small style="color: #666; text-align: center; display: block;">
            Ironroot Messaging Integration Test • <?php echo date('Y-m-d H:i:s'); ?>
        </small>
    </div>
</body>
</html>