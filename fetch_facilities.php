<?php
session_start();
if (!isset($_SESSION['user_id'])) {

    die("Unauthorized access.");
}
$host = 'localhost';
// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    
}

// Validate and sanitize input
$subcountyId = isset($_POST['subcounty_id']) ? intval($_POST['subcounty_id']) : 0;

// Fetch facilities for the selected subcounty
$query = "SELECT * FROM facilities WHERE subcounty_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $subcountyId);
$stmt->execute();
$result = $stmt->get_result();

$options = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='{$row['id']}'>{$row['name']}</option>";
    }
} else {
    $options .= "<option value=''>No facilities found</option>";
}

echo $options;

$stmt->close();
$conn->close();
?>