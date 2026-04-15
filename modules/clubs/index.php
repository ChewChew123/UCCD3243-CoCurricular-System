<?php
/**
 * File: index.php (Clubs Module)
 * Purpose: Club membership dashboard with Approval Workflow.
 */
session_start();
require_once '../../includes/db_connect.php';

// 1. Session Check: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 2. Fetch User Profile Details for Sidebar
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// --- ADMIN SPECIFIC: FETCH PENDING LIST FOR THE QUEUE ---
$pending_list = [];
if ($is_admin) {
    // 专门抓取状态为 Pending 的申请用于“审批区”
    $p_sql = "SELECT cm.member_id, c.club_name, u.full_name as student_name, cm.member_role 
              FROM club_members cm 
              JOIN clubs c ON cm.club_id = c.club_id 
              JOIN users u ON cm.user_id = u.user_id 
              WHERE cm.member_status = 'Pending' OR cm.member_status IS NULL OR cm.member_status = ''"; // 确保抓到空值
    $p_res = $conn->query($p_sql);
    while($p_row = $p_res->fetch_assoc()) {
        $pending_list[] = $p_row;
    }
    
    // 统计数据库里总共有多少个Club
    $total_clubs_in_db = $conn->query("SELECT COUNT(*) FROM clubs")->fetch_row()[0];
}

// 3. Fetch Memberships based on Role (Original Logic)
if ($is_admin) {
    $sql = "SELECT cm.member_id, c.club_id, c.club_name, c.club_category, cm.member_role, cm.member_status, u.full_name as student_name 
            FROM club_members cm 
            JOIN clubs c ON cm.club_id = c.club_id 
            JOIN users u ON cm.user_id = u.user_id";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT cm.member_id, c.club_id, c.club_name, c.club_category, cm.member_role, cm.member_status 
            FROM club_members cm 
            JOIN clubs c ON cm.club_id = c.club_id 
            WHERE cm.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$memberships = $stmt->get_result();

// 4. Calculate Dashboard Statistics (Original Functionality)
$active_roles = 0;
$pending_apps = 0;
$all_memberships = [];

while ($row = $memberships->fetch_assoc()) {
    // 统一逻辑：如果为空则默认为 Pending (这一步保证了刚加入的学生肯定是Pending)
    if (empty($row['member_status'])) {
        $row['member_status'] = 'Pending';
    }

    $all_memberships[] = $row;
    
    if ($row['member_status'] == 'Active') $active_roles++;
    if ($row['member_status'] == 'Pending') $pending_apps++; 
}

$total_clubs_count = count($all_memberships);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Club Membership Dashboard | The Academic Curator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
          tailwind.config = {
            darkMode: "class",
            theme: {
              extend: {
                "colors": {
                    "primary": "#003f87",
                    "surface": "#f6faff",
                    "background": "#f6faff",
                    "on-surface": "#141d23"
                },
                "fontFamily": {
                    "headline": ["Manrope"],
                    "body": ["Inter"]
                }
              },
            },
          }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
        .achievement-bloom { position: relative; overflow: hidden; }
        .achievement-bloom::after { content: ''; position: absolute; top: -20%; right: -10%; width: 200px; height: 200px; background: #003f87; opacity: 0.05; border-radius: 50%; pointer-events: none; }
    </style>
</head>
<body class="bg-background text-on-background font-body">

<aside class="h-screen w-72 fixed left-0 top-0 bg-white dark:bg-slate-900 flex flex-col p-6 space-y-8 z-50 border-r border-slate-100 dark:border-slate-800 shadow-sm">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
        <div class="text-2xl font-bold tracking-tight text-blue-900 dark:text-blue-100 font-headline">Academic Curator</div>
    </div>

    <div class="flex items-center gap-3 px-2 py-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
        <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=003f87&color=fff" alt="User">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
        </div>
    </div>

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../../index.php"><span class="material-symbols-outlined">dashboard</span><span class="text-sm font-semibold uppercase tracking-wider">Overview</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../events/index.php"><span class="material-symbols-outlined">event_note</span><span class="text-sm font-semibold uppercase tracking-wider">Events</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../achievements/index.php"><span class="material-symbols-outlined">verified</span><span class="text-sm font-semibold uppercase tracking-wider">Achievements</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../merits/index.php"><span class="material-symbols-outlined">military_tech</span><span class="text-sm font-semibold uppercase tracking-wider">Merits</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php"><span class="material-symbols-outlined">groups</span><span class="text-sm font-semibold uppercase tracking-wider">Club Memberships</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../../profile.php"><span class="material-symbols-outlined">person</span><span class="text-sm font-semibold uppercase tracking-wider">My Profile</span></a>
    </nav>

    <div class="pt-6 border-t border-slate-200/50 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-blue-600 transition-colors" href="../../logout.php"><span class="material-symbols-outlined">logout</span><span class="text-xs font-semibold uppercase tracking-wider">Log Out</span></a>
    </div>
</aside>

<header class="fixed top-0 right-0 left-72 bg-white/80 backdrop-blur-md flex justify-between items-center px-8 py-4 z-40 border-b border-slate-100">
    <div class="flex-1 max-w-xl">
        <p class="text-xs font-bold text-primary tracking-widest uppercase"><?php echo $is_admin ? "Administrator Console" : "Student Dashboard"; ?></p>
    </div>
    <div class="flex items-center gap-6 ml-8">
        <button onclick="toggleSettingsDropdown(event)" class="settings-btn p-2 text-slate-600 hover:bg-slate-100 rounded-full transition-colors relative"><span class="material-symbols-outlined">settings</span></button>
        <img alt="User profile" class="w-8 h-8 rounded-full border-2 border-primary" src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=003f87&color=fff"/>
    </div>
</header>

<main class="ml-72 mt-20 p-12 bg-surface min-h-screen">
    
    <?php if (isset($_GET['delete']) && $_GET['delete'] == 'success'): ?>
        <div class="mb-8 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 flex items-center gap-3 rounded-lg shadow-sm">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium">The record has been successfully removed.</span>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-end mb-12">
        <div>
            <span class="block text-sm font-semibold uppercase tracking-[0.15em] text-primary mb-2">Curated Connections</span>
            <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Club Memberships</h1>
        </div>
        
        <div class="flex gap-3">
            <?php if ($is_admin): ?>
                <a href="club_list.php" class="bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined">domain</span> Manage Clubs
                </a>
                
                <a href="add_club.php" class="bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-xl transition-all active:scale-95">
                    <span class="material-symbols-outlined">add_box</span> Create New Club
                </a>
            <?php else: ?>
                <a href="join.php" class="signature-gradient text-white px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-xl hover:scale-105 transition-all">
                    <span class="material-symbols-outlined">add_circle</span> Join New Club
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6 mb-12">
        <div class="col-span-12 md:col-span-8 bg-white p-8 rounded-xl achievement-bloom shadow-sm border border-slate-100">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-2xl font-bold font-headline text-primary mb-1">Membership Vitality</h3>
                    <p class="text-slate-500 text-sm"><?php echo $is_admin ? "System-wide" : "Personal"; ?> activity overview</p>
                </div>
            </div>
            <div class="flex gap-12 mt-4">
                <?php if ($is_admin): ?>
                    <div>
                        <span class="block text-4xl font-black text-on-surface"><?php echo str_pad($total_clubs_in_db, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Clubs in DB</span>
                    </div>
                <?php else: ?>
                    <div>
                        <span class="block text-4xl font-black text-on-surface"><?php echo str_pad($total_clubs_count, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">My Clubs</span>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="block text-4xl font-black text-emerald-600"><?php echo str_pad($active_roles, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest"><?php echo $is_admin ? "Total Active" : "Active Roles"; ?></span>
                </div>
                <div>
                    <span class="block text-4xl font-black text-amber-600"><?php echo str_pad($pending_apps, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest"><?php echo $is_admin ? "Pending Apps" : "Pending App"; ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_admin && !empty($pending_list)): ?>
        <section class="mb-12 space-y-6">
            <h2 class="text-xl font-bold text-amber-600 flex items-center gap-2">
                <span class="material-symbols-outlined">pending_actions</span> Pending Approval Queue
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($pending_list as $pending): ?>
                    <div class="bg-white p-6 rounded-2xl border-2 border-amber-100 shadow-sm">
                        <p class="text-[10px] font-black uppercase text-amber-500 tracking-widest mb-2">New Request</p>
                        <h4 class="font-bold text-slate-800 text-lg"><?php echo htmlspecialchars($pending['student_name']); ?></h4>
                        <p class="text-xs text-slate-500 mb-4">Applied to join: <span class="font-bold text-primary"><?php echo htmlspecialchars($pending['club_name']); ?></span></p>
                        <a href="edit.php?id=<?php echo $pending['member_id']; ?>" class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">Review & Approve</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-100">
        <div class="px-8 py-6 flex justify-between items-center bg-slate-50/50">
            <h2 class="text-lg font-bold font-headline text-on-surface">Registered Associations</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/20">
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Club Name</th>
                        <?php if ($is_admin): ?>
                            <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Student Name</th>
                        <?php endif; ?>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Role</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (count($all_memberships) > 0): ?>
                        <?php foreach ($all_memberships as $club): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-primary">
                                        <span class="material-symbols-outlined">groups</span>
                                    </div>
                                    <span class="font-bold text-on-surface font-headline"><?php echo htmlspecialchars($club['club_name']); ?></span>
                                </div>
                            </td>
                            <?php if ($is_admin): ?>
                                <td class="px-8 py-6 text-sm font-bold text-primary"><?php echo htmlspecialchars($club['student_name']); ?></td>
                            <?php endif; ?>
                            <td class="px-8 py-6 text-sm text-slate-600 font-medium"><?php echo htmlspecialchars($club['member_role']); ?></td>
                            <td class="px-8 py-6">
                                <?php 
                                    $current_status = $club['member_status']; // Already safely forced to 'Pending' if empty in PHP while loop
                                    $color_class = ($current_status == 'Active') ? 'text-emerald-600 bg-emerald-50' : (($current_status == 'Pending') ? 'text-amber-600 bg-amber-50' : 'text-slate-400 bg-slate-50');
                                ?>
                                <div class="inline-flex items-center px-3 py-1 rounded-full <?php echo $color_class; ?>">
                                    <span class="text-[10px] font-black uppercase tracking-wider"><?php echo htmlspecialchars($current_status); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <?php if ($is_admin): ?>
                                        <a href="edit.php?id=<?php echo $club['member_id']; ?>" class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all"><span class="material-symbols-outlined text-lg">edit</span></a>
                                        <a href="delete_membership.php?id=<?php echo $club['member_id']; ?>" onclick="return confirm('Delete record?')" class="p-2 text-slate-400 hover:text-error hover:bg-red-50 rounded-lg transition-all"><span class="material-symbols-outlined text-lg">delete</span></a>
                                    <?php else: ?>
                                        <a href="delete_membership.php?id=<?php echo $club['member_id']; ?>" onclick="return confirm('Are you sure you want to leave this club?')" class="px-4 py-2 bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-600 rounded-full text-[9px] font-black uppercase tracking-widest transition-all">Leave Club</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-8 py-12 text-center text-slate-400 italic">No membership records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function toggleSettingsDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('settings-dropdown');
    if (dropdown) dropdown.classList.toggle('hidden');
}
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('settings-dropdown');
    if (dropdown && !e.target.closest('.settings-btn')) dropdown.classList.add('hidden');
});
</script>
</body>
</html>