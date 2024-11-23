<?php
// database.php

$host = "localhost";
$dbname = "borrowers";
$username = "root";
$password = "";

// Create a new MySQLi connection
$mysqli = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($mysqli->connect_errno) {
    die("Connection error: " . $mysqli->connect_error);
}

// Return the connection object
return $mysqli;
?>