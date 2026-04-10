<?php
require('../../database/db_connect.php');

$query = "
SELECT events.*, clubs.club_name 
FROM events
JOIN clubs ON events.club_id = clubs.club_id
WHERE events.deleted = 1
ORDER BY events.event_id DESC
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recently Deleted</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f7f6;
}

.container-box {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.table th {
    background-color: #343a40;
    color: white;
}
</style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark px-3 mb-4">
    <a class="navbar-brand" href="index.php">← Back</a>
</nav>

<div class="container">
<div class="container-box">

<h3 class="text-center mb-4">🗑 Recently Deleted Records</h3>

<!-- SUCCESS MESSAGE -->
<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success text-center">
    <?php
    if ($_GET['msg'] == 'restored') {
        echo "Records restored successfully!";
    } elseif ($_GET['msg'] == 'deleted') {
        echo "Records permanently deleted!";
    }
    ?>
</div>
<?php endif; ?>

<form method="POST" action="recycle_action.php">

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle text-center">
<thead>
<tr>
<th>
<input type="checkbox" onclick="toggleAll(this)">
</th>
<th>No.</th>
<th>Event Name</th>
<th>Event Type</th>
<th>Event Location</th>
<th>Event Date</th>
<th>Event Time</th>
<th>Poster</th>
<th>Organizer</th>
</tr>
</thead>

<tbody>

<?php 
$count = 1;
while ($row = mysqli_fetch_assoc($result)) { 
?>

<tr>
<td>
<input type="checkbox" name="ids[]" value="<?php echo $row['event_id']; ?>">
</td>

<td><?php echo $count++; ?></td>

<td><?php echo $row['event_name']; ?></td>
<td><?php echo $row['event_type']; ?></td>
<td><?php echo $row['event_location']; ?></td>
<td><?php echo $row['event_date']; ?></td>
<td><?php echo $row['event_time']; ?></td>
<td>
<?php if (!empty($row['event_poster'])): ?>

    <?php $file = "../events/uploads/" . $row['event_poster']; ?>
    <?php $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); ?>

    <?php if ($ext == "pdf"): ?>
        <a href="<?php echo $file; ?>" target="_blank" class="btn btn-sm btn-danger">
            View PDF
        </a>
    <?php else: ?>
        <img src="<?php echo $file; ?>" width="80" class="img-thumbnail">
    <?php endif; ?>

<?php else: ?>
    <span class="text-muted">No file</span>
<?php endif; ?>
</td>
<td><?php echo $row['club_name']; ?></td>
</tr>

<?php } ?>

</tbody>
</table>
</div>

<!-- ACTION BUTTONS -->
<div class="d-flex justify-content-between mt-3">
    <button type="submit" name="action" value="restore" class="btn btn-primary">
        Restore
    </button>

    <button type="submit" name="action" value="permanent_delete" 
        class="btn btn-danger"
        onclick="return confirm('Permanently delete selected records? Your records will be lost forever.')">
        Permanent Delete
    </button>
</div>

</form>
</div>
</div>

<script>
function toggleAll(source) {
    let checkboxes = document.getElementsByName('ids[]');
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

</body>
</html>