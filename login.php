<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, password, role, facility_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role, $facility_id);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['user_role'] = $role;
        $_SESSION['facility_id'] = $facility_id;

        // Log activity
        $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, 'User logged in', ?)");
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt->bind_param("is", $id, $ip);
        $log_stmt->execute();
        $log_stmt->close();

        // Redirect by role
        header("Location: " . ($role === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
        exit();
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container">
            <h2>Login</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" placeholder="Enter username" required>
                </div>

                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit">Login</button>
            </form>

            <p>Don't have an account? <a href="register.php">Register here</a>.</p>
        </div>
    </div>
</body>
</html>
