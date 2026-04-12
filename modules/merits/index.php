<?php // Assigned to Beh Jin Yong
session_start();
require('../../database/db_connect.php');

// Check if user has logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Merit from Database
$query = "SELECT * FROM merits WHERE user_id = ? ORDER BY date_completed DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Calculate total hours
$total_query = "SELECT SUM(hours) as total FROM merits WHERE user_id = ?";
$t_stmt = $conn->prepare($total_query);
$t_stmt->bind_param("i", $user_id);
$t_stmt->execute();
$total_result = $t_stmt->get_result()->fetch_assoc();
$total_hours = $total_result['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Merit Tracker - Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; color: #333; }
    .container { max-width: 1000px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    
    h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
    
    .summary-box { background: #e3f2fd; color: #0d47a1; padding: 15px 25px; border-radius: 8px; font-size: 1.1rem; border-left: 5px solid #2196f3; margin-bottom: 25px; }

    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; overflow: hidden; border-radius: 8px; }
    th { background-color: #34495e; color: white; text-align: left; padding: 15px; }
    td { padding: 12px 15px; border-bottom: 1px solid #eee; }
    tr:hover { background-color: #f1f1f1; }

    .btn { padding: 10px 18px; text-decoration: none; border-radius: 5px; font-weight: 600; display: inline-block; transition: 0.3s; }
    .btn-primary { background: #3498db; color: white; border: none; }
    .btn-primary:hover { background: #2980b9; }
    
    .action-links a { margin-right: 10px; font-weight: bold; }
    .edit-link { color: #f39c12; }
    .delete-link { color: #e74c3c; }
</style>
</head>
<body>

<div class="container">
    <h1>Merit Tracker Module</h1>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success">Action completed successfully! List refreshed.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'deleted'): ?>
        <div class="alert alert-success">Record has been removed.</div>
    <?php endif; ?>

    <div class="summary-box">
        <strong>Total Merit Hours: </strong> <?php echo number_format($total_hours, 2); ?> hrs
    </div>

    <div style="margin-bottom: 20px;">
        <a href="add_merit.php" class="btn btn-primary">Add New Merit</a>
        <a href="../../index.php" class="btn">Main Menu</a>
        <a href="../../logout.php" class="btn" style="color: red;">Logout</a>
    </div>

    <table border="1" width="100%" cellpadding="10" style="border-collapse: collapse;">
        <thead>
            <tr style="background-color: #eee;">
                <th>Organizer</th>
                <th>Date Completed</th>
                <th>Hours</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['organizer']); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['date_completed'])); ?></td>
                        <td><?php echo number_format($row['hours'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['merit_description']); ?></td>
                        <td>
                            <a href="edit_merit.php?id=<?php echo $row['merit_id']; ?>">Edit</a> | 
                            
                            <a href="delete_merit.php?id=<?php echo $row['merit_id']; ?>" 
                               onclick="return confirm('Confirm Delete? This action cannot be undone.')" 
                               style="color: red;">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No records found. Start by adding your first merit!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>