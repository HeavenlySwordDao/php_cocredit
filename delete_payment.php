<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

// Check if payment_id is set and is a number
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $payment_id = $_GET['id'];

    // Prepare and execute the deletion query
    $stmt = $conn->prepare("DELETE FROM payments WHERE payment_id = ?");
    $stmt->bind_param("i", $payment_id);

    if ($stmt->execute()) {
        // Redirect back to the payments_today.php page with a success message
        header("Location: payments_today.php?message=Payment deleted successfully.");
        exit();
    } else {
        // Handle the error
        echo "Error deleting payment: " . $conn->error;
    }

    $stmt->close();
} else {
    // Handle the case where payment_id is not set
    echo "Invalid payment ID.";
}

$conn->close();
?>
