<?php
session_start();

// Include database connection
require 'db_connection.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['role'])) {
    $full_name = $_POST['full_name']; // Corrected from $_POS to $_POST
    $user = $_POST['username'];
    $email = $_POST['email']; // Ensure this is included in the form
    $pass = $_POST['password'];
    $role = $_POST['role'];

    // Check if passwords match
    if (isset($_POST['confirm_password']) && $_POST['password'] !== $_POST['confirm_password']) {
        echo "Passwords do not match.";
        exit();
    }

    // Check if the username already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "Username already taken. Please choose a different one.";
        exit();
    }

    // Hash the password for security
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // Insert the user into the database, including the email
    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $user, $email, $hashed_password, $role); // Updated to include email

    if ($stmt->execute()) {
        // Set session variables
        $_SESSION['username'] = $user;
        $_SESSION['role'] = $role;
        $_SESSION['user_id'] = $conn->insert_id; // Get the last inserted ID

        // Redirect to the login page after account creation
        header('Location: login.php');
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>