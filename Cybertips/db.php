<?php
// Use hosting environment variables (e.g. Railway MySQL) when set, otherwise XAMPP defaults
$servername = getenv('MYSQLHOST') ?: "localhost";
$port = getenv('MYSQLPORT') ?: "3306";
$username = getenv('MYSQLUSER') ?: "root";  // Default XAMPP username
$password = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : "";      // Default XAMPP password
$dbname = getenv('MYSQLDATABASE') ?: "cyber_tips"; // Your database name

try {
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create users table on a fresh database
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        profile_picture VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Check if username column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
    if ($result->rowCount() === 0) {
        // Add username column if it doesn't exist
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(255) NOT NULL AFTER id");
        
        // Update existing records to use email as username if there are any records
        $pdo->exec("UPDATE users SET username = email WHERE username IS NULL");
    }

    // Also add profile_picture column if it doesn't exist
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    if ($result->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
    }
    
} catch(PDOException $e) {
    // Log the error to a file
    $logFile = 'database_error.log';
    $timestamp = date('Y-m-d H:i:s');
    $errorMessage = "[$timestamp] Database Connection Error: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    
    die("Connection failed: " . $e->getMessage());
}
?>
