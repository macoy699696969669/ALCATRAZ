<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate form data
        if (empty($_POST['title'])) {
            throw new Exception('Title is required');
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please select a valid image file');
        }

        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        // File validation
        $file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed');
        }
        
        if ($file['size'] > $max_size) {
            throw new Exception('File size must be less than 10MB');
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/gallery/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception('Failed to upload file');
        }
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO gallery (user_id, title, description, filename, original_filename, file_size, mime_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $title,
            $description,
            $filename,
            $file['name'],
            $file['size'],
            $file['type']
        ]);
        
        $response = [
            'success' => true,
            'message' => 'Image uploaded successfully!',
            'image_id' => $pdo->lastInsertId()
        ];
        
    } catch (Exception $e) {
        // Clean up file if database insert failed
        if (isset($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }
        
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>  