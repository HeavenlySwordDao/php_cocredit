<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    exit('Access denied');
}

// Include database connection
require 'db_connection.php';

// Get the format and borrower ID from the GET request
$format = isset($_GET['format']) ? $_GET['format'] : '';
$borrowerId = isset($_GET['borrower_id']) ? intval($_GET['borrower_id']) : 0;

if ($format && $borrowerId) {
    // Fetch borrower details
    $borrowerSql = "SELECT first_name, middle_name, last_name, faculty_department, loan_amount FROM borrower_credentials WHERE id = ?";
    $borrowerStmt = $conn->prepare($borrowerSql);
    $borrowerStmt->bind_param('i', $borrowerId);
    $borrowerStmt->execute();
    $borrowerResult = $borrowerStmt->get_result();
    $borrower = $borrowerResult->fetch_assoc();

    // Fetch payment history
    $sql = "SELECT payment_id, amount AS payment_amount, deduction AS balance, payment_date FROM payments WHERE borrower_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $borrowerId);
    $stmt->execute();
    $paymentResult = $stmt->get_result();

    // Prepare the data to be exported
    $data = [];
    $data[] = ['Payment ID', 'Payment Amount', 'Balance', 'Payment Date'];
    while ($row = $paymentResult->fetch_assoc()) {
        $data[] = [
            $row['payment_id'],
            $row['payment_amount'],
            $row['balance'],
            $row['payment_date']
        ];
    }

    // Generate the file based on the selected format
    switch ($format) {
        case 'excel':
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="borrower_payments.xls"');
            echo implode("\t", array_keys($data[0])) . "\n"; // Header
            foreach ($data as $row) {
                echo implode("\t", $row) . "\n"; // Data rows
            }
            break;

        case 'pdf':
            require('path/to/tcpdf/tcpdf.php'); // Ensure you include the TCPDF library
            $pdf = new TCPDF();
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $html = "<h2>Borrower Payment History</h2>";
            foreach ($data as $row) {
                $html .= implode(' | ', $row) . "<br/>";
            }
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('borrower_payments.pdf', 'D');
            break;

        case 'image':
            // For image, use libraries like GD or Imagick to create PNG/JPEG
            break;

        default:
            echo "Invalid format specified.";
            break;
    }
} else {
    // Display borrower details and transaction history as a table for download options
    echo "<div style='text-align: center;'>
            <img src='logo.png' alt='Logo' style='float: left; width: 100px; height: auto;'>
            <h3 style='margin: 0;'>CoCredit</h3>
            <p>ISABELA STATE UNIVERSITY CABAGAN FACULTY CREDIT COOPERATIVE (ISUCFCC)</p>
            <p>Garita, Cabagan, Isabela</p>
          </div>
          <div style='text-align: left; margin: 20px;'>
            <p><strong>Full Name:</strong> " . htmlspecialchars($borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name']) . "</p>
            <p><strong>Department:</strong> " . htmlspecialchars($borrower['faculty_department']) . "</p>
            <p><strong>Loan Amount:</strong> " . htmlspecialchars($borrower['loan_amount']) . "</p>
          </div>";

    echo "<table id='loanDetailsTable' style='width: 100%; border-collapse: collapse;'>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Payment Amount</th>
                    <th>Balance</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>";

    while ($row = $paymentResult->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['payment_id']) . "</td>
                <td>" . htmlspecialchars($row['payment_amount']) . "</td>
                <td>" . htmlspecialchars($row['balance']) . "</td>
                <td>" . htmlspecialchars($row['payment_date']) . "</td>
              </tr>";
    }

    echo "</tbody></table>";

    // Download buttons
    echo "<div style='text-align: center; margin-top: 20px;'>
            <a href='download.php?format=excel&borrower_id={$borrowerId}'>Download as Excel</a>
            <a href='download.php?format=pdf&borrower_id={$borrowerId}'>Download as PDF</a>
          </div>";

    echo "<div style='text-align: center; margin-top: 20px;'>
            <p>ISABELA STATE UNIVERSITY CABAGAN FACULTY CREDIT COOPERATIVE (ISUCFCC)</p>
            <p>Garita, Cabagan, Isabela</p>
          </div>";
}

// Close the database connections
$stmt->close();
$borrowerStmt->close();
$conn->close();
?>
