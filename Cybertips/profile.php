<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file for debugging
$logFile = 'debug.log';
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    logError("User not logged in. Session ID: " . session_id());
    exit();
}

$userId = $_SESSION['user_id'];
logError("Attempting to fetch data for user ID: $userId");

// Fetch user data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // First, verify the database connection
        if (!$pdo) {
            throw new Exception("Database connection failed");
        }
        
        // Log the SQL query for debugging
        $sql = "SELECT username, email, profile_picture FROM users WHERE id = ?";
        logError("Executing query: $sql with ID: $userId");
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            logError("User data found: " . json_encode($userData));
            echo json_encode([
                'success' => true,
                'data' => [
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'profile_picture' => $userData['profile_picture'] ?? 'default-profile.jpg'
                ]
            ]);
        } else {
            logError("No user found with ID: $userId");
            echo json_encode(['error' => 'User not found in database']);
        }
        exit();
    } catch (PDOException $e) {
        logError("PDO Error: " . $e->getMessage());
        logError("Error Code: " . $e->getCode());
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    } catch (Exception $e) {
        logError("General Error: " . $e->getMessage());
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        exit();
    }
}

// Handle profile picture upload
if (isset($_FILES['profile-picture'])) {
    $file = $_FILES['profile-picture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['error' => 'Invalid file type. Please upload an image.']);
        exit();
    }
    
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate a unique filename with timestamp and random string
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $timestamp . '_' . $randomString . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    // Create a copy of the uploaded file in our uploads directory
    if (copy($file['tmp_name'], $targetPath)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$targetPath, $userId]);
            
            echo json_encode(['success' => true, 'newImagePath' => $targetPath]);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            unlink($targetPath); // Delete the uploaded file if database update fails
            echo json_encode(['error' => 'Failed to update profile picture in database']);
        }
        exit();
    } else {
        echo json_encode(['error' => 'Failed to save file']);
        exit();
    }
}

// Handle profile information update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['profile-picture'])) {
    try {
        $updates = [];
        $params = [];
        
        // Update username if provided
        if (!empty($_POST['username'])) {
            // Check if username is already taken
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$_POST['username'], $userId]);
            if ($stmt->fetch()) {
                echo json_encode(['error' => 'Username already taken']);
                exit();
            }
            
            $updates[] = "username = ?";
            $params[] = $_POST['username'];
        }
        
        // Update password if both current and new passwords are provided
        if (!empty($_POST['current-password']) && !empty($_POST['new-password'])) {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!password_verify($_POST['current-password'], $user['password'])) {
                echo json_encode(['error' => 'Current password is incorrect']);
                exit();
            }
            
            $updates[] = "password = ?";
            $params[] = password_hash($_POST['new-password'], PASSWORD_DEFAULT);
        }
        
        if (!empty($updates)) {
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                $response = ['success' => true];
                if (!empty($_POST['username'])) {
                    $response['newUsername'] = $_POST['username'];
                }
                echo json_encode($response);
            } else {
                echo json_encode(['error' => 'Failed to update profile']);
            }
        } else {
            echo json_encode(['error' => 'No updates provided']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['error' => 'Database error occurred']);
    }
    exit();
}

echo json_encode(['error' => 'Invalid request']);
exit();
?> 