<?php
/**
 * File: index.php (Achievements Module)
 * Path: /modules/achievements/index.php
 * Purpose: Dashboard for viewing student milestones and honor roll records.
 */
session_start();
require_once '../../includes/db_connect.php';

// 1. AUTHENTICATION CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 2. FETCH USER PROFILE DATA
$u_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$u_stmt = $conn->prepare($u_sql);
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user_data = $u_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// 3. STATUS FEEDBACK MESSAGES
$status_msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'added') $status_msg = "Achievement added successfully!";
    if ($_GET['msg'] == 'deleted') $status_msg = "Achievement removed from honor roll.";
    if ($_GET['msg'] == 'updated') $status_msg = "Achievement record updated.";
}

// 4. SEARCH AND FILTER LOGIC
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_cat = isset($_GET['filter_cat']) ? trim($_GET['filter_cat']) : '';

// Base Query: Admin sees all student data; Student sees only their own
if ($is_admin) {
    $sql = "SELECT a.*, e.event_name, u.full_name as student_name 
            FROM achievements a
            LEFT JOIN events e ON a.event_id = e.event_id
            JOIN users u ON a.user_id = u.user_id WHERE 1=1"; 
} else {
    $sql = "SELECT a.*, e.event_name 
            FROM achievements a
            LEFT JOIN events e ON a.event_id = e.event_id
            WHERE a.user_id = ?";
    $params = [$user_id];
    $types = "i";
}

// Search Filter
if (!empty($search)) {
    $sql .= " AND (a.achievement_title LIKE ? OR a.issuer LIKE ?)";
    $search_param = "%$search%";
    if (!isset($params)) { $params = []; $types = ""; }
    array_push($params, $search_param, $search_param);
    $types .= "ss";
}

// Category Filter
if (!empty($filter_cat)) {
    $sql .= " AND a.achievement_category = ?";
    if (!isset($params)) { $params = []; $types = ""; }
    $params[] = $filter_cat;
    $types .= "s";
}

$sql .= " ORDER BY a.date_received DESC"; 
$stmt = $conn->prepare($sql);
if (!empty($types)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// 5. CHART DATA PREPARATION
// Category Count
if ($is_admin) {
    $sql_cat = "SELECT achievement_category, COUNT(*) as count FROM achievements GROUP BY achievement_category";
    $stmt_cat = $conn->prepare($sql_cat);
} else {
    $sql_cat = "SELECT achievement_category, COUNT(*) as count FROM achievements WHERE user_id = ? GROUP BY achievement_category";
    $stmt_cat = $conn->prepare($sql_cat);
    $stmt_cat->bind_param("i", $user_id);
}
$stmt_cat->execute();
$res_cat = $stmt_cat->get_result();
$cat_labels = []; $cat_data = [];
while ($row = $res_cat->fetch_assoc()) {
    $cat_labels[] = $row['achievement_category'] ?: 'Uncategorized';
    $cat_data[] = $row['count'];
}

// Level Count
if ($is_admin) {
    $sql_lvl = "SELECT level, COUNT(*) as count FROM achievements GROUP BY level";
    $stmt_lvl = $conn->prepare($sql_lvl);
} else {
    $sql_lvl = "SELECT level, COUNT(*) as count FROM achievements WHERE user_id = ? GROUP BY level";
    $stmt_lvl = $conn->prepare($sql_lvl);
    $stmt_lvl->bind_param("i", $user_id);
}
$stmt_lvl->execute();
$res_lvl = $stmt_lvl->get_result();
$lvl_labels = []; $lvl_data = [];
while ($row = $res_lvl->fetch_assoc()) {
    $lvl_labels[] = $row['level'] ?: 'None';
    $lvl_data[] = $row['count'];
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { "primary": "#003f87", "surface": "#f6faff", "on-surface": "#141d23" },
                    fontFamily: { "headline": ["Manrope"], "body": ["Inter"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body min-h-screen">

<?php 
$base_path = "../../"; 
$current_page = "achievements"; 
include '../../includes/sidebar.php'; 
?>

<header class="fixed top-0 right-0 left-72 bg-white/80 backdrop-blur-md flex justify-between items-center px-8 py-4 z-40 border-b border-slate-100">
    <div class="text-sm font-bold text-primary tracking-widest uppercase">Academic Curator | Honor Roll Management</div>
    <div class="flex items-center gap-4">
        <?php if ($is_admin): ?>
            <a href="add_achievement.php" class="signature-gradient text-white px-6 py-2 rounded-full font-bold text-xs uppercase flex items-center gap-2 hover:opacity-90 transition-all">
                <span class="material-symbols-outlined text-sm">add</span> New Achievement
            </a>
        <?php endif; ?>
    </div>
</header>

<main class="ml-72 pt-28 p-12 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-10">
        
        <div>
            <span class="block text-xs font-bold uppercase tracking-[0.2em] text-primary mb-2">Academic Credentials</span>
            <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Student Honor Roll</h1>
            <p class="text-slate-500 mt-2"><?php echo $is_admin ? "Overseeing campus-wide milestones and institutional honors." : "Documenting your personal milestones and co-curricular legacy."; ?></p>
        </div>

        <?php if ($status_msg): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl flex items-center gap-3 shadow-sm">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-bold text-sm"><?php echo $status_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if (count($cat_data) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm text-center">
                    <h5 class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-6">Categorical Distribution</h5>
                    <div style="height: 250px;"><canvas id="categoryChart"></canvas></div>
                </div>
                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm text-center">
                    <h5 class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-6">Achievement Levels</h5>
                    <div style="height: 250px;"><canvas id="levelChart"></canvas></div>
                </div>
            </div>
        <?php endif; ?>

        <section class="bg-white p-4 rounded-full border border-slate-100 shadow-sm flex flex-col md:flex-row gap-4">
            <form action="index.php" method="GET" class="flex-1 flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1 relative w-full">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" name="search" class="w-full bg-slate-50 border-none rounded-full py-3 pl-12 pr-4 text-sm focus:ring-2 focus:ring-primary" placeholder="Search milestones..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="filter_cat" class="w-full md:w-64 bg-slate-50 border-none rounded-full py-3 px-6 text-sm focus:ring-2 focus:ring-primary">
                    <option value="">All Categories</option>
                    <option value="Academic" <?php echo $filter_cat == 'Academic' ? 'selected' : ''; ?>>Academic</option>
                    <option value="Sports" <?php echo $filter_cat == 'Sports' ? 'selected' : ''; ?>>Sports</option>
                    <option value="Arts & Culture" <?php echo $filter_cat == 'Arts & Culture' ? 'selected' : ''; ?>>Arts & Culture</option>
                    <option value="Innovation/Tech" <?php echo $filter_cat == 'Innovation/Tech' ? 'selected' : ''; ?>>Innovation & Tech</option>
                    <option value="Leadership" <?php echo $filter_cat == 'Leadership' ? 'selected' : ''; ?>>Leadership</option>
                </select>
                <button type="submit" class="signature-gradient text-white px-10 py-3 rounded-full font-bold text-sm">Apply Filters</button>
                <?php if ($search || $filter_cat): ?>
                    <a href="index.php" class="p-3 text-slate-400 hover:text-primary transition-all"><span class="material-symbols-outlined">restart_alt</span></a>
                <?php endif; ?>
            </form>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $lvl_color = ($row['level'] == 'International') ? 'text-red-600 bg-red-50' : (($row['level'] == 'National') ? 'text-amber-600 bg-amber-50' : 'text-blue-600 bg-blue-50');
                ?>
                    <article class="bg-white rounded-[2.5rem] overflow-hidden border border-slate-100 shadow-sm hover:-translate-y-2 transition-all group flex flex-col">
                        <div class="p-8 flex-1">
                            <div class="flex justify-between items-start mb-6">
                                <span class="px-4 py-1 bg-slate-100 text-slate-500 rounded-full text-[9px] font-black uppercase tracking-widest"><?php echo htmlspecialchars($row['achievement_category']); ?></span>
                                <span class="px-4 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $lvl_color; ?>"><?php echo htmlspecialchars($row['level']); ?></span>
                            </div>

                            <h4 class="text-xl font-black text-slate-800 font-headline mb-6 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($row['achievement_title']); ?></h4>

                            <div class="space-y-3 mb-8">
                                <?php if ($is_admin): ?>
                                    <div class="flex items-center gap-3 text-xs text-slate-500">
                                        <span class="material-symbols-outlined text-sm">person</span> <strong>Student:</strong> <span class="text-slate-800"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center gap-3 text-xs text-slate-500">
                                    <span class="material-symbols-outlined text-sm">corporate_fare</span> <strong>Issuer:</strong> <span class="text-slate-800"><?php echo htmlspecialchars($row['issuer']); ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500">
                                    <span class="material-symbols-outlined text-sm">calendar_today</span> <strong>Date:</strong> <span class="text-slate-800"><?php echo date('d M Y', strtotime($row['date_received'])); ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500">
                                    <span class="material-symbols-outlined text-sm">emoji_events</span> <strong>Event:</strong> <span class="text-slate-800"><?php echo htmlspecialchars($row['event_name'] ?: 'External Achievement'); ?></span>
                                </div>
                            </div>

                            <?php if ($row['achievement_description']): ?>
                                <div class="p-4 bg-slate-50 rounded-2xl mb-6">
                                    <p class="text-[11px] text-slate-500 leading-relaxed italic line-clamp-2 italic">"<?php echo htmlspecialchars($row['achievement_description']); ?>"</p>
                                </div>
                            <?php endif; ?>

                            <?php if ($row['certificate_image']): ?>
                                <button onclick="showCertificate('../../uploads/certificates/<?php echo $row['certificate_image']; ?>', '<?php echo addslashes($row['achievement_title']); ?>')" class="flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest hover:underline">
                                    <span class="material-symbols-outlined text-sm">attachment</span> View Certificate
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_admin): ?>
                            <div class="p-6 bg-slate-50 border-t border-slate-100 flex gap-4">
                                <a href="edit_achievement.php?id=<?php echo $row['achievement_id']; ?>" class="flex-1 text-center py-3 bg-white border border-slate-200 rounded-full text-[10px] font-black text-slate-600 hover:border-primary hover:text-primary transition-all uppercase">Edit</a>
                                <a href="delete_achievement.php?id=<?php echo $row['achievement_id']; ?>" onclick="return confirm('Remove milestone?')" class="flex-1 text-center py-3 bg-red-50 rounded-full text-[10px] font-black text-red-600 hover:bg-red-600 hover:text-white transition-all uppercase">Delete</a>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                                <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest italic flex items-center justify-center gap-2"><span class="material-symbols-outlined text-xs">verified</span> Verified Milestone</span>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-20 bg-white rounded-[3rem] border border-slate-100 shadow-sm">
                    <span class="material-symbols-outlined text-slate-200 text-[80px] mb-6">trophy</span>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">The trophy cabinet is empty.</h3>
                    <p class="text-slate-400 max-w-sm mx-auto">No achievements match your current filters. Start adding milestones to build your honor roll.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Tailwind Modal -->
<div id="certModal" class="hidden fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modalTitle" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 text-center">
        <!-- Overlay -->
        <div class="fixed inset-0 transition-opacity bg-slate-900/75 backdrop-blur-sm" aria-hidden="true" onclick="hideCertificate()"></div>
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2.5rem] shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative z-10">
            <div class="bg-slate-900 px-6 py-4 flex justify-between items-center text-white">
                <h3 class="text-sm font-black uppercase tracking-widest font-headline" id="modalTitle">Certificate Preview</h3>
                <button type="button" onclick="hideCertificate()" class="text-white/50 hover:text-white transition-colors flex items-center">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="bg-slate-100 p-0 flex justify-center">
                <img src="" id="fullCertImage" class="w-full h-auto max-h-[80vh] object-contain">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
    // Certificate Modal Handler (Tailwind Implementation)
    function showCertificate(imgSrc, title) {
        document.getElementById('fullCertImage').src = imgSrc;
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('certModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function hideCertificate() {
        document.getElementById('certModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Modal Close on Escape Key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hideCertificate();
    });

    <?php if (count($cat_data) > 0): ?>
        Chart.register(ChartDataLabels);
        const chartConfig = {
            plugins: {
                legend: { position: 'right', labels: { font: { weight: 'bold', size: 10 }, padding: 20 } },
                datalabels: { color: '#fff', font: { weight: 'bold' }, formatter: (v, c) => (v / c.chart._metasets[0].total * 100).toFixed(1) + '%' }
            },
            maintainAspectRatio: false
        };

        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_labels); ?>,
                datasets: [{ data: <?php echo json_encode($cat_data); ?>, backgroundColor: ['#003f87', '#fda085', '#00c6ff', '#a18cd1', '#fbc2eb'], borderWidth: 0 }]
            },
            options: chartConfig
        });

        new Chart(document.getElementById('levelChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($lvl_labels); ?>,
                datasets: [{ data: <?php echo json_encode($lvl_data); ?>, backgroundColor: ['#ff0844', '#ffb199', '#4facfe', '#00f2fe'], borderWidth: 0 }]
            },
            options: chartConfig
        });
    <?php endif; ?>
</script>
</body>
</html>