<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "docuflow_db";

// Connect to MySQL
$conn = mysqli_connect($host, $username, $password, $database);

// Check if connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

?>