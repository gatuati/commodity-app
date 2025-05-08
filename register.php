<?php
session_start();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username         = trim($_POST['username']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $facility_id      = !empty($_POST['facility']) ? intval($_POST['facility']) : null;

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if (empty($errors)) {
        $conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role_id = 2; // Default role: manager

            $stmt = $conn->prepare("INSERT INTO users (username, password, role, facility_id) VALUES (?, ?, ?, ?)");
            $role = 'manager'; // changeable
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $facility_id);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Error: " . $stmt->error;
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container">
            <h2>Register</h2>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
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

                <div class="form-group">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" placeholder="Confirm password" required>
                </div>

                <div class="form-group">
                    <label>Facility (optional):</label>
                    <select name="facility">
                        <option value="">Select Facility</option>
                        <?php
                        $conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
                        $facilities = $conn->query("SELECT id, name FROM facilities ORDER BY name");
                        while ($row = $facilities->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                        }
                        $conn->close();
                        ?>
                    </select>
                </div>

                <button type="submit">Register</button>
            </form>

            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </div>
    </div>
</body>
</html>
