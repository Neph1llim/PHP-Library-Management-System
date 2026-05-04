<?php
// This file is for connecting to the database.
    $db_server = "localhost";
    $db_username = "root";
    $db_password = "";
    $db_name = "library_management_system";

    // Create connection
    $conn = new mysqli($db_server, $db_username, $db_password, $db_name);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        die("Error loading character set utf8mb4: " . $conn->error);
    }
?>