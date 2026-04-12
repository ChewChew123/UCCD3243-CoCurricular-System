<?php
// File path: modules/achievements/delete_achievement.php
session_start();
require('../../includes/db_connect.php'); 

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $achievement_id = $_GET['id'];

    $delete_sql = "DELETE FROM achievements WHERE achievement_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    
    $stmt->bind_param("ii", $achievement_id, $user_id);

    if ($stmt->execute()) {
   
        header("Location: index.php?msg=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }

} else {
    header("Location: index.php");
    exit();
}
?>