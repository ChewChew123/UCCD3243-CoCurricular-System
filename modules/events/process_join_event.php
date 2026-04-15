<?php
session_start();
require_once '../../includes/db_connect.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// 1. AUTH CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_GET['event_id']) 
    ? intval($_GET['event_id']) 
    : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if ($event_id <= 0) {
    header("Location: index.php?msg=invalid_event");
    exit();
}

// 2. CHECK EVENT EXISTS + EXPIRY DATE
$event_stmt = $conn->prepare("
    SELECT register_expired_date 
    FROM events 
    WHERE event_id = ?
");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event = $event_stmt->get_result()->fetch_assoc();

if (!$event) {
    header("Location: index.php?msg=event_not_found");
    exit();
}

$today = date('Y-m-d');

// 3. EXPIRY VALIDATION
if (!empty($event['register_expired_date']) && $today > $event['register_expired_date']) {
    echo "<script>
        alert('Registration closed. Deadline has passed.');
        window.location.href = 'index.php?event_id=$event_id';
    </script>";
    exit();
}

// 4. CHECK IF ALREADY JOINED
$check_stmt = $conn->prepare("
    SELECT 1 
    FROM event_participants 
    WHERE user_id = ? AND event_id = ?
    LIMIT 1
");
$check_stmt->bind_param("ii", $user_id, $event_id);
$check_stmt->execute();
$exists = $check_stmt->get_result()->fetch_row();

// 5. PREVENT DUPLICATE JOIN
if ($exists) {
    header("Location: event_details.php?event_id=$event_id&msg=already_joined");
    exit();
}

// 6. INSERT PARTICIPATION
$insert_stmt = $conn->prepare("
    INSERT INTO event_participants (user_id, event_id, participant_status)
    VALUES (?, ?, 'Registered')
");
$insert_stmt->bind_param("ii", $user_id, $event_id);

// 7. RESULT HANDLING
if ($insert_stmt->execute()) {
    header("Location: event_details.php?event_id=$event_id&msg=joined_success");
} else {
    header("Location: event_details.php?event_id=$event_id&msg=error");
}

exit();
?>