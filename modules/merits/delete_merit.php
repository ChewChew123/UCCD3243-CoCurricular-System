<?php
session_start();
require('../../database/db_connect.php');

// Flowchart Step: User Logged In?
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get the ID from the URL (index.php?id=...)
if (isset($_GET['id'])) {
    $merit_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Flowchart Step: Execute SQL: DELETE from Merit Tracker
    // Note: We include user_id in the WHERE clause so a user can't delete someone else's record by guessing the ID
    $sql = "DELETE FROM merits WHERE merit_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $merit_id, $user_id);

    if ($stmt->execute()) {
        // Flowchart Step: Display Success Message & Refresh List
        header("Location: index.php?status=success&action=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    // If no ID is provided, just go back to the list
    header("Location: index.php");
    exit();
}
?>