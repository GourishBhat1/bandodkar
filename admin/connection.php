<?php
// Set default timezone
date_default_timezone_set("Asia/Kolkata");

// Database configuration
$host = "localhost";
$user = "u929750551_bandodkar";
$pass = ":Xuzbt>HX9d";
$db   = "u929750551_bandodkar";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset (recommended)
$conn->set_charset("utf8");

// Use $conn in your pages and close after usage:
// $conn->close();
?>