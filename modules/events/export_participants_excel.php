<?php
require('../../database/db_connect.php');

$event_id = $_GET['event_id'];

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=participants_event_$event_id.xls");

$query = "SELECT ep.*, 
                 u.username, 
                 u.full_name, 
                 u.email, 
                 u.programme
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
        <td>{$row['username']}</td>
        <td>{$row['full_name']}</td>
        <td>{$row['email']}</td>
        <td>{$row['programme']}</td>
        <td>{$row['participant_status']}</td>
    </tr>";
    $count++;
}

echo "</table>";
?>