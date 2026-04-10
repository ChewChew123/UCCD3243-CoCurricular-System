<?php
require('../../database/db_connect.php');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=events.xls");

$search = $_GET['search'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';

$query = "SELECT events.*, clubs.club_name 
          FROM events 
          LEFT JOIN clubs ON events.club_id = clubs.club_id 
          WHERE 1";

if (!empty($search)) {
    $query .= " AND event_name LIKE '%$search%'";
}

if (!empty($type_filter)) {
    $query .= " AND event_type = '$type_filter'";
}

$result = mysqli_query($conn, $query);

// Table output (Excel reads HTML table)
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
    <td>{$row['event_name']}</td>
    <td>{$row['event_type']}</td>
    <td>{$row['event_location']}</td>
    <td>{$row['event_date']}</td>
    <td>{$row['event_time']}</td>
    <td>{$row['register_expired_date']}</td>
    <td>{$row['club_name']}</td>
    <td>{$row['date_record']}</td>
    </tr>";
    $count++;
}

echo "</table>";
?>