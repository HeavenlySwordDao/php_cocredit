<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.html");
    exit();
}

// Include database connection
require 'db_connection.php';

// Prepare and bind
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = intval($_POST['userId']);
    $savingsDeposit = floatval($_POST['savingsDeposit']);
    $shareCapital = floatval($_POST['shareCapital']);

    $stmt = $conn->prepare("UPDATE users SET savings_deposit = ?, shared_capital = ? WHERE id = ?");
    $stmt->bind_param("ddi", $savingsDeposit, $shareCapital, $userId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>