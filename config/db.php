<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "kpi_tasks";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . $database;
if ($conn->query($sql) === TRUE) {
    // Database created successfully or already exists
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($database);

// Create tables if they don't exist
$createTablesSQL = "
CREATE TABLE IF NOT EXISTS people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    task_date DATE NOT NULL,
    assigned_to INT,
    status ENUM('Not Yet Started', 'Ongoing', 'Done', 'Overdue') DEFAULT 'Not Yet Started',
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    notes TEXT,
    anomalies_detected TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES people(id) ON DELETE SET NULL,
    INDEX (task_date),
    INDEX (assigned_to),
    INDEX (status)
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

// Execute table creation
if ($conn->multi_query($createTablesSQL) === TRUE) {
    // Clear all previous results
    while ($conn->next_result()) {;}
} else {
    die("Error creating tables: " . $conn->error);
}

// Add anomalies_detected column if it doesn't exist
$checkColumnSQL = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_NAME = 'tasks' AND TABLE_SCHEMA = '" . $database . "' 
                   AND COLUMN_NAME = 'anomalies_detected'";
$result = $conn->query($checkColumnSQL);

if ($result->num_rows == 0) {
    $alterSQL = "ALTER TABLE tasks ADD COLUMN anomalies_detected TEXT AFTER notes";
    if ($conn->query($alterSQL) === FALSE) {
        // Column might already exist, continue silently
    }
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>
