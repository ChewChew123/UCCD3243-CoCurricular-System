<?php
require('../../database/db_connect.php');

if (!isset($_POST['selected_ids'])) {
    die("No records selected.");
}

$ids = $_POST['selected_ids'];

if ($_POST['action'] == 'delete') {

    foreach ($ids as $id) {
        mysqli_query($conn, "UPDATE events SET deleted = 1 WHERE event_id = '$id'");
    }

    header("Location: view_search.php");
}
?>