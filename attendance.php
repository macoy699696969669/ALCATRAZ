<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    try {
        if ($_POST['action'] == 'time_in') {
            // Check if already timed in today
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ? AND time_in IS NOT NULL");
            $stmt->execute([$user_id, $today]);
            
            if ($stmt->fetch()) {
                $message = 'You have already timed in today!';
                $message_type = 'error';
            } else {
                // Record time in
                $stmt = $pdo->prepare("INSERT INTO attendance (user_id, date, time_in) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE time_in = VALUES(time_in)");
                $stmt->execute([$user_id, $today, date('H:i:s')]);
                $message = 'Time in recorded successfully!';
                $message_type = 'success';
            }
        } elseif ($_POST['action'] == 'time_out') {
            // Check if timed in today
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ? AND time_in IS NOT NULL");
            $stmt->execute([$user_id, $today]);
            
            if ($stmt->fetch()) {
                // Check if already timed out
                $stmt = $pdo->prepare("SELECT time_out FROM attendance WHERE user_id = ? AND date = ?");
                $stmt->execute([$user_id, $today]);
                $record = $stmt->fetch();
                
                if ($record['time_out']) {
                    $message = 'You have already timed out today!';
                    $message_type = 'error';
                } else {
                    // Record time out
                    $stmt = $pdo->prepare("UPDATE attendance SET time_out = ? WHERE user_id = ? AND date = ?");
                    $stmt->execute([date('H:i:s'), $user_id, $today]);
                    $message = 'Time out recorded successfully!';
                    $message_type = 'success';
                }
            } else {
                $message = 'You need to time in first!';
                $message_type = 'error';
            }
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get today's attendance status
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$today_attendance = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->execute([$user_id, $today]);
    $today_attendance = $stmt->fetch();
} catch (PDOException $e) {
    // Handle error
}

// Get recent attendance records
$recent_attendance = [];
try {
    if ($_SESSION['role'] == 'admin') {
        // Admin can see all records
        $stmt = $pdo->prepare("
            SELECT a.*, u.full_name, u.username 
            FROM attendance a 
            JOIN users u ON a.user_id = u.id 
            ORDER BY a.date DESC, a.time_in DESC 
            LIMIT 20
        ");
        $stmt->execute();
    } else {
        // Regular users see only their records
        $stmt = $pdo->prepare("
            SELECT a.*, u.full_name, u.username 
            FROM attendance a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.user_id = ? 
            ORDER BY a.date DESC 
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
    }
    $recent_attendance = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Alcatraz</title>
    <link rel="stylesheet" href="attendance-styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1>Alcatraz</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <span class="role">(<?php echo ucfirst($_SESSION['role']); ?>)</span>
                    <a href="gallery.php" class="nav-btn gallery-btn">Gallery</a>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="manage_users.php" class="nav-btn">Manage Users</a>
                    <?php endif; ?>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="attendance-section">
                <h2>Attendance System</h2>
                <div class="date-display">
                    <?php echo date('l, F j, Y'); ?>
                </div>

                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="attendance-status">
                    <?php if ($today_attendance): ?>
                        <div class="status-card">
                            <h3>Today's Status</h3>
                            <p><strong>Time In:</strong> <?php echo $today_attendance['time_in'] ? date('g:i A', strtotime($today_attendance['time_in'])) : 'Not recorded'; ?></p>
                            <p><strong>Time Out:</strong> <?php echo $today_attendance['time_out'] ? date('g:i A', strtotime($today_attendance['time_out'])) : 'Not recorded'; ?></p>
                            <?php if ($today_attendance['time_in'] && $today_attendance['time_out']): ?>
                                <?php
                                $time_in = new DateTime($today_attendance['time_in']);
                                $time_out = new DateTime($today_attendance['time_out']);
                                $duration = $time_in->diff($time_out);
                                ?>
                                <p><strong>Duration:</strong> <?php echo $duration->format('%h hours %i minutes'); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="status-card">
                            <h3>Today's Status</h3>
                            <p>No attendance record for today</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="attendance-buttons">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="time_in">
                        <button type="submit" class="btn btn-in" <?php echo ($today_attendance && $today_attendance['time_in']) ? 'disabled' : ''; ?>>
                            Time In
                        </button>
                    </form>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="time_out">
                        <button type="submit" class="btn btn-out" <?php echo (!$today_attendance || !$today_attendance['time_in'] || $today_attendance['time_out']) ? 'disabled' : ''; ?>>
                            Time Out
                        </button>
                    </form>
                </div>

                <!-- Quick Actions Section -->
                <div class="quick-actions">
                    <h4>Quick Actions</h4>
                    <div class="action-buttons">
                        <a href="gallery.php" class="quick-btn gallery-quick">
                            <span class="btn-icon">📸</span>
                            <span class="btn-text">Gallery</span>
                            <span class="btn-desc">Share moments</span>
                        </a>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="manage_users.php" class="quick-btn admin-quick">
                                <span class="btn-icon">👥</span>
                                <span class="btn-text">Manage Users</span>
                                <span class="btn-desc">User management</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="records-section">
                <h3><?php echo $_SESSION['role'] == 'admin' ? 'All Attendance Records' : 'Your Attendance History'; ?></h3>
                
                <?php if (!empty($recent_attendance)): ?>
                    <div class="records-table">
                        <table>
                            <thead>
                                <tr>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                        <th>Name</th>
                                    <?php endif; ?>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attendance as $record): ?>
                                    <tr>
                                        <?php if ($_SESSION['role'] == 'admin'): ?>
                                            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo $record['time_in'] ? date('g:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                        <td><?php echo $record['time_out'] ? date('g:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                        <td>
                                            <?php
                                            if ($record['time_in'] && $record['time_out']) {
                                                $time_in = new DateTime($record['time_in']);
                                                $time_out = new DateTime($record['time_out']);
                                                $duration = $time_in->diff($time_out);
                                                echo $duration->format('%h:%I');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-records">No attendance records found.</p>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <a href="index.html" class="back-home">← Back to Home</a>
        </footer>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Real-time clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.querySelector('.current-time').textContent = timeString;
        }

        // Add current time display
        const dateDisplay = document.querySelector('.date-display');
        const timeSpan = document.createElement('div');
        timeSpan.className = 'current-time';
        timeSpan.style.fontSize = '18px';
        timeSpan.style.marginTop = '5px';
        dateDisplay.appendChild(timeSpan);

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>