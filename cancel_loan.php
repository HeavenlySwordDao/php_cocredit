<?php
session_start();

// Check if the user is logged in and has the right role
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'borrower') {
    http_response_code(403); // Forbidden
    exit('Unauthorized access');
}

// Include database connection
require 'db_connection.php';

// Get the loan ID from the POST request
if (isset($_POST['id'])) {
    $loan_id = intval($_POST['id']);

    // Check the current status of the loan
    $sql = "SELECT loan_status FROM borrower_credentials WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $loan = $result->fetch_assoc();

        // Only allow cancellation if the status is 'not released' or NULL
        if ($loan['loan_status'] === 'not released' || is_null($loan['loan_status'])) {
            // Update the loan status to 'canceled'
            $update_sql = "UPDATE borrower_credentials SET loan_status = 'canceled' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $loan_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {
                echo "Loan canceled successfully.";
            } else {
                echo "Failed to cancel the loan. Please try again.";
            }
        } else {
            echo "This loan cannot be canceled.";
        }
    } else {
        echo "Loan not found.";
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();
?>