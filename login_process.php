<?php 
session_start();

// Include database connection
require 'db_connection.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed.'])); 
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Query to check the user's credentials
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Log the fetched data for debugging
        error_log("Fetched User Data: " . print_r($row, true)); // Log fetched data for inspection

        // Verify the password
        if (password_verify($pass, $row['password'])) {
            // Set session variables
            $_SESSION['username'] = $user;
            $_SESSION['role'] = $row['role'];
            $_SESSION['user_id'] = $row['id']; 

            // Redirect based on user role
            echo json_encode(['success' => true, 'role' => $row['role']]); // Send response for client-side handling
            exit(); 
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No such user found.']);
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
