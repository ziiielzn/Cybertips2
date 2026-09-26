<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // First verify the current password
    if (!isset($_POST['current-password'])) {
        throw new Exception('Current password is required');
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($_POST['current-password'], $user['password'])) {
        throw new Exception('Current password is incorrect');
    }

    // Handle username update
    if (isset($_POST['new-username'])) {
        $newUsername = trim($_POST['new-username']);
        
        // Validate username
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $newUsername)) {
            throw new Exception('Invalid username format');
        }

        // Check if username is already taken
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$newUsername, $userId]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username already taken');
        }

        // Update username
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$newUsername, $userId]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // Handle email update
    if (isset($_POST['new-email'])) {
        $newEmail = trim($_POST['new-email']);
        
        // Validate email
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email is already taken
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$newEmail, $userId]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email already registered');
        }

        // Update email
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$newEmail, $userId]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    // Handle password update
    if (isset($_POST['new-password'])) {
        $newPassword = $_POST['new-password'];
        
        // Validate password
        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $newPassword)) {
            throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, and one number');
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        echo json_encode(['success' => true]);
        exit();
    }

    throw new Exception('No valid update action specified');

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
?> 