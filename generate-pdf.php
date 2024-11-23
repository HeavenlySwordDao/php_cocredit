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

// HTML structure with embedded CSS
$html = "
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { text-align: center; padding: 10px; }
        .header img { width: 100px; height: auto; }
        .title { font-size: 24px; font-weight: bold; margin: 10px 0; }
        .sub-title { font-size: 12px; color: #555; }
        .details { margin: 20px; }
        .details p { font-size: 14px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: center; font-size: 12px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .footer { text-align: center; font-size: 12px; margin-top: 20px; color: #777; }
    </style>

    <div class='header'>
        <img src='" . $_SERVER['DOCUMENT_ROOT'] . "/New Log In/images/logo.png' alt='Logo'>
        <div class='title'>CoCredit</div>
        <div class='sub-title'>ISABELA STATE UNIVERSITY CABAGAN FACULTY CREDIT COOPERATIVE (ISUCFCC)</div>
        <div class='sub-title'>Garita, Cabagan, Isabela</div>
    </div>
    <div class='details'>
        <p><strong>Full Name:</strong> " . htmlspecialchars($borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name']) . "</p>
        <p><strong>Department:</strong> " . htmlspecialchars($borrower['faculty_department']) . "</p>
        <p><strong>Loan Amount:</strong> " . htmlspecialchars($borrower['loan_amount']) . "</p>
    </div>
    <table>
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

// Initial values for the loan
$totalOwed = $borrower['loan_amount'];
$principalBalance = $borrower['loan_amount'];

$html .= "<tr>
            <td>N/A</td>
            <td>0</td>
            <td>" . htmlspecialchars($principalBalance) . "</td>
            <td>" . htmlspecialchars($totalOwed) . "</td>
            <td>N/A</td>
         </tr>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $totalOwed -= $row['payment_amount'];
            $principalBalance -= $row['payment_amount'];
    
            // Format the payment_date
            $paymentDate = new DateTime($row['payment_date']);
            $formattedDate = $paymentDate->format('F d Y h:i A'); // Format as Month 00 2024 12:00 AM/PM
    
            $html .= "<tr>
                        <td>" . htmlspecialchars($row['payment_id']) . "</td>
                        <td>" . htmlspecialchars($row['payment_amount']) . "</td>
                        <td>" . htmlspecialchars($row['balance']) . "</td>
                        <td>" . htmlspecialchars($totalOwed) . "</td>
                        <td>" . htmlspecialchars($formattedDate) . "</td>
                      </tr>";
        }
    } else {
        $html .= "<tr><td colspan='5'>No transaction history found for this borrower.</td></tr>";
    }

$html .= "</tbody></table>";

$html .= "
    <div class='footer'>
        <p>&copy; 2024 Loan Management System</p>
    </div>
";

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

$dompdf->stream("ISUCFCC.pdf", ["Attachment" => true]);
?>
