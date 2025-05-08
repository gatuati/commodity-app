<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'db.php';

// Check if user has access to commodities
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || ($user['role'] !== 'admin' && $user['role'] !== 'manager')) {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: dashboard.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'csrf_validate.php';
    
    if (isset($_POST['add_commodity'])) {
        $commodity_name = htmlspecialchars(trim($_POST['commodity_name']));
        $unit_of_issue = htmlspecialchars(trim($_POST['unit_of_issue']));
        $unit_cost = floatval($_POST['unit_cost']);
        $quantity = 1; // Default quantity since we removed the field
        $total_cost = $quantity * $unit_cost;

        $stmt = $conn->prepare("INSERT INTO commodities (commodity_name, unit_of_issue, unit_cost, total_cost) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdd", $commodity_name, $unit_of_issue, $unit_cost, $total_cost);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Commodity added successfully";
            
            // Log the action
            $action = "Added commodity: $commodity_name";
            $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, created_at) VALUES (?, ?, NOW())");
            $log_stmt->bind_param("is", $_SESSION['user_id'], $action);
            $log_stmt->execute();
        } else {
            $_SESSION['error'] = "Error adding commodity: " . $conn->error;
        }
    }
    
    header("Location: commodities.php");
    exit();
}

// Get all commodities
$commodities = $conn->query("
    SELECT id, commodity_name, unit_of_issue, unit_cost, total_cost 
    FROM commodities 
    ORDER BY commodity_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commodity Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-boxes me-2"></i> Commodity Management</h1>
            <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommodityModal">
                    <i class="fas fa-plus me-1"></i> Add Commodity
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Commodity Name</th>
                        <th>Unit of Issue</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($commodity = $commodities->fetch_assoc()): ?>
                        <tr>
                            <td><?= $commodity['id'] ?></td>
                            <td><?= htmlspecialchars($commodity['commodity_name']) ?></td>
                            <td><?= htmlspecialchars($commodity['unit_of_issue']) ?></td>
                            <td><?= number_format($commodity['unit_cost'], 2) ?></td>
                            <td><?= number_format($commodity['total_cost'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Commodity Modal -->
    <div class="modal fade" id="addCommodityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Commodity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="commodity_name" class="form-label">Commodity Name</label>
                            <input type="text" class="form-control" name="commodity_name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unit_of_issue" class="form-label">Unit of Issue</label>
                                <select class="form-select" name="unit_of_issue" required>
                                    <option value="kg">Kilograms (kg)</option>
                                    <option value="g">Grams (g)</option>
                                    <option value="l">Liters (l)</option>
                                    <option value="ml">Milliliters (ml)</option>
                                    <option value="units">Units</option>
                                    <option value="boxes">Boxes</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <input type="number" step="0.01" class="form-control" name="unit_cost" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_commodity" class="btn btn-primary">Add Commodity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>