<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
  
    exit();
    
}
$host = 'localhost';
// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'commodity_management_tool');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch departments, subcounties, and commodities
$departments = $conn->query("SELECT * FROM departments");
$subcounties = $conn->query("SELECT * FROM subcounties");
$commodities = $conn->query("SELECT * FROM commodities");

// Check for query errors
if (!$departments || !$subcounties || !$commodities) {
    die("Error fetching data: " . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="header">
        <img src="images/kiambu.jpeg" alt="Logo" class="logo">
        <div class="date-range">
            <input type="date" id="start_date" name="start_date" required>
            <input type="date" id="end_date" name="end_date" required>
        </div>
    </div>
    <div class="content">
        <form id="commodity-form" action="save.php" method="POST" onsubmit="handleFormSubmit(event)">
            <!-- Department -->
            <div class="form-group">
                <label for="department">Department:</label>
                <select id="department" name="department" required>
                    <?php while ($row = $departments->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Subcounty -->
            <div class="form-group">
                <label for="subcounty">Subcounty:</label>
                <select id="subcounty" name="subcounty" required onchange="fetchFacilities()">
                    <?php while ($row = $subcounties->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Facility -->
            <div class="form-group">
                <label for="facility">Facility:</label>
                <select id="facility" name="facility" required>
                    <!-- Facilities will be populated dynamically -->
                </select>
            </div>

            <!-- Commodity -->
            <div class="form-group">
                <label for="commodity">Commodity:</label>
                <select id="commodity" name="commodity" required onchange="fillCommodityDetails()">
                    <?php while ($row = $commodities->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['id']); ?>" data-unit-cost="<?php echo htmlspecialchars($row['unit_cost']); ?>" data-unit-of-issue="<?php echo htmlspecialchars($row['unit_of_issue']); ?>">
                            <?php echo htmlspecialchars($row['commodity_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Unit Cost -->
            <div class="form-group">
                <label for="unit-cost">Unit Cost:</label>
                <input type="number" id="unit-cost" name="unit_cost" readonly>
            </div>

            <!-- Unit of Issue -->
            <div class="form-group">
                <label for="unit-of-issue">Unit of Issue:</label>
                <input type="text" id="unit-of-issue" name="unit_of_issue" readonly>
            </div>

            <!-- Quantity Fields -->
            <div class="form-group">
                <label for="beginning-balance">Beginning Balance:</label>
                <input type="number" id="beginning-balance" name="beginning_balance" required onchange="calculateTotalCost()">
            </div>

            <div class="form-group">
                <label for="received-from-supplier">Received from Supplier:</label>
                <input type="number" id="received-from-supplier" name="received_from_supplier" required onchange="calculateTotalCost()">
            </div>

            <div class="form-group">
                <label for="used">Used:</label>
                <input type="number" id="used" name="used" required onchange="calculateTotalCost()">
            </div>

            <div class="form-group">
                <label for="requested">Requested:</label>
                <input type="number" id="requested" name="requested" required onchange="calculateTotalCost()">
            </div>

            <div class="form-group">
                <label for="positive-adjustment">Positive Adjustment:</label>
                <input type="number" id="positive-adjustment" name="positive_adjustment" required onchange="calculateTotalCost()">
            </div>

            <div class="form-group">
                <label for="negative-adjustment">Negative Adjustment:</label>
                <input type="number" id="negative-adjustment" name="negative_adjustment" required onchange="calculateTotalCost()">
            </div>

            <div class="form-group">
                <label for="losses">Losses:</label>
                <input type="number" id="losses" name="losses" required onchange="calculateTotalCost()">
            </div>

            <div class="form-group">
                <label for="days-out-of-stock">Days Out of Stock:</label>
                <input type="number" id="days-out-of-stock" name="days_out_of_stock" required>
            </div>

            <div class="form-group">
                <label for="last-summary-date">Last Summary Date:</label>
                <input type="date" id="last-summary-date" name="last_summary_date" required>
            </div>

            <div class="form-group">
                <label for="revenue-last-month">Revenue Last Month:</label>
                <input type="number" id="revenue-last-month" name="revenue_last_month" step="0.01" required>
            </div>

            <!-- Total Cost -->
            <div class="form-group">
                <label for="total-cost">Total Cost:</label>
                <input type="number" id="total-cost" name="total_cost" readonly>
            </div>

            <button type="submit">Save</button>
        </form>
    </div>
    <div class="footer">
        <button id="add-commodity-btn" onclick="addCommodity()">Add Commodity</button>
        <button id="pdf-btn" onclick="generatePDF()">Download PDF</button>
        <button id="excel-btn" onclick="generateExcel()">Download Excel</button>
        <button id="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>

    <script>
        // Function to handle form submission
        function handleFormSubmit(event) {
            event.preventDefault(); // Prevent default form submission
            const form = document.getElementById('commodity-form');
            const formData = new FormData(form);
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            // Log form data for debugging
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            // Send form data to save.php
            fetch('save.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Data saved successfully!");
                } else {
                    alert("Failed to save data: " + (data.error || "Unknown error"));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred while saving data.");
            });
        }

        // Function to fetch facilities based on subcounty selection
        function fetchFacilities() {
            const subcountyId = document.getElementById('subcounty').value;
            if (subcountyId) {
                $.ajax({
                    url: 'fetch_facilities.php',
                    type: 'POST',
                    data: { subcounty_id: subcountyId },
                    success: function(response) {
                        const facilityDropdown = document.getElementById('facility');
                        facilityDropdown.innerHTML = response;

                        // Automatically select the first facility if there's only one
                        if (facilityDropdown.options.length === 1) {
                            facilityDropdown.selectedIndex = 0;
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                    }
                });
            }
        }

        // Function to fill unit cost and unit of issue when a commodity is selected
        function fillCommodityDetails() {
            const commoditySelect = document.getElementById('commodity');
            const selectedOption = commoditySelect.options[commoditySelect.selectedIndex];
            const unitCost = selectedOption.getAttribute('data-unit-cost');
            const unitOfIssue = selectedOption.getAttribute('data-unit-of-issue');

            document.getElementById('unit-cost').value = unitCost;
            document.getElementById('unit-of-issue').value = unitOfIssue;
            calculateTotalCost(); // Recalculate total cost when commodity changes
        }

        // Function to calculate total cost
        function calculateTotalCost() {
            const unitCost = parseFloat(document.getElementById('unit-cost').value) || 0;
            const beginningBalance = parseFloat(document.getElementById('beginning-balance').value) || 0;
            const receivedFromSupplier = parseFloat(document.getElementById('received-from-supplier').value) || 0;
            const used = parseFloat(document.getElementById('used').value) || 0;
            const requested = parseFloat(document.getElementById('requested').value) || 0;
            const positiveAdjustment = parseFloat(document.getElementById('positive-adjustment').value) || 0;
            const negativeAdjustment = parseFloat(document.getElementById('negative-adjustment').value) || 0;
            const losses = parseFloat(document.getElementById('losses').value) || 0;

            const totalQuantity = beginningBalance + receivedFromSupplier - used + requested + positiveAdjustment - negativeAdjustment - losses;
            const totalCost = totalQuantity * unitCost;

            document.getElementById('total-cost').value = totalCost.toFixed(2);
        }

        // Function to add a new commodity
        function addCommodity() {
            const commodityName = prompt("Enter Commodity Name:");
            if (commodityName) {
                const unitCost = prompt("Enter Unit Cost:");
                const unitOfIssue = prompt("Enter Unit of Issue:");
                if (unitCost && unitOfIssue) {
                    // Send data to the server to save the new commodity
                    fetch('add_commodity.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            commodity_name: commodityName,
                            unit_cost: unitCost,
                            unit_of_issue: unitOfIssue
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Commodity added successfully!");
                            // Reload the page to reflect the new commodity
                            location.reload();
                        } else {
                            alert("Failed to add commodity.");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("An error occurred while adding the commodity.");
                    });
                }
            }
        }

        // Function to generate PDF
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Add title
            doc.setFontSize(18);
            doc.text("Commodity Management Report", 10, 10);

            // Add form data
            const form = document.getElementById('commodity-form');
            const data = [
                ["Department", form.department.options[form.department.selectedIndex].text],
                ["Subcounty", form.subcounty.options[form.subcounty.selectedIndex].text],
                ["Facility", form.facility.options[form.facility.selectedIndex].text],
                ["Commodity", form.commodity.options[form.commodity.selectedIndex].text],
                ["Unit Cost", form.unit_cost.value],
                ["Unit of Issue", form.unit_of_issue.value],
                ["Beginning Balance", form.beginning_balance.value],
                ["Received from Supplier", form.received_from_supplier.value],
                ["Used", form.used.value],
                ["Requested", form.requested.value],
                ["Positive Adjustment", form.positive_adjustment.value],
                ["Negative Adjustment", form.negative_adjustment.value],
                ["Losses", form.losses.value],
                ["Days Out of Stock", form.days_out_of_stock.value],
                ["Last Summary Date", form.last_summary_date.value],
                ["Revenue Last Month", form.revenue_last_month.value],
                ["Total Cost", form.total_cost.value]
            ];

            // Add data to PDF
            doc.setFontSize(12);
            let y = 20;
            data.forEach(row => {
                doc.text(`${row[0]}: ${row[1]}`, 10, y);
                y += 10;
            });

            // Save PDF
            doc.save("commodity_report.pdf");
        }

        // Function to generate Excel
        function generateExcel() {
            const form = document.getElementById('commodity-form');
            const labels = [
                "Department", "Subcounty", "Facility", "Commodity", "Unit Cost", "Unit of Issue",
                "Beginning Balance", "Received from Supplier", "Used", "Requested", "Positive Adjustment",
                "Negative Adjustment", "Losses", "Days Out of Stock", "Last Summary Date", "Revenue Last Month", "Total Cost"
            ];
            const values = [
                form.department.options[form.department.selectedIndex].text,
                form.subcounty.options[form.subcounty.selectedIndex].text,
                form.facility.options[form.facility.selectedIndex].text,
                form.commodity.options[form.commodity.selectedIndex].text,
                form.unit_cost.value,
                form.unit_of_issue.value,
                form.beginning_balance.value,
                form.received_from_supplier.value,
                form.used.value,
                form.requested.value,
                form.positive_adjustment.value,
                form.negative_adjustment.value,
                form.losses.value,
                form.days_out_of_stock.value,
                form.last_summary_date.value,
                form.revenue_last_month.value,
                form.total_cost.value
            ];

            // Create worksheet
            const wsData = [labels, values]; // Labels in row 1, values in row 2
            const ws = XLSX.utils.aoa_to_sheet(wsData);

            // Create workbook
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Report");

            // Save Excel file
            XLSX.writeFile(wb, "commodity_report.xlsx");
        }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
      .then((registration) => {
        console.log('Service Worker registered with scope:', registration.scope);
      })
      .catch((error) => {
        console.error('Service Worker registration failed:', error);
      });
  }
</script>
    </script>
</body>
</html>