<?php
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role']; // Either 'admin' or 'borrower'

    $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
    if (mysqli_query($conn, $query)) {
        echo "User registered successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
