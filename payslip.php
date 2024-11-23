<?php
// Start session
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

// Fetch the payslip based on the loan ID
if (isset($_GET['id'])) {
    $loan_id = $_GET['id'];

    // Get the payslip path from the database
    $sql = "SELECT payslip FROM borrower_credentials WHERE id='$loan_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $payslip_path = $row['payslip'];

        // Check if the file exists
        if (file_exists($payslip_path)) {
            // Get the file extension
            $file_extension = pathinfo($payslip_path, PATHINFO_EXTENSION);

            // Set the appropriate content type based on the file extension
            switch (strtolower($file_extension)) {
                case 'pdf':
                    $content_type = 'application/pdf';
                    break;
                case 'jpg':
                case 'jpeg':
                    $content_type = 'image/jpeg';
                    break;
                case 'png':
                    $content_type = 'image/png';
                    break;
                case 'doc':
                    $content_type = 'application/msword';
                    break;
                case 'docx':
                    $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;
                default:
                    echo "Unsupported file type.";
                    exit;
            }

            // Display the file in an iframe
            echo '<iframe src="' . htmlspecialchars($payslip_path) . '" width="100%" height="600px" style="border: none;"></iframe>';
            
            // Add a "Back to Loan" button
            echo '<br><br><button onclick="closeWindow()" style="padding: 10px 20px; background-color:green; color: white; border: none; border-radius: 5px; cursor: pointer;">Back to Loan List</button>';
            echo '
            <script>
                function closeWindow() {
                    window.close(); // Attempt to close the window
                    // Fallback: Redirect if the window cannot be closed
                    setTimeout(function() {
                        window.location.href = "loan_list.php";
                    }, 1000);
                }
            </script>';
            exit;
        } else {
            echo "Payslip file not found: " . htmlspecialchars($payslip_path);
        }
    } else {
        echo "No record found for the given loan ID.";
    }
} else {
    echo "Invalid request.";
}
$conn->close();
?>