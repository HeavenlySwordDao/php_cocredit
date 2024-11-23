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

// Count Active Loans
$activeLoansResult = $conn->query("SELECT COUNT(*) AS count FROM borrower_credentials WHERE loan_status = 'released'");
$activeLoansCount = $activeLoansResult->fetch_assoc()['count'];

// Count Payments Today
$paymentsTodayResult = $conn->query("SELECT COUNT(*) AS count FROM payments WHERE DATE(payment_date) = CURDATE()");
$paymentsTodayCount = $paymentsTodayResult->fetch_assoc()['count'];

// Count Collateral Users
$collateralUsersResult = $conn->query("SELECT COUNT(*) AS count FROM users WHERE  role ='borrower'"); // Adjust this query as needed
$collateralUsersCount = $collateralUsersResult->fetch_assoc()['count'];

// Count loans with null status
$nullStatusLoansResult = $conn->query("SELECT COUNT(*) AS count FROM borrower_credentials WHERE loan_status IS NULL");
$nullStatusLoansCount = $nullStatusLoansResult->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <link rel="stylesheet" href= "borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
       body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4; /* Light background color */
    display: flex;
    flex-direction: row;
    height: 100vh;
    overflow-x: hidden; /* Prevent horizontal scrollbar */
}

.content {
    margin-left: 220px; /* Space for sidebar */
    padding: 20px;
    width: calc(100% - 220px); /* Adjust width to sidebar space */
}

.notification-bell {
            position: relative;
            margin-left: 20px;
        }

        .notification-bell a {
            color: #333;
            text-decoration: none;
            position: relative;
        }

        .notification-bell a.highlight {
            color: yellow; /* Yellow highlight */
            animation: glow 1s infinite alternate; /* Animation */
        }

        @keyframes glow {
            from {
                text-shadow: 0 0 5px yellow, 0 0 10px yellow, 0 0 15px yellow;
            }
            to {
                text-shadow: 0 0 10px yellow, 0 0 20px yellow, 0 0 30px yellow;
            }
        }

        .scrolling-message {
            background-color: yellow;
            color: black;
            white-space: nowrap;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: absolute;
            top: 5px;
            left: 0;
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            animation: scroll 10s linear infinite;
        }

        @keyframes scroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        .dashboard-section {
            margin-top: 20px;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            grid-gap: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            margin: 0;
            color: #333;
        }

        .card p {
            margin-top: 10px;
        }
        
/* Background colors for specific cards */
.card:nth-child(1) {
    background-color: #e7f3fe;
    border-left: 5px solid #007bff;
}

.card:nth-child(2) {
    background-color: #fff3cd;
    border-left: 5px solid #ffc107;
}

.card:nth-child(3) {
    background-color: #d4edda;
    border-left: 5px solid #28a745;
}

        .circle-icon {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #28a745;
            color: white;
            text-align: center;
            line-height: 30px;
            margin-left: 5px;
            font-size: 14px;
        }

/* Responsive Styles */
@media (max-width: 768px) {
    .content {
        margin-left: 150px; /* Maintain spacing from sidebar */
        padding: 15px;
        width: calc(100% - 100px);
    }

    .dashboard-cards {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .card {
        padding: 15px;
    }

    .form-row {
        flex-direction: column; /* Stack form rows vertically */
    }

    .button-container {
        flex-direction: column;
    }

    .btn {
        width: 100%; /* Full width buttons */
        margin-bottom: 10px;
    }
}

@media (max-width: 576px) {
    .content {
        margin-left: 90px; /* Keep space from sidebar */
        width: calc(100% - 70px);
        padding: 10px;
    }

    .dashboard-cards {
        grid-template-columns: 1fr; /* Single column for smaller screens */
    }

    .card {
        padding: 10px;
    }

    .form-row {
        gap: 10px;
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
                <li><a href="Loan_list.php"><i class="fas fa-money-bill-wave"></i> Loans</a></li>
                <li><a href="Payments_list.php"><i class="fas fa-calendar-alt"></i> Payments</a></li>
                <li><a href="Borrower_list.php"><i class="fas fa-users"></i> Borrowers</a></li>
                <li><a href="Users.php"><i class="fas fa-user-friends"></i> Users</a></li>
            </ul>
        </div>

        <div class="content">
        <li class="notification-bell">
                    <a href="Loan_list.php" class="<?php echo $nullStatusLoansCount > 0 ? 'highlight' : ''; ?>">
                        <i class="fas fa-bell"></i>
                    </a>
                    <?php if ($nullStatusLoansCount > 0): ?>
                        <div class="scrolling-message">
                            New Application Alert: There are <?php echo $nullStatusLoansCount; ?> new loan applications!
                        </div>
                    <?php endif; ?>
                </li>

            <!-- Home Section -->
            <section id="homepage" class="dashboard-section">
                <h2>Administrator</h2>
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Active Loans <span class="circle-icon"><?php echo $activeLoansCount; ?></span></h3>
                        <p><a href="active_loans.php">View Active Loans</a></p>
                    </div>

                    <div class="card">
                        <h3>Payments Today <span class="circle-icon"><?php echo $paymentsTodayCount; ?></span></h3>
                        <p><a href="payments_today.php">View Payments</a></p>
                    </div>

                    <div class="card">
                        <h3>Collateral User <span class="circle-icon"><?php echo $collateralUsersCount; ?></span></h3>
                        <p><a href="collateral_user.php">Saving Deposit & Share Capital</a></p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Loan Management System</p>
    </footer>

</body>
</html>
