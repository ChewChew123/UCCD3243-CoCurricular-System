<?php
require_once 'includes/db_connect.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
   
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];

    
    $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "Username or Email already exists!";
    } else {
        $sql = "INSERT INTO users (username, password, full_name, email) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $password, $full_name, $email);

        if ($stmt->execute()) {
            header("Location: login.php?register=success");
            exit();
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="auth-container">
        <div class="auth-card">
            <h2>Student Register</h2>
            <p style="margin-bottom: 20px; color: #666;">Create an account to manage your merits</p>
            
            <?php if ($error): ?>
                <p style="color: #e74c3c; margin-bottom: 15px; font-size: 14px;"><?php echo $error; ?></p>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="text" name="username" placeholder="Student ID / Username" required>
                <input type="text" name="full_name" placeholder="Full Name (As per IC)" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Create Password" required>
                
                <button type="submit">Create Account</button>
            </form>
            
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

</body>
</html>