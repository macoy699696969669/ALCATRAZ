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

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    try {
        $image_id = $_POST['image_id'];
        $user_id = $_SESSION['user_id'];
        
        // Get image info and check permissions
        $stmt = $pdo->prepare("SELECT filename, user_id FROM gallery WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();
        
        if (!$image) {
            throw new Exception('Image not found');
        }
        
        // Check if user owns the image or is admin
        if ($image['user_id'] != $user_id && $_SESSION['role'] != 'admin') {
            throw new Exception('Permission denied');
        }
        
        // Delete file
        $file_path = 'uploads/gallery/' . $image['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$image_id]);
        
        $message = 'Image deleted successfully!';
        $message_type = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Get user's images or all images if admin
try {
    if ($_SESSION['role'] == 'admin') {
        $stmt = $pdo->prepare("
            SELECT 
                g.*,
                u.full_name as uploader_name
            FROM gallery g
            JOIN users u ON g.user_id = u.id
            ORDER BY g.upload_date DESC
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                g.*,
                u.full_name as uploader_name
            FROM gallery g
            JOIN users u ON g.user_id = u.id
            WHERE g.user_id = ?
            ORDER BY g.upload_date DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    $images = $stmt->fetchAll();
} catch (Exception $e) {
    $images = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Hermano Syndicate</title>
    <link rel="stylesheet" href="gallery-styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1>HERMANO SYNDICATE</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="attendance.php" class="nav-btn">Attendance</a>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="manage_users.php" class="nav-btn">Manage Users</a>
                    <?php endif; ?>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="page-title">
                <h2>Gallery Management</h2>
                <p><?php echo $_SESSION['role'] == 'admin' ? 'All uploaded images' : 'Your uploaded images'; ?></p>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Upload Section -->
            <div class="upload-section">
                <h3>Upload New Image</h3>
                <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Title*</label>
                            <input type="text" id="title" name="title" required maxlength="255">
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Image File*</label>
                            <input type="file" id="image" name="image" accept="image/*" required>
                            <small>Max size: 10MB. Formats: JPG, PNG, GIF, WebP</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" maxlength="1000"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Upload Image</span>
                        <span class="btn-loading" style="display: none;">Uploading...</span>
                    </button>
                </form>
            </div>

            <!-- Gallery Grid -->
            <div class="gallery-section">
                <h3>Your Images (<?php echo count($images); ?>)</h3>
                
                <?php if (!empty($images)): ?>
                    <div class="gallery-grid">
                        <?php foreach ($images as $image): ?>
                            <div class="gallery-item">
                                <div class="image-container">
                                    <img src="uploads/gallery/<?php echo htmlspecialchars($image['filename']); ?>" 
                                         alt="<?php echo htmlspecialchars($image['title']); ?>"
                                         loading="lazy"
                                         onerror="this.parentElement.innerHTML='<div class=\'image-error\'>Image not found</div>'">
                                    
                                    <div class="image-overlay">
                                        <div class="overlay-actions">
                                            <?php if ($image['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                                    <button type="submit" class="btn-delete">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="image-info">
                                    <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                                    <?php if ($image['description']): ?>
                                        <p class="description"><?php echo htmlspecialchars($image['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="meta">
                                        <span>by <?php echo htmlspecialchars($image['uploader_name']); ?></span>
                                        <span><?php echo date('M j, Y', strtotime($image['upload_date'])); ?></span>
                                    </div>
                                    <div class="status">
                                        <span class="status-badge <?php echo $image['is_public'] ? 'public' : 'private'; ?>">
                                            <?php echo $image['is_public'] ? 'Public' : 'Private'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-gallery">
                        <div class="empty-icon">📷</div>
                        <p>No images uploaded yet</p>
                        <small>Upload your first image using the form above</small>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <a href="attendance.php" class="back-link">← Back to Attendance</a>
        </footer>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            // Show loading state
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            
            try {
                const response = await fetch('upload_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message and reload page
                    alert('Image uploaded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error uploading image: ' + error.message);
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
        });

        // Preview selected image
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    this.value = '';
                    return;
                }
                
                // You can add image preview functionality here if needed
                console.log('Selected file:', file.name, 'Size:', (file.size / 1024 / 1024).toFixed(2) + 'MB');
            }
        });
    </script>
</body>
</html>