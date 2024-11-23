<?php
// Include database connection
require 'db_connection.php';

// Validate incoming POST data
$borrower_id = $_POST['borrower_id'] ?? null;
$payment_amount = $_POST['payment_amount'] ?? null;
$interest_rate = $_POST['interest'] ?? null;
$loan_amount = $_POST['principle'] ?? null;

if ($borrower_id === null || $payment_amount === null || $interest_rate === null || $loan_amount === null) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Calculate the new balance (deduction)
$monthly_interest = ($interest_rate / 100) / 12;
$balance = $loan_amount - $payment_amount + ($loan_amount * $monthly_interest);

// Insert the new payment into the payments table
$query = "INSERT INTO payments (borrower_id, amount, deduction, payment_date) VALUES ('$borrower_id', '$payment_amount', '$balance', NOW())";

if ($conn->query($query) === TRUE) {
    // Check if the borrower is fully paid
    $checkPaymentQuery = "SELECT loan_amount, (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE borrower_id = ?) AS total_paid
                           FROM borrower_credentials WHERE id = ?";
    $stmt = $conn->prepare($checkPaymentQuery);
    $stmt->bind_param("ii", $borrower_id, $borrower_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $loan_amount = $row['loan_amount'];
        $total_paid = $row['total_paid'];

        // Determine if fully paid
        $fully_paid_status = ($total_paid >= $loan_amount) ? 'Paid' : 'Unpaid';

        // Update the fully_paid status in borrower_credentials
        $updateStatusQuery = "UPDATE borrower_credentials SET fully_paid = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateStatusQuery);
        $updateStmt->bind_param("si", $fully_paid_status, $borrower_id);
        $updateStmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Payment added successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}

$conn->close();
?>