<?php
$mysqli = new mysqli("localhost", "root", "", "cbt");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$queries = [
    "ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL AFTER active_session_id",
    "ALTER TABLE users ADD COLUMN user_agent VARCHAR(255) NULL AFTER last_ip"
];

foreach ($queries as $query) {
    if ($mysqli->query($query) === TRUE) {
        echo "Query successful: " . $query . "\n";
    } else {
        echo "Error: " . $mysqli->error . "\n";
    }
}

$mysqli->close();
