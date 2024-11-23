<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

// Handle the form submission for updating savings and capital
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['userId']);
    $savingsDeposit = floatval($_POST['savingsDeposit']); // New savings deposit from user input
    $addSavings = floatval($_POST['addSavings']); // Amount to add (if applicable)
    $shareCapital = floatval($_POST['shareCapital']); // New share capital from user input
    $addCapital = floatval($_POST['addCapital']); // Amount to add (if applicable)

    // Fetch current values
    $currentSql = "SELECT savings_deposit, shared_capital FROM users WHERE id = ?";
    $stmt = $conn->prepare($currentSql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Calculate new totals
    $newSavingsDeposit = $savingsDeposit + $addSavings; // Allow for direct edits
    $newSharedCapital = $shareCapital + $addCapital; // Allow for direct edits

    // Update the user's savings and capital
    $updateSql = "UPDATE users SET savings_deposit = ?, shared_capital = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param('ddi', $newSavingsDeposit, $newSharedCapital, $userId);

    if ($stmt->execute()) {
        // Log transactions only if values are added
        if ($addSavings > 0) {
            $logSql = "INSERT INTO collateral_transaction (borrower_id, amount, type, timestamp) VALUES (?, ?, 'Savings Deposit', NOW())";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param('id', $userId, $addSavings);
            $logStmt->execute();
            $logStmt->close(); // Close the statement
        }

        if ($addCapital > 0) {
            $logSql = "INSERT INTO collateral_transaction (borrower_id, amount, type, timestamp) VALUES (?, ?, 'Shared Capital', NOW())";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param('id', $userId, $addCapital);
            $logStmt->execute();
            $logStmt->close(); // Close the statement
        }

        // Feedback for successful update
        echo json_encode(['success' => true]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update user data.']);
        exit();
    }
}

// Fetch collateral users (only borrowers)
$sql = "SELECT id, full_name, username, email, profile_picture, savings_deposit, shared_capital 
        FROM users 
        WHERE role = 'borrower'"; // Adjust role as per your database values
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collateral User</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<style>
  table {
    width: 100%; /* Full width */
    border-collapse: collapse; /* Merge borders */
    margin-top: 20px; /* Space above the table */
}

th, td {
    border: 1px solid #ccc; /* Light gray border */
    padding: 10px; /* Space inside cells */
    text-align: left; /* Align text to the left */
}

th {
    background-color: #f2f2f2; /* Light background for header */
    font-weight: bold; /* Make header text bold */
}

tr:nth-child(even) {
    background-color: #f9f9f9; /* Alternate row color */
}

tr:hover {
    background-color: #e9e9e9; /* Highlight row on hover */
}

.inner-element {
    margin: 30px; /* Adds space around the element inside the cell */
}
img {
    border-radius: 50%; /* Rounded profile pictures */
}

.btn {
    display: inline-block; /* Make it inline */
    padding: 10px 15px; /* Padding for the button */
    border: none; /* Remove border */
    border-radius: 5px; /* Rounded corners */
    text-decoration: none; /* No underline */
    color: white; /* Text color */
    background-color: #28a745; /* Green background */
    transition: background-color 0.3s; /* Smooth transition for hover effect */
}

.btn:hover {
    background-color: #218838; /* Darker green on hover */
}

/* Modal Styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0, 0, 0, 0.6); /* Dark overlay with some transparency */
   
}

.modal-content {
    background-color: #ffffff; /* White background */
    margin: auto; /* Center the modal */
    padding: 20px;
    border: 1px solid #ccc; /* Light border */
    width: 400px; /* Fixed width for square effect */
    border-radius: 10px; /* Rounded corners */
    margin-top: 200px;
    
}

.close-button {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: transparent;
    border: none;
    font-size: 20px;
    color: #333;
    cursor: pointer;
}

.close-button:hover {
    color: red; /* Change color on hover */
}

label {
    font-weight: bold; /* Bold labels for clarity */
    margin-top: 10px; /* Space above labels */
}

input[type="number"], 
input[type="text"] {
    width: 100%; /* Full width */
    padding: 10px; /* Padding for input */
    margin-top: 5px; /* Space above input */
    border: 1px solid #ccc; /* Light border */
    border-radius: 5px; /* Rounded corners */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle inner shadow */
    transition: border-color 0.3s; /* Smooth border color transition */
}

input[type="number"]:focus, 
input[type="text"]:focus {
    border-color: #28a745; /* Green border on focus */
    outline: none; /* Remove default outline */
}

.cancel-button {
    background-color: #dc3545; /* Red for cancel */
    margin-right: 10px; /* Space between buttons */
}

.action-buttons {
    display: flex; /* Use flexbox for horizontal alignment */
    align-items: center; /* Center items vertically */
}

.divider {
    margin: 0 10px; /* Space around the divider */
}

/* Optional: Adjust button styles if necessary */
.edit-button, .history-button {
    text-decoration: none; /* Remove underline */
    color: #28a745; /* Color for the edit button */
}

.edit-button .icon-text {
    display: flex; /* Ensure the icon and text are aligned */
    align-items: center; /* Center vertically */
}
.edit-text {
    display: none; /* Hide text by default */
    margin-left: 5px; /* Space between icon and text */
}

.edit-button:hover .edit-text {
    display: inline; /* Show text on hover */
}

.edit-button:hover i,
.edit-button:hover {
    color: #218838; /* Darker green on hover */
}
/* Modal Styles for History */
.history-modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
}

.history-modal-content {
    background-color: #ffffff;
    margin: auto;
    padding: 20px;
    border: 1px solid #ccc;
    width: 400px;
    border-radius: 10px;
    margin-top: 200px;
}

/* Additional styles for the history table inside the modal */
.history-table {
    width: 100%;
    border-collapse: collapse;
}
.history-table th, .history-table td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: left;
}
.divider {
   text-align: center; /* Space around the divider */
}

/* Optional: Adjust button styles if necessary */
.edit-button, .history-button {
    text-decoration: none; /* Remove underline */
    color: #28a745; /* Color for the edit button */
}

.history-button {
    margin-right: auto; /* Push the history button to the left */
}

/* Responsive Styles */
@media (max-width: 768px) {
    table {
        display: block; /* Allow table to be a block element */
        overflow-x: auto; /* Enable horizontal scrolling */
        white-space: nowrap; /* Prevent text wrapping */
    }

    th, td {
        padding: 8px; /* Reduced padding for smaller screens */
    }

    img {
        width: 40px; /* Smaller profile picture */
        height: 40px; /* Smaller profile picture */
    }

    .btn {
        padding: 8px 12px; /* Slightly smaller button padding */
        font-size: 14px; /* Adjust font size for buttons */
    }

    .modal-content {
        width: 90%; /* Increase modal width on small screens */
    }

    .close-button {
        font-size: 20px; /* Increase close button size for better visibility */
    }

    .edit-button {
        justify-content: center; /* Center button content */
    }

    .search {
        width: 100%; /* Full width for search input */
        margin-bottom: 10px; /* Space below search input */
    }
}

/* Additional styles for very small screens */
@media (max-width: 480px) {
    th, td {
        font-size: 12px; /* Smaller font size for very small screens */
    }

    .btn {
        font-size: 12px; /* Adjust button font size */
    }

    .modal-content {
        padding: 15px; /* Reduce padding in modal on small screens */
    }
}
</style>
<body>
    <header>
        <div class="form-image">
            <img src="logo.png" alt="Logo Image">
        </div>
        <nav>
            <ul>
                <li class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
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
            <section id="collateral-user" class="dashboard-section">
            <a href="admin_dashboard.php" class="close-btn" title="Close" style="margin-right: 100%;">&#10006;</a>
                <h2>Collateral User</h2>
                <div>
                    <input type="text" id="search" placeholder="Search..." />
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Profile Picture</th>
                            <th>User Information</th>
                            <th>Collateral Offered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $profile_picture = htmlspecialchars($row["profile_picture"]);
                                $full_name = htmlspecialchars($row["full_name"]);
                                $username = htmlspecialchars($row["username"]);
                                $email = htmlspecialchars($row["email"]);
                                $savings_deposit = htmlspecialchars($row["savings_deposit"]);
                                $shared_capital = htmlspecialchars($row["shared_capital"]);
                                
                                echo "<tr>
                                    <td><img src='$profile_picture' alt='Profile Picture' style='width: 50px; height: 50px; border-radius: 50%;'></td>
                                    <td>
                                        <strong>$username</strong><br>
                                        $full_name<br>
                                        $email
                                    </td>
                                    <td>
                                        Savings Deposit: $savings_deposit<br>
                                        Share Capital: $shared_capital
                                    </td>
                                  <td>
                                        <div class='action-buttons'>
                                     <a href='#' class='edit-button' onclick='openEditModal({$row['id']}, \"{$savings_deposit}\", \"{$shared_capital}\")'>
                                        <span class='icon-text'>
                                            <i class='fas fa-edit'></i> <!-- Edit icon -->
                                            <span class='edit-text'>Edit</span> <!-- Text that appears on hover -->
                                        </span>
                                    </a>
                                    <span class='divider'>|</span>
                                    <a href='#' class='history-button' onclick='fetchHistory({$row['id']})' title='Show History'>
                                        <i class='fas fa-history' style='color: blue;'></i>
                                    </a>
                                </div>
                            </td>  
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No collateral users found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Loan Management System</p>
    </footer>

   <!-- Modal Structure -->
   <div id="editModal" class="modal">
        <div class="modal-content">
            <a href="admin_dashboard.php" class="closed-btn" title="Close" style="margin-right: 100%;">&#10006;</a>
            <span class="close-button">&times;</span>
            <h2>Edit Collateral Offered</h2>
            <form id="editForm">
            <input type="hidden" id="userId" name="userId">
                <label for="savingsDeposit">Savings Deposit:</label>
                <input type="number" id="savingsDeposit" name="savingsDeposit" required step="0.01" value="0"><br>
                <label for="addSavings">Add to Savings Deposit:</label>
                <input type="number" id="addSavings" name="addSavings" step="0.01" value="0"><br>
                <label for="shareCapital">Share Capital:</label>
                <input type="number" id="shareCapital" name="shareCapital" required step="0.01" value="0"><br>
                <label for="addCapital">Add to Share Capital:</label>
                <input type="number" id="addCapital" name="addCapital" step="0.01" value="0"><br>
                <button type="submit" class="btn submit-button">Submit</button>
            </form>
        </div>
    </div>

     <!-- History Modal Structure -->
     <div id="historyModal" class="history-modal">
        <div class="history-modal-content">
            <span class="close-button" onclick="closeHistoryModal()">&times;</span>
            <h2>Transaction History</h2>
            <div id="historyTableContainer">
                <!-- Transaction history will be injected here -->
            </div>
        </div>
    </div>
    <script>
    function openEditModal(userId, savingsDeposit, shareCapital) {
        // Populate the modal with current data
        document.getElementById('userId').value = userId;
        document.getElementById('savingsDeposit').value = savingsDeposit;
        document.getElementById('shareCapital').value = shareCapital;

        // Show the modal
        document.getElementById('editModal').style.display = 'block';
    }

    // Close the edit modal when the user clicks on the close button
    document.querySelector('.close-button').onclick = function() {
        closeEditModal();
    }

    // Close the edit modal when the user clicks outside of the modal
    window.onclick = function(event) {
        if (event.target === document.getElementById('editModal')) {
            closeEditModal();
        }
        // Close history modal when clicking outside of it
        if (event.target === document.getElementById('historyModal')) {
            closeHistoryModal();
        }
    }

   // Handle form submission for editing
document.getElementById('editForm').onsubmit = function(event) {
    event.preventDefault();
    const userId = document.getElementById('userId').value;
    const savingsDeposit = parseFloat(document.getElementById('savingsDeposit').value); // New savings deposit
    const addSavings = parseFloat(document.getElementById('addSavings').value); // Amount to add
    const shareCapital = parseFloat(document.getElementById('shareCapital').value); // New share capital
    const addCapital = parseFloat(document.getElementById('addCapital').value); // Amount to add

    // Validate that at least one of the fields has a positive value
    if (savingsDeposit >= 0 || shareCapital >= 0) {
        // Create a new XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "collateral_user.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Define what happens on successful data submission
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert("Data updated successfully!");
                    closeEditModal();
                    location.reload(); // Reload the page to see the updated data
                } else {
                    alert("Error: " + response.error);
                }
            } else {
                alert("Request failed. Status: " + xhr.status);
            }
        };

        // Send the request with the form data
        xhr.send(`userId=${userId}&savingsDeposit=${savingsDeposit}&addSavings=${addSavings}&shareCapital=${shareCapital}&addCapital=${addCapital}`);
    } else {
        alert("Please enter a value for either Savings Deposit or Share Capital.");
    }
};

    // Fetch and display history
    function fetchHistory(borrowerId) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch_collateral_history.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('historyTableContainer').innerHTML = xhr.responseText;
                document.getElementById('historyModal').style.display = 'block';
            }
        };
        xhr.send('borrower_id=' + encodeURIComponent(borrowerId));
    }

    // Close history modal
    function closeHistoryModal() {
        document.getElementById('historyModal').style.display = 'none';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Close the history modal when the user clicks on the close button
    document.querySelector('.history-modal .close-button').onclick = function() {
        closeHistoryModal();
    }
</script>
</body>
</html>
<?php
$conn->close();
?>