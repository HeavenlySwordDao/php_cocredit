<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Admin dashboard content
$username = $_SESSION['username']; // Get the username from the session
$role = $_SESSION['role']; // Get the position

// Include database connection
require 'db_connection.php';

// Set up the base SQL query
$sql = "
    SELECT 
        p.payment_id,
        p.borrower_id,
        p.amount AS payment_amount,
        p.deduction AS balance,
        p.payment_date,
        bc.first_name,
        bc.middle_name,
        bc.last_name,
        bc.faculty_department,
        bc.loan_amount
    FROM 
        payments AS p
    JOIN 
        borrower_credentials AS bc ON p.borrower_id = bc.id
    WHERE 1=1
";

// Add search criteria if the search form is submitted
if (isset($_POST['search'])) {
    $search = $conn->real_escape_string($_POST['search']);
    
    $sql .= " AND (
        CONCAT(bc.first_name, ' ', bc.middle_name, ' ', bc.last_name) LIKE '%$search%' OR
        bc.faculty_department LIKE '%$search%' OR
        bc.loan_amount LIKE '%$search%' OR
        p.payment_date LIKE '%$search%'
    )";
}

// Add order by clause
$sql .= " ORDER BY p.payment_date DESC, p.borrower_id DESC";

// Execute the query
$result = $conn->query($sql);

// Check the query result
if ($result === false) {
    echo "Error in query: " . $conn->error;
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Today</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
  * General Layout for Larger Screens */
.container {
    margin: 20px auto;
    margin-right: 300px;
    max-width: 400px;
}

.table-container {
    border: 2px solid #ddd; /* Add border around the table */
    border-radius: 5px; /* Optional: adds rounded corners */
    overflow: hidden; /* Ensures child elements do not overflow */
}

.payment-wrapper {
    padding: 50px;
    border: 2px solid #ddd;  /* Outer border around the table */
    border-radius: 10px;  /* Optional: adds rounded corners */
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);  /* Adds a subtle shadow for depth */
    background-color: #f9f9f9;  /* Light background for the wrapper */
    justify-content: center;
    margin-left: 200px;
    margin-top: 10px;
}
h2 {
    text-align: center;
    margin-bottom: 20px;
}

.table-container {
    border: 2px solid #ccc;  /* Inner border around the table */
    border-radius: 5px;  /* Optional: rounded corners inside */
    overflow: hidden;  /* Ensures child elements do not overflow */
}

table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto; /* Allow columns to adjust their widths */
}

table th, table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

table th {
    background-color: #f2f2f2;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #f1f1f1;
}

/* Responsive Table Styling for Small Screens */
@media (max-width: 768px) {
    .container {
        margin: 10px auto;
        max-width: 100%;
        padding: 0 10px;
        display:initial;
    }

    .payment-wrapper {
        padding: 20px;
        max-width: 100%;
        position: inherit;
        margin-left: 115px;
    }

    .table-container {
        width: 100%;
        overflow-x: auto; /* Enable horizontal scrolling on smaller screens */
        -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
    }

    table {
        width: 100%;
        min-width: 100px; /* Set a minimum width for the table to allow scrolling */
        font-size: 12px; /* Adjust font size for better fit */
    }

    table th, table td {
        padding: 8px;
        white-space: nowrap; /* Prevent columns from wrapping on smaller screens */
    }
}

/* Search bar styling */
.search-bar {
    position: relative;
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    max-width: 100%;
}

.search-bar input {
    width: 100%;
    padding-right: 35px;
    padding-left: 10px;
    height: 36px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: #888;
    pointer-events: none;
}

/* Close button */
.close-button {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: transparent;
    border: none;
    font-size: 16px;
    color: #333;
    cursor: pointer;
}

.close-button:hover {
    color: red;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
}

/* Action icons */
.action-icons {
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-icons a {
    margin: 0 10px;
    text-decoration: none;
}

.divider {
    margin: 0 10px;
    color: #ccc;
}
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById("historyModal");
        const closeModal = document.getElementById("closeModal");

        // Show transaction history for each borrower
        document.querySelectorAll('.show-history').forEach(function (element) {
            element.addEventListener('click', function (e) {
                e.preventDefault();
                const borrowerId = this.getAttribute('data-borrower-id');
                fetchTransactionHistory(borrowerId);
            });
        });

        // Close modal on close button click
        closeModal.onclick = function () {
            modal.style.display = "none";
        };

        // Close modal on clicking outside of it
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };

        // Function to fetch transaction history and display it in a modal
        function fetchTransactionHistory(borrowerId) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'fetch_transaction_history.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Populate modal with fetched data
                    document.getElementById('historyTableContainer').innerHTML = xhr.responseText;
                    modal.style.display = "block";
                }
            };
            xhr.send('borrower_id=' + encodeURIComponent(borrowerId));
        }

        // Live search functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            liveSearch();
        });

        function liveSearch() {
            const searchInput = document.getElementById('searchInput').value;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'search_payments.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    document.getElementById('resultsTable').innerHTML = xhr.responseText;
                }
            };
            xhr.send('search=' + encodeURIComponent(searchInput));
        }
    });
</script>

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
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </li>
        </ul>
    </nav>
</header>

<main>
    <div class="sidebar">
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="Loan_list.php"><i class="fas fa-money-bill-wave"></i> Loans</a></li>
            <li><a href="Payments_list.php"><i class="fas fa-calendar-alt"></i> Payments</a></li>
            <li><a href="Borrower_list.php"><i class="fas fa-users"></i> Borrowers</a></li>
            <li><a href="Users.php"><i class="fas fa-user-friends"></i> Users</a></li>
        </ul>
    </div>
    <div class="container">
        <div class="payment-wrapper" style="position: relative;">
            <a href="admin_dashboard.php" class="close-button" title="Close">&#10006;</a>
            <h2>Payments Today</h2>
            <form method="POST">
                <div class="search-bar">
                    <input type="text" name="search" id="searchInput" placeholder="Search by Name, Department, Loan Amount, or Date" aria-label="Search">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </form>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Borrower ID</th>
                            <th>Payer Name</th>
                            <th>Department</th>
                            <th>Loan Amount</th>
                            <th>Payment Amount</th>
                            <th>Balance</th>
                            <th>Payment Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $fullName = htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['middle_name']) . ' ' . htmlspecialchars($row['last_name']);
                            echo "<tr>
                                <td>" . htmlspecialchars($row['borrower_id']) . "</td>
                                <td>" . $fullName . "</td>
                                <td>" . htmlspecialchars($row['faculty_department']) . "</td>
                                <td>" . htmlspecialchars($row['loan_amount']) . "</td>
                                <td>" . htmlspecialchars($row['payment_amount']) . "</td>
                                <td>" . htmlspecialchars($row['balance']) . "</td>
                                <td>";
                                
                                // Format the payment_date here
                                $payment_date = new DateTime($row['payment_date']);
                                echo $payment_date->format('F d Y h:i A'); // Format as Month 00 2024 12:00 AM/PM

                                echo "</td>
                                <td>
                                    <div class='action-icons'>
                                        <a href='delete_payment.php?id=" . htmlspecialchars($row['payment_id']) . "' onclick='return confirm(\"Are you sure you want to delete this payment?\");' title='Delete'>
                                            <i class='fas fa-trash' style='color: red;'></i>
                                        </a>
                                        <span class='divider'>|</span>
                                        <a href='#' class='show-history' data-borrower-id='" . htmlspecialchars($row['borrower_id']) . "' title='Show History'>
                                            <i class='fas fa-history' style='color: blue;'></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>No payments found.</td></tr>";
                    }
                    ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<div id="historyModal" class="modal">
    <div class="modal-content">
        <span id="closeModal" class="close">&times;</span>
        <h2>Transaction History</h2>
        <div id="historyTableContainer">
            <!-- Transaction history will be injected here -->
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2024 Loan Management System</p>
</footer>

</body>
</html>

<?php
$conn->close();
?>