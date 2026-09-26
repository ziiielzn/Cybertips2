<?php
require_once 'db.php';

function validatePassword($password) {
    // Check minimum length
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    
    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter";
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter";
    }
    
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number";
    }
    
    // Check for special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'\\|,.<>\/?]/', $password)) {
        return "Password must contain at least one special character";
    }
    
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Validate inputs
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Validate password
        $passwordValidation = validatePassword($password);
        if ($passwordValidation !== true) {
            throw new Exception($passwordValidation);
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already registered");
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already taken");
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user data into the database using PDO
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            // Set success message in session
            session_start();
            $_SESSION['success_message'] = "Account created successfully! Please log in.";
            header("Location: login.html");
            exit();
        } else {
            throw new Exception("Error creating account");
        }
    } catch (Exception $e) {
        // Return error as JSON
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
