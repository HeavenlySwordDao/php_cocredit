<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'borrower') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

// Fetch error message from session (if any)
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['error_message']); // Clear the message after displaying

// Define the SQL query to fetch loan details
$sql = 'SELECT id, username, 
        UPPER(first_name) AS first_name, 
        UPPER(middle_name) AS middle_name, 
        UPPER(last_name) AS last_name, 
        UPPER(address) AS address, 
        contact_no, 
        net_pay, 
        faculty_department, 
        UPPER(loan_type) AS loan_type, 
        loan_amount, 
        UPPER(loan_purpose) AS loan_purpose, 
        loan_term, 
        UPPER(loan_status) AS loan_status,
        UPPER(fully_paid) AS fully_paid, 
        created_at,
        (SELECT IFNULL(deduction, 0) FROM payments WHERE borrower_id = id ORDER BY payment_date DESC LIMIT 1) AS deduction,
        (SELECT COUNT(*) FROM payments WHERE borrower_id = id) AS payment_count
        FROM borrower_credentials 
        WHERE username = ? 
        ORDER BY sort_order ASC';

// Prepare and execute the SQL statement
$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();

// Store all loan details for the user in an array
$loan_details = [];
while ($row = $result->fetch_assoc()) {
    $loan_details[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Details</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

.loan-details-wrapper {
    padding: 20px;
    max-width: 1200px; 
    margin: auto; 
    border: 2px solid #ddd; 
    border-radius: 8px; 
    background-color: #f9f9f9; 
    margin-bottom: 30px;
    box-sizing: border-box;  /* Ensure border and padding are included in the width */
}

.loan-details-inner {
    padding: 20px; 
    border: 1px solid #ccc; 
    border-radius: 5px; 
    background-color: white; 
    box-sizing: border-box;  /* Ensure border and padding are included in the width */
}

.loan-details-header h2 {
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

.loan-details-table {
    width: 100%; 
    border-collapse: collapse;
    margin: 20px 0;
    box-sizing: border-box;  /* Ensure table padding and borders are included in the width */
}

.loan-details-table th,
.loan-details-table td {
    border: 1px solid #ddd;
    padding: 15px;
    text-align: left;
    vertical-align: top;
    box-sizing: border-box;  /* Prevent overflow */
}

.loan-details-table th {
    background-color: #4CAF50;
    color: white;
}

.loan-details-table tr:nth-child(even) {
    background-color: #f2f2f2;
}

/* Button styles */
.button-container {
    display: flex;
    flex-direction: column; /* Stack buttons vertically */
    align-items: center;    /* Center buttons */
    gap: 10px;              /* Space between buttons */
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
    width: 80px;
    text-align: center;
}

.btn-edit {
    background-color: green;
}

.btn-delete {
    background-color: red;
    color: #ff0000;
}

.btn-cancel {
    background-color: blue;
}

.btn:hover {
    opacity: 0.8;
}

/* Close button styles */
.close-btn {
    position: absolute;
    top: 5px;
    right: 20px;
    background-color: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #333;
    transition: color 0.3s ease;
}

.close-btn:hover {
    color: #ff0000;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .loan-details-wrapper {
        padding: 15px;
        margin: 10px;
    }

    .loan-details-inner {
        padding: 15px;
    }

    .loan-details-table {
        font-size: 12px;
    }

    .loan-details-table th,
    .loan-details-table td {
        padding: 10px;
    }
}

@media screen and (max-width: 576px) {
    .loan-details-wrapper {
        padding: 10px;
        margin: 5px;
    }

    .loan-details-inner {
        padding: 10px;
    }

    .loan-details-table th,
    .loan-details-table td {
        padding: 8px;
        font-size: 11px;
    }
}
.icon-container {
    display: flex;
    gap: 15px; /* Space between icons */
    align-items: center; /* Vertically align the icons */
}

.icon-container a, .icon-container span {
    font-size: 20px; /* Default size for desktop */
    color: #333;
    text-decoration: none;
    cursor: pointer;
}

.icon-container a:hover {
    color: #007bff;
}

.icon-container span {
    color: #ccc;
    cursor: not-allowed;
}

/* Responsive Styles */

/* For tablets and smaller screens */
@media (max-width: 768px) {
    .icon-container {
        flex-direction: column; /* Stack icons vertically on small screens */
        align-items: flex-start; /* Align icons to the left */
        gap: 10px; /* Smaller gap between stacked icons */
    }

    .icon-container a, .icon-container span {
        font-size: 18px; /* Smaller icon size for tablets */
    }
}

/* For mobile devices */
@media (max-width: 480px) {
    .icon-container a, .icon-container span {
        font-size: 16px; /* Even smaller icon size on very small screens */
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
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
            <li><a href="borrower_dashboard.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="Loan_types.php"><i class="fas fa-money-bill-wave"></i> Loan Types</a></li>
            <li><a href="Loan_plans.php"><i class="fas fa-calendar-alt"></i> Loan Plans</a></li>
            <li><a href="user_profile.php"><i class="fas fa-user"></i> Profile</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="loan-details-wrapper">
            <div class="loan-details-inner">
            <div class="container">
                <h2>Loan Details</h2>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <div class="payment-wrapper" style="position: relative;">
                 <a href="borrower_dashboard.php" class="close-button" title="Close">&#10006;</a>
                <?php if (!empty($loan_details)): ?>
                    <table class="loan-details-table">
                        <thead>
                            <tr>
                                <th>Borrowers Information</th>
                                <th>Loan Information</th>
                                <th>Status & Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php foreach ($loan_details as $detail): ?>
                    <tr>
                        <td>
                            <strong>First Name:</strong> <?php echo htmlspecialchars(ucfirst($detail['first_name'])); ?><br>
                            <strong>Middle Name:</strong> <?php echo htmlspecialchars(ucfirst($detail['middle_name'])); ?><br>
                            <strong>Last Name:</strong> <?php echo htmlspecialchars(ucfirst($detail['last_name'])); ?><br>
                            <strong>Address:</strong> <?php echo htmlspecialchars(ucfirst($detail['address'])); ?><br>
                            <strong>Contact Number:</strong> <?php echo htmlspecialchars(ucfirst($detail['contact_no'])); ?><br>
                            <strong>Faculty Department:</strong> <?php echo htmlspecialchars(ucfirst($detail['faculty_department'])); ?>
                        </td>
                        <td>
                            <strong>Net Pay:</strong> <?php echo htmlspecialchars(ucfirst($detail['net_pay'])); ?><br>
                            <strong>Loan Type:</strong> <?php echo htmlspecialchars(ucfirst($detail['loan_type'])); ?><br>
                            <strong>Loan Amount:</strong> <?php echo htmlspecialchars(ucfirst($detail['loan_amount'])); ?><br>
                            <strong>Loan Purpose:</strong> <?php echo htmlspecialchars(ucfirst($detail['loan_purpose'])); ?><br>
                            <strong>Loan Term (Months):</strong> <?php echo htmlspecialchars(ucfirst($detail['loan_term'])); ?><br>
                        </td>
                        <td>
                        <strong>Status:</strong>
                        <span class="<?php echo $detail['loan_status'] == 'released' ? 'status-released' : 'status-not-released'; ?>">
                            <?php echo htmlspecialchars(ucfirst($detail['loan_status'])); ?>
                        </span><br>
                        <strong>Balance:</strong> <?php echo htmlspecialchars($detail['deduction']); ?><br>
                        <strong>Paid Status:</strong> <?php echo htmlspecialchars($detail['fully_paid']); ?><br>
                        <strong>Date:</strong> 
                        <?php 
                            $created_at = new DateTime($detail['created_at']); 
                            echo $created_at->format('F d Y h:i A'); // Format as Month 00 2024 12:00 AM/PM
                        ?>
                        </td>
                        <td>
                        <div class="button-container">
                        <div class="icon-container">
                                <?php if ($detail['payment_count'] == 0): ?>
                                    <a href="javascript:void(0);" onclick="editLoan(<?php echo $detail['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php else: ?>
                                    <span title="Cannot edit loan with payments">
                                        <i class="fas fa-edit"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if ($detail['loan_status'] !== 'released' && $detail['loan_status'] !== NULL): ?>
                                    <a href="javascript:void(0);" onclick="cancelLoan(<?php echo $detail['id']; ?>)" title="Cancel">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($detail['loan_status'] !== 'released' && $detail['loan_status'] !== 'canceled'): ?>
                                    <a href="javascript:void(0);" onclick="deleteLoan(<?php echo $detail['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>


                        </td>
                                            </tr>
                    <?php endforeach; ?>
                    </tbody>
                    </table>
                <?php else: ?>
                    <p>No loan details found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<footer>
    <p>&copy; 2024 Loan Management System</p>
</footer>

<script>
    function editLoan(id) {
        window.location.href = `edit_loan.php?id=${id}`;
    }

    
    function cancelLoan(id) {
        if (confirm('Are you sure you want to cancel this loan?')) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "cancel_loan.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (xhr.status === 200) {
                    window.location.reload();
                } else {
                    alert('Error canceling loan. Please try again.');
                }
            };
            xhr.send("id=" + id);
        }
    }

    function deleteLoan(id) {
        if (confirm('Are you sure you want to delete this loan?')) {
            window.location.href = `delete_loan.php?id=${id}`;
        }
    }
</script>
</body>
</html> 