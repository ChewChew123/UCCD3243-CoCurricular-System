<?php
session_start();
require('../../database/db_connect.php');

// Check if user has logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get the ID from the URL
if (isset($_GET['id'])) {
    $merit_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Delete nerit
    $sql = "DELETE FROM merits WHERE merit_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $merit_id, $user_id);

    if ($stmt->execute()) {
        header("Location: index.php?status=success&action=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    // Go back if no ID provided
    header("Location: index.php");
    exit();
}
?>