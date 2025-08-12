<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_user':
                    $username = trim($_POST['username']);
                    $password = $_POST['password'];
                    $full_name = trim($_POST['full_name']);
                    $role = $_POST['role'];
                    $email = trim($_POST['email']);
                    
                    // Validate inputs
                    if (empty($username) || empty($password) || empty($full_name)) {
                        throw new Exception('Username, password, and full name are required!');
                    }
                    
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        throw new Exception('Username already exists!');
                    }
                    
                    // Hash password and insert user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $hashed_password, $full_name, $role, $email]);
                    
                    $message = 'User added successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'delete_user':
                    $user_id = $_POST['user_id'];
                    
                    // Prevent deleting current admin
                    if ($user_id == $_SESSION['user_id']) {
                        throw new Exception('You cannot delete your own account!');
                    }
                    
                    // Delete user and their attendance records
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    $message = 'User deleted successfully!';
                    $message_type = 'success';
                    break;
                    
                case 'edit_user':
                    $user_id = $_POST['user_id'];
                    $username = trim($_POST['username']);
                    $full_name = trim($_POST['full_name']);
                    $role = $_POST['role'];
                    $email = trim($_POST['email']);
                    $new_password = $_POST['new_password'];
                    
                    // Validate inputs
                    if (empty($username) || empty($full_name)) {
                        throw new Exception('Username and full name are required!');
                    }
                    
                    // Check if username already exists (except for current user)
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $user_id]);
                    if ($stmt->fetch()) {
                        throw new Exception('Username already exists!');
                    }
                    
                    // Update user
                    if (!empty($new_password)) {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, role = ?, email = ? WHERE id = ?");
                        $stmt->execute([$username, $hashed_password, $full_name, $role, $email, $user_id]);
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, role = ?, email = ? WHERE id = ?");
                        $stmt->execute([$username, $full_name, $role, $email, $user_id]);
                    }
                    
                    $message = 'User updated successfully!';
                    $message_type = 'success';
                    break;
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Get all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY role DESC, full_name ASC");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Hermano Syndicate</title>
    <link rel="stylesheet" href="manage-users-styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1>HERMANO SYNDICATE</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="attendance.php" class="nav-btn">Attendance</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="page-title">
                <h2>User Management</h2>
                <p>Add, edit, or remove user accounts</p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="section">
                <h3>Add New User</h3>
                <form method="POST" class="user-form">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username*</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password*</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name*</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role*</label>
                            <select id="role" name="role" required>
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users List -->
            <div class="section">
                <h3>Existing Users (<?php echo count($users); ?>)</h3>
                
                <div class="users-grid">
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
                                <p class="email"><?php echo htmlspecialchars($user['email'] ?: 'No email'); ?></p>
                                <span class="role <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                            </div>
                            
                            <div class="user-actions">
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="btn btn-small btn-secondary">Edit</button>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their attendance records.')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="current-user">Current User</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>

        <footer class="footer">
            <a href="attendance.php" class="back-link">← Back to Attendance</a>
        </footer>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit User</h3>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label for="edit_username">Username*</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_full_name">Full Name*</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email">
                </div>
                
                <div class="form-group">
                    <label for="edit_role">Role*</label>
                    <select id="edit_role" name="role" required>
                        <option value="member">Member</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_new_password">New Password (leave empty to keep current)</label>
                    <input type="password" id="edit_new_password" name="new_password">
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_new_password').value = '';
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>