<?php
session_start();
require_once '../../includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if current user is an admin
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Capture and Verify ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $member_id = intval($_GET['id']);

    // Assign different SQL statements based on user role
    if ($is_admin) {
        // Admin: Has full rights to delete any record without ownership check
        $sql = "DELETE FROM club_members WHERE member_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $member_id);
    } else {
        // Student: Can only delete their own records (Ownership Verification)
        $sql = "DELETE FROM club_members WHERE member_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $member_id, $user_id);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header("Location: index.php?delete=success");
        } else {
            // No rows affected might mean the record didn't exist or didn't belong to the user
            header("Location: index.php?delete=error&msg=unauthorized_or_not_found");
        }
    } else {
        header("Location: index.php?delete=error&msg=query_failed");
    }
} else {
    header("Location: index.php");
}
exit();
?>