<?php
require_once 'config.php';

try {
    // Generate proper password hashes
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $member_hash = password_hash('member123', PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$admin_hash]);
    
    // Update all member passwords
    $members = ['member1', 'member2', 'member3', 'member4', 'member5', 'member6', 'member7', 'member8'];
    foreach ($members as $member) {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$member_hash, $member]);
    }
    
    echo "<h2>✅ Password Setup Complete!</h2>";
    echo "<p>All passwords have been properly hashed and updated in the database.</p>";
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: <code>admin</code>, password: <code>admin123</code></li>";
    echo "<li><strong>Members:</strong> username: <code>member1-member8</code>, password: <code>member123</code></li>";
    echo "</ul>";
    echo "<p><a href='login.php'>→ Go to Login Page</a></p>";
    echo "<p><a href='index.html'>→ Back to Homepage</a></p>";
    
    echo "<br><p><strong>Note:</strong> You can delete this file (setup_passwords.php) after running it once.</p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>Database error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure your database is properly set up and config.php has correct settings.</p>";
}
?>