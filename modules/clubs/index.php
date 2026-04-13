<?php
/**
 * File: index.php (Clubs Module)
 * Purpose: Club membership dashboard with role-based access control.
 */
session_start();
require_once '../../includes/db_connect.php';

// 1. Session Check: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Identify User Role (Admin vs Student)
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 3. Fetch User Profile Details for Sidebar
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// 4. Fetch Memberships based on Role
if ($is_admin) {
    // Admin: Fetch all memberships in the system to manage
    $sql = "SELECT cm.member_id, c.club_id, c.club_name, c.club_category, cm.member_role, cm.member_status, u.full_name as student_name 
            FROM club_members cm 
            JOIN clubs c ON cm.club_id = c.club_id 
            JOIN users u ON cm.user_id = u.user_id";
    $stmt = $conn->prepare($sql);
} else {
    // Student: Fetch only their personal memberships
    $sql = "SELECT cm.member_id, c.club_id, c.club_name, c.club_category, cm.member_role, cm.member_status 
            FROM club_members cm 
            JOIN clubs c ON cm.club_id = c.club_id 
            WHERE cm.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$memberships = $stmt->get_result();

// 5. Calculate Dashboard Statistics
$active_roles = 0;
$pending_apps = 0;
$all_memberships = [];

while ($row = $memberships->fetch_assoc()) {
    $all_memberships[] = $row;
    if ($row['member_status'] == 'Active') $active_roles++;
    if ($row['member_status'] == 'Pending') $pending_apps++; 
}

$total_clubs = count($all_memberships);
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
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
                    "on-primary-fixed": "#001a40",
                    "surface-container": "#e6eff8",
                    "surface-bright": "#f6faff",
                    "error": "#ba1a1a",
                    "secondary-fixed-dim": "#c4c7ca",
                    "on-primary-fixed-variant": "#004491",
                    "outline-variant": "#c2c6d4",
                    "tertiary-fixed-dim": "#ffb694",
                    "secondary": "#5b5f62",
                    "outline": "#727784",
                    "on-error-container": "#93000a",
                    "on-error": "#ffffff",
                    "error-container": "#ffdad6",
                    "surface-container-lowest": "#ffffff",
                    "on-tertiary-fixed": "#351000",
                    "on-secondary-fixed": "#181c1e",
                    "on-surface-variant": "#424752",
                    "primary-container": "#0056b3",
                    "on-tertiary": "#ffffff",
                    "primary-fixed": "#d7e2ff",
                    "on-background": "#141d23",
                    "surface-dim": "#d2dbe4",
                    "surface-tint": "#115cb9",
                    "on-tertiary-container": "#ffc2a7",
                    "on-surface": "#141d23",
                    "primary-fixed-dim": "#acc7ff",
                    "tertiary": "#722b00",
                    "primary": "#003f87",
                    "surface-container-highest": "#dbe4ed",
                    "surface-container-high": "#e0e9f2",
                    "on-primary-container": "#bbd0ff",
                    "on-primary": "#ffffff",
                    "on-secondary-container": "#5f6366",
                    "secondary-fixed": "#e0e3e6",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "tertiary-fixed": "#ffdbcc",
                    "surface-variant": "#dbe4ed",
                    "surface": "#f6faff",
                    "surface-container-low": "#ecf5fe",
                    "background": "#f6faff",
                    "inverse-primary": "#acc7ff",
                    "inverse-surface": "#293138",
                    "secondary-container": "#dde0e3",
                    "on-secondary": "#ffffff",
                    "on-secondary-fixed-variant": "#43474a",
                    "tertiary-container": "#983c00",
                    "inverse-on-surface": "#e9f2fb"
            },
            "borderRadius": {
                    "DEFAULT": "0.125rem",
                    "lg": "0.25rem",
                    "xl": "0.5rem",
                    "full": "0.75rem"
            },
            "fontFamily": {
                    "headline": ["Manrope"],
                    "body": ["Inter"],
                    "label": ["Inter"]
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
<img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=003f87&color=fff" alt="User">
<div class="overflow-hidden">
<p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($full_name); ?></p>
<p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
</div>
</div>

<nav class="flex-1 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../index.php">
<span class="material-symbols-outlined">dashboard</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../events/index.php">
<span class="material-symbols-outlined">event_note</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Events</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../achievements/index.php">
<span class="material-symbols-outlined">verified</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Achievements</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../merits/index.php">
<span class="material-symbols-outlined">military_tech</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Merits</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
<span class="material-symbols-outlined">groups</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../profile.php">
<span class="material-symbols-outlined">person</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
</a>
</nav>

<div class="pt-6 border-t border-slate-200/50 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-blue-600 transition-colors" href="../../logout.php">
<span class="material-symbols-outlined">logout</span>
<span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
</a>
</div>
</aside>
<header class="fixed top-0 right-0 left-72 bg-slate-50 dark:bg-slate-950 flex justify-between items-center px-8 py-4 z-40 border-b border-slate-100 dark:border-slate-800">
<div class="flex-1 max-w-xl">
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
<input class="w-full bg-surface-container-low border-none rounded-full py-2 pl-10 pr-4 focus:ring-2 focus:ring-primary transition-all text-sm" placeholder="Search curated clubs..." type="text"/>
</div>
</div>
<div class="flex items-center gap-6 ml-8">
<div class="flex items-center gap-4">
    <div class="relative group">
        <button onclick="toggleSettingsDropdown(event)" class="settings-btn p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 rounded-full transition-colors relative">
            <span class="material-symbols-outlined">settings</span>
        </button>
        <div id="settings-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 py-2 z-50">
            <a href="../../profile.php" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg">person</span>
                <span>Account Profile</span>
            </a>
            <div class="h-[1px] bg-slate-100 dark:bg-slate-800 my-1"></div>
            <a href="../../logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <span class="material-symbols-outlined text-lg">logout</span>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</div>
<div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-800"></div>
<img alt="Student profile picture" class="w-8 h-8 rounded-full border-2 border-primary-fixed" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB_ZUstEX2uJ8fxMVq-7RaP9RdQIPxE4A1MajOEIAbs3ZmZoJPwIJkUM3oWCbQo5P3jEIF2gRrNHp-Eo6w2APijwpGoQmwh6Oca9ORZPu294JVWkqCgXmupjlPGBwCyDRBJFl0I5R_1Ie5T3nEjuYx2KCUHn4kngTCWd6ZFquBHm_4e3cgAouUP-L2xgjWHhq72KHIwlrzAcd2HKUue6pV39BuyKrSHnFcgxpP7ELOPbRbMn_oMjvZddlddDm1Itg7xUCerH7BVtp02"/>
</div>
</header>
<main class="ml-72 mt-20 p-12 bg-surface min-h-screen">
    
    <?php if (isset($_GET['delete']) && $_GET['delete'] == 'success'): ?>
            <div class="mb-8 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 flex items-center gap-3 rounded-lg shadow-sm">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-medium">You have successfully left the club. Your membership has been removed.</span>
            </div>
    <?php endif; ?>

<div class="flex justify-between items-end mb-12">
    <div>
        <span class="block text-sm font-semibold Manrope uppercase tracking-[0.15em] text-primary mb-2">Curated Connections</span>
        <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Club Memberships</h1>
    </div>
    
   <div class="flex flex-col items-end gap-3">
       <?php if ($is_admin): ?>
            <a href="add_club.php" 
               class="bg-emerald-500 hover:bg-emerald-600 text-white px-12 py-6 rounded-[2rem] font-black font-headline shadow-2xl hover:shadow-emerald-200/50 hover:-translate-y-1 active:scale-95 transition-all flex items-center gap-4 text-xl group">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center group-hover:rotate-90 transition-transform duration-500">
                    <span class="material-symbols-outlined text-2xl">add_box</span>
                </div>
                Create New Club
            </a>
        <?php else: ?>
            <a href="join.php" class="signature-gradient text-white px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-xl hover:scale-[1.02] transition-transform active:scale-95 duration-150">
                <span class="material-symbols-outlined">add_circle</span>
                Join New Club
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-12 gap-6 mb-12">
<div class="col-span-12 md:col-span-8 bg-surface-container-lowest p-8 rounded-xl achievement-bloom shadow-sm border border-slate-100">
<div class="flex justify-between items-start mb-6">
<div>
<h3 class="text-2xl font-bold font-headline text-primary mb-1">Membership Vitality</h3>
<p class="text-slate-500 text-sm"><?php echo $is_admin ? "System-wide" : "Personal"; ?> engagement health summary</p>
</div>
<span class="bg-primary-container text-on-primary-container px-4 py-1 rounded-full text-xs font-bold"><?php echo ($active_roles >= 3) ? 'EXCELLENT' : 'STABLE'; ?></span>
</div>
<div class="flex gap-12 mt-4">
<div>
<span class="block text-4xl font-black font-headline text-on-surface"><?php echo str_pad($active_roles, 2, '0', STR_PAD_LEFT); ?></span>
<span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Active Roles</span>
</div>
    <div>
        <span class="block text-4xl font-black font-headline text-on-surface">--</span>
        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Events Attended</span>
    </div>
    <div>
        <span class="block text-4xl font-black font-headline text-on-surface"><?php echo str_pad($pending_apps, 2, '0', STR_PAD_LEFT); ?></span>
        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Pending App</span>
    </div>
</div>
</div>
</div>

<div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden border border-slate-100">
<div class="px-8 py-6 flex justify-between items-center bg-surface-container-low/50">
<h2 class="text-lg font-bold font-headline text-on-surface">Registered Associations</h2>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-slate-50/50">
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Club Name</th>
<?php if ($is_admin): ?><th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Student Name</th><?php endif; ?>
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Role</th>
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Status</th>
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label text-right">Action</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-100">
<?php if (count($all_memberships) > 0): ?>
    <?php foreach ($all_memberships as $club): ?>
    <tr class="hover:bg-surface-container-low transition-colors group">
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
        // 🌟 Fix: If status is empty in DB, default to 'Pending'
        $current_status = !empty($club['member_status']) ? $club['member_status'] : 'Pending';
        
        // Define specific colors for each status
        if ($current_status == 'Active') {
            $text_color = 'text-emerald-600';
            $dot_color = 'bg-emerald-600';
        } elseif ($current_status == 'Pending') {
            $text_color = 'text-amber-600'; // Amber for Pending
            $dot_color = 'bg-amber-600';
        } else {
            $text_color = 'text-slate-400'; // Gray for Inactive or others
            $dot_color = 'bg-slate-400';
        }
    ?>
    <div class="flex items-center gap-2 <?php echo $text_color; ?>">
        <span class="w-1.5 h-1.5 rounded-full <?php echo $dot_color; ?>"></span>
        <span class="text-[10px] font-black uppercase tracking-wider">
            <?php echo htmlspecialchars($current_status); ?>
        </span>
    </div>
</td>

<td class="px-8 py-6 text-right">
    <div class="flex justify-end gap-2 <?php echo $is_admin ? 'opacity-0 group-hover:opacity-100 transition-opacity' : ''; ?>">
        <?php if ($is_admin): ?>
            <a href="edit.php?id=<?php echo $club['member_id']; ?>" class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all">
                <span class="material-symbols-outlined text-lg">edit</span>
            </a>
            <a href="delete_membership.php?id=<?php echo $club['member_id']; ?>" onclick="return confirm('Delete this student record?')" class="p-2 text-slate-400 hover:text-error hover:bg-error/10 rounded-lg transition-all">
                <span class="material-symbols-outlined text-lg">delete</span>
            </a>
        <?php else: ?>
            <a href="delete_membership.php?id=<?php echo $club['member_id']; ?>" 
               onclick="return confirm('Are you sure you want to leave this club?')" 
               class="px-4 py-2 bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-600 rounded-full text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">logout</span>
                Leave Club
            </a>
        <?php endif; ?>
    </div>
</td>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6" class="px-8 py-12 text-center text-slate-400 italic">No records found.</td></tr>
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
    dropdown.classList.toggle('hidden');
}
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('settings-dropdown');
    if (dropdown && !e.target.closest('.settings-btn')) dropdown.classList.add('hidden');
});
</script>
</body></html>