<?php
session_start();

// Check if the user is logged in and is a borrower
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'borrower') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure session data for registration and loan type exists
    if (!isset($_SESSION['registration']) || !isset($_SESSION['loan_type'])) {
        echo "<script>alert('Please complete the Registration and Loan Type forms before submitting the Loan Plan.');</script>";
    } else {
        // Determine loan term
        $loan_term = ($_POST['loan_term'] === 'custom') ? $_POST['custom_loan_term'] : $_POST['loan_term'];

        // Handle the file upload
        $payslip = $_FILES['payslip'];
        $upload_dir = 'uploads/'; // Directory to save the uploaded files
        $upload_file = $upload_dir . basename($payslip['name']);
        $upload_ok = 1; // Variable to check if the upload is successful

        // Check file type and size (optional)
        $file_type = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));
        if ($payslip['size'] > 2000000) { // Limit to 2MB
            echo "Sorry, your file is too large.";
            $upload_ok = 0;
        }
        if (!in_array($file_type, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])) {
            echo "Sorry, only PDF, JPG, JPEG, DOC, DOCX & PNG files are allowed.";
            $upload_ok = 0;
        }

        // Proceed with upload if no errors
        if ($upload_ok === 1) {
            if (move_uploaded_file($payslip['tmp_name'], $upload_file)) {
                // Store loan plan data in session
                $_SESSION['loan_plan'] = [
                    'loan_term' => $loan_term,
                    'payslip' => $upload_file // Store the file path in session
                ];

                // Insert data into the database
                $sql = "INSERT INTO borrower_credentials 
                (username, first_name, middle_name, last_name, address, contact_no, net_pay, faculty_department, 
                loan_type, loan_amount, loan_purpose, loan_term, payslip) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                // Prepare the statement
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssssss",
                $_SESSION['username'],
                $_SESSION['registration']['first_name'],
                $_SESSION['registration']['middle_name'],
                $_SESSION['registration']['last_name'],
                $_SESSION['registration']['address'],
                $_SESSION['registration']['contact_no'],
                $_SESSION['registration']['net_pay'],
                $_SESSION['registration']['faculty_department'],
                $_SESSION['loan_type']['loan_type'],
                $_SESSION['loan_type']['loan_amount'],
                $_SESSION['loan_type']['loan_purpose'],
                $_SESSION['loan_plan']['loan_term'],
                $upload_file // Add the file path to the insert query
                );

                // Execute the prepared statement
                if ($stmt->execute()) {
                    header("Location: loan_details.php");
                    exit();
                } else {
                    echo "Error: " . $stmt->error;
                }

                // Close the statement and connection
                $stmt->close();
                $conn->close();
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Plans</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
       /* General styles for all screen sizes */
.loan-plans-wrapper {
    padding: 20px;
    background-color: white;
    margin-bottom: 20px;
    border: 1px solid white;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.loan-plans-header {
    text-align: left;
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 20px;
    color: black;
}

.loan-plans {
    padding: 20px;
    border: 2px solid gray;
    border-radius: 8px;
    background-color: gray;
}

.loan-plans .form-group {
    margin-bottom: 20px;
}

.loan-plans select,
.loan-plans input,
.loan-plans textarea {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 16px;
}

.loan-plans .form-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.loan-plans .form-row .input-group {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin-right: 10px;
}

.loan-plans .form-row .input-group:last-child {
    margin-right: 0;
}

/* Button container to align buttons */
.loan-plans .button-container {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

/* Styled buttons */
.loan-plans .btn {
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    color: white;
    background-color: green;
    cursor: pointer;
    font-size: 16px;
}

.loan-plans .btn.cancel {
    background-color: red;
}

.loan-plans .btn:hover {
    opacity: 0.9;
}

/* Collateral container to divide left and right sections */
.collateral-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.collateral-left,
.collateral-right {
    flex: 1;
    padding: 10px;
    margin-top: 5px;
}

/* Ensure textarea inside collateral-right takes up appropriate space */
.collateral-right textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

/* Additional styling for form labels */
.loan-plans label {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 8px;
    display: block;
    color: black;
}

/* Ensure text input and textarea have uniform padding */
input, textarea {
    padding: 12px;
}

/* Margin and padding adjustments */
.loan-plans .form-group input, 
.loan-plans .form-group textarea {
    margin-top: 8px;
    margin-bottom: 15px;
}

.loan-plans .form-row {
    margin-bottom: 25px;
}

.loan-plans .btn:hover {
    opacity: 0.9;
    transition: background-color 0.3s ease;
}

.loan-plans .btn.cancel:hover {
    background-color: red;
}

/* Media queries for responsive design */

/* For screens smaller than 768px (tablets and below) */
@media only screen and (max-width: 768px) {
    .loan-plans .form-row {
        flex-direction: column;
    }

    .collateral-container {
        flex-direction: column;
        align-items: flex-start;
    }

    .loan-plans .button-container {
        flex-direction: column;
        gap: 10px;
    }

    .loan-plans .btn {
        width: 100%; /* Make buttons full width */
    }
}

/* For screens smaller than 480px (mobile devices) */
@media only screen and (max-width: 480px) {
    .loan-plans-wrapper {
        padding: 15px;
    }

    .loan-plans-header {
        font-size: 14px;
    }

    .loan-plans select,
    .loan-plans input,
    .loan-plans textarea {
        padding: 10px;
        font-size: 14px;
    }

    .loan-plans label {
        font-size: 16px;
    }

    .collateral-container {
        flex-direction: column;
    }

    .loan-plans .button-container {
        flex-direction: column;
    }

    .loan-plans .btn {
        width: 100%; /* Make buttons full width for smaller screens */
    }
}

.loan-plans .btn.cancel {
    background-color: red; /* Red cancel button */
}

.loan-plans .btn:hover {
    opacity: 0.9; /* Slight hover effect */
}

/* Collateral container to divide left and right sections */
.collateral-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.collateral-left,
.collateral-right {
    flex: 1;
    padding: 10px;
    margin-top:5px ;
}

/* Ensure textarea inside collateral-right takes up appropriate space */
.collateral-right textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}

/* Additional styling for form labels to match the design */
.loan-plans label {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 8px;
    display: block;
    color: black; /* Dark text for readability */
}

/* Adjust the overall padding and layout for form elements */
.loan-plans .form-group input[type="number"],
.loan-plans .form-group textarea {
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 16px;
}

/* Ensure text input and textarea have uniform padding */
input, textarea {
    padding: 12px;
}

/* Improve hover effects for better user interaction */
button:hover {
    background-color: #45a049;
}

/* Margin and padding adjustments to ensure better spacing */
.loan-plans .form-group input, 
.loan-plans .form-group textarea {
    margin-top: 8px;
    margin-bottom: 15px;
}

/* Overall margin for form sections */
.loan-plans .form-row {
    margin-bottom: 25px;
}

/* Button hover styling */
.loan-plans .btn:hover {
    opacity: 0.9;
    transition: background-color 0.3s ease;
}

/* Cancel button hover effect */
.loan-plans .btn.cancel:hover {
    background-color: #e60000;
}

</style>
<script>
        function calculateLoanDetails() {
            const loanAmount = parseFloat(<?php echo json_encode($_SESSION['loan_type']['loan_amount']); ?>);
            const loanTerm = document.getElementById('loan-term').value === 'custom' 
                ? parseFloat(document.getElementById('custom-loan-term').value) 
                : parseFloat(document.getElementById('loan-term').value);
            const interestRate = 0.06; // Example interest rate of 6% per annum
            const monthlyInterestRate = interestRate / 12;

            // Calculate monthly payment using the formula
            const monthlyPayment = (loanAmount * monthlyInterestRate) / (1 - Math.pow(1 + monthlyInterestRate, -loanTerm));

            // Show the summary in a confirmation dialog
            const message = `
                Loan Amount: ${loanAmount.toFixed(2)}
                Duration: ${loanTerm} months
                Interest Rate: ${(interestRate * 100).toFixed(2)}%
                Monthly Payment: ${monthlyPayment.toFixed(2)}
            `;

            if (confirm(message + "\n\nDo you want to proceed?")) {
                document.querySelector('form').submit(); // Submit the form if user confirms
            }
        }

        function validateForm() {
            // Check if the necessary session variables are set
            var registrationComplete = <?php echo isset($_SESSION['registration']) ? 'true' : 'false'; ?>;
            var loanTypeComplete = <?php echo isset($_SESSION['loan_type']) ? 'true' : 'false'; ?>;

            if (!registrationComplete || !loanTypeComplete) {
                alert('Please complete the Registration and Loan Type forms before submitting the Loan Plan.');
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }
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
            <!-- Loan Plans Section -->
            <div class="loan-plans-wrapper">
                <div class="loan-plans-header">
                    <h2>Loan Plans</h2>
                </div>
                <section id="loan-plans" class="loan-plans">
                <form action="Loan_plans.php" method="POST" onsubmit="return validateForm();" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="loan-term">Loan Term (in months):</label>
                        <select id="loan-term" name="loan_term" required>
                            <option value="6">6 months</option>
                            <option value="12">12 months</option>
                            <option value="24">24 months</option>
                            <option value="custom">Custom</option>
                        </select>
                        <input type="number" id="custom-loan-term" name="custom_loan_term" placeholder="Enter custom term" min="1" style="display:none;">
                    </div>

                    <script>
                        const loanTermSelect = document.getElementById('loan-term');
                        const customLoanTermInput = document.getElementById('custom-loan-term');

                        loanTermSelect.addEventListener('change', function() {
                            if (this.value === 'custom') {
                                customLoanTermInput.style.display = 'block';
                                customLoanTermInput.required = true;  // Make the custom input required
                            } else {
                                customLoanTermInput.style.display = 'none';
                                customLoanTermInput.required = false;  // Remove the requirement if not custom
                            }
                        });
                    </script>
                    <div class="file-upload-wrapper">
                        <label for="supporting-doc">Upload Payslip (PDF/Image/Docx):</label>
                        <input type="file" id="supporting-doc" name="payslip" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    </div>

                    <div class="button-container">
                    <button type="button" id="cancel-btn" class="btn cancel" onclick="window.location.href='Borrower_dashboard.php'">Cancel</button>
                    <button type="button" class="btn" onclick="calculateLoanDetails()">Register</button>
                </div>
                </form>
                </section>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Loan Management System</p>
    </footer>
</body>
