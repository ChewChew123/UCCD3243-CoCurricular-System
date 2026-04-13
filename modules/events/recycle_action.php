<?php
/**
 * File: recycle_action.php
 * Purpose: Backend logic for restoring or permanently deleting events.
 */
session_start();
require_once '../../includes/db_connect.php'; 

// Check admin privilege
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized Access.");
}

// HANDLE SINGLE RESTORE (via GET)
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    $sql = "UPDATE events SET deleted = 0 WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    header("Location: recently_deleted.php?msg=restored");
    exit;
}

// HANDLE BULK ACTIONS (via POST)
if (isset($_POST['action']) && !empty($_POST['ids'])) {
    $ids = $_POST['ids'];
    $action = $_POST['action'];

    foreach ($ids as $id) {
        $id = intval($id);
        if ($action == 'permanent_delete') {
            // Remove completely from DB
            mysqli_query($conn, "DELETE FROM events WHERE event_id = '$id'");
        } elseif ($action == 'restore') {
            // Set deleted back to 0
            mysqli_query($conn, "UPDATE events SET deleted = 0 WHERE event_id = '$id'");
        }
    }

    $msg = ($action == 'restore') ? 'restored' : 'deleted';
    header("Location: recently_deleted.php?msg=$msg");
    exit;
}

// Fallback redirect
header("Location: recently_deleted.php");
exit;
?>