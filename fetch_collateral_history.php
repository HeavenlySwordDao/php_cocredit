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
        full_name,
        savings_deposit,
        shared_capital
    FROM 
        users
    WHERE 
        id = ?
";

$borrowerStmt = $conn->prepare($borrowerSql);
$borrowerStmt->bind_param('i', $borrowerId);
$borrowerStmt->execute();
$borrowerResult = $borrowerStmt->get_result();
$borrower = $borrowerResult->fetch_assoc();

// Check if borrower exists
if (!$borrower) {
    exit('Borrower not found');
}

// Fetch transaction history for the borrower
$sql = "
    SELECT 
        ct.amount, 
        ct.type, 
        ct.timestamp 
    FROM 
        collateral_transaction ct
    WHERE 
        ct.borrower_id = ? 
    ORDER BY ct.timestamp DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $borrowerId);
$stmt->execute();
$result = $stmt->get_result();

// Output the pop-up window content
echo "<div id='transactionPopup'>
    <div style='text-align: center;'>
        <img src='logo.png' alt='Logo' style='float: left; width: 100px; height: auto;'>
        <h3 style='margin: 0;'>CoCredit</h3>
        <p>ISABELA STATE UNIVERSITY CABAGAN FACULTY CREDIT COOPERATIVE (ISUCFCC)</p>
        <p>Garita, Cabagan, Isabela</p>
    </div>
    <div style='text-align: left; margin: 20px;'>
        <p><strong>Full Name:</strong> " . htmlspecialchars($borrower['full_name']) . "</p>
        <p><strong>Total Savings Deposit:</strong> " . htmlspecialchars($borrower['savings_deposit']) . "</p>
        <p><strong>Total Shared Capital:</strong> " . htmlspecialchars($borrower['shared_capital']) . "</p>
    </div>";

echo "<table id='transactionHistoryTable' style='width: 100%; border-collapse: collapse;'>
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $type = htmlspecialchars($row['type']);
        $amount = htmlspecialchars($row['amount']);
        $timestamp = htmlspecialchars($row['timestamp']);
        $formattedTime = date('F d, Y h:i A', strtotime($timestamp)); // Format the date

        echo "<tr>
                <td>$type</td>
                <td>$amount</td>
                <td>$formattedTime</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='3'>No transaction history found.</td></tr>";
}

echo "</tbody></table>";

/// Download button
echo "<div style='text-align: center; margin-top: 20px;'>
<button onclick=\"window.location.href='generate_pdf_collateral.php?borrower_id={$borrowerId}'\">Generate PDF</button>
</div>";

$stmt->close();
$borrowerStmt->close();
$conn->close();
?>