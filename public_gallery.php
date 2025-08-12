<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get all public gallery images
    $stmt = $pdo->prepare("
        SELECT 
            g.id,
            g.title,
            g.description,
            g.filename,
            g.upload_date,
            u.full_name as uploader_name
        FROM gallery g
        JOIN users u ON g.user_id = u.id
        WHERE g.is_public = 1
        ORDER BY g.upload_date DESC
    ");
    
    $stmt->execute();
    $images = $stmt->fetchAll();
    
    $response_images = [];
    
    foreach ($images as $image) {
        // Build image URL
        $image_url = 'uploads/gallery/' . $image['filename'];
        
        // Check if file exists
        if (file_exists($image_url)) {
            $response_images[] = [
                'id' => $image['id'],
                'title' => $image['title'],
                'description' => $image['description'] ?: 'No description available',
                'url' => $image_url,
                'uploader' => $image['uploader_name'],
                'date' => date('M j, Y', strtotime($image['upload_date']))
            ];
        }
    }
    
    $response = [
        'success' => true,
        'images' => $response_images,
        'count' => count($response_images)
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error loading gallery: ' . $e->getMessage(),
        'images' => [],
        'count' => 0
    ];
}

echo json_encode($response);
?>