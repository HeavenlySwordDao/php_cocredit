<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

$query = "SELECT id, username, first_name, middle_name, last_name, faculty_department, loan_type, loan_amount, loan_term, collateral, created_at, loan_status FROM borrower_credentials WHERE loan_status = 'released'";
$result = mysqli_query($conn, $query);

$borrowers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $borrowers[] = $row; // Collect each borrower as an associative array
}

echo json_encode($borrowers); // Return the data as JSON
?>
