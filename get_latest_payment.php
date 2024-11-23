<?php
// Include database connection
require 'db_connection.php';

// Ensure borrower_id is an integer to prevent SQL injection
$borrower_id = intval($_GET['borrower_id']);

// Query to get the latest payment deduction for the borrower
$query = "SELECT deduction FROM payments WHERE borrower_id = ? ORDER BY payment_date DESC, payment_id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $borrower_id); // Bind the borrower_id parameter
$stmt->execute();
$result = $stmt->get_result();

$data = $result->fetch_assoc();
if ($data) {
    // Return the latest deduction (balance)
    echo json_encode($data);
} else {
    // No previous payment found
    echo json_encode(['deduction' => 0]); // Return 0 if no payments found
}

$stmt->close();
$conn->close();
?>
