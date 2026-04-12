<?php
session_start();
require('../../database/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $organizer = trim($_POST['organizer']);
    $hours = $_POST['hours'];
    $date = $_POST['date_completed'];
    $desc = trim($_POST['merit_description']);

    // --- Flowchart Step: All fields filled with valid format/value? ---
    if (empty($organizer) || empty($hours) || empty($date)) {
        header("Location: add_merit.php?error=empty");
        exit();
    }

    // --- Flowchart Step: Merit data already exists? ---
    // Checking if the same user has logged the same organizer, date, and hours
    $check_sql = "SELECT * FROM merits WHERE user_id = ? AND organizer = ? AND date_completed = ? AND hours = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("isds", $user_id, $organizer, $date, $hours);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // YES (Duplicate) -> Flowchart: Display Warning Message
        header("Location: add_merit.php?error=duplicate");
        exit();
    } else {
        // NO (New Record) -> Flowchart: Execute SQL INSERT
        $insert_sql = "INSERT INTO merits (user_id, organizer, hours, date_completed, merit_description) VALUES (?, ?, ?, ?, ?)";
        $i_stmt = $conn->prepare($insert_sql);
        $i_stmt->bind_param("isdss", $user_id, $organizer, $hours, $date, $desc);

        if ($i_stmt->execute()) {
            // Flowchart: Display Success Message & Refresh List
            header("Location: index.php?status=success");
            exit();
        } else {
            echo "Database error: " . $conn->error;
        }
    }
} else {
    header("Location: add_merit.php");
    exit();
}