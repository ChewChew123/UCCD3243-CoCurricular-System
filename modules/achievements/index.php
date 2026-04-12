<?php
// 文件路径: modules/achievements/index.php
session_start();
require('../../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_msg = "";

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') $status_msg = "Achievement added successfully!";
    if ($_GET['msg'] == 'deleted') $status_msg = "Achievement deleted successfully!";
    if ($_GET['msg'] == 'updated') $status_msg = "Achievement updated successfully!";
}

$sql = "SELECT a.achievement_id, a.achievement_title, a.achievement_category, a.level, a.issuer, a.date_received, e.event_name 
        FROM achievements a
        LEFT JOIN events e ON a.event_id = e.event_id
        WHERE a.user_id = ? 
        ORDER BY a.date_received DESC"; 

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Honor Roll | Academic Curator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
       
        body { 
            background: linear-gradient(90deg, #ebce89, #3012f3) !important; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #e2e8f0;
            min-height: 100vh;
        }
        
        .page-header {
            background-color: #1e293b;
            padding: 45px 0;
            margin-top: 56px; 
            border-bottom: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .page-header h1 {
            color: #f8fafc;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .page-header p {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        .achievement-card {
            background: linear-gradient(90deg, #f29a9a, #f8f481);
            border: 1px solid #020a17 !important;
            border-radius: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .achievement-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.5);
            border-color: #1e5db4 !important;
        }

        .card-body {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .cat-label {
            background-color: #000000 !important; 
            color: #ffffff !important;           
            
            
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            
            
            height: 24px !important;            
            padding: 0 12px !important;          
            border-radius: 50px !important;      
            
           
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1 !important;
            
            padding-top: 1px !important; 
        }

        
        .cat-label i {
            color: #ffffff !important; 
            margin-right: 5px !important;
            font-size: 0.85rem !important;
        }

       
        .level-badge {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 24px !important;
            padding: 0 12px !important;
            line-height: 1 !important;
            padding-top: 1px !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            border-radius: 50px !important;
            text-transform: uppercase;
        }


        .card-title {
            color: #0f2e4e;
            font-weight: 700;
            font-size: 1.3rem;
            margin-top: 15px;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .info-text {
            color: #203e62;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .info-text i {
            color: #3514ef;
            font-size: 1.1rem;
        }

        .divider {
            height: 1px;
            background-color: #0a2751;
            margin: 15px 0;
        }

        .card-footer {
            background-color: transparent !important;
            border-top: 1px solid #102d55 !important;
            padding: 1.2rem 1.5rem;
            display: flex;
            gap: 10px;
        }

        .btn-action {
            border-radius: 8px;
            font-weight: 600;
            flex-grow: 1;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 fixed-top shadow">
    <a class="navbar-brand fw-bold" href="../../index.php"><i class="bi bi-grid-fill me-2"></i>Dashboard</a>
    <div class="ms-auto">
        <a class="btn btn-warning btn-sm fw-bold px-3 shadow-sm rounded-pill text-dark" href="add_achievement.php">
            <i class="bi bi-plus-lg"></i> Add Achievement
        </a>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-trophy-fill me-3" style="color: #f6d365;"></i>My Honor Roll</h1>
        <p>Track and celebrate your academic and co-curricular milestones.</p>
    </div>
</div>

<div class="container pb-5" style="margin-top: -20px;">
    
    <?php if($status_msg != ""): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 bg-success bg-opacity-25 text-success fw-bold d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i> 
            <div><?php echo $status_msg; ?></div>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mt-2">
        <?php 
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { 
                
                $level_color = "bg-secondary text-white";
                if ($row['level'] == 'International') $level_color = "bg-danger text-white";
                if ($row['level'] == 'National') $level_color = "bg-warning text-dark";
                if ($row['level'] == 'State') $level_color = "bg-primary text-white";
                if ($row['level'] == 'University') $level_color = "bg-info text-dark";
        ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="achievement-card">
                    <div class="card-top-bar"></div>
                    <div class="card-body">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="cat-label">
                                <i class="bi bi-tag-fill"></i>
                                <?php echo htmlspecialchars(trim($row['achievement_category'])); ?>
                            </div>
                            
                            <span class="badge <?php echo $level_color; ?> level-badge">
                                <?php echo htmlspecialchars(trim($row['level'])); ?>
                            </span>
                        </div>

                        <h4 class="card-title">
                            <?php echo htmlspecialchars($row['achievement_title']); ?>
                        </h4>

                        <div class="info-text">
                            <i class="bi bi-building"></i> 
                            <span><strong>Issuer: </strong><b class="text-dark"><?php echo htmlspecialchars($row['issuer']); ?></b></span>
                        </div>
                        
                        <div class="info-text">
                            <i class="bi bi-calendar3"></i> 
                            <span><strong>Date: </strong> <b class="text-dark"><?php echo htmlspecialchars(date('d M Y', strtotime($row['date_received']))); ?></b></span>
                        </div>

                        <div class="divider"></div>

                        <div class="info-text m-0">
                            <i class="bi bi-geo-alt-fill text-primary"></i> 
                            <span>
                                <?php if (!empty($row['event_name'])): ?>
                                    Event: <strong class="text-dark"><?php echo htmlspecialchars($row['event_name']); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Off-Campus Achievement</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="edit_achievement.php?id=<?php echo $row['achievement_id']; ?>" class="btn btn-outline-primary btn-sm btn-action">
                            <i class="bi bi-pencil-square me-1"></i> Edit
                        </a>
                        <a href="delete_achievement.php?id=<?php echo $row['achievement_id']; ?>" class="btn btn-outline-danger btn-sm btn-action" onclick="return confirm('Delete this milestone forever?');">
                            <i class="bi bi-trash3 me-1"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        <?php 
            }
        } else {
        ?>
            <div class="col-12 text-center py-5">
                <div class="p-5 rounded-4 shadow" style="background-color: #061e44; border: 1px dashed #0d274b; max-width: 500px; margin: 0 auto;">
                    <i class="bi bi-inbox text-secondary" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 fw-bold text-white">No Achievements Yet</h3>
                    <p class="text-muted mb-4">Your trophy cabinet is empty. Start adding your academic and co-curricular milestones to build your portfolio!</p>
                    <a href="add_achievement.php" class="btn btn-warning btn-lg px-4 rounded-pill shadow-sm fw-bold text-dark">
                        <i class="bi bi-plus-lg me-1"></i> Add Milestone
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>