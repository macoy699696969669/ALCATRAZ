<?php
// Generate correct password hashes for the demo accounts

$passwords = [
    'admin123' => password_hash('admin123', PASSWORD_DEFAULT),
    'member123' => password_hash('member123', PASSWORD_DEFAULT)
];

echo "<h2>Generated Password Hashes:</h2>";
echo "<p><strong>admin123:</strong> " . $passwords['admin123'] . "</p>";
echo "<p><strong>member123:</strong> " . $passwords['member123'] . "</p>";

echo "<br><h3>Copy these SQL commands to fix your database:</h3>";
echo "<pre>";
echo "UPDATE users SET password = '" . $passwords['admin123'] . "' WHERE username = 'admin';\n";
echo "UPDATE users SET password = '" . $passwords['member123'] . "' WHERE username IN ('member1', 'member2', 'member3', 'member4', 'member5', 'member6', 'member7', 'member8');";
echo "</pre>";

echo "<br><h3>Or run this complete script:</h3>";
?>

<pre>
-- Fix password hashes in database
UPDATE users SET password = '<?php echo $passwords['admin123']; ?>' WHERE username = 'admin';
UPDATE users SET password = '<?php echo $passwords['member123']; ?>' WHERE username IN ('member1', 'member2', 'member3', 'member4', 'member5', 'member6', 'member7', 'member8');
</pre>