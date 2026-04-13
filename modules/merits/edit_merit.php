<?php
session_start();
require('../../database/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch record with ID
if (isset($_GET['id'])) {
    $merit_id = $_GET['id'];
    $query = "SELECT * FROM merits WHERE merit_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $merit_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

 if (!$data) {
        header("Location: index.php?error=notfound");
        exit();
    }
    
    $event_sql = "SELECT event_id, event_name FROM events ORDER BY event_date DESC";
    $events_result = $conn->query($event_sql);
    
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Merit Record</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
    .container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    
    .form-group { margin-bottom: 20px; }
    label { display: block; font-weight: bold; margin-bottom: 8px; color: #555; }
    
    input[type="text"], input[type="number"], input[type="date"], textarea {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
    }
    
    input:focus, textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }

    .form-actions { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    .btn { padding: 12px 25px; border-radius: 6px; cursor: pointer; border: none; font-size: 1rem; }
    .btn-primary { background: #2ecc71; color: white; margin-right: 10px; }
    .btn-primary:hover { background: #27ae60; }
    .btn-secondary { background: #95a5a6; color: white; text-decoration: none; }
</style>
</head>
<body>
    <div class="container">
        <h2>Modify Merit Data</h2>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin-bottom: 15px;">
                <?php 
                    if ($_GET['error'] == 'empty') echo "Warning: All fields must be filled with a valid format!";
                    if ($_GET['error'] == 'duplicate') echo "Warning: Another record with this exact data already exists!";
                ?>
            </div>
        <?php endif; ?>

        <form action="process_edit_merit.php" method="POST">
            <input type="hidden" name="merit_id" value="<?php echo $data['merit_id']; ?>">

            <div class="form-group">
                <label>Linked Event (Optional):</label>
                <select name="event_id" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem; margin-bottom: 20px;">
                    <option value="">-- Independent Activity (Not linked to an event) --</option>
                    <?php if($events_result && $events_result->num_rows > 0): ?>
                        <?php while($ev = $events_result->fetch_assoc()): ?>
                            <option value="<?php echo $ev['event_id']; ?>" <?php echo ($data['event_id'] == $ev['event_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ev['event_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Organizer:</label>
                <input type="text" name="organizer" value="<?php echo htmlspecialchars($data['organizer']); ?>" required>
            </div>

            <div class="form-group">
                <label>Hours Earned:</label>
                <input type="number" step="0.01" name="hours" value="<?php echo $data['hours']; ?>" required>
            </div>

            <div class="form-group">
                <label>Date Completed:</label>
                <input type="date" name="date_completed" value="<?php echo $data['date_completed']; ?>" required>
            </div>

            <div class="form-group">
                <label>Description:</label>
                <textarea name="merit_description" rows="4"><?php echo htmlspecialchars($data['merit_description']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Merit</button>
                <a href="index.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>