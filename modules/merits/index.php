<?php
/**
 * File: index.php (Merits Module)
 * Path: /modules/merits/index.php
 * Purpose: Track and visualize volunteering hours and community engagement records.
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

// 2. FETCH USER PROFILE FOR SIDEBAR
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// 3. FETCH MERIT RECORDS (Admin: System-wide | Student: Personal)
if ($is_admin) {
    $query = "SELECT m.*, e.event_name, u.full_name as student_name 
              FROM merits m 
              LEFT JOIN events e ON m.event_id = e.event_id 
              JOIN users u ON m.user_id = u.user_id
              ORDER BY m.date_completed DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT m.*, e.event_name 
              FROM merits m 
              LEFT JOIN events e ON m.event_id = e.event_id 
              WHERE m.user_id = ? 
              ORDER BY m.date_completed DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

$all_merits = [];
while ($row = $result->fetch_assoc()) {
    $all_merits[] = $row;
}
$total_records = count($all_merits);

// 4. CALCULATE TOTAL HOURS SUMMARY
if ($is_admin) {
    $total_query = "SELECT SUM(hours) as total FROM merits";
    $t_stmt = $conn->prepare($total_query);
} else {
    $total_query = "SELECT SUM(hours) as total FROM merits WHERE user_id = ?";
    $t_stmt = $conn->prepare($total_query);
    $t_stmt->bind_param("i", $user_id);
}
$t_stmt->execute();
$total_result = $t_stmt->get_result()->fetch_assoc();
$total_hours = $total_result['total'] ?? 0;

// 5. CHART DATA PREPARATION
// Trend: Monthly Engagement (Last 6 Months)
if ($is_admin) {
    $sql_trend = "SELECT DATE_FORMAT(date_completed, '%b %Y') as month_label, SUM(hours) as month_total 
                  FROM merits GROUP BY month_label ORDER BY date_completed ASC";
    $stmt_trend = $conn->prepare($sql_trend);
} else {
    $sql_trend = "SELECT DATE_FORMAT(date_completed, '%b %Y') as month_label, SUM(hours) as month_total 
                  FROM merits WHERE user_id = ? GROUP BY month_label ORDER BY date_completed ASC";
    $stmt_trend = $conn->prepare($sql_trend);
    $stmt_trend->bind_param("i", $user_id);
}
$stmt_trend->execute();
$res_trend = $stmt_trend->get_result();
$trend_labels = []; $trend_data = [];
while($r = $res_trend->fetch_assoc()){
    $trend_labels[] = $r['month_label'];
    $trend_data[] = $r['month_total'];
}

// Distribution: Activity Source (By Organizer) - LIMIT removed to show all data
if ($is_admin) {
    $sql_org = "SELECT organizer, COUNT(*) as count FROM merits GROUP BY organizer";
    $stmt_org = $conn->prepare($sql_org);
} else {
    $sql_org = "SELECT organizer, COUNT(*) as count FROM merits WHERE user_id = ? GROUP BY organizer";
    $stmt_org = $conn->prepare($sql_org);
    $stmt_org->bind_param("i", $user_id);
}
$stmt_org->execute();
$res_org = $stmt_org->get_result();
$org_labels = []; $org_data = [];
while($r = $res_org->fetch_assoc()){
    $org_labels[] = $r['organizer'];
    $org_data[] = $r['count'];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Merit Tracker | Academic Curator</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
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
        .achievement-bloom { position: relative; overflow: hidden; }
        .achievement-bloom::after { content: ''; position: absolute; top: -20%; right: -10%; width: 200px; height: 200px; background: #003f87; opacity: 0.05; border-radius: 50%; pointer-events: none; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body min-h-screen">

<aside class="h-screen w-72 fixed left-0 top-0 bg-white flex flex-col p-6 space-y-8 z-50 border-r border-slate-100 shadow-sm">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
        <div class="text-2xl font-bold tracking-tight text-blue-900 font-headline">Academic Curator</div>
    </div>

    <div class="flex items-center gap-3 px-2 py-4 bg-slate-50 rounded-2xl">
        <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=003f87&color=fff" alt="User">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 truncate"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
        </div>
    </div>

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../../index.php"><span class="material-symbols-outlined">dashboard</span><span class="text-sm font-semibold uppercase tracking-wider">Overview</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../events/index.php"><span class="material-symbols-outlined">event_note</span><span class="text-sm font-semibold uppercase tracking-wider">Events</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../achievements/index.php"><span class="material-symbols-outlined">verified</span><span class="text-sm font-semibold uppercase tracking-wider">Achievements</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php"><span class="material-symbols-outlined">military_tech</span><span class="text-sm font-semibold uppercase tracking-wider">Merits</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../clubs/index.php"><span class="material-symbols-outlined">groups</span><span class="text-sm font-semibold uppercase tracking-wider">Clubs</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../../profile.php"><span class="material-symbols-outlined">person</span><span class="text-sm font-semibold uppercase tracking-wider">My Profile</span></a>
    </nav>

    <div class="pt-6 border-t border-slate-200/50">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-red-600 transition-colors" href="../../logout.php">
            <span class="material-symbols-outlined">logout</span><span class="text-xs font-semibold uppercase tracking-wider">Log Out</span>
        </a>
    </div>
</aside>

<header class="fixed top-0 right-0 left-72 bg-white/80 backdrop-blur-md flex justify-between items-center px-8 py-4 z-40 border-b border-slate-100">
    <div class="flex-1 max-w-xl text-sm font-bold text-primary tracking-widest uppercase">
        Academic Curator | Merit Tracking System
    </div>
</header>

<main class="ml-72 mt-20 p-12 bg-surface min-h-screen">
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="mb-8 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 flex items-center gap-3 rounded-xl shadow-sm">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-bold text-sm">Merit record successfully logged into the system.</span>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-end mb-12">
        <div>
            <span class="block text-xs font-bold uppercase tracking-[0.2em] text-primary mb-2">Curated Contributions</span>
            <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Merit Tracker</h1>
        </div>
        
        <div class="flex flex-col items-end gap-3">
            <?php if ($is_admin): ?>
                <a href="add_merit.php" class="signature-gradient text-white px-10 py-5 rounded-full font-black text-xs uppercase tracking-widest flex items-center gap-3 shadow-xl hover:-translate-y-1 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-lg">add_circle</span> Record Merit
                </a>
            <?php else: ?>
                <span class="bg-slate-200/50 text-slate-500 px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">lock</span> Student View Mode
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 mb-12">
        <div class="col-span-12 md:col-span-8 bg-white p-10 rounded-[2.5rem] achievement-bloom shadow-sm border border-slate-100">
            <div class="flex justify-between items-start mb-10">
                <div>
                    <h3 class="text-2xl font-bold font-headline text-primary mb-1">Volunteering & Engagement</h3>
                    <p class="text-slate-400 text-sm font-medium">
                        <?php echo $is_admin ? "System-wide tracking of student active campus involvement." : "Personal hours accumulated through verified campus involvement."; ?>
                    </p>
                </div>
                <span class="bg-amber-50 text-amber-600 px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border border-amber-100">
                    <?php echo ($total_hours >= 20 || $is_admin) ? 'HONORS STATUS' : 'ACTIVE'; ?>
                </span>
            </div>
            <div class="flex gap-20">
                <div>
                    <span class="block text-6xl font-black font-headline text-slate-800"><?php echo number_format($total_hours, 1); ?></span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2 block">Total Hours</span>
                </div>
                <div>
                    <span class="block text-6xl font-black font-headline text-slate-800"><?php echo str_pad($total_records, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2 block">Records Logged</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
        <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm text-center">
            <h5 class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-6">Engagement Trend (Hours/Month)</h5>
            <div style="height: 250px;"><canvas id="meritTrendChart"></canvas></div>
        </div>
        <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm text-center">
            <h5 class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-6">Activity Source Breakdown</h5>
            <div style="height: 250px;"><canvas id="organizerChart"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-8 py-6 bg-slate-50/50 border-b border-slate-100">
            <h2 class="text-lg font-bold font-headline text-slate-800">Merit History Log</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-50">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Activity & Organizer</th>
                        <?php if ($is_admin): ?>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Student</th>
                        <?php endif; ?>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Completion Date</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Duration</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($total_records > 0): ?>
                        <?php foreach ($all_merits as $merit): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-primary shadow-sm">
                                            <span class="material-symbols-outlined">military_tech</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800"><?php echo htmlspecialchars($merit['event_name'] ?? 'General Activity'); ?></span>
                                            <span class="text-[9px] text-slate-400 font-black uppercase tracking-tighter mt-1">ORG: <?php echo htmlspecialchars($merit['organizer']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <?php if ($is_admin): ?>
                                    <td class="px-8 py-6 text-sm font-bold text-primary"><?php echo htmlspecialchars($merit['student_name']); ?></td>
                                <?php endif; ?>
                                <td class="px-8 py-6 text-sm text-slate-500 font-bold"><?php echo date('d M Y', strtotime($merit['date_completed'])); ?></td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-[10px] font-black uppercase tracking-widest">+ <?php echo number_format($merit['hours'], 1); ?> hrs</span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <?php if ($is_admin): ?>
                                            <a href="edit_merit.php?id=<?php echo $merit['merit_id']; ?>" class="p-2 bg-blue-50 text-primary rounded-lg hover:bg-primary hover:text-white transition-all"><span class="material-symbols-outlined text-lg">edit</span></a>
                                            <a href="delete_merit.php?id=<?php echo $merit['merit_id']; ?>" onclick="return confirm('Remove this record?')" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-all"><span class="material-symbols-outlined text-lg">delete</span></a>
                                        <?php else: ?>
                                            <span class="text-[9px] font-black text-slate-300 uppercase italic">Verified Record</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-8 py-20 text-center text-slate-300 font-bold uppercase text-[10px] tracking-[0.2em]">No engagement records found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Merit Hours Bar Chart
    new Chart(document.getElementById('meritTrendChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($trend_labels); ?>,
            datasets: [{ label: 'Hours', data: <?php echo json_encode($trend_data); ?>, backgroundColor: '#003f87', borderRadius: 8 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
        }
    });

    // 2. Organizer Distribution Doughnut Chart
    new Chart(document.getElementById('organizerChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($org_labels); ?>,
            datasets: [{ data: <?php echo json_encode($org_data); ?>, backgroundColor: ['#003f87', '#fda085', '#00c6ff', '#a18cd1', '#fbc2eb'], borderWidth: 0 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { font: { weight: 'bold', size: 10 }, padding: 15 } } }
        }
    });
</script>

</body>
</html>