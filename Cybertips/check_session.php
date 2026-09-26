<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit();
}

// Check if user still exists in database
require_once 'db.php';

try {
    $stmt = $pdo->prepare("SELECT id, username, email, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // User no longer exists in database
        session_destroy();
        echo json_encode(['logged_in' => false, 'user_deleted' => true]);
        exit();
    }

    echo json_encode([
        'logged_in' => true,
        'success' => true,
        'data' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'profile_picture' => $user['profile_picture'] ?? 'default-profile.jpg'
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['logged_in' => false, 'error' => 'Database error']);
}
?> 