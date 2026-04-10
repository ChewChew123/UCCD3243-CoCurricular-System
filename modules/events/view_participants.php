<?php
require('../../database/db_connect.php');

$event_id = $_GET['event_id'];

$event_query = "SELECT event_date, event_time FROM events WHERE event_id='$event_id'";
$event_result = mysqli_query($conn, $event_query);
$event = mysqli_fetch_assoc($event_result);

$event_timestamp = strtotime($event['event_date'] . ' ' . $event['event_time']);
$current_timestamp = time();

$query = "SELECT ep.user_id,
            ep.participant_status,
            u.username,
            u.full_name,
            u.email,
            u.programme
          FROM event_participants ep
          JOIN users u ON ep.user_id = u.user_id
          WHERE ep.event_id = '$event_id'";

$result = mysqli_query($conn, $query);
?>

<?php if(isset($_GET['msg']) && $_GET['msg']=='attendance_updated'): ?>
<div class="alert alert-success">
    Attendance updated successfully!
</div>
<?php endif; ?>

<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Participants</title>
</head>

<body class="bg-light">

<div class="container mt-5">

<h3>Event Participants</h3>

<table class="table table-bordered">
<tr>
    <th>No</th>
    <th>Student ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Programme</th>
    <th>Status</th>
</tr>

<?php
$count = 1;
while($row = mysqli_fetch_assoc($result)) {
?>
<tr>
    <td><?php echo $count++; ?></td>
    <td><?php echo $row['username']; ?></td>
    <td><?php echo $row['full_name']; ?></td>
    <td><?php echo $row['email']; ?></td>
    <td><?php echo $row['programme']; ?></td>
    <td><?php echo $row['participant_status'] ?></td>
</tr>
<?php } ?>
</table>

<div class="d-flex gap-2 mb-3">
    <a href="event_details.php?event_id=<?php echo $event_id; ?>" 
       class="btn btn-secondary btn-sm">
        Back
    </a>

    <a href="export_participants_excel.php?event_id=<?php echo $event_id; ?>" 
       class="btn btn-success btn-sm">
        Export to Excel
    </a>

    <div>
    <?php
        $disabled = ($current_timestamp < $event_timestamp) ? 'disabled' : '';
    ?>
    <a href="attendance.php?event_id=<?php echo $event_id; ?>" 
        class="btn btn-primary btn-sm <?php echo $disabled; ?>">
        Update Attendance
    </a>
    </div>
</div>

</div>

</body>
</html>