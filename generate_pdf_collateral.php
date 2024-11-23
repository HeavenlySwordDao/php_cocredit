<?php
session_start();

if (!isset($_SESSION['username'])) {
    exit('Access denied');
}

require 'vendor/autoload.php'; // Ensure Composer autoload is included

use Dompdf\Dompdf;
use Dompdf\Options;

// Include database connection
require 'db_connection.php';

$borrowerId = isset($_GET['borrower_id']) ? intval($_GET['borrower_id']) : 0;

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

// HTML structure with embedded CSS
$html = "
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { text-align: center; padding: 10px; }
        .header img { width: 100px; height: auto; }
        .title { font-size: 24px; font-weight: bold; margin: 10px 0; }
        .details { margin: 20px; }
        .details p { font-size: 14px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: center; font-size: 12px; }
        th { background-color: #f2f2f2; font-weight: bold; }
    </style>

    <div class='header'>
        <img src='" . $_SERVER['DOCUMENT_ROOT'] . "/New Log In/images/logo.png' alt='Logo'>
        <div class='title'>CoCredit</div>
        <div class='details'>
            <p><strong>Full Name:</strong> " . htmlspecialchars($borrower['full_name']) . "</p>
            <p><strong>Total Savings Deposit:</strong> " . htmlspecialchars($borrower['savings_deposit']) . "</p>
            <p><strong>Total Shared Capital:</strong> " . htmlspecialchars($borrower['shared_capital']) . "</p>
        </div>
    </div>
    <table>
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
        $formattedTime = date('F d, Y h:i A', strtotime($timestamp));

        $html .= "<tr>
                    <td>$type</td>
                    <td>$amount</td>
                    <td>$formattedTime</td>
                  </tr>";
    }
} else {
    $html .= "<tr><td colspan='3'>No transaction history found.</td></tr>";
}

$html .= "</tbody></table>";

// Close database connections
$borrowerStmt->close();
$stmt->close();
$conn->close();

// Dompdf setup and generation
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Collateral_History.pdf", ["Attachment" => true]);
?>