<?php
/**
 * File: export_participants_excel.php
 * Purpose: Export event participant list to Excel for Admin use.
 */
session_start();
// 1. Unified Path Check
require_once '../../includes/db_connect.php'; 

// 2. Security Check: Only Admins should be able to export student lists
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized Access.");
}

$event_id = intval($_GET['event_id']);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=participants_event_$event_id.xls");

// Fetch participant details joined with user data
$query = "SELECT ep.*, u.username, u.full_name, u.email, u.programme
          FROM event_participants ep
          JOIN users u ON ep.user_id = u.user_id
          WHERE ep.event_id = '$event_id'";

$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr>
    <th>No</th>
    <th>Student ID</th>
    <th>Full Name</th>
    <th>Email</th>
    <th>Programme</th>
    <th>Status</th>
</tr>";

$count = 1;
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>
        <td>$count</td>
        <td>".htmlspecialchars($row['username'])."</td>
        <td>".htmlspecialchars($row['full_name'])."</td>
        <td>".htmlspecialchars($row['email'])."</td>
        <td>".htmlspecialchars($row['programme'])."</td>
        <td>".htmlspecialchars($row['participant_status'])."</td>
    </tr>";
    $count++;
}
echo "</table>";
?>