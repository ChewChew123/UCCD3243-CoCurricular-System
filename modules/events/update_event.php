<?php  
require('../../database/db_connect.php'); 
date_default_timezone_set('Asia/Kuala_Lumpur');

$event_id = $_REQUEST['event_id']; 

$query = "SELECT * FROM events WHERE event_id='".$event_id."'";  
$result = mysqli_query($conn, $query) or die(mysqli_error($conn)); 
$row = mysqli_fetch_assoc($result); 

$status = ""; 

if(isset($_POST['new']) && $_POST['new']==1){ 

    $event_id = $_REQUEST['event_id']; 
    $event_name = $_REQUEST['event_name']; 
    $event_type = $_REQUEST['event_type']; 
    $event_location = $_REQUEST['event_location']; 
    $event_date = $_REQUEST['event_date']; 
    $event_time = $_REQUEST['event_time']; 
    $register_expired_date = $_REQUEST['register_expired_date'];
    $club_id = $_REQUEST['club_id'];
    $date_record = date("Y-m-d H:i:s");
    $poster = $row['event_poster']; // keep old file by default

    // HANDLE NEW UPLOAD
    if (!empty($_FILES['event_poster']['name'])) {

        $target_dir = "uploads/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["event_poster"]["name"]);
        $target_file = $target_dir . $file_name;

        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed = ["jpg", "jpeg", "png", "pdf"];

        if (in_array($file_type, $allowed)) {
            move_uploaded_file($_FILES["event_poster"]["tmp_name"], $target_file);

            // OPTIONAL: delete old file
            if (!empty($row['event_poster']) && file_exists($target_dir . $row['event_poster'])) {
                unlink($target_dir . $row['event_poster']);
            }

            $poster = $file_name;
        }
    } 

    $update = "UPDATE events SET 
        date_record='$date_record',
        event_name='$event_name',
        event_type='$event_type',
        event_location='$event_location',
        event_date='$event_date',
        event_time='$event_time',
        register_expired_date='$register_expired_date',
        club_id = '$club_id',
        event_poster='$poster'
    WHERE event_id='$event_id'"; 

    mysqli_query($conn, $update) or die(mysqli_error($conn)); 

    $status = "Event Updated Successfully!"; 
}
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
<meta charset="utf-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Update Event</title> 

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #43cea2, #185a9d);
    min-height: 100vh;
}
.card {
    border-radius: 15px;
}
</style>

</head> 

<body>

<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="../../index.php">Dashboard</a>
    <div class="ms-auto">
        <a class="btn btn-outline-light btn-sm me-2" href="addEvent.php">Add Event</a>
        <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
    </div>
</nav>

<div class="container d-flex justify-content-center align-items-center" style="min-height:90vh;">

<div class="card shadow-lg p-4" style="width: 100%; max-width: 500px;">

<h3 class="text-center mb-4">Update Event</h3>

<?php if($status != ""): ?>
<div class="alert alert-success text-center">
    <?php echo $status; ?> <br>
    <a href="event_details.php?event_id=<?php echo $row['event_id']; ?>">View Updated Record</a>
</div>
<?php endif; ?>

<form method="post" action="" enctype="multipart/form-data">
<input type="hidden" name="new" value="1" />
<input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">

<div class="mb-3">
<label class="form-label">Event Name</label>
<input type="text" name="event_name" class="form-control" value="<?php echo $row['event_name']; ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Event Type</label>
<select name="event_type" class="form-select" required>
<option value="">-- Select Option --</option>
<?php
$types = ["Seminar","Workshop","Competition","Volunteering","Club Activity","Sports","Cultural","Leadership"];
foreach($types as $type){
    $selected = ($row['event_type'] == $type) ? 'selected' : '';
    echo "<option value='$type' $selected>$type</option>";
}
?>
</select>
</div>

<div class="mb-3">
<label class="form-label">Event Location</label>
<input type="text" name="event_location" class="form-control" value="<?php echo $row['event_location']; ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Event Date</label>
<input type="date" name="event_date" class="form-control" value="<?php echo $row['event_date']; ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Event Time</label>
<input type="time" name="event_time" class="form-control" value="<?php echo $row['event_time']; ?>" required>
</div>

<div class="mb-3">
    <label class="form-label">Event Poster</label>
    <input type="file" name="event_poster" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
    
    <small class="text-muted">
        Current: 
        <?php if (!empty($row['event_poster'])): ?>
            <?php echo $row['event_poster']; ?>
        <?php else: ?>
            No file
        <?php endif; ?>
    </small>
</div>

<div class="mb-3">
    <label class="form-label">Register Expired Date</label>
    <input type="date" name="register_expired_date" class="form-control"
           value="<?php echo $row['register_expired_date']; ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Organizer</label>
<select name="club_id" class="form-select" required>
    <option value="">-- Select Club --</option>
    <?php 
    $club_query = "SELECT club_id, club_name FROM clubs ORDER BY club_name ASC";
    $club_result = mysqli_query($conn, $club_query);

    while($club = mysqli_fetch_assoc($club_result)) { 
        $selected = ($row['club_id'] == $club['club_id']) ? 'selected' : '';
    ?>
        <option value="<?php echo $club['club_id']; ?>" <?php echo $selected; ?>>
            <?php echo $club['club_name']; ?>
        </option>
    <?php } ?>
</select>
</div>

<div class="d-grid gap-2">
    <button type="submit" class="btn btn-warning">
        Update Event
    </button>

    <a href="index.php" class="btn btn-secondary">
        Cancel
    </a>
</div>

</form>

</div>

</div>

</body> 
</html>