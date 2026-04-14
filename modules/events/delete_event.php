<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$event_id = $_GET['event_id'];

// optional: also delete participants first
$conn->query("DELETE FROM event_participants WHERE event_id = $event_id");

$stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();

header("Location: index.php");
exit();
?>
