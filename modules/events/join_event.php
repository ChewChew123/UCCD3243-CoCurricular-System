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
    echo "<script>alert('Registration closed. Deadline passed.'); window.location='event_details.php?event_id=$event_id';</script>";
    exit();
}


// check if already joined
$check = "SELECT * FROM event_participants 
          WHERE user_id='$user_id' AND event_id='$event_id'";
$res = mysqli_query($conn, $check);

if (mysqli_num_rows($res) > 0) {
    // already joined
    header("Location: event_details.php?event_id=$event_id&msg=already_joined");
    exit;
}

$sql = "INSERT INTO event_participants (user_id, event_id, participant_status)
        VALUES ('$user_id', '$event_id', 'Registered')";

if (mysqli_query($conn, $sql)) {
    header("Location: event_details.php?event_id=$event_id&msg=joined_success");
} else {
    header("Location: event_details.php?event_id=$event_id&msg=error");
}

exit;
?>