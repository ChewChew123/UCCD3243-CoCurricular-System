<?php 
require('../../database/db_connect.php'); 
date_default_timezone_set('Asia/Kuala_Lumpur');

$status = ""; 

if(isset($_POST['new']) && $_POST['new']==1){ 
    $event_name = $_REQUEST['event_name']; 
    $event_type = $_REQUEST['event_type']; 
    $event_location = $_REQUEST['event_location']; 
    $event_date = $_REQUEST['event_date']; 
    $event_time = $_REQUEST['event_time']; 
    $register_expired_date = $_REQUEST['register_expired_date'];
    $club_id = $_REQUEST['club_id']; 
    $date_record = date("Y-m-d H:i:s");  
    $poster = NULL;

    // HANDLE FILE UPLOAD
    if (!empty($_FILES['event_poster']['name'])) {

        $target_dir = "uploads/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["event_poster"]["name"]);
        $target_file = $target_dir . $file_name;

        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // validation
        $allowed = ["jpg", "jpeg", "png", "pdf"];

        if (in_array($file_type, $allowed)) {
            move_uploaded_file($_FILES["event_poster"]["tmp_name"], $target_file);
            $poster = $file_name;
        }
    }

    $ins_query = "INSERT INTO events (event_name, event_type, event_location, event_date, event_time, club_id, date_record, event_poster, register_expired_date)
                VALUES ('$event_name','$event_type','$event_location','$event_date','$event_time','$club_id','$date_record','$poster','$register_expired_date')";

    mysqli_query($conn,$ins_query) or die(mysqli_error($conn)); 

    $status = "New Event Added Successfully!"; 
}

?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add New Event</title> 

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
}
.card {
    border-radius: 15px;
}
</style>

</head> 

<body>

<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="index.php">Main Menu</a>
    <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm me-2" href="index.php">View Events</a>
        <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
    </div>
</nav>

<div class="container d-flex justify-content-center align-items-center" style="min-height:90vh;">

<div class="card shadow-lg p-4" style="width: 100%; max-width: 500px;">

<h3 class="text-center mb-4">Add New Event</h3>

<?php if($status != ""): ?>
<div class="alert alert-success text-center">
    <?php echo $status; ?> <br>
    <a href="index.php">View Event Records</a>
</div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
<input type="hidden" name="new" value="1" />

<div class="mb-3">
<label class="form-label">Event Name</label>
<input type="text" name="event_name" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Event Type</label>
<select name="event_type" class="form-select" required>
<option value="">-- Select Option --</option>
<option>Seminar</option>
<option>Workshop</option>
<option>Competition</option>
<option>Volunteering</option>
<option>Club Activity</option>
<option>Sports</option>
<option>Cultural</option>
<option>Leadership</option>
</select>
</div>

<div class="mb-3">
<label class="form-label">Event Location</label>
<input type="text" name="event_location" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Event Date</label>
<input type="date" name="event_date" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Event Time</label>
<input type="time" name="event_time" class="form-control" required>
</div>

<div class="mb-3">
    <label class="form-label">Event Poster (PDF / Image)</label>
    <input type="file" name="event_poster" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
    <small class="text-muted">Allowed: JPG, PNG, PDF</small>
</div>

<div class="mb-3">
    <label class="form-label">Register Expired Date</label>
    <input type="date" name="register_expired_date" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Organizer</label>
<select name="club_id" class="form-select" required>
    <option value="">-- Select Club --</option>
    <?php 
    $club_query = "SELECT club_id, club_name FROM clubs ORDER BY club_name ASC";
    $club_result = mysqli_query($conn, $club_query);
    while($club = mysqli_fetch_assoc($club_result)) { ?>
        <option value="<?php echo $club['club_id']; ?>">
            <?php echo $club['club_name']; ?>
        </option>
    <?php } ?>

</select>

<div class="mt-2 text-end">
    <small>
        No club registered? 
        <a href="../clubs/index.php" class="text-decoration-none fw-bold">
            Register here
        </a>
    </small>
</div>

</div>


<div class="d-grid">
<button type="submit" class="btn btn-primary">Add Event</button>
</div>

</form>

</div>

</div>

</body>
</html>