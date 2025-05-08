<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
    
}
$host = 'sql206.infinityfree.com';
// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

// Validate and sanitize input
$department = $_POST['department'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$subcounty = $_POST['subcounty'] ?? '';
$facility = $_POST['facility'] ?? '';
$commodity = $_POST['commodity'] ?? '';
$lastSummaryDate = $_POST['last_summary_date'] ?? '';

// Initialize other variables
$beginningBalance = 0; // Set default values or retrieve from POST if applicable
$receivedFromSupplier = 0;
$used = 0;
$requested = 0;
$positiveAdjustment = 0;
$negativeAdjustment = 0;
$losses = 0;
$daysOutOfStock = 0;
$revenueLastMonth = 0.0;
$totalCost = 0.0;
$unitCost = 0.0;
$unitOfIssue = ''; // Set default or retrieve from POST if applicable

// Debugging: Log received data
error_log("Department: $department");
error_log("Start Date: $startDate");
error_log("End Date: $endDate");
error_log("Subcounty: $subcounty");
error_log("Facility: $facility");
error_log("Commodity: $commodity");
error_log("Last Summary Date: $lastSummaryDate");


// Insert the transaction into the database
$query = "INSERT INTO transactions (
    department, start_date, end_date, subcounty, facility, commodity, 
    beginning_balance, received_from_supplier, used, requested, 
    positive_adjustment, negative_adjustment, losses, days_out_of_stock, 
    last_summary_date, revenue_last_month, total_cost, unit_cost, unit_of_issue
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die(json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]));
}

// Bind parameters with the correct format string
$stmt->bind_param(
    "ssssssiiiiiiiisddds", // Format string
    $department, $startDate, $endDate, $subcounty, $facility, $commodity,
    $beginningBalance, $receivedFromSupplier, $used, $requested,
    $positiveAdjustment, $negativeAdjustment, $losses, $daysOutOfStock,
    $lastSummaryDate, $revenueLastMonth, $totalCost, $unitCost, $unitOfIssue
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>