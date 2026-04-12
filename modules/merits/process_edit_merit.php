<?php
session_start();
require('../../database/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $merit_id = $_POST['merit_id'];
    $organizer = trim($_POST['organizer']);
    $hours = $_POST['hours'];
    $date = $_POST['date_completed'];
    $desc = trim($_POST['merit_description']);

    // Validation for data value and format
    if (empty($organizer) || empty($hours) || empty($date)) {
        header("Location: edit_merit.php?id=$merit_id&error=empty");
        exit();
    }

    // Repeating data checking
    $check_sql = "SELECT * FROM merits WHERE user_id = ? AND organizer = ? AND date_completed = ? AND hours = ? AND merit_id != ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("isdsi", $user_id, $organizer, $date, $hours, $merit_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: edit_merit.php?id=$merit_id&error=duplicate");
        exit();
    }

    // Update record from database
    $update_sql = "UPDATE merits SET organizer = ?, hours = ?, date_completed = ?, merit_description = ? WHERE merit_id = ? AND user_id = ?";
    $u_stmt = $conn->prepare($update_sql);
    $u_stmt->bind_param("sdssii", $organizer, $hours, $date, $desc, $merit_id, $user_id);

    if ($u_stmt->execute()) {
        header("Location: index.php?status=success");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}