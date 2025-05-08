<?php
session_start();

// Enhanced session validation
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection with error handling
require_once 'db.php';

// Get current user's role (assuming role is stored directly in users table)
$current_user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();

// Strict role validation
if ($current_user['role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: dashboard.php");
    exit();
}

// Handle user actions with prepared statements
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'csrf_validate.php';
    
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        // Prevent self-deletion
        if ($user_id == $current_user_id) {
            $_SESSION['error'] = "You cannot delete your own account";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                // Log the action
                $action = "Deleted user ID: $user_id";
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, created_at) VALUES (?, ?, 'users')");
                $log_stmt->bind_param("is", $current_user_id, $action);
                $log_stmt->execute();
                $_SESSION['success'] = "User deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting user: " . $conn->error;
            }
        }
    }

    if (isset($_POST['edit_user'])) {
        $user_id = intval($_POST['user_id']);
        $username = htmlspecialchars(trim($_POST['username']));
        $role = htmlspecialchars(trim($_POST['role']));
        $facility_id = !empty($_POST['facility_id']) ? intval($_POST['facility_id']) : NULL;

        // Validate username
        if (empty($username)) {
            $_SESSION['error'] = "Username cannot be empty";
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, facility_id = ? WHERE id = ?");
            $stmt->bind_param("ssii", $username, $role, $facility_id, $user_id);
            
            if ($stmt->execute()) {
                // Log the action
                $action = "Updated user ID: $user_id";
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, created_at) VALUES (?, ?, 'users')");
                $log_stmt->bind_param("is", $current_user_id, $action);
                $log_stmt->execute();
                $_SESSION['success'] = "User updated successfully";
            } else {
                $_SESSION['error'] = "Error updating user: " . $conn->error;
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: manage_users.php");
    exit();
}

// Get all users with their facilities
$users = $conn->query("
    SELECT users.id, users.username, users.role, 
           facilities.id AS facility_id, facilities.name AS facility 
    FROM users 
    LEFT JOIN facilities ON users.facility_id = facilities.id 
    ORDER BY users.id
");

// Get all distinct roles from users table
$roles = $conn->query("SELECT DISTINCT role FROM users");

// Get all available facilities
$facilities = $conn->query("SELECT * FROM facilities ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4">Manage Users</h1>
        
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
                        <th>Username</th>
                        <th>Role</th>
                        <th>Facility</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= htmlspecialchars($user['facility'] ?? 'N/A') ?></td>
                        <td>
                            <div class="btn-group">
                                <!-- Edit Button -->
                                <button class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal"
                                        data-userid="<?= $user['id'] ?>"
                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                        data-role="<?= htmlspecialchars($user['role']) ?>"
                                        data-facilityid="<?= $user['facility_id'] ?? '' ?>">
                                    Edit
                                </button>
                                
                                <!-- Delete Button (hidden for current user) -->
                                <?php if ($user['id'] != $current_user_id): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete this user?')">
                                        Delete
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="modalUserId">
                        <div class="mb-3">
                            <label for="modalUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="modalUsername" required>
                        </div>
                        <div class="mb-3">
                            <label for="modalRole" class="form-label">Role</label>
                            <select class="form-select" name="role" id="modalRole" required>
                                <?php while ($role = $roles->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($role['role']) ?>"><?= htmlspecialchars($role['role']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="modalFacility" class="form-label">Facility</label>
                            <select class="form-select" name="facility_id" id="modalFacility">
                                <option value="">None</option>
                                <?php while ($facility = $facilities->fetch_assoc()): ?>
                                    <option value="<?= $facility['id'] ?>"><?= htmlspecialchars($facility['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modal with user data
        document.getElementById('editUserModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;
            
            modal.querySelector('#modalUserId').value = button.getAttribute('data-userid');
            modal.querySelector('#modalUsername').value = button.getAttribute('data-username');
            modal.querySelector('#modalRole').value = button.getAttribute('data-role');
            modal.querySelector('#modalFacility').value = button.getAttribute('data-facilityid') || '';
        });
    </script>
</body>
</html>