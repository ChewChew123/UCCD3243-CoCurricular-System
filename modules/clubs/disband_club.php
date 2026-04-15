<?php
session_start();
require_once '../../includes/db_connect.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}


if (isset($_GET['club_id']) && !empty($_GET['club_id']) && isset($_GET['action'])) {
    
    $club_id = intval($_GET['club_id']);
    $action = $_GET['action']; //  disband or restore

    if ($action === 'disband') {
        //  Disbanded
        $sql = "UPDATE clubs SET club_status = 'Disbanded' WHERE club_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $club_id);
        
        if ($stmt->execute()) {
            header("Location: club_list.php?action=disbanded");
        } else {
            header("Location: club_list.php?action=error");
        }
        exit();

    } elseif ($action === 'restore') {
        // restore Active
        $sql = "UPDATE clubs SET club_status = 'Active' WHERE club_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $club_id);
        
        if ($stmt->execute()) {
            header("Location: club_list.php?action=restored");
        } else {
            header("Location: club_list.php?action=error");
        }
        exit();
    }

}

header("Location: club_list.php");
exit();
?>