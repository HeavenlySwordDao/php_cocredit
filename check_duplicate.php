<?php
header('Content-Type: application/json');
session_start();

// Include database connection
require 'db_connection.php';

// Get the data from the AJAX request
$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'];
$email = $data['email'];

// Check for username existence
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($usernameExists);
$stmt->fetch();
$stmt->close();

// Check for email existence
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($emailExists);
$stmt->fetch();
$stmt->close();

echo json_encode([
    'usernameExists' => $usernameExists > 0,
    'emailExists' => $emailExists > 0
]);

$conn->close();
?>
