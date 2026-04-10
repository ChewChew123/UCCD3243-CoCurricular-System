<?php
require('../../database/db_connect.php');

$event_id = $_POST['event_id'];

// STEP 1: mark ALL as Absent first
$update_all = "UPDATE event_participants 
               SET participant_status='Absent' 
               WHERE event_id='$event_id'";
mysqli_query($conn, $update_all);

// STEP 2: mark selected as Attended
if (!empty($_POST['attended'])) {

    foreach ($_POST['attended'] as $user_id) {

        $update = "UPDATE event_participants 
                   SET participant_status='Attended'
                   WHERE event_id='$event_id' 
                   AND user_id='$user_id'";

        mysqli_query($conn, $update);
    }
}

header("Location: view_participants.php?event_id=$event_id&msg=attendance_updated");
exit;
?>