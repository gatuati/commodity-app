<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';

// Redirect non-admins
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// DB connection
$conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data
$user_count = 0;
$transaction_count = 0;
$activities = [];

// Query 1: User count
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result && $row = $result->fetch_assoc()) {
    $user_count = $row['count'];
}

// Query 2: Transaction count
$result = $conn->query("SELECT COUNT(*) as count FROM transactions");
if ($result && $row = $result->fetch_assoc()) {
    $transaction_count = $row['count'];
}

// Query 3: Activity log
$result = $conn->query("
    SELECT a.*, u.username 
    FROM activity_log a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 50
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Additional Admin Dashboard Styles */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-nav {
            background: #2c3e50;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            background: #3498db;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            margin-top: 0;
        }
        
        .stat-card p {
            font-size: 2rem;
            margin: 10px 0 0;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .activity-log {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .activity-log h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .activity-table th, .activity-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .activity-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .activity-table tr:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .activity-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/kiambu.jpeg" alt="Logo" class="logo">
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="admin-container">
        <div class="admin-nav">
            <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="commodities.php"><i class="fas fa-boxes"></i> Commodities</a>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Total Users</h3>
                <p><?php echo $user_count; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-exchange-alt"></i> Total Transactions</h3>
                <p><?php echo $transaction_count; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
                <p><?php echo count($activities); ?></p>
            </div>
        </div>
        
        <div class="activity-log">
            <h2><i class="fas fa-list"></i> Recent Activities</h2>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['username']); ?></td>
                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                            <td><?php echo htmlspecialchars($activity['details']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($activity['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No recent activities found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>