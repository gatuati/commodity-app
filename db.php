<?php
$host = 'mysql.railway.internal';
$port = 3306;
$dbname = 'railway';
$db_username = 'root';
$db_password = 'kIxyndwVhGhZthOsUfTgEXMVXjwFbRNt';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $db_username, $db_password, $dbname, $port);
    
    $conn->set_charset("utf8mb4");

    // Verify required tables exist
    $required_tables = ['users', 'facilities', 'activity_log'];
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            throw new Exception("Required table '$table' is missing");
        }
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
