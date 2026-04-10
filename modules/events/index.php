<?php // Assigned to Chia Tze Wei ?>

<?php
require('../../database/db_connect.php');
date_default_timezone_set('Asia/Kuala_Lumpur');

$search = $_GET['search'] ?? '';
$min_date = $_GET['min_date'] ?? '';
$max_date = $_GET['max_date'] ?? '';
$min_time = $_GET['min_time'] ?? '';
$max_time = $_GET['max_time'] ?? '';
$sort_name = $_GET['sort_name'] ?? '';
$sort_date = $_GET['sort_date'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';
$sort_club = $_GET['sort_club'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Event Records</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background: #f4f7f6; }
.navbar { margin-bottom: 20px; }
.table-container {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.table th {
    background-color: #343a40;
    color: #fff;
}
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 fixed-top">
    <a class="navbar-brand" href="../../index.php">Dashboard</a>
    <div class="ms-auto">
        <a class="btn btn-success btn-sm me-2" href="add_event.php">+ New Event</a>
        <a class="btn btn-danger btn-sm" href="../../logout.php">Logout</a>
    </div>
</nav>

<div class="container" style="margin-top: 80px;">

<!-- FILTER + SEARCH -->
<form method="GET" action="view_search.php" class="card p-3 mb-4 shadow-sm">
<div class="row g-3">

<div class="col-md-12">
<input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo $search; ?>">
</div>

<div class="col-md-6">
<label class="form-label">Date From</label>
<input type="date" name="min_date" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Date To</label>
<input type="date" name="max_date" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Time From</label>
<input type="time" name="min_time" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Time To</label>
<input type="time" name="max_time" class="form-control">
</div>

<div class="col-12 text-end">
<button type="submit" class="btn btn-primary">Search & Filter</button>
<a href="index.php" class="btn btn-secondary">Reset</a>
</div>

</div>
</form>

<div class="table-container">
<nav class="navbar justify-content-center " style="background-color: #edebe6;">
<h2 class="mb-4 text-center">Event Records</h2>
</nav>
<!-- TYPE FILTER -->
<form method="GET" class="mb-3">
<input type="hidden" name="search" value="<?php echo $search; ?>">

<select name="type_filter" class="form-select w-auto d-inline">
    <option value="">All Types</option>
    <option value="Seminar">Seminar</option>
    <option value="Workshop">Workshop</option>
    <option value="Competition">Competition</option>
    <option value="Volunteering">Volunteering</option>
    <option value="Club Activity">Club Activity</option>
    <option value="Sports">Sports</option>
    <option value="Cultural">Cultural</option>
    <option value="Leadership">Leadership</option>
</select>

<button type="submit" class="btn btn-primary btn-sm">Go</button>
</form>

<form method="POST" action="bulk_delete.php">
<!-- BULK ACTION BUTTONS -->
<div class="d-flex justify-content-between align-items-center mb-3">

    <!-- LEFT SIDE -->
    <div>
        <button type="submit" name="action" value="delete"
            class="btn btn-danger btn-sm"
            onclick="return confirm('Delete selected records?')">
            Delete
        </button>
    </div>

    <!-- RIGHT SIDE -->
    <div class="d-flex gap-2">
        <a href="recently_deleted.php" class="btn btn-warning btn-sm">
            Recently Deleted
        </a>

        <button type="button" class="btn btn-secondary btn-sm" onclick="location.reload();">
            Refresh
        </button>
    </div>
</div>

<table class="table table-bordered table-hover align-middle text-center">
<thead class="table-dark">
<tr>
    <th><input type="checkbox" onclick="toggleAll(this)"></th>
    <th>No.</th>

    <th>
        Event Name
        <a href="?sort_name=asc">🠉</a>
        <a href="?sort_name=desc">🠋</a>
    </th>

    <th>Event Type</th>

    <th>
        Event Date
        <a href="?sort_date=old">🠉</a>
        <a href="?sort_date=new">🠋</a>
    </th>

    <th>Organizer</th>

    <th>Participants</th>

    <th>Actions</th>
</tr>
</thead>

<tbody>

<?php
$count = $offset + 1;
$where = "WHERE events.deleted = 0";

// FILTERS
if (!empty($type_filter)) {
    $where .= " AND event_type = '$type_filter'";
}

if (!empty($search)) {
    $where .= " AND event_name LIKE '%$search%'";
}

/* DATE FILTER */
if (!empty($min_date) && !empty($max_date)) {
    $where .= " AND event_date BETWEEN '$min_date' AND '$max_date'";
} elseif (!empty($min_date)) {
    // if max_date empty → from min_date onward
    $where .= " AND event_date >= '$min_date'";
} elseif (!empty($max_date)) {
    $where .= " AND event_date <= '$max_date'";
}

/* TIME FILTER */
if (!empty($min_time) && !empty($max_time)) {
    $where .= " AND event_time BETWEEN '$min_time' AND '$max_time'";
} elseif (!empty($min_time)) {
    // if max_time empty → from min_time onward
    $where .= " AND event_time >= '$min_time'";
} elseif (!empty($max_time)) {
    $where .= " AND event_time <= '$max_time'";
}

$sel_query = "SELECT events.*, clubs.club_name,
            COUNT(event_participants.participant_id) AS participant_count
            FROM events
            LEFT JOIN clubs ON events.club_id = clubs.club_id
            LEFT JOIN event_participants 
                ON events.event_id = event_participants.event_id
            $where
            GROUP BY events.event_id";

// SORTING
if ($sort_name == 'asc') {
    $sel_query .= " ORDER BY event_name ASC";
} elseif ($sort_name == 'desc') {
    $sel_query .= " ORDER BY event_name DESC";
} elseif ($sort_date == 'new') {
    $sel_query .= " ORDER BY event_date DESC";
} elseif ($sort_date == 'old') {
    $sel_query .= " ORDER BY event_date ASC";
} elseif ($sort_club == 'asc'){
    $sel_query .= " ORDER BY club_name ASC";
} elseif ($sort_club == 'desc'){
    $sel_query .= " ORDER BY club_name DESC";
} else {
    $sel_query .= " ORDER BY event_id DESC";
}

// APPLY LIMIT
$sel_query .= " LIMIT $limit OFFSET $offset";

// TOTAL RECORDS (for pagination)
$total_query = "SELECT COUNT(*) as total 
                FROM events 
                LEFT JOIN clubs ON events.club_id = clubs.club_id 
                WHERE events.deleted = 0";


$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_records = $total_row['total'];

$total_pages = ceil($total_records / $limit);


$result = mysqli_query($conn, $sel_query);

while ($row = mysqli_fetch_assoc($result)) {
?>

<tr>
<td>
<input type="checkbox" name="selected_ids[]" value="<?php echo $row['event_id']; ?>">
</td>

<td><?php echo $count; ?></td>

<td><?php echo $row["event_name"]; ?></td>

<td><?php echo $row["event_type"]; ?></td>

<td><?php echo $row["event_date"]; ?></td>

<td><?php echo $row["club_name"]; ?></td>

<td><?php echo $row["participant_count"]; ?></td>

<td class="d-flex gap-1 justify-content-center">

<!-- VIEW DETAILS -->
<a href="event_details.php?event_id=<?php echo $row['event_id']; ?>"
   class="btn btn-sm btn-info">
   View Details
</a>

</td>
</tr>

<?php $count++; } ?>

</tbody>
</table>
</form>


<!-- PAGINATION -->
<nav>
<ul class="pagination justify-content-center">

<?php if ($page > 1): ?>
<li class="page-item">
<a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&type_filter=<?php echo $type_filter; ?>">Previous</a>
</li>
<?php endif; ?>

<?php for ($i = 1; $i <= $total_pages; $i++): ?>
<li class="page-item <?php if ($i == $page) echo 'active'; ?>">
<a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&type_filter=<?php echo $type_filter; ?>">
<?php echo $i; ?>
</a>
</li>
<?php endfor; ?>

<?php if ($page < $total_pages): ?>
<li class="page-item">
<a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&type_filter=<?php echo $type_filter; ?>">Next</a>
</li>
<?php endif; ?>

</ul>
</nav>

<a href="export_event_excel.php?search=<?php echo $search; ?>&type_filter=<?php echo $type_filter; ?>" class="btn btn-success mb-3">
Export to Excel
</a>

</div>

</div>
<script>
function toggleAll(source) {
    let checkboxes = document.getElementsByName('selected_ids[]');
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>
</body>
</html>
