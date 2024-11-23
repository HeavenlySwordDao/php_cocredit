<?php
// Start the session
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

$sql = "SELECT id, full_name, username, email, position, role FROM users WHERE role = 'admin' OR position IN ('Admin', 'Secretary', 'Bookkeeper', 'Borrower')";
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
/* Popup Styling */
.popup {
    display: none; /* Hide the popup by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    justify-content: center;
    align-items: center;
    z-index: 1000; /* Ensure popup is on top */
}

.popup-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.popup-header h2 {
    margin: 0;
}

.popup-header button {
    background-color: black;
    color: white;
    border: none;
    padding: 10px;
    cursor: pointer;
    border-radius: 5px;
}

.popup-header button.cancel {
    background-color: #6c757d;
}

.popup-content label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.popup-content input,
.popup-content select {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.popup-content .button-container {
    display: flex;
    justify-content: space-between;
}

.popup-content .button-container button {
    background-color: green;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 5px;
}

.popup-content .button-container button.cancel {
    background-color: red;
}

/* User List Wrapper Styles */
.user-list-wrapper {
    max-width: 100%; /* Ensure it doesn't exceed screen width */
    border: 2px solid #ccc;
    border-radius: 10px;
    padding: 20px;
    background-color: #f9f9f9;
    position: relative;
    overflow-x: auto; /* Enable horizontal scrolling */
    margin: 0 auto; /* Center the wrapper */
}

/* User List Header Styles */
.user-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    flex-wrap: wrap; /* Allows wrapping on smaller screens */
    gap: 10px;
}

.user-list-header h2 {
    margin: 0;
    font-size: 1.5rem;
    flex: 1 100%; /* Full width on smaller screens */
}

.user-list-header button {
    display: block;
    margin-top: 10px;
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    width: fit-content;
    margin-left: auto;
}

/* User List Inner Border Styles */
.user-list-inner-border {
    border: 2px solid #ddd;
    border-radius: 10px;
    padding: 1rem;
    background-color: #ffffff;
    overflow-x: auto; /* Enable horizontal scrolling for the table */
}

/* User List Table Styles */
.user-list-entries {
    margin-bottom: 20px;
}

.user-list-table {
    width: 100%;
    min-width: 600px; /* Ensure table maintains width on smaller screens */
    border-collapse: collapse;
}

.user-list-table th,
.user-list-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
    font-size: 0.9rem;
}

.user-list-table th {
    background-color: #f2f2f2;
}

/* Popup Responsiveness */
.popup-content {
    width: 90%;
    max-width: 400px;
    padding: 15px;
}

@media (max-width: 768px) {
    .popup-content {
        width: 100%;
        max-width: 350px;
    }
}

/* User List Responsiveness */
@media (max-width: 768px) {
    .user-list-wrapper {
        overflow-x: auto; /* Allow horizontal scrolling for the table */
    }

    .user-list-header,
    .user-list-inner-border {
        flex-direction: column; /* Stack elements vertically */
        align-items: flex-start; /* Align to start */
    }

    .user-list-table th,
    .user-list-table td {
        font-size: 0.9rem; /* Smaller font on small screens */
        padding: 6px;
    }

    .user-list-header button {
        width: 100%; /* Full width button on small screens */
    }
}

@media (max-width: 480px) {
    .user-list-header h2 {
        font-size: 1.5rem; /* Smaller header on very small screens */
    }
}

/* Pagination Controls */
.pagination-controls {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap; /* Allow wrapping on small screens */
    gap: 10px;
    margin-top: 1rem;
}

.pagination-info {
    font-size: 0.9rem;
}

.pagination-nav a {
    margin: 0 5px;
    text-decoration: none;
    color: #007bff;
    font-size: 0.9rem;
}

/* Adjust font size for pagination on small screens */
@media (max-width: 600px) {
    .pagination-info {
        font-size: 0.8rem;
    }

    .pagination-nav a {
        font-size: 0.8rem;
    }
}

/* Button Custom Styles */
.btn-custom-green {
    color: green;
}

.btn-custom-red {
    color: red;
}

/* Style for Add New User Button */
#add-new-user-btn {
    display: block;
    background-color: green;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 5px;
    margin: 20px 0 0 auto;
    width: fit-content;
    margin-bottom: 5px;
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
            <!-- User List Section -->
            <div class="user-list-wrapper">
                <div class="user-list-header">
                </div>
                <section id="user-list" class="user-list-inner-border">
                    <div class="user-list-entries">
                    <h2>User List</h2>
                    </div>
               
                <!-- Display success or error message -->
                <?php
                // Check for delete success or error messages
                if (isset($_GET['delete'])) {
                    if ($_GET['delete'] == 'success') {
                        echo "<p style='color: green;'>User deleted successfully!</p>";
                    } elseif ($_GET['delete'] == 'error') {
                        echo "<p style='color: red;'>Error deleting user. Please try again.</p>";
                    } elseif ($_GET['delete'] == 'invalid') {
                        echo "<p style='color: red;'>Invalid request. No user ID provided.</p>";
                    }
                }
                ?>

                <!-- Button to Add New User -->
                <button id="add-new-user-btn">+ Add New User</button>

                <!-- User List Table -->
                <table class="user-list-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                    <?php
                        // Check if there are results
                        if ($result->num_rows > 0) {
                            // Output data for each row
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>" . htmlspecialchars($row['username']) . "</td>
                                    
                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                    <td>" . htmlspecialchars($row['position']) . "</td>
                                    <td>
                                        <a href='edit_user.php?id={$row['id']}' class='btn-custom-green'>Edit</a>  |
                                        <form method='post' action='delete_user.php' style='display:inline;' onsubmit=\"return confirmDelete(event, this);\">
                                            <input type='hidden' name='id' value='{$row['id']}'>
                                            <button type='submit' class='btn-custom-red'>Delete</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No admin users found.</td></tr>";
                        }
                        ?> 
                    </tbody>
                </table>
                </section>
            </div>
        </div>
    </main>

            <!-- Popup Form for Adding New User -->
            <div id="popup-form" class="popup">
                <div class="popup-content">
                    <div class="popup-header">
                        <h2>Add New User</h2>
                        <button id="close-popup">X</button>
                        </div>

                        <form id="add-user-form" method="post" action="submit_user.php"> <!-- Updated action -->
                              
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>

                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>

                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>

                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>

                        <label for="confirm-password">Confirm Password:</label>
                        <input type="password" id="confirm-password" name="confirm-password" required>

                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="borrower">Borrower</option>
                        </select>

                        <label for="position">Position</label>
                        <select id="position" name="position" required>
                            <option value="">Select Position</option>
                            <option value="Admin">Admin</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Bookkeeper">Bookkeeper</option>
                            <option value="Borrower">Borrower</option>
                        </select>

                        <div class="button-container">
                            <button type="submit">Add User</button>
                            <button type="button" id="cancel-btn" class="cancel">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // script.js

        // Get the popup and button elements
        const popupForm = document.getElementById("popup-form");
        const addNewUserBtn = document.getElementById("add-new-user-btn");
        const closePopupBtn = document.getElementById("close-popup");
        const cancelBtn = document.getElementById("cancel-btn");

        // Function to show the popup form
        const showPopup = () => {
            popupForm.style.display = "flex"; // Show the popup as flex to center it
        };

        // Function to hide the popup form
        const hidePopup = () => {
            popupForm.style.display = "none"; // Hide the popup
        };

        // Event listeners for opening and closing the popup
        addNewUserBtn.addEventListener("click", showPopup);
        closePopupBtn.addEventListener("click", hidePopup);
        cancelBtn.addEventListener("click", hidePopup);

        // Close the popup when clicking outside of the popup content
        window.onclick = (event) => {
            if (event.target == popupForm) {
                hidePopup();
            }
        };
    </script>
    <script>
function confirmDelete(event, form) {
    event.preventDefault(); // Prevent form submission

    // Show confirmation dialog
    if (confirm("Are you sure you want to delete this user?")) {
        // If confirmed, submit the form
        form.submit();
    } else {
        // If canceled, show a notification
        alert("User deletion canceled.");
    }
}
</script>

    
    <footer>
        <p>&copy; 2024 Loan Management System</p>
    </footer>
</body>
</html>
<?php
// Close the database connection
$conn->close();

?> 
