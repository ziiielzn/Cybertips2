<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Validate input
$required_fields = ['course_id', 'current_slide', 'total_slides'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit();
    }
}

$course_id = intval($_POST['course_id']);
$current_slide = intval($_POST['current_slide']);
$total_slides = intval($_POST['total_slides']);

// Validate values
if ($current_slide < 0 || $current_slide > $total_slides) {
    echo json_encode(['success' => false, 'error' => 'Invalid slide number']);
    exit();
}

require_once 'db.php';

try {
    // Check if a record exists
    $stmt = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE user_progress 
            SET current_slide = ?,
                completed = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ? AND course_id = ?
        ");
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO user_progress (user_id, course_id, current_slide, completed)
            VALUES (?, ?, ?, ?)
        ");
    }

    // Calculate if course is completed
    $completed = ($current_slide >= $total_slides) ? 1 : 0;

    if ($exists) {
        $stmt->execute([$current_slide, $completed, $_SESSION['user_id'], $course_id]);
    } else {
        $stmt->execute([$_SESSION['user_id'], $course_id, $current_slide, $completed]);
    }

    // Calculate percentage
    $percentage = round(($current_slide / $total_slides) * 100);

    echo json_encode([
        'success' => true,
        'data' => [
            'percentage' => $percentage,
            'completed' => $completed == 1
        ]
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?> 