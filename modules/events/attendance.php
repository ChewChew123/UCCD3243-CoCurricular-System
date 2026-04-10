<?php
require('../../database/db_connect.php');

$event_id = $_GET['event_id'];

// GET EVENT TIME FIRST
$event_query = "SELECT event_date, event_time FROM events WHERE event_id='$event_id'";
$event_result = mysqli_query($conn, $event_query);
$event = mysqli_fetch_assoc($event_result);

if (!$event) {
    die("Event not found");
}

$event_time = strtotime($event['event_date'] . ' ' . $event['event_time']);
$current_time = time();

// BLOCK BEFORE EVENT START
if ($current_time < $event_time) {
    echo "
    <div class='container mt-5'>
        <div class='alert alert-danger text-center'>
            Attendance can only be updated AFTER the event starts.
        </div>
        <a href='view_participants.php?event_id=$event_id' class='btn btn-secondary'>
            Back
        </a>
    </div>";
    exit;
}

// GET PARTICIPANTS
$query = "SELECT ep.user_id,
                 ep.participant_status,
                 u.username,
                 u.full_name
          FROM event_participants ep
          JOIN users u ON ep.user_id = u.user_id
          WHERE ep.event_id = '$event_id'";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Attendance</title>
</head>

<body class="bg-light">

<div class="container mt-5">

<h3>Update Attendance</h3>

<form method="POST" action="attendance_update.php">

<input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

<table class="table table-bordered text-center">
    <tr>
        <th>No</th>
        <th>Student ID</th>
        <th>Name</th>
        <th>Attended?</th>
    </tr>

<?php
$count = 1;
while($row = mysqli_fetch_assoc($result)) {
?>
<tr>
    <td><?php echo $count++; ?></td>
    <td><?php echo $row['username']; ?></td>
    <td><?php echo $row['full_name']; ?></td>

    <td>
        <input type="checkbox" name="attended[]" value="<?php echo $row['user_id']; ?>"
        <?php if ($row['participant_status'] == 'Attended') echo 'checked'; ?>>
    </td>
</tr>
<?php } ?>

</table>

<button type="submit" class="btn btn-primary">
    Save Attendance
</button>

</form>

</div>

</body>
</html>