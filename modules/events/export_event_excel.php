<?php
/**
 * File: export_event_excel.php
 * Purpose: Export filtered event list to Excel.
 */
session_start();
// 1. Unified Path Check
require_once '../../includes/db_connect.php'; 

// 2. Security Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=campus_events_report.xls");

$search = $_GET['search'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';

// 3. Logic Fix: Only export events that are NOT deleted (deleted = 0)
$query = "SELECT events.*, clubs.club_name 
          FROM events 
          LEFT JOIN clubs ON events.club_id = clubs.club_id 
          WHERE events.deleted = 0";

if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $query .= " AND event_name LIKE '%$safe_search%'";
}

if (!empty($type_filter)) {
    $safe_type = mysqli_real_escape_string($conn, $type_filter);
    $query .= " AND event_type = '$safe_type'";
}

$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr>
    <th>No</th>
    <th>Event Name</th>
    <th>Event Type</th>
    <th>Event Location</th>
    <th>Event Date</th>
    <th>Event Time</th>
    <th>Register Deadline</th>
    <th>Organizer</th>
    <th>Date Recorded</th>
</tr>";

$count = 1;
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>
    <td>$count</td>
    <td>".htmlspecialchars($row['event_name'])."</td>
    <td>".htmlspecialchars($row['event_type'])."</td>
    <td>".htmlspecialchars($row['event_location'])."</td>
    <td>".htmlspecialchars($row['event_date'])."</td>
    <td>".htmlspecialchars($row['event_time'])."</td>
    <td>".htmlspecialchars($row['register_expired_date'])."</td>
    <td>".htmlspecialchars($row['club_name'])."</td>
    <td>".htmlspecialchars($row['date_record'])."</td>
    </tr>";
    $count++;
}
echo "</table>";
?>