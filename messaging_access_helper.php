<!DOCTYPE html>
<html>
<head>
    <title>Messaging Access Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn { padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px; }
        .btn:hover { background: #0056b3; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Ironroot Messaging Access Options</h1>
        
        <div class="info">
            <h3>📍 Current Issue:</h3>
            <p>The routing system expects <code>/Ironroot/</code> base URL, but we're accessing through <code>/New%20folder/htdocs/Ironroot/</code></p>
        </div>

        <div class="success">
            <h3>✅ Direct Access Methods:</h3>
            <p>Try these direct access methods that bypass the routing system:</p>
        </div>

        <h3>🎯 Access Options:</h3>
        
        <div style="margin: 20px 0;">
            <h4>1. Direct File Access (Recommended for Testing):</h4>
            <a href="app/views/messaging/index.php" class="btn">🔗 Access Messaging System Directly</a>
            <p><small>This bypasses routing and accesses the file directly</small></p>
        </div>

        <div style="margin: 20px 0;">
            <h4>2. Integration Test:</h4>
            <a href="messaging_integration_test.php" class="btn">📊 View Integration Status</a>
            <p><small>Check if all components are properly set up</small></p>
        </div>

        <div style="margin: 20px 0;">
            <h4>3. Setup Sample Data:</h4>
            <a href="setup_messaging_integration.php" class="btn">🛠️ Initialize Sample Data</a>
            <p><small>Create sample users and messages for testing</small></p>
        </div>

        <div style="margin: 20px 0;">
            <h4>4. Dashboard Access (if logged in):</h4>
            <a href="app/views/dashboards/admin.php" class="btn">👨‍💼 Admin Dashboard (with messaging button)</a>
            <p><small>Access dashboard that includes messaging navigation</small></p>
        </div>

        <div class="info">
            <h3>🔧 To Fix Routing (Optional):</h3>
            <ol>
                <li><strong>Update .htaccess RewriteBase</strong> to match your folder structure</li>
                <li><strong>Or configure virtual host</strong> to serve from <code>/Ironroot/</code></li>
                <li><strong>Or access through proper URL</strong> if you have one configured</li>
            </ol>
        </div>

        <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4>💡 Quick Solution:</h4>
            <p>For immediate testing, use the <strong>"Direct File Access"</strong> link above. The messaging system will work perfectly - it just bypasses the routing layer.</p>
        </div>

        <hr style="margin: 30px 0;">
        <small style="color: #666; text-align: center; display: block;">
            Ironroot Messaging Access Helper • <?php echo date('Y-m-d H:i:s'); ?>
        </small>
    </div>
</body>
</html>