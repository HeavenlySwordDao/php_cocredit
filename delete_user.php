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

// Check if the ID is set
if (isset($_POST['id'])) {
    $id = intval($_POST['id']); // Get the user ID and convert it to an integer

    // Prepare and execute the delete query
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Success - Redirect back to the user list
        header("Location: Users.php?delete=success");
    } else {
        // Error - Redirect back with an error message
        header("Location: Users.php?delete=error");
    }
    
    $stmt->close();
} else {
    // ID was not set - Redirect back with an error message
    header("Location: Users.php?delete=invalid");
}

// Close the database connection
$conn->close();
?>
