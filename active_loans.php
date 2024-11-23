<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

// Admin dashboard content
$username = $_SESSION['username']; // Get the username from the session
$role = $_SESSION['role']; // Get the position

// Query to get released loans
$sql = "SELECT * FROM borrower_credentials WHERE loan_status = 'released'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Released Loans</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .table-wrapper {
            border: 1px solid #ddd; /* Inner border */
            border-radius: 10px;
            overflow-y: auto; /* Enable vertical scrollbar */
            max-height: 400px; /* Set a max height for the scrollable area */
            margin-left: 30px;
            margin-right: 30px;
        }

        .released-loans-wrapper {
            border: 2px solid #ddd; /* Outer border for released loans */
            border-radius: 8px;
            padding: 1rem;
            background-color: #f9f9f9;
            margin-bottom: 3.5rem; /* Space above footer */
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse; /* Merge borders */
            font-size: 14px; /* Set the base font size */
        }

        table th, table td {
            border: 1px solid #ddd; /* Border for table cells */
            padding: 12px; /* Increase padding to reduce overlap */
            text-align: left;
            white-space: normal; /* Allow text wrapping */
            overflow-wrap: break-word; /* Break long text into multiple lines */
        }

        table th {
            background-color: #f2f2f2;
        }

        /* Zebra striping */
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        } 
        .released-loans-container {
            padding: 20px; /* Add some padding around the container */
            background-color: #fff; /* Set a background color for the container */
            border-radius: 8px; /* Rounded corners for the container */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            margin-left: 100px;
        }

        /* Responsive styles for the new wrapper */
        @media screen and (max-width: 768px) {
            .released-loans-container {
                padding: 10px; /* Reduced padding for smaller screens */
                margin-left: 5px;
            }
        }

        /* Mobile responsive */
        @media screen and (max-width: 768px) {
            .wrapper {
                padding: 10px;
                margin-left: 120px; /* Removed left margin for smaller screens */
            }

            .table-wrapper {
                margin-left: 0; /* Remove left margin for smaller screens */
                border-radius: 0; /* Remove border-radius for smaller screens */
                border-left: none; /* Remove left border for smaller screens */
                border-right: none; /* Remove right border for smaller screens */
                margin-top: 15px;
            }

            table {
                font-size: 12px; /* Reduce font size for smaller screens */
                width: 100%; /* Ensure the table takes up full width */
                font-size: 11px; /* Reduce font size further for smaller screens */
            }

            table, thead, tbody, th, td, tr {
                display: block; /* Stack table elements */
                margin-bottom: 50px;
                padding: 8px 12px; /* Reduce padding for smaller screens */
            }

            table thead {
                display: none; /* Hide the header on small screens */
                margin-bottom: 5px;
                margin-top: 5px;
            }

            table tr {
              
                margin-bottom: 20px; /* Space between rows */
                border: 1px solid #ddd; /* Border for each row */
                border-radius: 5px; /* Border-radius for each row */
                padding: 10px; /* Added padding to create more space between rows */
            }

            table td {
                margin-bottom: 15px;

                margin-top: 5px;
                text-align: left; /* Align text to the left */
                padding: 10px 15px; /* Increased padding to prevent text from touching borders */
                position: relative;
                white-space: normal; /* Allow text wrapping */
                overflow-wrap: break-word; /* Break long text into multiple lines */
                word-wrap: break-word; /* Ensure long words break properly */
                word-break: break-word; /* Prevent long words from overflowing */
            }

            table td::before {
                content: attr(data-label);
                position: absolute;
                top: 5px;
                left: 10px; /* Adjust left padding for labels */
                font-weight: bold;
                text-align: left;
                display: block;
                margin-bottom: 5px; /* Space between label and content */
            }

            /* Ensure that labels inside table cells are clearly spaced */
            table td {
                padding-left: 10px; /* Add some space to the left of the text */
                padding-right: 10px; /* Add space to the right */
            }

            /* Increase spacing between table rows */
            table tr {
                margin-bottom: 30px; /* Increased margin for better spacing */
            }

            .close-btn {
                position: absolute;
                top: 20px; /* Adjust as needed */
                right: 20px; /* Adjust as needed */
                background-color: transparent;
                border: none;
                font-size: 24px; /* Adjust the size as needed */
                cursor: pointer;
                color: #333; /* Change color as needed */
                transition: color 0.3s ease;
                margin-right: 90%;
            }

            .close-btn:hover {
                color: #ff0000; /* Change color on hover */
            }
        } 
        @media screen and (max-width: 768px) {
    table, thead, tbody, th, td, tr {
        display: block; /* Stack table elements */
        margin-bottom: 30px; /* Space between rows */
    }

    table tr {
        border: 1px solid #ddd; /* Border for each row */
        border-radius: 5px; /* Border-radius for each row */
        padding: 15px; /* Added padding to create more space between rows */
    }

    table td {
        margin-bottom: 15px; /* Remove bottom margin */
        margin-top: 0; /* Remove top margin */
        text-align: left; /* Align text to the left */
        padding: 10px 15px; /* Increased padding to prevent text from touching borders */
        position: relative;
        white-space: nowrap; /* Prevent text wrapping */
        overflow-wrap: normal; /* Normal wrapping for text */
        word-wrap: normal; /* Normal wrapping for long words */
        word-break: normal; /* Normal breaking for long words */
        display: inline-block; /* Change to inline-block for horizontal layout */
        width: auto; /* Set width to auto */
    }

    table td::before {
        content: attr(data-label);
        position: relative;
        font-weight: bold;
        text-align: left;
        display: inline; /* Change to inline for horizontal layout */
        margin-right: 5px; /* Space between label and content */
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
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="Loan_list.php"><i class="fas fa-money-bill-wave"></i> Loans</a></li>
            <li><a href="Payments_list.php"><i class="fas fa-calendar-alt"></i> Payments</a></li>
            <li><a href="Borrower_list.php"><i class="fas fa-users"></i> Borrowers</a></li>
            <li><a href="Users.php"><i class="fas fa-user-friends"></i> Users</a></li>
        </ul>
    </div> 
    <div class="wrapper">
        <div class="released-loans-container"> <!-- New wrapper added -->
            <div class="payment-wrapper" style="position: relative;">
                <a href="admin_dashboard.php" class="close-btn" title="Close">&#10006;</a>
                <h2>Released Loans</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Borrowers Information</th>
                                <th>Loan Information</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td data-label='Borrowers Information'>
                                        <div><strong>ID:</strong> " . htmlspecialchars($row['id']) . "</div>
                                        <div><strong>Username:</strong> " . htmlspecialchars($row['username']) . "</div>
                                        <div><strong>First Name:</strong> " . htmlspecialchars($row['first_name']) . "</div>
                                        <div><strong>Middle Name:</strong> " . htmlspecialchars($row['middle_name']) . "</div>
                                        <div><strong>Last Name:</strong> " . htmlspecialchars($row['last_name']) . "</div>
                                        <div><strong>Address:</strong> " . htmlspecialchars($row['address']) . "</div>
                                        <div><strong>Contact No:</strong> " . htmlspecialchars($row['contact_no']) . "</div>
                                        <div><strong>Faculty/Department:</strong> " . htmlspecialchars($row['faculty_department']) . "</div>
                                    </td>
                                    <td data-label='Loan Information'>
                                        <div><strong>Loan Type:</strong> " . htmlspecialchars($row['loan_type']) . "</div>
                                        <div><strong>Loan Amount:</strong> " . htmlspecialchars($row['loan_amount']) . "</div>
                                        <div><strong>Loan Purpose:</strong> " . htmlspecialchars($row['loan_purpose']) . "</div>
                                        <div><strong>Loan Term:</strong> " . htmlspecialchars($row['loan_term']) . "</div>
                                        <div><strong>Net Pay:</strong> " . htmlspecialchars($row['net_pay']) . "</div>
                                    </td>
                                    <td data-label='Status'>
                                        <div><strong>Loan Status:</strong> Released</div>
                                        <div><strong>Created At:</strong> ";
                                        
                                        // Format the created_at date here
                                        $created_at = new DateTime($row['created_at']);
                                        echo $created_at->format('F d Y h:i A'); // Format as Month 00 2024 12:00 AM/PM

                                echo "</div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No released loans found.</td></tr>";
                        }
                        ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<footer>
    <p>&copy; 2024 Loan Management System</p>
</footer>
</body>
</html>

<?php
$conn->close();
?>
