<?php

session_start();
if (!isset($_SESSION['user_id'])) {
   
    die("Unauthorized access.");
}
$host = 'sql206.infinityfree.com';
// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from the request
$data = json_decode(file_get_contents('php://input'), true);
$commodityName = $data['commodity_name'];
$unitCost = $data['unit_cost'];
$unitOfIssue = $data['unit_of_issue'];

// Insert the new commodity into the database
$stmt = $conn->prepare("INSERT INTO commodities (commodity_name, unit_cost, unit_of_issue) VALUES (?, ?, ?)");
$stmt->bind_param("sds", $commodityName, $unitCost, $unitOfIssue);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>