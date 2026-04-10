<?php
require('../../database/db_connect.php');

$event_id = $_GET['event_id'] ?? 0;

$query = "SELECT events.*, clubs.club_name 
          FROM events 
          LEFT JOIN clubs ON events.club_id = clubs.club_id 
          WHERE event_id='$event_id'";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("Event not found");
}

$participant_count = 0;

$count_query = "SELECT COUNT(*) AS total 
                FROM event_participants 
                WHERE event_id='$event_id'";

$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);

$participant_count = $count_row['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Event Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#f4f7f6;">

<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="index.php">← Back</a>
</nav>

<div class="container mt-5">

<?php if (isset($_GET['msg'])): ?>

<?php if ($_GET['msg'] == 'joined_success'): ?>
<script>alert("You have successfully joined the event!");</script>

<?php elseif ($_GET['msg'] == 'left_success'): ?>
<script>alert("You have left the event successfully!");</script>

<?php elseif ($_GET['msg'] == 'already_joined'): ?>
<script>alert("You already joined this event!");</script>

<?php elseif ($_GET['msg'] == 'not_joined'): ?>
<script>alert("You have not joined this event yet!");</script>

<?php endif; ?>

<?php endif; ?>

<div class="card shadow p-4">

<h3 class="mb-4">Event Details</h3>

<table class="table table-bordered">

<tr>
<th>Event Name</th>
<td><?php echo $row['event_name']; ?></td>
</tr>

<tr>
<th>Event Type</th>
<td><?php echo $row['event_type']; ?></td>
</tr>

<tr>
<th>Event Location</th>
<td><?php echo $row['event_location']; ?></td>
</tr>

<tr>
<th>Event Date</th>
<td><?php echo $row['event_date']; ?></td>
</tr>

<tr>
<th>Event Poster</th>
<td>
<?php if (!empty($row['event_poster'])): ?>

<?php $file = "uploads/" . $row['event_poster']; ?>

<!-- CLICK IMAGE -->
<img src="<?php echo $file; ?>" 
     width="150" 
     class="img-thumbnail"
     style="cursor:pointer;"
     data-bs-toggle="modal"
     data-bs-target="#posterModal">

<!-- MODAL -->
<div class="modal fade" id="posterModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">

      <div class="modal-body text-center position-relative p-0">

        <!-- CLOSE BUTTON -->
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2"
                data-bs-dismiss="modal"></button>

        <!-- DOWNLOAD LINK (TOP RIGHT) -->
        <a href="<?php echo $file; ?>" download
           class="position-absolute top-0 end-0 m-2 text-white fw-bold text-decoration-underline"
           style="z-index: 10; font-size: 14px;">
            Download
        </a>

        <!-- IMAGE -->
        <img src="<?php echo $file; ?>" class="img-fluid rounded">

      </div>
    </div>
  </div>
</div>

<?php else: ?>
    <span class="text-muted">No poster uploaded</span>
<?php endif; ?>
</td>
</tr>

<tr>
<th>Event Time</th>
<td><?php echo $row['event_time']; ?></td>
</tr>

<tr>
    <th>Register Deadline</th>
    <td><?php echo $row['register_expired_date']; ?></td>
</tr>

<tr>
<th>Organizer</th>
<td><?php echo $row['club_name']; ?></td>
</tr>

<tr>
    <th>Participants</th>
    <td>
        <a href="view_participants.php?event_id=<?php echo $row['event_id']; ?>"
           class="text-decoration-underline text-primary">
            View Participant (<?php echo $participant_count ?? 0; ?>)
        </a>
    </td>
</tr>

<tr>
<th>Date Recorded</th>
<td><?php echo $row['date_record']; ?></td>
</tr>

</table>

<div class="d-flex gap-2">
<!-- EDIT -->
<a href="update_event.php?event_id=<?php echo $row['event_id']; ?>"
   class="btn btn-warning">
   Edit
</a>

<!-- JOIN -->
<a href="join_event.php?event_id=<?php echo $row['event_id']; ?>"
    class="btn btn-success btn-sm"
    onclick="return confirm('Join this event?')">
        Join Event
</a>

<!-- LEAVE EVENT -->
<a href="leave_event.php?event_id=<?php echo $row['event_id']; ?>"
    class="btn btn-danger btn-sm"
    onclick="return confirm('Leave this event?')">
    Leave Event
</a>
</div>

</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>