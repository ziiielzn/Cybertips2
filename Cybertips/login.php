<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'error' => 'Please fill in all fields'
        ]);
        exit();
    }

    try {
        // Use prepared statement to prevent SQL injection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];  // Store user ID in session
                echo json_encode([
                    'success' => true,
                    'redirect' => 'home.html'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Incorrect password. Please try again.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No account found with this email.'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'An error occurred during login. Please try again.'
        ]);
    }
    exit();
}

// If not a POST request, return error
echo json_encode([
    'success' => false,
    'error' => 'Invalid request method'
]);
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Log In</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    .error-message {
      color: #ff4444;
      background: rgba(255, 68, 68, 0.1);
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
      text-align: center;
      display: none;
    }
  </style>
</head>
<body class="login-page">
  <!-- Background glow elements -->
  <div class="glow-box"></div>
  <div class="glow-box"></div>
  <div class="glow-box"></div>

  <div class="login-container">
    <!-- Left Panel (Logo) -->
    <div class="left-panel">
      <img src="logo.png" alt="Cyber Tips Logo" class="logo-left" />
    </div>

    <!-- Right Panel (Form) -->
    <div class="right-panel">
      <h2>Log In</h2>
      <?php if (isset($error)): ?>
        <div class="error-message" style="display: block;"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form class="form-plain" method="POST">
        <input 
          type="email" 
          name="email" 
          placeholder="Email address" 
          required
          pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
          title="Please enter a valid email address"
        />
        <input 
          type="password" 
          name="password" 
          placeholder="Password" 
          required
          minlength="8"
        />
        <button type="submit" class="submit-btn">Log In</button>
        <div class="signup-link">
          Don't have an account? <a href="index.html">Sign Up</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
