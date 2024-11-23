<?php
session_start();

// Admin dashboard content
$username = $_SESSION['username']; // Get the username from the session
$role = $_SESSION['role']; // Get the position

// Include database connection
require 'db_connection.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}
$search_query = "";
if (isset($_POST['search'])) {
    $search = $conn->real_escape_string($_POST['search']);
    // Search for the full name (first, middle, and last name)
    $search_query = " AND CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE '%$search%'";
}

// Query to fetch only loans with 'released' status
$sql = "SELECT id, username, first_name, middle_name, last_name, faculty_department, loan_type, loan_amount, loan_term
        FROM borrower_credentials
        WHERE loan_status IN ('released', 'pending', 'approved') $search_query
        ORDER BY sort_order ASC";
$result = $conn->query($sql);
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
    /*sic styling for the payment list wrapper */
        .payment-list-wrapper {
    border: 2px solid #ddd; /* Border for the payment list wrapper */
    border-radius: 8px;
    padding: 1rem;
    background-color: #f9f9f9;
    max-width: 100%; /* Ensures it doesn't overflow */
    overflow-x: auto; /* Enables horizontal scroll on smaller screens */
    margin-bottom: 5px;
}

/* Styling for the payment list header */
.payment-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap; /* Allows items to wrap in smaller screens */
}

/* Styling for the inner payment list section */
.payment-list-inner-border {
    border: 2px solid #ddd; /* Inner border */
    border-radius: 8px;
    padding: 1rem;
    background-color: #ffffff;
    overflow: auto; /* Enables both horizontal and vertical scroll */
}

/* Inner header styles */
.payment-list-inner-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap; /* Allows items to wrap in smaller screens */
    padding: 0; /* Ensure no extra padding */
}

/* Button styling */
.new-payment {
    background-color: green; /* Button color */
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 5px;
    margin-bottom: 1rem; /* Space below button */
    margin-top: 1rem;
}

/* Show entries section */
.show-entries {
    display: flex;
    align-items: center;
    margin-bottom: 1rem; /* Space below */
}

.show-entries select {
    margin-left: 5px;
}
.search-bar {
    position: relative;
    display: flex; 
    max-width: 100%; /* Ensures it uses full width of the parent */
    margin-bottom: 5px; /* Adjust margin for spacing */
}

.search-bar input {
    width: 100%; /* Ensure the input takes available space */
    padding-right: 35px; /* Space for the icon */
    padding-left: 10px; /* Padding for better alignment */
    height: 36px; /* Ensure a good height for the input */
    border: 1px solid #ccc; /* Border styling */
    border-radius: 4px; /* Rounded edges */
    box-sizing: border-box; /* Include padding and border in the element's total width */
}

.search-icon {
    position: absolute;
    right: 10px; /* Position the icon inside the input */
    top: 50%;
    transform: translateY(-50%); /* Center the icon vertically */
    font-size: 18px; /* Size of the icon */
    color: #888; /* Icon color */
    pointer-events: none; /* Prevent icon interaction */
}

/* Responsive styles for smaller screens */
@media (max-width: 768px) {
    .search-bar {
        max-width: 100%; /* Full width on smaller screens */
    }

   
}

/* Table styling */
.payment-list-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto; /* Allows columns to adjust automatically */
}

.payment-list-table th, .payment-list-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.payment-list-table th {
    background-color: #f2f2f2;
}

/* Pagination controls styling */
.pagination-controls {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    flex-wrap: wrap; /* Allows items to wrap in smaller screens */
}

.pagination-info {
    font-size: 0.9rem;
}

.pagination-nav a {
    margin: 0 5px;
    text-decoration: none;
    color: #007bff; /* Link color */
}

/* Responsive styles */
@media (max-width: 768px) {
    .payment-list-header,
    .payment-list-inner-header {
        flex-direction: column; /* Stack elements vertically */
        align-items: flex-start; /* Align to start */
    }

    .new-payment {
        width: 100%; /* Full width button on small screens */
    }

    .show-entries,
    .search-bar {
        width: 100%; /* Full width sections */
        margin-bottom: 1rem; /* Space below */
    }

    .payment-list-table th, .payment-list-table td {
        font-size: 0.9rem; /* Smaller font on small screens */
    }
}

@media (max-width: 480px) {
    .payment-list-header h2 {
        font-size: 1.5rem; /* Smaller header on very small screens */
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
            <div class="payment-list-wrapper">
                <section id="payment-list" class="payment-list-inner-border">
                    <div class="payment-list-header">
                        <h2>Payment List</h2>
                    </div>

                    <div class="payment-list-inner-header">
                        <!-- Wrap the search input in a form -->
                        <form method="POST" action="">
                            <div class="search-bar">
                                <input type="text" name="search" placeholder="Search...">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </form>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="payment-list-table">
                            <thead>
                                <tr>
                                    
                                    <th>#</th>
                                    <th>Profile Picture</th>
                                    <th>Username</th>
                                    <th>Payer Name</th>
                                    <th>Faculty Department</th>
                                    <th>Loan Type</th>
                                    <th>Loan Amount</th>
                                    <th>Fully Paid</th> 
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                // Include database connection
                            require 'db_connection.php';

                                // Create connection
                                $conn = new mysqli($servername, $username, $password, $dbname);

                                // Check connection
                                if ($conn->connect_error) {
                                    die("Connection failed: " . $conn->connect_error);
                                }
                                // Query to retrieve borrower information with profile picture from users table
                                $sql = "SELECT bc.id, bc.username, bc.first_name, bc.middle_name, bc.last_name, 
                                            bc.faculty_department, bc.loan_type, bc.loan_amount, 
                                            bc.loan_term, bc.fully_paid, u.profile_picture 
                                        FROM borrower_credentials bc 
                                        JOIN users u ON bc.username = u.username 
                                        WHERE bc.loan_status IN ('released', 'pending', 'approved') 
                                        AND bc.fully_paid != 'Paid' $search_query
                                        ORDER BY bc.created_at DESC"; // Adjust ordering as needed

                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    $count = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        $full_name = $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
                                        $profile_picture = htmlspecialchars($row["profile_picture"]); // Ensure to escape the URL
                                        echo "<tr data-borrower-id='{$row['id']}'>";
                                        echo "<td>" . $count++ . "</td>";
                                        echo "<td><img src='$profile_picture' alt='Profile Picture' style='width: 50px; height: 50px; border-radius: 50%;'></td>"; 
                                       
                                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                        echo "<td>" . htmlspecialchars($full_name) . "</td>";
                                        echo "<td>" . ucfirst(strtolower(htmlspecialchars($row['faculty_department']))) . "</td>";
                                        echo "<td>" . ucfirst(strtolower(htmlspecialchars($row['loan_type']))) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['loan_amount']) . "</td>";
                                        echo "<td>".  htmlspecialchars($row['fully_paid']) . "</td>";
                                        echo "<td><button class='new-payment' onclick=\"openPaymentModal('{$row['id']}', '{$full_name}', '{$row['loan_amount']}', '{$row['loan_term']}')\">Add Payment</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='9'>No released loans found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
            <div id="paymentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add Payment for <span id="payer-name"></span></h2>

        <form id="paymentForm" method="POST" action="add_payment.php">
            <input type="hidden" id="borrower_id" name="borrower_id" value="">

            <label for="date">Date:</label>
            <input type="text" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" readonly>

            <label for="payment_amount">Payment Amount:</label>
            <input type="number" id="payment_amount" name="payment_amount" step="0.01" required>

            <label for="principle">Principle:</label>
            <input type="number" id="principle" name="principle" readonly>

            <label for="interest">Interest Rate (%):</label>
            <input type="number" id="interest" name="interest" step="0.01" readonly>

            <label for="due_date">Due Date:</label>
            <input type="text" id="due_date" name="due_date" readonly>

            <label for="balance">Balance:</label>
            <input type="number" id="balance" name="balance" readonly>

            <button type="submit">Submit Payment</button>
        </form>
    </div>
</div>

<!-- Styles for modal -->
<style>
    .modal {
    display: none; 
    position: fixed; 
    z-index: 1000; /* Increased z-index for better overlay */
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0, 0, 0, 0.6); /* Darker background for better contrast */
    transition: all 0.3s ease; /* Smooth transition */
}

.modal-content {
    background-color: #ffffff; /* White background for the modal */
    margin: 10% auto; 
    padding: 30px;
    border: 1px solid #ddd; /* Lighter border */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Soft shadow effect */
    border-radius: 15px; /* More rounded corners */
    width: 90%;
    max-width: 500px;
    animation: slideIn 0.4s; /* Animation for modal appearance */
}

/* Animation for modal entry */
@keyframes slideIn {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.close {
    color: #e74c3c; /* Bright red color for the close button */
    float: right;
    font-size: 30px;
    font-weight: bold;
    transition: color 0.3s; /* Smooth color transition */
}

.close:hover,
.close:focus {
    color: #c0392b; /* Darker red on hover for effect */
    text-decoration: none;
    cursor: pointer;
}

h2 {
    margin: 0 0 15px 0; /* Margin adjustments for the title */
    color: #333; /* Darker text color for better readability */
}

label {
    font-weight: bold; /* Bold labels for better visibility */
    margin-top: 10px; /* Space above labels */
    color: #555; /* Softer label color */
}

input[type="text"],
input[type="number"] {
    width: 100%; /* Full width inputs */
    padding: 10px; /* Padding for comfort */
    margin-top: 5px; /* Space above inputs */
    border: 1px solid #ccc; /* Subtle border */
    border-radius: 5px; /* Rounded edges for inputs */
    box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1); /* Inner shadow effect */
    transition: border-color 0.3s; /* Transition for border color */
}

input[type="text"]:focus,
input[type="number"]:focus {
    border-color: #3498db; /* Border color change on focus */
    outline: none; /* Remove outline */
}

button[type="submit"] {
    margin-top: 5px;
    background-color: green; /* Primary button color */
    color: white; /* White text for contrast */
    padding: 12px 20px; /* Padding for buttons */
    border: none; /* No border */
    border-radius: 5px; /* Rounded edges for button */
    cursor: pointer; /* Pointer cursor */
    transition: background-color 0.3s; /* Transition for background color */
}

button[type="submit"]:hover {
    background-color: green; /* Darker blue on hover */
}

</style>

<footer>
    <p>&copy; 2024 Loan Management System</p>
</footer>

<script>
    // Modal functionality
    const modal = document.getElementById("paymentModal");
    const closeBtn = document.getElementsByClassName("close")[0];
    const paymentForm = document.getElementById("paymentForm");

    // Function to open modal and fill in borrower details
    function openPaymentModal(borrowerId, fullName, loanAmount, loanTerm) {
        // Fetch the latest payment information via AJAX
        fetch(`get_latest_payment.php?borrower_id=${borrowerId}`)
        .then(response => response.json())
        .then(data => {
            let principal = loanAmount; // Default to loanAmount if no payments are found
            if (data && data.deduction) {
                principal = data.deduction; // Use the latest deduction from the last payment
            }

            // Set fixed interest rate
            const interestRate = 0.06; // Example interest rate of 6% per annum
            const monthlyInterestRate = interestRate / 12;

            // Calculate monthly payment using the formula
            const monthlyPayment = (principal * monthlyInterestRate) / (1 - Math.pow(1 + monthlyInterestRate, -loanTerm));

            // Populate modal fields
            document.getElementById("payer-name").innerText = fullName;
            document.getElementById("borrower_id").value = borrowerId;
            document.getElementById("principle").value = principal; // Principal amount
            document.getElementById("interest").value = (interestRate * 100).toFixed(2); // Interest rate display
            document.getElementById("payment_amount").value = monthlyPayment.toFixed(2); // Monthly payment display

            // Remove the readonly attribute to allow input
            document.getElementById("payment_amount").removeAttribute("readonly");

            // Calculate and set due date based on loan term
            const currentDate = new Date();
            const dueDate = new Date();
            dueDate.setMonth(currentDate.getMonth() + parseInt(loanTerm)); // Assuming loanTerm is in months
            document.getElementById("due_date").value = dueDate.toISOString().split('T')[0];

            // Update balance
            updateBalance();

            // Add event listener for payment amount change
            document.getElementById("payment_amount").addEventListener('input', updateBalance);

            modal.style.display = "block";
        });
    }

    // Close the modal
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }

    // Function to calculate the balance
    function updateBalance() {
        const loanAmount = parseFloat(document.getElementById("principle").value);
        const paymentAmount = parseFloat(document.getElementById("payment_amount").value) || 0; // Default to 0 if NaN

        if (!isNaN(loanAmount)) {
            const balance = loanAmount - paymentAmount; // Simplified balance calculation
            document.getElementById("balance").value = balance.toFixed(2); // Set the balance
        } else {
            document.getElementById("balance").value = ""; // Clear balance if inputs are invalid
        }
    }

    // Handle form submission
    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Use Fetch API to submit the form data
        const formData = new FormData(paymentForm);
        
        fetch('add_payment.php', { // Make sure the file name is correct
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) { // Check if the submission was successful
                // Close the modal
                modal.style.display = "none";

                // Redirect to payment_list.php
                window.location.href = 'Payments_list.php';
            } else {
                // Handle errors (e.g., show error message)
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error submitting payment:', error);
            alert('An error occurred while submitting the payment.');
        });
    });

    // Real-time search functionality
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.payment-list-table tbody tr');

        rows.forEach(row => {
            const cells = row.getElementsByTagName('td');
            let rowContainsSearchTerm = false;

            // Check all cells in the row
            for (let cell of cells) {
                if (cell.innerText.toLowerCase().includes(searchTerm)) {
                    rowContainsSearchTerm = true; // Set flag if any cell matches
                    break; // No need to check further
                }
            }

            // Show or hide the row based on the search term
            row.style.display = rowContainsSearchTerm ? '' : 'none';
        });
    });
</script>
</div> <!-- End of .content -->
    </main>
</body>
</html>
