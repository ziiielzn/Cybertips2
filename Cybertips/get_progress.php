<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file for debugging
$logFile = 'debug_progress.log';
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

if (!isset($_SESSION['user_id'])) {
    logError("User not logged in");
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

require_once 'db.php';

try {
    logError("Starting progress fetch for user ID: " . $_SESSION['user_id']);

    // Verify database connection
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    logError("Database connection successful");

    // Get all courses first - Updated slide counts for Cloud and Phishing
    $courses = [
        ['id' => 1, 'name' => 'Cyber Threats', 'total_slides' => 24],
        ['id' => 2, 'name' => 'Account Security', 'total_slides' => 14],
        ['id' => 3, 'name' => 'Network Security', 'total_slides' => 14],
        ['id' => 4, 'name' => 'Cloud & Data Security', 'total_slides' => 10], // Updated to exclude quiz slides
        ['id' => 5, 'name' => 'Social Engineering & Phishing', 'total_slides' => 10] // Updated to exclude quiz slides
    ];

    // Check if user_progress table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_progress'");
    if (!$stmt->fetch()) {
        logError("user_progress table does not exist, creating it");
        // Create the user_progress table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS user_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            current_slide INT DEFAULT 0,
            completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY user_course (user_id, course_id)
        )";
        $pdo->exec($sql);
        logError("user_progress table created successfully");
    }

    // Get user progress for each course
    $stmt = $pdo->prepare("
        SELECT course_id, current_slide, completed 
        FROM user_progress 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug log
    error_log("User ID: " . $_SESSION['user_id'] . " - Found progress records: " . count($progress));

    // Create a lookup array for easy access to progress data
    $progressLookup = [];
    foreach ($progress as $p) {
        // Adjust progress for Cloud and Phishing courses to exclude quiz slides
        $current_slide = $p['current_slide'];
        if ($p['course_id'] == 4 || $p['course_id'] == 5) { // Cloud or Phishing
            $current_slide = min($current_slide, 10); // Cap at 10 slides
        }
        
        $progressLookup[$p['course_id']] = [
            'current_slide' => $current_slide,
            'completed' => $p['completed']
        ];
        // Debug log
        error_log("Course ID: " . $p['course_id'] . " - Current slide: " . $current_slide . " - Completed: " . $p['completed']);
    }

    // Calculate progress for each course
    $courseProgress = [];
    foreach ($courses as $course) {
        $progress = $progressLookup[$course['id']] ?? null;
        
        if ($progress) {
            $percentage = min(($progress['current_slide'] / $course['total_slides']) * 100, 100); // Cap at 100%
            // For Cloud and Phishing courses, mark as completed when reaching last content slide
            if (($course['id'] == 4 || $course['id'] == 5) && $progress['current_slide'] >= 10) {
                $percentage = 100;
                $status = 'completed';
            } else {
                $status = $progress['completed'] ? 'completed' : ($percentage > 0 ? 'in-progress' : 'not-started');
            }
        } else {
            // If no progress record exists, create one
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO user_progress (user_id, course_id, current_slide, completed) VALUES (?, ?, 0, FALSE)");
                $stmt->execute([$_SESSION['user_id'], $course['id']]);
                logError("Created new progress record for course " . $course['id']);
            } catch (PDOException $e) {
                logError("Error creating progress record: " . $e->getMessage());
            }
            $percentage = 0;
            $status = 'not-started';
        }

        $courseProgress[] = [
            'id' => $course['id'],
            'name' => $course['name'],
            'percentage' => round(min($percentage, 100)), // Ensure percentage never exceeds 100
            'status' => $status
        ];

        // Debug log
        error_log("Course: " . $course['name'] . " - Percentage: " . round($percentage) . "% - Status: " . $status);
    }

    logError("Successfully prepared course progress data");
    echo json_encode([
        'success' => true,
        'data' => $courseProgress
    ]);

} catch (PDOException $e) {
    logError("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    logError("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error occurred',
        'details' => $e->getMessage()
    ]);
}
?> 