<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

// Get the admin user ID from the URL
if (isset($_GET['id'])) {
    $admin_id = $_GET['id'];

    // Fetch the user's current details
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
    } else {
        echo "No user found!";
        exit();
    }
} else {
    header("Location: Users.php");
    exit();
}

// Handle form submission to update admin details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $position = $_POST['position'];
    $password = $_POST['password']; // Get the new password from the form

    // If password is provided, hash it
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
        $update_sql = "UPDATE users SET username = ?, email = ?, password = ?, position = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi", $username, $email, $hashed_password, $position, $admin_id);
    } else {
        // If no new password is provided, update other fields only
        $update_sql = "UPDATE users SET username = ?, email = ?, position = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $username, $email, $position, $admin_id);
    }

    // Execute the update query
    if ($stmt->execute()) {
        header("Location: Users.php"); // Redirect to the Users page after updating
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin User</title>
    <link rel="stylesheet" href="borrowers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .table-container {
            border: 2px solid black; /* Outer border for the table */
            padding: 20px; /* Padding around the table */
            margin: 20px 50px; /* Margin around the container to separate from the sidebar */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #fff; /* Background color for the container */
            height: max-content;
        }
        /* Additional styles for the form table */
        table {
            width: 100%;
            border-collapse: collapse; /* Ensures borders are collapsed */
            margin: 0; /* Reset margin for the table */
            color: black;
            height: max-content;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd; /* Inner border for table cells */
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        h2 {
            text-align: center;
            margin: 20px 0;
        }

        /* Button styles */
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 16px;
            margin-left: 50px;
        } 
        button:hover {
            background-color: #45a049;
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
            <li class="dropdown">
                <a href="#" id="user-icon">
                    <i class="fas fa-user"></i>
                </a>
                <div class="dropdown-menu" id="dropdown-menu">
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
            <li><a href="Loan_list.html"><i class="fas fa-money-bill-wave"></i> Loans</a></li>
            <li><a href="Payments_list.php"><i class="fas fa-calendar-alt"></i> Payments</a></li>
            <li><a href="Borrower_list.php"><i class="fas fa-users"></i> Borrowers</a></li>
            <li><a href="Users.php"><i class="fas fa-user-friends"></i> Users</a></li>
        </ul>
    </div>

    <h2>Edit Admin User</h2>
    <div class="table-container">   
        <form method="post">
            <table>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td><label for="username">Username</label></td>
                    <td><input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required></td>
                </tr>
                <tr>
                    <td><label for="email">Email</label></td>
                    <td><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required></td>
                </tr>
                <tr>
                    <td><label for="password">Password</label></td>
                    <td><input type="password" id="password" name="password" placeholder="New Password"></td>
                </tr>
                <tr>
                    <td><label for="position">Position</label></td>
                    <td>
                        <select id="position" name="position" required>
                            <option value="Admin" <?php echo ($admin['position'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="Secretary" <?php echo ($admin['position'] == 'Secretary') ? 'selected' : ''; ?>>Secretary</option>
                            <option value="Bookkeeper" <?php echo ($admin['position'] == 'Bookkeeper') ? 'selected' : ''; ?>>Bookkeeper</option>
                        </select>
                    </td>
                </tr>
            </table>

            <button type="submit">Submit</button>
            <button type="cancel" style="background-color: red;" href="Users.php">Cancel</a></button>
        </form>
    </div>
</main>

<footer>
    <p>&copy; 2024 Loan Management System</p>
</footer>
</body>
</html>


