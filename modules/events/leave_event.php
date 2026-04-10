<?php
require('../../database/db_connect.php');
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
$user_id = $_SESSION['user_id'];
$event_id = $_GET['event_id'];

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

// get expiry date
$check_event = "SELECT register_expired_date FROM events WHERE event_id='$event_id'";
$result = mysqli_query($conn, $check_event);
$event = mysqli_fetch_assoc($result);

$today = date('Y-m-d');

if ($today > $event['register_expired_date']) {
    echo "<script>alert('Cannot leave event after deadline'); window.location='event_details.php?event_id=$event_id';</script>";
    exit();
}

// check if already leave / not joined
$check = "SELECT * FROM event_participants 
          WHERE user_id='$user_id' AND event_id='$event_id'";
$res = mysqli_query($conn, $check);

if (mysqli_num_rows($res) == 0) {
    // never joined
    header("Location: event_details.php?event_id=$event_id&msg=not_joined");
    exit;
}

$sql = "DELETE FROM event_participants 
        WHERE user_id='$user_id' AND event_id='$event_id'";

if (mysqli_query($conn, $sql)) {
    header("Location: event_details.php?event_id=$event_id&msg=left_success");
} else {
    header("Location: event_details.php?event_id=$event_id&msg=error");
}

exit;
?>