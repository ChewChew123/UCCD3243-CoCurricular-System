<?php
// File path: modules/achievements/index.php
session_start();
require('../../includes/db_connect.php');

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_msg = "";

// Check for feedback messages from other pages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') $status_msg = "Achievement added successfully!";
    if ($_GET['msg'] == 'deleted') $status_msg = "Achievement deleted successfully!";
    if ($_GET['msg'] == 'updated') $status_msg = "Achievement updated successfully!";
}

// --- Handle Search and Filter Logic ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_cat = isset($_GET['filter_cat']) ? trim($_GET['filter_cat']) : '';

// Base SQL query to fetch achievements and linked event names
$sql = "SELECT a.achievement_id, a.achievement_title, a.achievement_category, a.level, a.issuer, a.date_received, a.certificate_image, e.event_name 
        FROM achievements a
        LEFT JOIN events e ON a.event_id = e.event_id
        WHERE a.user_id = ?";

$params = [$user_id];
$types = "i";

// Add search condition if keyword is provided
if (!empty($search)) {
    $sql .= " AND (a.achievement_title LIKE ? OR a.issuer LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Add category filter condition
if (!empty($filter_cat)) {
    $sql .= " AND a.achievement_category = ?";
    $params[] = $filter_cat;
    $types .= "s";
}

$sql .= " ORDER BY a.date_received DESC"; 

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// For CHART
// 1. count Category 
$sql_cat = "SELECT achievement_category, COUNT(*) as count FROM achievements WHERE user_id = ? GROUP BY achievement_category";
$stmt_cat = $conn->prepare($sql_cat);
$stmt_cat->bind_param("i", $user_id);
$stmt_cat->execute();
$res_cat = $stmt_cat->get_result();

$cat_labels = [];
$cat_data = [];
while ($row = $res_cat->fetch_assoc()) {
    $cat_labels[] = $row['achievement_category'] ?: 'Uncategorized';
    $cat_data[] = $row['count'];
}

// 2. count level
$sql_lvl = "SELECT level, COUNT(*) as count FROM achievements WHERE user_id = ? GROUP BY level";
$stmt_lvl = $conn->prepare($sql_lvl);
$stmt_lvl->bind_param("i", $user_id);
$stmt_lvl->execute();
$res_lvl = $stmt_lvl->get_result();

$lvl_labels = [];
$lvl_data = [];
while ($row = $res_lvl->fetch_assoc()) {
    $lvl_labels[] = $row['level'] ?: 'None';
    $lvl_data[] = $row['count'];
}
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

        /* --- Search Bar Section Styling --- */
        .search-container {
            margin-top: -35px;
            position: relative;
            z-index: 10;
        }

        .search-card {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
            border-radius: 15px !important;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        /* Fix for white dropdown/placeholder issues */
        .search-input {
            background-color: #0f172a !important;
            border: 1px solid #475569 !important;
            color: #ffffff !important;
        }

        .search-input::placeholder {
            color: #94a3b8 !important;
            font-style: italic;
        }

        /* Forces dropdown options to be readable */
        .form-select option {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }

        /* --- Achievement Card Styling --- */
        .achievement-card {
            background: linear-gradient(90deg, #f29a9a, #f8f481);
            border: 1px solid #334155 !important;
            border-radius: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden; 
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

        .cert-preview-img {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ffffff; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            cursor: pointer;
        }

        .cert-preview-img:hover {
            transform: scale(1.1);
        }

        .card-top-bar {
            height: 6px;
            background: rgba(0,0,0,0.1);
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

<div class="container search-container">
    <div class="card border-0 shadow search-card">
        <div class="card-body p-3">
            <form action="index.php" method="GET" class="row g-2">
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-secondary text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control search-input" 
                               placeholder="🔍 Try: 'Dean List', 'UTAR', or 'Champion'..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="filter_cat" class="form-select search-input text-white">
                        <option value="" class="text-white">All Categories</option>
                        <option value="Academic" <?php echo $filter_cat == 'Academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="Sports" <?php echo $filter_cat == 'Sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="Arts & Culture" <?php echo $filter_cat == 'Arts & Culture' ? 'selected' : ''; ?>>Arts & Culture</option>
                        <option value="Innovation/Tech" <?php echo $filter_cat == 'Innovation/Tech' ? 'selected' : ''; ?>>Innovation & Tech</option>
                        <option value="Leadership" <?php echo $filter_cat == 'Leadership' ? 'selected' : ''; ?>>Leadership</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-warning fw-bold flex-grow-1">Filter</button>
                    <?php if(!empty($search) || !empty($filter_cat)): ?>
                        <a href="index.php" class="btn btn-outline-light" title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container pb-5 mt-4">
    
    <?php if($status_msg != ""): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 bg-success bg-opacity-25 text-success fw-bold d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i> 
            <div><?php echo $status_msg; ?></div>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (count($cat_data) > 0): ?>
<div class="container mb-4">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background-color: #1e293b; border: 1px solid #334155 !important;">
                <div class="card-body p-4 text-center">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Category Distribution</h5>
                    <div style="height: 220px; display: flex; justify-content: center;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background-color: #1e293b; border: 1px solid #334155 !important;">
                <div class="card-body p-4 text-center">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-bar-chart-fill text-info me-2"></i>Achievement Levels</h5>
                    <div style="height: 220px; display: flex; justify-content: center;">
                        <canvas id="levelChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

    <div class="row g-4">
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

                        <h4 class="card-title"><?php echo htmlspecialchars($row['achievement_title']); ?></h4>

                        <div class="info-text">
                            <i class="bi bi-building"></i> 
                            <span><strong>Issuer: </strong><b class="text-dark"><?php echo htmlspecialchars($row['issuer']); ?></b></span>
                        </div>
                        
                        <div class="info-text">
                            <i class="bi bi-calendar3"></i> 
                            <span><strong>Date: </strong> <b class="text-dark"><?php echo htmlspecialchars(date('d M Y', strtotime($row['date_received']))); ?></b></span>
                        </div>

                        <div class="divider"></div>

                        <?php if (!empty($row['achievement_description'])): ?>
                            <p class="text-secondary small mt-2 fst-italic">
                                "<?php echo htmlspecialchars($row['achievement_description']); ?>"
                            </p>
                        <?php endif; ?>

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

                        <?php if (!empty($row['certificate_image'])): ?>
                            <div class="mt-3">
                                <p class="text-muted small mb-1 fw-bold"><i class="bi bi-paperclip"></i> Certificate Attachment:</p>
                                <img src="../../uploads/certificates/<?php echo $row['certificate_image']; ?>" 
                                    class="cert-preview-img" 
                                    onclick="showCertificate(this.src, '<?php echo addslashes($row['achievement_title']); ?>')"
                                    alt="Certificate">
                            </div>
                        <?php endif; ?>
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
                    <i class="bi <?php echo (!empty($search) || !empty($filter_cat)) ? 'bi-search' : 'bi-inbox'; ?> text-secondary" style="font-size: 4rem;"></i>
                    <h3 class="mt-3 fw-bold text-white"><?php echo (!empty($search) || !empty($filter_cat)) ? 'No Results Found' : 'No Achievements Yet'; ?></h3>
                    <p class="text-muted mb-4"><?php echo (!empty($search) || !empty($filter_cat)) ? 'Try adjusting your search keywords or filters.' : 'Your trophy cabinet is empty. Start adding your milestones to build your portfolio!'; ?></p>
                    
                    <?php if(!empty($search) || !empty($filter_cat)): ?>
                        <a href="index.php" class="btn btn-warning btn-lg px-4 rounded-pill shadow-sm fw-bold text-dark">Clear All Filters</a>
                    <?php else: ?>
                        <a href="add_achievement.php" class="btn btn-warning btn-lg px-4 rounded-pill shadow-sm fw-bold text-dark">
                            <i class="bi bi-plus-lg me-1"></i> Add Milestone
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<div class="modal fade" id="certModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="modalTitle">Certificate Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img src="" id="fullCertImage" class="img-fluid" alt="Full Certificate">
            </div>
        </div>
    </div>
</div>

<script>
// Logic to populate and show the Modal
function showCertificate(imgSrc, title) {
    document.getElementById('fullCertImage').src = imgSrc;
    document.getElementById('modalTitle').innerText = title;
    var myModal = new bootstrap.Modal(document.getElementById('certModal'));
    myModal.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
<?php if (count($cat_data) > 0): ?>
  
    Chart.defaults.color = '#cbd5e1';

    Chart.register(ChartDataLabels);

    const percentageTooltip = {
        callbacks: {
            label: function(context) {
                let label = context.label || '';
                if (label) label += ': ';
                let currentValue = context.raw;
                let total = context.chart._metasets[context.datasetIndex].total;
                let percentage = parseFloat((currentValue / total * 100).toFixed(1));
                return label + currentValue + '  (' + percentage + '%)';
            }
        }
    };

    const insideLabelsConfig = {
        color: '#ffffff', 
        font: {
            weight: 'bold',
            size: 14 
        },
        formatter: (value, context) => {
            // Count %
            let total = context.chart._metasets[context.datasetIndex].total;
            let percentage = (value / total * 100).toFixed(1);
            if (percentage < 5) return ''; 
            return percentage + '%'; 
        }
    };

    // === Category Doughnut Chart ===
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($cat_data); ?>,
                backgroundColor: [
                    '#f6d365', '#fda085', '#00c6ff', '#0072ff', '#a18cd1', '#fbc2eb'
                ],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: percentageTooltip,
                datalabels: insideLabelsConfig 
            }
        }
    });

    // === Level Pie Chart  ===
    const ctxLvl = document.getElementById('levelChart').getContext('2d');
    new Chart(ctxLvl, {
        type: 'pie', 
        data: {
            labels: <?php echo json_encode($lvl_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($lvl_data); ?>,
                backgroundColor: [
                    '#ff0844', '#ffb199', '#4facfe', '#00f2fe'
                ],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: percentageTooltip,
                datalabels: insideLabelsConfig 
            }
        }
    });
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>