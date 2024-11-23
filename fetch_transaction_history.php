<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    exit('Access denied');
}

// Include database connection
require 'db_connection.php';

// Get borrower ID from the POST request
$borrowerId = isset($_POST['borrower_id']) ? intval($_POST['borrower_id']) : 0;

// Fetch borrower details
$borrowerSql = "
    SELECT 
        first_name,
        middle_name,
        last_name,
        faculty_department,
        loan_amount
    FROM 
        borrower_credentials
    WHERE 
        id = ?
";

$borrowerStmt = $conn->prepare($borrowerSql);
$borrowerStmt->bind_param('i', $borrowerId);
$borrowerStmt->execute();
$borrowerResult = $borrowerStmt->get_result();
$borrower = $borrowerResult->fetch_assoc();

// Fetch transaction history for the borrower
$sql = "
    SELECT 
        p.payment_id,
        p.amount AS payment_amount,
        p.deduction AS balance,
        p.payment_date
    FROM 
        payments AS p
    WHERE 
        p.borrower_id = ?
    ORDER BY p.payment_date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $borrowerId);
$stmt->execute();
$result = $stmt->get_result();

// Initialize variables for total owed and principal balance
$totalOwed = $borrower['loan_amount']; // Start with the loan amount as total owed
$principalBalance = $borrower['loan_amount']; // The initial loan amount is the principal balance

// Output the pop-up window content
 echo "<div id='transactionPopup'>
    <div style='text-align: center;'>
        <img src='logo.png' alt='Logo' style='float: left; width: 100px; height: auto;'>
        <h3 style='margin: 0;'>CoCredit</h3>
        <p>ISABELA STATE UNIVERSITY CABAGAN FACULTY CREDIT COOPERATIVE (ISUCFCC)</p>
        <p>Garita, Cabagan, Isabela</p>
    </div>
    <div style='text-align: left; margin: 20px;'>
        <p><strong>Full Name:</strong> " . htmlspecialchars($borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name']) . "</p>
        <p><strong>Department:</strong> " . htmlspecialchars($borrower['faculty_department']) . "</p>
        <p><strong>Loan Amount:</strong> " . htmlspecialchars($borrower['loan_amount']) . "</p>
      </div> ";

echo "<table id='loanDetailsTable' style='width: 100%; border-collapse: collapse;'>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Payment Amount</th>
                <th>Balance</th>
                <th>Total Owed</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>";

// Add the initial loan amount as the first transaction
echo "<tr>
        <td>N/A</td> <!-- No payment ID for the initial loan -->
        <td>0</td> <!-- No payment made initially -->
        <td>" . htmlspecialchars($principalBalance) . "</td>
        <td>" . htmlspecialchars($totalOwed) . "</td>
        <td>N/A</td> <!-- No payment date for the initial loan -->
      </tr>";

// If there are transactions, output them
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Update the total owed and principal balance
        $totalOwed -= $row['payment_amount']; // Reduce total owed by payment amount
        $principalBalance -= $row['payment_amount']; // Reduce principal balance by payment amount
    
        echo "<tr>
                <td>" . htmlspecialchars($row['payment_id']) . "</td>
                <td>" . htmlspecialchars($row['payment_amount']) . "</td>
                <td>" . htmlspecialchars($row['balance']) . "</td>
                <td>" . htmlspecialchars($totalOwed) . "</td> <!-- Display updated total owed -->
                <td>";
        
        // Format the payment_date here
        $payment_date = new DateTime($row['payment_date']);
        echo $payment_date->format('F d Y h:i A'); // Format as Month 00 2024 12:00 AM/PM
    
        echo "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5'>No transaction history found for this borrower.</td></tr>";
}

echo "</tbody></table>";


/// Download buttons
echo "<div style='text-align: center; margin-top: 20px;'>
<button onclick=\"window.location.href='generate-pdf.php?borrower_id={$borrowerId}'\">Generate PDF</button>
<button onclick=\"window.location.href='generate-excel.php?borrower_id={$borrowerId}'\">Generate Excel</button>
</div>";



$stmt->close();
$borrowerStmt->close();
$conn->close();


?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>


