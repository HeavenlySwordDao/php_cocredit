<?php
session_start();

if (!isset($_SESSION['username'])) {
    exit('Access denied');
}

require 'vendor/autoload.php'; // Ensure Composer autoload is included

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

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

// Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add the logo image
$logo = new Drawing();
$logo->setName('Logo');
$logo->setDescription('Logo');
$logo->setPath('logo.png'); // Path to your logo file
$logo->setHeight(80); // Set height; adjust as necessary
$logo->setCoordinates('A1'); // Position the logo
$logo->setWorksheet($sheet);

// Set the header
$sheet->setCellValue('A3', 'ISABELA STATE UNIVERSITY CABAGAN FACULTY CREDIT COOPERATIVE (ISUCFCC)');
$sheet->setCellValue('A4', 'Garita, Cabagan, Isabela');
$sheet->mergeCells('A3:E3');
$sheet->mergeCells('A4:E4');

// Center align the header and make it bold
$headerStyle = [
    'font' => [
        'bold' => true,
        'size' => 12,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
];

// Apply header style
$sheet->getStyle('A3:E4')->applyFromArray($headerStyle);

// Borrower Details
$sheet->setCellValue('A6', 'Full Name');
$sheet->setCellValue('B6', $borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name']);
$sheet->setCellValue('A7', 'Department');
$sheet->setCellValue('B7', $borrower['faculty_department']);
$sheet->setCellValue('A8', 'Loan Amount');
$sheet->setCellValue('B8', $borrower['loan_amount']);

// Table headers for payments
$sheet->setCellValue('A10', 'Payment ID');
$sheet->setCellValue('B10', 'Payment Amount');
$sheet->setCellValue('C10', 'Balance');
$sheet->setCellValue('D10', 'Total Owed');
$sheet->setCellValue('E10', 'Payment Date');

// Center align and bold table headers
$sheet->getStyle('A10:E10')->applyFromArray($headerStyle);

// Add payment data
$totalOwed = $borrower['loan_amount'];
$rowIndex = 11;

while ($payment = $result->fetch_assoc()) {
    $totalOwed -= $payment['payment_amount'];

    $sheet->setCellValue("A$rowIndex", $payment['payment_id']);
    $sheet->setCellValue("B$rowIndex", $payment['payment_amount']);
    $sheet->setCellValue("C$rowIndex", $payment['balance']);
    $sheet->setCellValue("D$rowIndex", $totalOwed);

    // Format the payment_date
    $paymentDate = new DateTime($payment['payment_date']);
    $formattedDate = $paymentDate->format('F d Y h:i A'); // Format as Month 00 2024 12:00 AM/PM
    $sheet->setCellValue("E$rowIndex", $formattedDate);

    // Center align data rows
    $sheet->getStyle("A$rowIndex:E$rowIndex")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $rowIndex++;
}

$borrowerStmt->close();
$stmt->close();
$conn->close();

// Set column widths for readability
foreach (range('A', 'E') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Send the generated file to the browser for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="ISUCFCC.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
