<?php
session_start();

// Include database connection
require 'db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'borrower') {
    header("Location: login.html");
    exit();
}

// Fetch user data to get shared capital and savings deposit
$username = $_SESSION['username'];

$sql = "SELECT shared_capital, savings_deposit FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($shared_capital, $savings_deposit);
$stmt->fetch();
$stmt->close();

// Store values in session
$_SESSION['shared_capital'] = $shared_capital;
$_SESSION['savings_deposit'] = $savings_deposit;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store loan type data in session
    $_SESSION['loan_type'] = [
        'loan_type' => $_POST['loan_type'],
        'loan_amount' => $_POST['loan_amount'],
        'loan_purpose' => $_POST['loan_purpose']
    ];

    // Redirect to loan plans page
    header("Location: Loan_plans.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Types</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Focus on main form styling matching the design from the screenshot */
.registration-form-container {
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background-color: gray;
    box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

.registration-form-container h2 {
    font-size: 24px;
    margin-bottom: 20px;
    text-align: center;
    color: black;
}

.form-group {
    margin-bottom: 20px;
    text-align: left;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: black;
}

input[type="number"], textarea, select {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    margin-top: 5px;
}

/* Loan type radio buttons */
.form-row {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
}

.form-row .input-group {
    display: flex;
    align-items: center;
}

.form-row .input-group input {
    margin-right: 10px;
}

/* Button container */
.button-container {
    display: flex;
    justify-content: space-between;
}

.btn {
    padding: 10px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.btn.submit {
    background-color: green;
    color: white;
}

.btn.cancel {
    background-color: red;
    color: white;
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
        flex-direction: column;
        justify-content: flex-start;
        gap: 15px;
    }

    .form-row .input-group {
        justify-content: flex-start;
    }

    .button-container {
        flex-direction: column;
        gap: 10px;
    }

    .btn {
        width: 100%;
        padding: 10px;
    }
}

/* Responsive design for mobile devices */
@media only screen and (max-width: 480px) {
    .registration-form-container {
        max-width: 100%;
        padding: 10px;
    }

    .registration-form-container h2 {
        font-size: 20px;
    }

    .form-group label {
        font-size: 14px;
    }

    input[type="number"], textarea, select {
        font-size: 14px;
        padding: 8px;
    }

    .button-container {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        padding: 10px 0;
        font-size: 14px;
    }
}


        .btn.submit {
            background-color: green;
            color: white;
        }

        .btn.cancel {
            background-color: red;
            color: white;
        }
    </style>
</head>
<body>

    <header>
        <!-- Keep your existing header code unchanged -->
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
            <section id="homepage" class="dashboard-section">
                <div class="registration-form-container">
                    <h2>Loan Type</h2>
                    <form action="Loan_types.php" method="POST">
    <div class="form-group">
        <label for="loan-type">Loan Type:</label>
        <div class="form-row">
        <div class="input-group">
            <input type="radio" id="special" name="loan_type" value="special" required>
            <label for="special">Special</label>
        </div>
        <div class="input-group">
            <input type="radio" id="regular" name="loan_type" value="regular" required>
            <label for="regular">Regular</label>
        </div>
    </div>
    </div>

    <div class="form-group">
                        <label for="shared-capital">Shared Capital:</label>
                        <input id="shared-capital" name="shared_capital" value="<?php echo htmlspecialchars($_SESSION['shared_capital']); ?>" placeholder="Enter Shared Capital" readonly>
                    </div>
                    <div class="form-group">
                        <label for="savings-deposit">Savings Deposit:</label>
                        <input id="savings-deposit" name="savings_deposit" value="<?php echo htmlspecialchars($_SESSION['savings_deposit']); ?>" placeholder="Enter Savings Deposit" readonly>
                    </div>
                    <div class="form-group">
                        <label for="loan-amount">Amount of Loan:</label>
                        <input type="number" id="loan-amount" name="loan_amount" required>
                    </div>


    <div class="form-group">
        <label for="loan-purpose">Purpose of Loan:</label>
        <textarea id="loan-purpose" name="loan_purpose" rows="4" required></textarea>
    </div>

    <div class="button-container">
        <button type="button" id="cancel-btn" class="btn cancel">Cancel</button>
        <button type="submit" class="btn submit">Next</button>
    </div>

                    </form>
                </div>
            </section>
        </div>
    </main>
    
    <footer>
        <!-- Keep your existing footer code unchanged -->
        <p>&copy; 2024 Loan Management System</p>
    </footer>
    <script>
const loanTypeRadios = document.querySelectorAll('input[name="loan_type"]');
const loanAmountInput = document.getElementById('loan-amount');
const sharedCapitalInput = document.getElementById('shared-capital');

// Function to set loan amount limits based on loan type
function setLoanAmountLimits() {
    loanTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'special') {
                loanAmountInput.value = 20000; 
                loanAmountInput.setAttribute('max', 20000);
                loanAmountInput.setAttribute('min', 20000);
            } else if (this.value === 'regular') {
                // Calculate max loan amount as three times shared capital
                const maxLoanAmount = parseFloat(sharedCapitalInput.value) * 3;
                loanAmountInput.value = ''; 
                loanAmountInput.setAttribute('max', maxLoanAmount);
                loanAmountInput.removeAttribute('min');
            }
        });
    });

    // Set initial value based on the default checked radio button
    const initialLoanType = document.querySelector('input[name="loan_type"]:checked');
    if (initialLoanType) {
        initialLoanType.dispatchEvent(new Event('change')); // Trigger change event to set the limit
    }
}

// Validate loan amount when the input changes
loanAmountInput.addEventListener('input', function() {
    const maxLoanAmount = parseFloat(sharedCapitalInput.value) * 3;
    if (this.value > maxLoanAmount) {
        alert(`The loan amount cannot exceed three times the SHARED CAPITAL. Recommended maximum amount is ${maxLoanAmount}.`);
        this.value = maxLoanAmount; // Reset to max allowed
    }
});

// Initialize loan amount limits on page load
setLoanAmountLimits();

// Cancel button to redirect user back to dashboard
document.getElementById('cancel-btn').addEventListener('click', function () {
    window.location.href = 'borrower_dashboard.php'; // Redirect back to dashboard
});
</script>
    <script>
        // Cancel button to redirect user back to dashboard
        document.getElementById('cancel-btn').addEventListener('click', function () {
            window.location.href = 'borrower_dashboard.php'; // Redirect back to dashboard
        });
    </script>
</body>
</html>
