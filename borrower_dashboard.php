<?php
session_start();

// Include database connection
require 'db_connection.php';

// Check if the user is logged in and is a borrower
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'borrower') {
    header("Location: login.html");
    exit();
}



// Fetch user data from the database
$user_check_query = "SELECT shared_capital, savings_deposit FROM users WHERE username = ?";
$stmt = $conn->prepare($user_check_query);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Determine if the user has the required values
$has_shared_capital = !empty($user_data['shared_capital']) && $user_data['shared_capital'] > 0;
$has_savings_deposit = !empty($user_data['savings_deposit']) && $user_data['savings_deposit'] > 0;

// Check if only shared capital is available
$has_only_shared_capital = $has_shared_capital && !$has_savings_deposit;

// Debugging output
error_log("Shared Capital: " . $user_data['shared_capital']);
error_log("Savings Deposit: " . $user_data['savings_deposit']);
error_log("Has Shared Capital: " . json_encode($has_shared_capital));
error_log("Has Savings Deposit: " . json_encode($has_savings_deposit));
error_log("Has Only Shared Capital: " . json_encode($has_only_shared_capital));

// Fetch loan count based on the username
$loan_count_query = "SELECT COUNT(*) as loan_count FROM borrower_credentials WHERE username = ?";
$stmt = $conn->prepare($loan_count_query);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$loan_count_data = $result->fetch_assoc();
$loan_count = $loan_count_data['loan_count'];
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Store registration form data in session
    $_SESSION['registration'] = [
        'first_name' => $_POST['first_name'],
        'middle_name' => $_POST['middle_name'],
        'last_name' => $_POST['last_name'],
        'address' => $_POST['address'],
        'contact_no' => $_POST['contact_no'],
        'net_pay' => $_POST['net_pay'],
        'faculty_department' => $_POST['faculty_department'],
    ];

    // Redirect to loan types form
    header("Location: Loan_types.php");
    exit();


    if (!$has_shared_capital || !$has_savings_deposit) {
        $_SESSION['error'] = "You cannot proceed without Shared Capital and Savings Deposit greater than zero.";
        header("Location: borrower_dashboard.php");
        exit();
    }

    
}

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management Dashboard</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4; /* Light background color */
        }

        .content {
            margin-left: 220px; /* Space for sidebar */
            padding: 20px;
        }

        .dashboard-section {
            margin-top: 20px;
        }

        .dashboard-cards {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex: 1; /* Equal width for cards */
            margin-right: 20px; /* Space between cards */
            transition: transform 0.3s;
        }

        .card:last-child {
            margin-right: 0; /* Remove margin for last card */
        }

        .card:hover {
            transform: translateY(-5px); /* Lift effect on hover */
        }

        .card h3 {
            margin: 0;
            color: #333;
        }

        .card p {
            margin-top: 10px;
        }

        /* Background colors for the cards */
        .card:nth-child(1) {
            background-color: #e7f3fe; /* Light blue for Registration Form */
            border-left: 5px solid #007bff; /* Blue border */
        }

        .card:nth-child(2) {
            background-color: #fff3cd; /* Light yellow for Loan Details */
            border-left: 5px solid #ffc107; /* Yellow border */
        }

        /* Styling for the registration form when active */
        .registration-form {
            display: none; /* Hide by default */
            padding: 20px;
            background-color: #f9f9f9; /* Light gray background for form */
            border: 2px solid #ccc;
            border-radius: 5px;
            margin-top: 20px;
        }

        .registration-form.active {
            display: block; /* Show when active */
        }

        .registration-form h2 {
            text-align: center;
            margin-bottom: 1rem;
            padding: 10px;
            border-bottom: 2px solid #ccc;
            background-color: #e9e9e9;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            color: white;
            background-color: green; /* Green color for buttons */
            cursor: pointer;
            border-radius: 5px; /* Rounded button edges */
            transition: background-color 0.3s;
        }

        .btn.cancel {
            background-color: red; /* Red color for cancel button */
        }

        .btn:hover {
            background-color: darkgreen; /* Darker green on hover */
        }

        .btn.cancel:hover {
            background-color: darkred; /* Darker red on hover */
        }

        select, input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 4px; /* Rounded input corners */
            border: 1px solid #ccc; /* Light border for inputs */
        }
        /* Basic styling for hidden and visible states */
        .registration-form {
            display: none; /* Hide the form by default */
            padding: 20px;
            background-color: gray;
            border: 1px solid #ccc;
            margin-top: 20px;
        }
        .registration-form.active {
            display: block; /* Show the form when active */
        }

        /* Styling for the registration form container */
        .registration-form-container {
            border: 2px solid #ccc;
            padding: 20px;
            border-radius: 5px;
        }

        .registration-form h2 {
            text-align: center;
            margin-bottom: 1rem;
            padding: 10px;
            border-bottom: 2px solid #ccc;
            background-color: #e9e9e9;
        }

        /* Styling for labels to make them darker and more prominent */
        .registration-form label {
            color: #333; /* Darker color for labels */
            font-weight: bold; /* Make labels bold */
            display: block;
            margin-bottom: 5px;
        }

        /* Layout for the form fields */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .input-group {
            flex: 1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            color: white;
            background-color: green; /* Blue color for buttons */
            cursor: pointer;
        }

        .btn.cancel {
            background-color: red; /* Grey color for cancel button */
        }

        select, input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        .badge {
            display: inline-block;
            width: 24px; /* Width of the circle */
            height: 24px; /* Height of the circle */
            border-radius: 50%; /* Make it a circle */
            background-color: #007bff; /* Blue background */
            color: white; /* White text */
            text-align: center; /* Center text */
            line-height: 24px; /* Center text vertically */
            font-size: 12px; /* Font size */
            margin-left: 15px; /* Space between text and badge */
        }
       /* Responsive design for tablets and smaller screens */
@media only screen and (max-width: 768px) {
    .registration-form-container {
        max-width: 90%;
        padding: 15px;
    }

    .registration-form-container h2 {
        font-size: 22px;
        margin-bottom: 15px;
    }

    input[type="number"], textarea, select {
        padding: 8px;
        font-size: 14px;
    }

    .form-row {
        flex-direction: column; /* Stack form fields vertically */
        justify-content: flex-start;
        gap: 15px; /* Space between fields */
    }

    .form-row .input-group {
        justify-content: flex-start;
    }

    .button-container {
        flex-direction: column; /* Stack buttons vertically */
        gap: 10px; /* Space between buttons */
    }

    .btn {
        width: 100%; /* Full width for buttons */
        padding: 10px; /* Consistent padding */
    }

    /* Adjust sidebar for smaller screens */
    .sidebar {
        width: 100px; /* Smaller width for sidebar */
        padding: 10px;
    }

    .sidebar.collapsed {
        width: 0; /* Hide the sidebar when collapsed */
        padding: 0;
    }

    .content {
        margin-left: 80px; /* Adjust for smaller sidebar */
        width: calc(100% - 80px);
    }

    .sidebar.collapsed + .content {
        margin-left: 0; /* Remove margin when sidebar is collapsed */
        width: 100%;
    }
}

/* Responsive design for mobile devices */
@media only screen and (max-width: 480px) {
    .registration-form-container {
        max-width: 100%;
        padding: 10px; /* Reduced padding */
    }

    .registration-form-container h2 {
        font-size: 20px; /* Smaller heading */
    }

    .form-group label {
        font-size: 14px; /* Adjust label size */
    }

    input[type="number"], textarea, select {
        font-size: 14px; /* Consistent font size */
        padding: 8px; /* Consistent padding */
    }

    .button-container {
        flex-direction: column; /* Stack buttons */
    }

    .btn {
        width: 100%; /* Full width for buttons */
        padding: 10px 0; /* Vertical padding */
        font-size: 14px; /* Consistent font size */
    }
}

/* Button styles */
.btn.submit {
    background-color: green; /* Green for submit button */
    color: white; /* White text */
}

.btn.cancel {
    background-color: red; /* Red for cancel button */
    color: white; /* White text */
}
    </style>
</head>
<body>
<?php if (isset($_SESSION['error'])): ?>
        <div class="notification-box" style="color: red; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0;">
            <strong>Notification:</strong> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); // Clear the message after displaying ?>
        </div>
    <?php endif; ?>
    <header>
        <div class="form-image">
            <img src="logo.png" alt="Logo Image">
        </div>
        <nav>
            <ul>
                <li class="user-info">
                    <!-- Display the username to the left of the user icon -->
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
        <section id="homepage" class="dashboard-section">
            <h2>Home</h2>
           
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Registration Form</h3>
                    <p><a href="#" id="register-now">Register Now</a></p>
                    <div class="notification-box" id="registration-notification" 
                        style="display: none; color: red; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0;">
                        <strong>Notification:</strong> You need to have a Shared Capital to register.
                    </div>
                </div>
                <div class="card">
                <h3>Loan Details</h3>
                    <p>
                        <a href="loan_details.php" id="loan-details-form">Loan Details</a>
                        <span class="badge" id="loan-count"><?php echo $loan_count; ?></span> <!-- Badge for number of entries -->
                    </p>
                </div>
            </div>
        </section>



            <!-- Hidden Registration Form -->
            <div class="registration-form" id="registration-form">
                <div class="registration-form-container">
                    <h2>Registration Form</h2>
                    <form action="" method="POST">
                        <!-- Top Section -->
                        <div class="form-row">
                            <div class="input-group">
                                <label for="last-name">Last Name:</label>
                                <input type="text" id="last-name" name="last_name" required>
                            </div>
                            <div class="input-group">
                                <label for="first-name">First Name:</label>
                                <input type="text" id="first-name" name="first_name" required>
                            </div>
                            <div class="input-group">
                                <label for="middle-name">Middle Name:</label>
                                <input type="text" id="middle-name" name="middle_name">
                            </div>
                        </div>
                        <!-- Middle Section -->
                        <div class="form-row">
                            <div class="input-group">
                                <label for="address">Address:</label>
                                <input type="text" id="address" name="address" required>
                            </div>
                            <div class="input-group">
                                <label for="contact-no">Contact No:</label>
                                <input type="text" id="contact-no" name="contact_no" required>
                            </div>
                            <div class="input-group">
                                <label for="net-pay">Net Take Home Pay:(PaySlip)</label>
                                <input type="number" id="net-pay" name="net_pay" required>
                            </div>
                        </div>
                        <!-- Bottom Section -->
                        <div class="form-group">
                            <label for="faculty-department">Faculty Department:</label>
                            <select id="faculty-department" name="faculty_department" required>
                                <option value="">Select Department</option>
                                <option value="CCJE">CCJE</option>
                                <option value="CCSICT">CCSICT</option>
                                <option value="CDCAS">CDCAS</option>
                                <option value="CED">CED</option>
                                <option value="CFEM">CFEM</option>
                                <option value="IBM">IBM</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="button-container">
                            <button type="button" class="btn cancel" id="cancel-button">Cancel</button>
                            <button type="submit" class="btn submit">Next</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2024 Loan Management System</p>
    </footer>
    <script>
    document.getElementById('register-now').addEventListener('click', function (e) {
        e.preventDefault();

        var form = document.getElementById('registration-form');

        // Check if the user has the required values
        var hasRequiredValues = <?php echo json_encode($has_shared_capital || $has_only_shared_capital); ?>;

        if (!hasRequiredValues) {
            document.getElementById('registration-notification').style.display = 'block'; // Show notification
            return; 
        }

        document.getElementById('registration-notification').style.display = 'none';

        // Show the registration form and hide other sections
        form.classList.add('active');
        var loanDetailsCard = document.getElementById('loan-details-form').closest('.card'); 
        loanDetailsCard.style.display = 'none'; 
        var homeSection = document.getElementById('homepage'); 
        homeSection.style.display = 'none'; 
    });

    // Hide the registration form on cancel button click
    document.getElementById('cancel-button').addEventListener('click', function () {
        var form = document.getElementById('registration-form');
        form.classList.remove('active');
        document.getElementById('registration-notification').style.display = 'none'; // Hide notification
        var loanDetailsCard = document.getElementById('loan-details-form').closest('.card'); 
        loanDetailsCard.style.display = 'block'; 
        var homeSection = document.getElementById('homepage'); 
        homeSection.style.display = 'block'; 
    });
    </script>
    <script>
            // JavaScript code to toggle the sidebar
            const toggleSidebar = document.querySelector('.toggle-sidebar');
            const sidebar = document.querySelector('.sidebar');

            toggleSidebar.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        </script>
</body>
</html>