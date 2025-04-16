<?php
$conn = new mysqli("localhost", "root", "", "project2");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "MySQL connected successfully!";
?>