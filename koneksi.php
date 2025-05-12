<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_eduklas";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>