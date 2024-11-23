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
        /* Basic styling for the borrower list wrapper */
        .borrower-list-wrapper {
            border: 2px solid #ddd; /* Border for the borrower list wrapper */
            border-radius: 8px;
            padding: 1rem;
            background-color: #f9f9f9;
            margin-bottom: 3.5rem; /* Add margin-bottom to create space above the footer */
        }


        /* Styling for the borrower list header */
        .borrower-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        /* Styling for the inner borrower list section */
        .borrower-list-inner-border {
            border: 2px solid #ddd; /* Inner border */
            border-radius: 8px;
            padding: 1rem;
            background-color: #ffffff;
            overflow-x: auto; /* Allow horizontal scrolling on smaller screens */
            overflow-y: auto;
        }

        /* Search bar styling */
        .search-bar {
            position: relative;
            display: flex; /* Ensure the bar adjusts responsively */
            max-width: 100%;
            margin-bottom: 10px;
        }

        .search-bar input {
            width: 100%; /* Ensure the input takes available space */
            padding-right: 35px; /* Space for the icon */
            padding-left: 10px;
            height: 36px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-icon {
            position: absolute;
            right: 10px; /* Position the icon inside the input */
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #888;
            pointer-events: none;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        /* Table styling */
        .borrower-list-table {
            width: 100%; /* Ensure the table takes the full width */
            border-collapse: collapse; /* Merge borders */
            min-width: 700px; /* Adjusted minimum width for better responsiveness */
        }

        .borrower-list-table th, .borrower-list-table td {
            border: 1px solid #ddd; /* Border for table cells */
            padding: 8px;
            text-align: left;
        }

        .borrower-list-table th {
            background-color: #f2f2f2;
        }
        /* Make the table and elements responsive */
        @media screen and (max-width: 768px) {
            .borrower-list-wrapper {
                padding: 0.5rem;
            }
            .borrower-list-header h2 {
                font-size: 18px;
            }
            .search-bar input {
                font-size: 12px;
                height: 30px;
            }
            .borrower-list-inner-border {
                padding: 0.5rem;
            }
            .borrower-list-table {
                min-width: 600px; /* Reduce the minimum width of the table for smaller screens */
                font-size: 12px; /* Make text smaller on smaller screens */
            }
        }

        @media screen and (max-width: 480px) {
            .search-bar input {
                font-size: 10px;
                height: 28px;
            }
            .borrower-list-table {
                min-width: 400px; /* Further reduce the table width for smaller screens */
                font-size: 10px; /* Make text even smaller on extra small screens */
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
                    <span class="user-name"><?php echo htmlspecialchars(strtolower($role)); ?></span>
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
            <!-- Wrapper for Borrower List Section -->
            <div class="borrower-list-wrapper">
                <!-- Borrower List Header -->
                <section id="borrower-list" class="borrower-list-inner-border">
                <div class="borrower-list-header">
                    <h2>Borrower List</h2>
                </div>

                <!-- Section with Inner Border -->
               
                    <!-- Inner Header for Search Bar -->
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search..." aria-label="Search">
                        <i class="fas fa-search search-icon"></i>
                    </div>

                    <!-- Borrower List Table -->
                    <div class="table-responsive"> <!-- Wrapping table in a div for horizontal scrolling -->
                        <table class="borrower-list-table">
                        <thead>
                            <tr>
                                <th>Profile Picture</th> <!-- New column for profile picture -->
                                <th>#</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Contact Details</th>
                                <th>Address</th>
                                <th>Faculty Department</th>
                                <th>Created At</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Include database connection
                            require 'db_connection.php';

                            $sql = "
                                    SELECT u.id, u.username, u.email, u.profile_picture, u.created_at, u.role, 
                                        u.position, u.reset_token_hash, u.reset_token_expires_at, 
                                        u.shared_capital, u.savings_deposit, 
                                        bc.first_name, bc.middle_name, bc.last_name, bc.address, 
                                        bc.contact_no, bc.net_pay, bc.faculty_department, 
                                        bc.loan_amount, bc.loan_purpose, bc.loan_status, 
                                        bc.created_at AS loan_created_at 
                                    FROM users u 
                                    JOIN borrower_credentials bc ON u.username = bc.username 
                                    WHERE u.role = 'borrower' 
                                    AND bc.loan_status = 'released' 
                                    AND bc.created_at = (
                                        SELECT MIN(bc2.created_at) 
                                        FROM borrower_credentials bc2 
                                        WHERE bc2.username = u.username 
                                        AND bc2.loan_status = 'released'
                                    )
                                    ORDER BY u.username ASC"; // Sort alphabetically by username
                                                            $result = $conn->query($sql);

                                 // Check if there are results
                            if ($result->num_rows > 0) {
                                // Output data of each row
                                while ($row = $result->fetch_assoc()) {
                                    $full_name = $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
                                    $profile_picture = htmlspecialchars($row["profile_picture"]); // Ensure to escape the URL
                                    echo "<tr>
                                    <td><img src='$profile_picture' alt='Profile Picture' style='width: 50px; height: 50px; border-radius: 50%;'></td>
                                    <td>" . htmlspecialchars($row["id"]) . "</td>
                                    <td>" . htmlspecialchars($row["username"]) . "</td>
                                    <td>" . htmlspecialchars($full_name) . "</td>
                                    <td>" . htmlspecialchars($row["contact_no"]) . "</td>
                                    <td>" . htmlspecialchars($row["address"]) . "</td>
                                    <td>" . ucfirst(strtolower(htmlspecialchars($row["faculty_department"]))) . "</td>
                                    <td>";
                                                        
                                    // Format the created_at date
                                    $created_at = new DateTime($row["created_at"]);
                                    echo $created_at->format('F d Y h:i A'); // Month day year hour:minute AM/PM

                                    echo "</td>
                                    <td>" . ucfirst(htmlspecialchars($row["loan_status"])). "</td>
                                        </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='12'>No borrowers found.</td></tr>";
                            }
                            // Close the connection
                            $conn->close();
                            ?>
                        </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Loan Management System</p>
    </footer>
    <script>
        // Get the search input
        const searchInput = document.getElementById('searchInput');

        // Add event listener for input on the search field
        searchInput.addEventListener('input', function() {
            const filter = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('.borrower-list-table tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let found = false;
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        found = true;
                    }
                });
                row.style.display = found ? '' : 'none'; // Show or hide the row
            });
        });
    </script>
</body>
</html>
