<?php
// Include database connection
require 'db_connection.php';

// Retrieve form data
$full_name = $conn->real_escape_string($_POST['full_name']);
$username = $conn->real_escape_string($_POST['username']);
$email = $conn->real_escape_string($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm-password'];
$role = $conn->real_escape_string($_POST['role']);
$position = $conn->real_escape_string($_POST['position']); // Get the position

// Set first_name to "Admin"
$admin_name = "Admin"; // Automatically set name as "Admin"

// Validate form data
$errors = [];

// Check if any fields are empty
if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role) || empty($position)) {
    $errors[] = "All fields are required.";
}

// Check if passwords match
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

// Check for existing username
$result = $conn->query("SELECT * FROM users WHERE username='$username'");
if ($result->num_rows > 0) {
    $errors[] = "Username already exists.";
}

// If there are no errors, proceed with insertion
if (empty($errors)) {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into the database
    $sql = "INSERT INTO users (full_name, username, email, password, role, position) VALUES ('$full_name','$username', '$email', '$hashed_password', '$role', '$position')";

    if ($conn->query($sql) === TRUE) {
        // User created successfully
        header("Location: Users.php?success=User created successfully."); // Redirect with success message
        exit();
    } else {
        // Error during insertion
        $errors[] = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Close the connection
$conn->close();

// Display errors if any
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<p style='color: red;'>$error</p>";
    }
}
?>
