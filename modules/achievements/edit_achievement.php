<?php
// 文件路径: modules/achievements/edit_achievement.php
session_start();
require('../../includes/db_connect.php'); 
date_default_timezone_set('Asia/Kuala_Lumpur');

// 1. 安全检查
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

// 2. 检查 ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$achievement_id = $_GET['id'];

// 3. 处理表单提交 (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['achievement_title'];
    $category = $_POST['achievement_category'];
    $level = $_POST['level'];
    $issuer = $_POST['issuer'];
    $date_received = $_POST['date_received'];
    $description = $_POST['achievement_description'];
    
    $event_id = !empty($_POST['event_id']) ? $_POST['event_id'] : NULL; 

    $update_sql = "UPDATE achievements SET 
                    event_id = ?, 
                    achievement_title = ?, 
                    achievement_description = ?, 
                    achievement_category = ?, 
                    level = ?, 
                    issuer = ?, 
                    date_received = ?
                   WHERE achievement_id = ? AND user_id = ?";
            
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("issssssii", $event_id, $title, $description, $category, $level, $issuer, $date_received, $achievement_id, $user_id);

    if ($stmt->execute()) {
        header("Location: index.php?msg=updated");
        exit();
    } else {
        $error = "Failed to update achievement. Please try again.";
    }
}

// 4. 获取旧数据 (用于填入表单)
$fetch_sql = "SELECT * FROM achievements WHERE achievement_id = ? AND user_id = ?";
$fetch_stmt = $conn->prepare($fetch_sql);
$fetch_stmt->bind_param("ii", $achievement_id, $user_id);
$fetch_stmt->execute();
$result = $fetch_stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Milestone | Academic Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
       <style>
        /* 统一的高级背景色 */
        body { 
            background-color: #5a7ccc; /* 夜空蓝背景 */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #e2e8f0;
        }

        .main-container {
            margin-top: 80px; 
            margin-bottom: 50px;
        }

        /* 表单卡片的高级暗色质感 */
        .form-card {
            background: linear-gradient(90deg, #fda085, #f6d365);
            border: 1px solid #334155;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            overflow: hidden;
        }

        .card-top-bar {
            height: 6px;
            background: linear-gradient(90deg, #fda085, #f6d365);
            width: 100%;
        }

        /* 统一输入框和下拉菜单的颜色 */
        .form-control, .form-select {
            background-color: #0f172a !important; /* 强制深色 */
            border: 1px solid #334155;
            color: #f8fafc !important; /* 强制白字 */
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .form-control:focus, .form-select:focus {
            background-color: #0f172a;
            border-color: #fda085;
            color: #f8fafc;
            box-shadow: 0 0 0 3px rgba(253, 160, 133, 0.15);
        }

        /* 修复下拉选项的颜色 */
        .form-select option {
            background-color: #1e293b;
            color: #f8fafc;
        }

        /* 解决浏览器自动填充导致输入框变白/变黄的问题 */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0f172a inset !important;
            -webkit-text-fill-color: #f8fafc !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .form-label {
            color: #2c3948;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .icon-prepend {
            color: #fda085;
            margin-right: 8px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            color: #1e293b;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 15px;
            border-radius: 10px;
            transition: transform 0.2s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(253, 160, 133, 0.2);
        }

        /* 强制让日期输入框自带的日历小图标变成白色 */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
        
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 fixed-top shadow-sm">
    <a class="navbar-brand fw-bold" href="../../index.php"><i class="bi bi-grid-fill me-2"></i>Dashboard</a>
    <div class="ms-auto">
        <a class="btn btn-outline-secondary border-0 btn-sm me-2" href="index.php">
            <i class="bi bi-arrow-left"></i> Back to Roll
        </a>
    </div>
</nav>

<div class="container main-container d-flex justify-content-center align-items-center">
    
    <div class="form-card w-100" style="max-width: 650px;">
        <div class="card-top-bar"></div>
        <div class="p-4 p-md-5">
            
            <div class="text-center mb-5">
                <div class="d-inline-block bg-dark rounded-circle p-3 mb-3 border border-secondary border-opacity-25 shadow-sm">
                    <i class="bi bi-pencil-square text-warning fs-2"></i>
                </div>
                <h3 class="fw-bold text-white">Edit Milestone</h3>
                <p class="text-muted">Update the details of your achievement.</p>
            </div>

            <?php if($error != ""): ?>
                <div class="alert alert-danger border-0 rounded-3 text-center bg-danger bg-opacity-25 text-danger fw-bold">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                
                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-fonts icon-prepend"></i>Achievement Title</label>
                    <input type="text" name="achievement_title" class="form-control" value="<?php echo htmlspecialchars($row['achievement_title']); ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold"><i class="bi bi-tags-fill icon-prepend"></i>Category</label>
                        <select name="achievement_category" class="form-select" required>
                            <option value="Academic" <?php if($row['achievement_category'] == 'Academic') echo 'selected'; ?>>Academic</option>
                            <option value="Sports" <?php if($row['achievement_category'] == 'Sports') echo 'selected'; ?>>Sports</option>
                            <option value="Arts & Culture" <?php if($row['achievement_category'] == 'Arts & Culture') echo 'selected'; ?>>Arts & Culture</option>
                            <option value="Innovation/Tech" <?php if($row['achievement_category'] == 'Innovation/Tech') echo 'selected'; ?>>Innovation & Tech</option>
                            <option value="Leadership" <?php if($row['achievement_category'] == 'Leadership') echo 'selected'; ?>>Leadership</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-bold"><i class="bi bi-bar-chart-fill icon-prepend"></i>Level</label>
                        <select name="level" class="form-select" required>
                            <option value="University" <?php if($row['level'] == 'University') echo 'selected'; ?>>University</option>
                            <option value="State" <?php if($row['level'] == 'State') echo 'selected'; ?>>State</option>
                            <option value="National" <?php if($row['level'] == 'National') echo 'selected'; ?>>National</option>
                            <option value="International" <?php if($row['level'] == 'International') echo 'selected'; ?>>International</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-building icon-prepend"></i>Issuer / Organizer</label>
                    <input type="text" name="issuer" class="form-control" value="<?php echo htmlspecialchars($row['issuer']); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-calendar-event icon-prepend"></i>Date Received</label>
                    <input type="date" name="date_received" class="form-control" value="<?php echo htmlspecialchars($row['date_received']); ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold"><i class="bi bi-geo-alt-fill icon-prepend"></i>Related Campus Event</label>
                    <select name="event_id" class="form-select">
                        <option value="">-- No linked event (Off-campus) --</option>
                        <?php 
                        $event_query = "SELECT event_id, event_name FROM events WHERE deleted = 0 ORDER BY event_name ASC";
                        $event_result = mysqli_query($conn, $event_query);
                        if ($event_result) {
                            while($evt = mysqli_fetch_assoc($event_result)) { 
                                $selected = ($row['event_id'] == $evt['event_id']) ? "selected" : "";
                                echo "<option value='".$evt['event_id']."' $selected>".$evt['event_name']."</option>";
                            }
                        }
                        ?>
                    </select>
                    <small class="text-secondary mt-1 d-block"><i class="bi bi-info-circle me-1"></i>If earned in a UTAR event, link it here.</small>
                </div>

                <div class="mb-5">
                    <label class="form-label fw-bold"><i class="bi bi-card-text icon-prepend"></i>Description</label>
                    <textarea name="achievement_description" class="form-control" rows="3"><?php echo htmlspecialchars($row['achievement_description']); ?></textarea>
                </div>

                <div class="row g-2 mt-4">
                    <div class="col-sm-8">
                        <button type="submit" class="btn submit-btn w-100 shadow-sm text-uppercase">
                            <i class="bi bi-cloud-arrow-up-fill me-2 fs-5 align-middle"></i>Update Achievement
                        </button>
                    </div>
                    <div class="col-sm-4">
                        <a href="index.php" class="btn cancel-btn w-100 text-uppercase d-flex align-items-center justify-content-center h-100">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>