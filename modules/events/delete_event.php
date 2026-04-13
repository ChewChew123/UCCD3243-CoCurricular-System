<?php
/**
 * File: delete_event.php
 * Purpose: Handle single event deletion (Soft Delete).
 */
session_start();
require_once '../../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['event_id'])) {
    $id = intval($_GET['event_id']);
    // Perform soft delete by setting deleted = 1
    $sql = "UPDATE events SET deleted = 1 WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?msg=deleted");
    } else {
        header("Location: index.php?msg=error");
    }
} else {
    header("Location: index.php");
}
exit();
?>