<?php
session_start();
require_once 'auth_check.php'; // Ensure the admin is logged in

// Only allow admin access
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch reports
$reports = $conn->query("SELECT * FROM reports");

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
    <div class="header">
        <img src="images/kiambu.jpeg" alt="Logo" class="logo">
        <div class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="admin-content">
        <h2>Reports</h2>
        
        <h3>Available Reports</h3>
        <table>
            <thead>
                <tr>
                    <th>Report Name</th>
                    <th>Generated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($report = $reports->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($report['report_name']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($report['generated_at'])); ?></td>
                        <td>
                            <a href="view_report.php?id=<?php echo $report['id']; ?>">View</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
