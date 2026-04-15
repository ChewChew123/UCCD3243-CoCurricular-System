<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$event_id = $_GET['event_id'] ?? null;

if ($event_id) {
    $stmt = $conn->prepare("UPDATE events SET deleted = 1 WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
}

header("Location: index.php");
exit();
?>