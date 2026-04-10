<?php
require('../../database/db_connect.php');

// SINGLE RESTORE
if (isset($_GET['restore'])) {
    $id = $_GET['restore'];
    mysqli_query($conn, "UPDATE events SET deleted = 0 WHERE event_id = '$id'");
    header("Location: recently_deleted.php");
    exit;
}

// BULK ACTION
if (isset($_POST['action'])) {
    $ids = $_POST['ids'] ?? [];
    $action = $_POST['action'];

    if ($action == 'permanent_delete') {
        foreach ($ids as $id) {
            mysqli_query($conn, "DELETE FROM events WHERE event_id = '$id'");
        }
    }

    if ($action == 'restore') {
        foreach ($ids as $id) {
            mysqli_query($conn, "UPDATE events SET deleted = 0 WHERE event_id = '$id'");
        }
    }

    header("Location: recently_deleted.php");
    exit;
}
?>