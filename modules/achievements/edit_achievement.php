<?php
// File path: modules/achievements/edit_achievement.php
session_start();
require('../../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch existing achievement data
$sql = "SELECT * FROM achievements WHERE achievement_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Achievement not found or access denied.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Achievement | Academic Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { 
            background: linear-gradient(90deg, #ebce89, #3012f3) !important; 
            font-family: 'Segoe UI', sans-serif;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 0;
        }
        .edit-container {
            background-color: #1e293b;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 600px;
            border: 1px solid #334155;
        }
        .form-control, .form-select {
            background-color: #0f172a !important;
            border: 1px solid #334155;
            color: #f8fafc !important;
            padding: 12px;
            border-radius: 10px;
            color-scheme: dark;
        }
        .current-cert-preview {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #334155;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="edit-container">
    <h2 class="fw-bold mb-4 text-white"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Milestone</h2>
    
    <form action="process_edit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="achievement_id" value="<?php echo $data['achievement_id']; ?>">

        <div class="mb-3">
            <label class="form-label fw-bold">Achievement Title</label>
            <input type="text" name="achievement_title" class="form-control" value="<?php echo htmlspecialchars($data['achievement_title']); ?>" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Category</label>
                <select name="achievement_category" class="form-select" required>
                    <option value="">-- Select Category --</option>
                    <option value="Academic" <?php if($data['achievement_category'] == 'Academic') echo 'selected'; ?>>Academic</option>
                    <option value="Sports" <?php if($data['achievement_category'] == 'Sports') echo 'selected'; ?>>Sports</option>
                    <option value="Arts & Culture" <?php if($data['achievement_category'] == 'Arts & Culture') echo 'selected'; ?>>Arts & Culture</option>
                    <option value="Innovation/Tech" <?php if($data['achievement_category'] == 'Innovation/Tech') echo 'selected'; ?>>Innovation & Tech</option>
                    <option value="Leadership" <?php if($data['achievement_category'] == 'Leadership') echo 'selected'; ?>>Leadership</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Level</label>
                <select name="level" class="form-select" required>
                    <option value="International" <?php if($data['level'] == 'International') echo 'selected'; ?>>International</option>
                    <option value="National" <?php if($data['level'] == 'National') echo 'selected'; ?>>National</option>
                    <option value="State" <?php if($data['level'] == 'State') echo 'selected'; ?>>State</option>
                    <option value="University" <?php if($data['level'] == 'University') echo 'selected'; ?>>University</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Issuer</label>
            <input type="text" name="issuer" class="form-control" value="<?php echo htmlspecialchars($data['issuer']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Related Campus Event</label>
            <select name="event_id" class="form-select">
                <option value="">-- No linked event (Off-campus) --</option>
                <?php 
                $event_query = "SELECT event_id, event_name FROM events WHERE deleted = 0 ORDER BY event_name ASC";
                $event_result = mysqli_query($conn, $event_query);
                if ($event_result) {
                    while($evt = mysqli_fetch_assoc($event_result)) { 
                        $selected = ($data['event_id'] == $evt['event_id']) ? 'selected' : '';
                        echo "<option value='".$evt['event_id']."' $selected>".$evt['event_name']."</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Description</label>
            <textarea name="achievement_description" class="form-control" rows="3"><?php echo htmlspecialchars($data['achievement_description'] ?? ''); ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Update Certificate (Leave blank to keep current)</label>
            <input type="file" name="certificate_image" class="form-control" accept="image/*">
            
            <?php if (!empty($data['certificate_image'])): ?>
                <div class="mt-2">
                    <small class="text-muted d-block">Current file:</small>
                    <img src="../../uploads/certificates/<?php echo $data['certificate_image']; ?>" class="current-cert-preview">
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-warning fw-bold flex-grow-1 py-2 rounded-pill">Save Changes</button>
            <a href="index.php" class="btn btn-outline-secondary fw-bold px-4 rounded-pill">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>