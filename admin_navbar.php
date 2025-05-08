<?php
// admin_navbar.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin status (optional security check)
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <!-- Brand/Logo -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-warehouse me-2"></i>Commodity Management
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="adminNavbar">
            <!-- Left Side Navigation -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                
                <?php if ($is_admin): ?>
                <li class="nav-item">
                    <a class="nav-link active" href="manage_users.php">
                        <i class="fas fa-users-cog me-1"></i> Manage Users
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="commodities.php">
                        <i class="fas fa-boxes me-1"></i> Commodities
                    </a>
                </li>
                
                
                    </ul>
                </li>
            </ul>

            
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <div class="d-flex align-items-center px-3 py-2">
                                <div class="me-3">
                                    <i class="fas fa-user-circle fa-2x"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Include necessary scripts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
    /* Custom navbar styles */
    .navbar {
        padding: 0.5rem 1rem;
    }
    .dropdown-menu {
        min-width: 250px;
    }
    .nav-link {
        padding: 0.5rem 1rem;
    }
    .dropdown-item {
        padding: 0.5rem 1.5rem;
    }
</style>