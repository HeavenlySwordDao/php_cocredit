<?php
session_start();

// Include database connection
require 'db_connection.php';

// Check if form is submitted
// Check if form is submitted
if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['fullname'])) {
    $user = $_POST['username'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $fullname = $_POST['fullname'];
    
    // Set role as Permanent Borrower
    $role = "borrower";


    // Check if the username already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "Username already taken.";
        exit();
    }

    // Check if the email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "Email already taken.";
        exit();
    }

    // Insert the new user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name) VALUES (?, ?, ?, ?, ?)");
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
    $stmt->bind_param("sssss", $user, $email, $hashed_password, $role, $fullname);
    
    if ($stmt->execute()) {
        echo "success"; // Return success message
    } else {
        echo "Error occurred during signup.";
    }

    $stmt->close();
}
