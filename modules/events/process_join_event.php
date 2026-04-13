<?php
session_start();
require_once '../../includes/db_connect.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Check if already joined to prevent duplicates
    $check_sql = "SELECT * FROM event_participants WHERE event_id = ? AND user_id = ?";
    $c_stmt = $conn->prepare($check_sql);
    $c_stmt->bind_param("ii", $event_id, $user_id);
    $c_stmt->execute();
    
    if ($c_stmt->get_result()->num_rows == 0) {
        // Insert joining record
        $ins_sql = "INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)";
        $i_stmt = $conn->prepare($ins_sql);
        $i_stmt->bind_param("ii", $event_id, $user_id);
        $i_stmt->execute();
    }
}

// Redirect back to the event dashboard
header("Location: index.php");
exit();
?>