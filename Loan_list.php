<?php
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Adjust the path as needed

// Start session
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Assign role from session to avoid undefined variable error
$role = $_SESSION['role'];

// Include database connection
require 'db_connection.php';

// Get the count of new applicants
$sql = "SELECT COUNT(*) as new_applicants 
        FROM borrower_credentials 
        WHERE loan_status = 'not released'";
$result = $conn->query($sql);
$new_applicants_row = $result->fetch_assoc();
$new_applicants_count = $new_applicants_row['new_applicants'];

// Count new applications with null status
$sql_null_status = "SELECT COUNT(*) as null_status_count 
                    FROM borrower_credentials 
                    WHERE loan_status IS NULL"; // Adjust the condition as needed
$result_null_status = $conn->query($sql_null_status);
$null_status_row = $result_null_status->fetch_assoc();
$null_applicants_count = $null_status_row['null_status_count'];

// Updated SQL query to prioritize new entries and sort by creation time
$sql = "SELECT bc.*, u.shared_capital, u.savings_deposit, u.email 
        FROM borrower_credentials AS bc
        JOIN users AS u ON bc.username = u.username
        ORDER BY 
            CASE 
                WHEN bc.loan_status IS NULL THEN 1 
                WHEN bc.loan_status = 'released' THEN 2 
                ELSE 3 
            END, 
            bc.created_at DESC,  -- Sort by creation date, newest first
            bc.sort_order, 
            bc.id ASC";
$result = $conn->query($sql);

// Handle loan status update if the form is submitted via AJAX
if (isset($_POST['loan_id']) && isset($_POST['action'])) {
    $loan_id = $_POST['loan_id'];
    $action = $_POST['action'];

    // Check if the borrower has any payments before disapproving
    if ($action == 'disapprove') {
        $payment_check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE borrower_id = (SELECT id FROM borrower_credentials WHERE id = ?)");
        $payment_check_stmt->bind_param("i", $loan_id);
        $payment_check_stmt->execute();
        $payment_check_result = $payment_check_stmt->get_result();
        $payment_row = $payment_check_result->fetch_assoc();

        if ($payment_row['count'] > 0) {
            echo "Cannot disapprove this loan because there are existing payments.";
            exit();
        }
    }

    // Determine the new loan status based on the action
    $new_status = $action == 'approve' ? 'released' : 'not released';
    $sort_order = $action == 'approve' ? 0 : 1;

    // Use prepared statement to update loan status
    $stmt = $conn->prepare("UPDATE borrower_credentials SET loan_status=?, sort_order=? WHERE id=?");
    $stmt->bind_param("sii", $new_status, $sort_order, $loan_id);

    if ($stmt->execute()) {
        // Fetch the borrower's email
        $email_stmt = $conn->prepare("SELECT email FROM users WHERE username = (SELECT username FROM borrower_credentials WHERE id = ?)");
        $email_stmt->bind_param("i", $loan_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $email_row = $email_result->fetch_assoc();

        if ($email_row) {
            $borrower_email = $email_row['email'];

            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->SMTPAuth = true;
                $mail->Host = "smtp.gmail.com";
                $mail->Username = "isucoop543@gmail.com"; // Your email address
                $mail->Password = "tmeyugsnuvnnmykb"; // Your app password
                $mail->Port = 587;
                $mail->setFrom('no-reply@yourdomain.com', 'CoCredit ISUCFCC'); // Change to your domain
                $mail->addAddress($borrower_email); // Add the recipient

                // Email content
                $mail->isHTML(true);
                $mail->Subject = "Loan Status Update";
                $mail->Body    = "Your loan status has been " . ($action == 'approve' ? "approved" : "disapproved") . ". Thank you!";

                // Send the email
                $mail->send();
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        }

        echo "Status updated successfully.";
    } else {
        echo "Error updating status: " . $stmt->error;
    }

    $stmt->close();
    $email_stmt->close();
    $payment_check_stmt->close();
    $conn->close();
    exit(); // Exit after processing AJAX request
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
     .content {
         margin-left: 220px;
         padding: 20px;
        }

        .loan-list-wrapper {
                border: 2px solid #ddd; /* Border for the loan list wrapper */
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 2rem;
                
            }

            .loan-list-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                font-weight: bold;
                font-size: 22px; /* Increased size for header text */
            }

            .loan-list-inner-border {
                border: 1px solid #ccc;
                padding: 15px;
                border-radius: 8px;
                overflow-x: auto; /* Allow horizontal scrolling on smaller screens */
                overflow-y: auto;
                background-color: #ffff;
                margin-bottom: 2px;
            }

            .loan-list-inner-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
            }

            .show-entries {
                display: flex;
                align-items: center;
                margin-top: 10px;
            }

            .show-entries select {
                margin-left: 1px;
            }
            /* Show vertical scrollbar only on smaller screens */
            @media screen and (max-width: 768px) {
                .loan-list-inner-border {
                    overflow-x: auto; /* Shows horizontal scrollbar on smaller screens */
                    margin-left: 200px;
                }
            }

            .loan-list-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: auto;
            }

            .loan-list-table th,
            .loan-list-table td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
                word-wrap: break-word;
            }

            .loan-list-table th {
                background-color: #f4f4f4;
                text-align: center;
                font-weight: bold;
            }

            .loan-list-table-actions a {
                text-decoration: none;
                padding: 5px;
                color: #0ab52f;
            }

            .loan-list-table-actions a:hover {
                text-decoration: underline;
            }

            /* Search bar styling */
            .search-bar {
                position: relative;
                display: flex; /* Ensure the bar adjusts responsively */
                max-width: 100%;
                margin-right: 10px; /* Adjust margin for responsiveness */
                align-items: center;
                margin-bottom: 5px;
            }

            .search-bar input {
                width: 100%; /* Ensure the input takes available space */
                padding-right: 35px; /* Space for the icon */
                padding-left: 10px; /* Padding for better alignment */
                height: 36px; /* Ensure a good height for the input */
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 14px;
            }

            .search-icon {
                position: absolute;
                right: 10px; /* Position the icon inside the input */
                top: 50%;
                transform: translateY(-50%); /* Vertically center the icon */
                font-size: 18px; /* Size of the icon */
                color: #888; /* Icon color */
                pointer-events: none; /* Prevent icon interaction */
            }

            /* Media queries for responsiveness */
            @media screen and (max-width: 768px) {
                .search-bar {
                    margin-right: 0; /* Adjust margin for smaller screens */
                }

                .loan-list-header h2 {
                    font-size: 20px; /* Adjust header size for smaller screens */
                }
                .content {
                  
                    margin-left: 100px;
                    max-width: 900%;
                }
            }

            .loan-list-header h2 {
                font-size: 25px; /* Set to the larger size by default */
                font-weight: bold;
            }

            .show-entries select {
                font-size: 12px;
            }

            @media screen and (max-width: 576px) {
                .loan-list-table th,
                .loan-list-table td {
                    font-size: 10px;
                    padding: 3px;
                }

                .loan-list-header h2 {
                    font-size: 16px; /* Adjust for very small screens */
                }
            }

            /* Style for Approve and Not Approve buttons */
            .approve-btn {
                color: #007bff;
                font-weight: bold;
                
            }

            .approve-btn:hover {
                text-decoration: underline;
                
            }

            .not-approve-btn {
                color: #ff9900;
                font-weight: bold;
            }

            .not-approve-btn:hover {
                text-decoration: underline;
            }

            .loan-list-table-actions {
                text-align: center; /* Center align the buttons */
                padding: 10px;
            }


            .loan-list-table-actions .action-bottom {
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .loan-list-table-actions .action-divider {
                margin: 0 5px; /* Adds space between buttons */
            }

            .loan-list-table-actions .action-divider-line {
                border: 0;
                border-top: 1px solid #ddd;
                margin: 10px 0;
            }
            .loan-status-released {
                color: green; /* Green for released status */
            }

            .loan-status-not-released {
                color: red; /* Red for not released status */
            }
            .new-applicants-badge {
                background-color: #ff0000;
                color: #fff;
                border-radius: 50%;
                padding: 2px 7px;
                font-size: 12px;
                margin-left: 50px;
            } 
            /* Media queries for responsiveness */
@media screen and (max-width: 768px) {
    .loan-list-header {
        flex-direction: column; /* Stack header items vertically */
        align-items: flex-start; /* Align items to the start */
    }

    .show-entries {
        flex-direction: row; /* Display entries row-wise */
        justify-content: flex-start; /* Align to the start */
        flex-wrap: wrap; /* Allow wrapping */
    }

    .show-entries select {
        margin-left: 10px; /* Add margin for spacing */
    }

    .search-bar {
        flex-direction: row; /* Align search bar elements horizontally */
        width: 100%; /* Ensure the search bar fills the width */
    }

    .search-bar input {
        flex: 1; /* Allow input to grow */
        margin-right: 10px; /* Spacing between input and icon */
    }

    .loan-list-inner-border {
        display: flex; /* Use flexbox for the inner border */
        flex-direction: column; /* Stack items vertically */
    }

    .loan-list-table {
        display: flex; /* Use flexbox for the table */
        flex-direction: column; /* Stack rows vertically */
    }

    .loan-list-table tr {
        display: flex; /* Use flexbox for table rows */
        justify-content: space-between; /* Space out columns */
        flex-wrap: wrap; /* Allow wrapping of columns */
    }

    .loan-list-table th,
    .loan-list-table td {
        flex: 1; /* Allow cells to grow and fill space */
        min-width: 100px; /* Set a min width for cells */
    }

    .loan-list-table-actions {
        flex-direction: column; /* Stack action buttons vertically */
        align-items: center; /* Center align the buttons */
    }

    .loan-list-table-actions .action-bottom {
        flex-direction: row; /* Align action buttons horizontally */
    }

    .new-applicants-badge {
        margin-left: 0px; /* Adjust margin for badge */
    }
}

@media screen and (max-width: 576px) {
    .loan-list-table th,
    .loan-list-table td {
        font-size: 9px; /* Further reduce font size for very small screens */
        padding: 2px; /* Reduce padding for better fit */
        
    }
}
</style>
</head>
<body>
    <header>
        <div class="form-image">
            <img src="logo.png" alt="Logo Image">
        </div>
        <nav>
            <ul>
                <li class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($role); ?></span>
                    <a href="#" class="logout-icon">
                        <i class="fas fa-user"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main>
    <div class="sidebar">
    <ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="Loan_list.php"><i class="fas fa-money-bill-wave"></i> Loans 
            <span class="new-applicants-badge" style="background-color: green;"><?php echo $null_applicants_count; ?></span>
        </a></li>
        <li><a href="Payments_list.php"><i class="fas fa-calendar-alt"></i> Payments</a></li>
        <li><a href="Borrower_list.php"><i class="fas fa-users"></i> Borrowers</a></li>
        <li><a href="Users.php"><i class="fas fa-user-friends"></i> Users</a></li>
    </ul>
</div>

        <div class="content">
            <div class="loan-list-wrapper">
                <h2>Loan List</h2>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search..." aria-label="Search">
                    <i class="fas fa-search search-icon"></i>
                </div>

                <div class="table-responsive">
                    <table class="loan-list-table">
                        <thead>
                            <tr>
                                <th>Borrower Information</th>
                                <th>Loan Information</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusColor = ($row['loan_status'] == 'released') ? 'green' : 'red';
            echo "<tr data-loan-id='{$row['id']}'>";
            $dateTime = new DateTime($row['created_at']);
            $formattedDate = $dateTime->format('F j, Y'); // e.g., October 31, 2024
            $formattedTime = $dateTime->format('g:i A'); // e.g., 3:45 PM
            echo "<td>
                    <strong>Borrower ID:</strong> {$row['id']}<br>
                    <strong>First Name:</strong> {$row['first_name']}<br>
                    <strong>Middle Name:</strong> {$row['middle_name']}<br>
                    <strong>Last Name:</strong> {$row['last_name']}<br>
                    <strong>User Name:</strong> {$row['username']}<br>
                    <strong>Address:</strong> {$row['address']}<br>
                    <strong>Contact Number:</strong> {$row['contact_no']}<br>
                    <strong>Faculty Department:</strong> {$row['faculty_department']}
                  </td>";
                  echo "<td>
                    <strong>Loan Type:</strong> {$row['loan_type']}<br>
                    <strong>Loan Amount:</strong> {$row['loan_amount']}<br>
                    <strong>Loan Term (Months):</strong> {$row['loan_term']}<br>
                    <strong>Loan Purpose:</strong> {$row['loan_purpose']}<br>
                    <strong>Net Pay:</strong> {$row['net_pay']}<br>
                    <strong>Pay slip:</strong> <a href='payslip.php?id={$row['id']}' target='_blank'>View Payslip</a><br>
                    <strong>Collateral Offered:</strong><br>
                    <strong>Saving Deposit: </strong> {$row['savings_deposit']}<br>
                     <strong>Shared Capital:</strong> {$row['shared_capital']}<br>
                    <strong>Date:</strong> {$formattedDate} at {$formattedTime}
                </td>";
                        echo "<td>
                    <strong>Status:</strong> <span style='color: {$statusColor};'>{$row['loan_status']}</span><br>
                  </td>
                  <td class='loan-list-table-actions'>
                    <div class='action-bottom'>
                        <a href='#' class='approve-btn' onclick='updateLoanStatus({$row['id']}, \"approve\")'>Approve</a>
                        <span class='action-divider'>|</span>
                        <a href='#' class='not-approve-btn' onclick='updateLoanStatus({$row['id']}, \"disapprove\")'>Disapprove</a>
                    </div>
                  </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No records found</td></tr>";
    }
    ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>


    <script>
function updateLoanStatus(loanId, action) {
    // Confirmation for approve/disapprove actions
    const confirmationMessage = action === "approve" 
        ? "Are you sure you want to approve this loan?" 
        : "Are you sure you want to disapprove this loan?";

    // Show confirmation dialog
    if (!confirm(confirmationMessage)) {
        alert("Action canceled.");
        return; // Exit the function if the user cancels
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'Loan_list.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            const response = this.responseText;

            if (response.includes("Cannot disapprove")) {
                alert(response); // Show the error message
            } else {
                const row = document.querySelector(`tr[data-loan-id="${loanId}"]`);
                const statusCell = row.querySelector('td:nth-child(3)');

                if (action === 'approve') {
                    statusCell.textContent = 'Released';
                    statusCell.style.color = 'green';
                    const tableBody = document.querySelector('.loan-list-table tbody');
                    // Move the approved row to the top
                    tableBody.insertBefore(row, tableBody.firstChild);
                    alert('Loan approved successfully!');
                } else if (action === 'disapprove') {
                    statusCell.textContent = 'Not Released';
                    statusCell.style.color = 'red';
                    const tableBody = document.querySelector('.loan-list-table tbody');
                    // Move the disapproved row to the end
                    tableBody.appendChild(row);
                    alert('Loan disapproved successfully!');
                }
            }
        } else {
            alert('Error updating status: ' + this.responseText);
        }
    };

    // Send the AJAX request with loan ID and action
    xhr.send(`loan_id=${loanId}&action=${action}`);
}

// Search functionality for filtering loans
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const table = document.querySelector('.loan-list-table');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let rowVisible = false;

        for (let j = 0; j < cells.length; j++) {
            if (cells[j] && cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                rowVisible = true;
                break;
            }
        }
        rows[i].style.display = rowVisible ? '' : 'none';
    }
});
</script>

    <footer>
        <p>&copy; 2024 Loan Management System</p>
    </footer>
</body>
</html>