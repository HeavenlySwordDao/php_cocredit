<?php
session_start();
// Include database connection
require 'db_connection.php';

// Check if the loan ID is provided
if (isset($_GET['id'])) {
    $loan_id = $_GET['id'];

    // Fetch the loan status from the database
    $sql = 'SELECT loan_status FROM borrower_credentials WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $loan = $result->fetch_assoc();
        $loan_status = $loan['loan_status'];

        // Check if the loan status is not "released" or "canceled"
        if ($loan_status !== 'released') {
            // Proceed to delete the loan entry from the database
            $sql = 'DELETE FROM borrower_credentials WHERE id = ?';
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $loan_id);
            
            if ($stmt->execute()) {
                // Redirect back to loan details page after deletion
                header("Location: loan_details.php");
                exit;
            } else {
                echo "Error deleting loan: " . htmlspecialchars($conn->error);
            }
        } else {
            // Set the error message in session and redirect
            $_SESSION['error_message'] = "Loan cannot be deleted because it is either released.";
            header("Location: loan_details.php");
            exit;
        }
    } else {
        echo "Loan not found.";
    }
} else {
    die("No loan ID provided.");
}

$conn->close();
?>