<?php
session_start();
require('../../database/db_connect.php');

// Check if user has logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Merit Record</title>
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
        <h2>Input Merit Data</h2>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border: 1px solid #f5c6cb;">
                <?php 
                    if ($_GET['error'] == 'empty') echo "Warning: All fields must be filled with a valid format!";
                    if ($_GET['error'] == 'duplicate') echo "Warning: Merit data already exists for this activity on this date!";
                ?>
            </div>
        <?php endif; ?>

        <form action="process_add_merit.php" method="POST">
            <div class="form-group">
                <label>Organizer:</label>
                <input type="text" name="organizer" required placeholder="e.g. Student Council">
            </div>

            <div class="form-group">
                <label>Hours Earned:</label>
                <input type="number" step="0.01" name="hours" required>
            </div>

            <div class="form-group">
                <label>Date Completed:</label>
                <input type="date" name="date_completed" required>
            </div>

            <div class="form-group">
                <label>Description:</label>
                <textarea name="merit_description" rows="4"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Merit</button>
                <a href="index.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>