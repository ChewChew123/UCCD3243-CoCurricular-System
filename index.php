<?php
session_start();
require_once 'includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Data for Header
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// 1. Data Statistics
// Query A: Clubs
$club_stmt = $conn->prepare("SELECT COUNT(*) as count FROM club_members WHERE user_id = ?");
$club_stmt->bind_param("i", $user_id);
$club_stmt->execute();
$club_count = $club_stmt->get_result()->fetch_assoc()['count'];

// Query B: Events
$event_stmt = $conn->prepare("SELECT COUNT(*) as count FROM event_participants WHERE user_id = ?");
$event_stmt->bind_param("i", $user_id);
$event_stmt->execute();
$event_count = $event_stmt->get_result()->fetch_assoc()['count'];

// Query C: Achievements
$ach_stmt = $conn->prepare("SELECT COUNT(*) as count FROM achievements WHERE user_id = ?");
$ach_stmt->bind_param("i", $user_id);
$ach_stmt->execute();
$ach_count = $ach_stmt->get_result()->fetch_assoc()['count'];

// 2. Activity Feed (UNION) - Fetch 3 most recent
$recent_sql = "
    (SELECT 'groups' as icon, 'Club' as type, c.club_name as title, 'Joined association' as action, 'Recent' as date 
     FROM club_members cm JOIN clubs c ON cm.club_id = c.club_id WHERE cm.user_id = ?)
    UNION ALL
    (SELECT 'event_available' as icon, 'Event' as type, e.event_name as title, 'Registered for event' as action, CAST(e.event_date AS CHAR) as date 
     FROM event_participants ep JOIN events e ON ep.event_id = e.event_id WHERE ep.user_id = ?)
    UNION ALL
    (SELECT 'verified' as icon, 'Achievement' as type, achievement_title as title, 'Earned certificate' as action, CAST(date_received AS CHAR) as date 
     FROM achievements WHERE user_id = ?)
    ORDER BY date DESC LIMIT 3
";
$r_stmt = $conn->prepare($recent_sql);
$r_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$r_stmt->execute();
$recent_activities = $r_stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Overview Dashboard | Academic Curator</title>
<!-- Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap" rel="stylesheet"/>
<!-- Icons -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "primary": "#003f87",
                    "surface": "#f6faff",
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body { font-family: 'Inter', sans-serif; }
        .signature-gradient {
            background: linear-gradient(135deg, #003f87 0%, #0056b3 100%);
        }
    </style>
</head>
<body class="bg-surface text-on-surface">
<!-- SideNavBar -->
<aside class="h-screen w-72 fixed left-0 top-0 bg-white dark:bg-slate-900 flex flex-col p-6 space-y-8 z-50 border-r border-slate-100 dark:border-slate-800">
<!-- Logo -->
<div class="flex items-center gap-3">
<div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
<span class="material-symbols-outlined">auto_stories</span>
</div>
<div class="text-2xl font-bold tracking-tight text-blue-900 dark:text-blue-100 font-headline">Academic Curator</div>
</div>

<!-- Profile Mini-Card -->
<div class="flex items-center gap-3 px-2 py-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
<img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAjZ_oSRVSiGbl-3d0SW9fUmXz9Cu1MsAMPA7uZdp3KuIWCiPdAWXp15aOKt9aLa2FkwcUxtBO05z6u-ogifVlXzX56G2KA7UbUdMBSB1uMhIpCG03NhCTr70NwqcdWocj5NSzxeUSFF82mW0AxbY5Ft0tNfNS9NbjtTFERRBKfxxuLeeWGrJSXoPjfm_RGYDBXFDuelpRkwJIobR20MbVLBbgchPC_RKTmJU3n44N8Pwn4XffLrKhZ5N5a0ThzG72QhBaSNGmc0Xew" alt="User Avatar">
<div class="overflow-hidden">
<p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
<p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($user_data['programme']); ?></p>
</div>
</div>

<!-- New Activity Button -->
<a href="modules/clubs/join.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-sm">add</span>
New Activity
</a>

<!-- Navigation -->
<nav class="flex-1 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
<span class="material-symbols-outlined">dashboard</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="modules/events/index.php">
<span class="material-symbols-outlined">event_note</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Events</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="modules/achievements/index.php">
<span class="material-symbols-outlined">verified</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Achievements</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="modules/clubs/index.php">
<span class="material-symbols-outlined">groups</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="profile.php">
<span class="material-symbols-outlined">person</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
</a>
</nav>

<!-- Logout -->
<div class="pt-6 border-t border-slate-200/50 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-blue-600 transition-colors" href="logout.php">
<span class="material-symbols-outlined">logout</span>
<span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
</a>
</div>
</aside>

<!-- TopAppBar -->
<header class="flex justify-between items-center h-20 px-8 ml-72 w-[calc(100%-18rem)] fixed top-0 sticky z-40 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md shadow-sm dark:shadow-none border-b border-slate-100 dark:border-slate-800">
<div class="flex items-center gap-4">
    <div class="relative group">
        <button onclick="toggleSettingsDropdown(event)" class="settings-btn p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 rounded-full transition-colors relative">
            <span class="material-symbols-outlined">settings</span>
        </button>
        <div id="settings-dropdown" class="hidden absolute left-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 py-2 z-50">
            <a href="profile.php" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg">person</span>
                <span>Account Profile</span>
            </a>
            <div class="h-[1px] bg-slate-100 dark:bg-slate-800 my-1"></div>
            <a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <span class="material-symbols-outlined text-lg">logout</span>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</div>
<div class="flex items-center gap-6">
<div class="flex items-center gap-3 border-l pl-6 border-slate-200">
<div class="text-right">
<p class="text-xs font-bold text-on-surface uppercase tracking-tight"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
<p class="text-xs text-slate-500"><?php echo htmlspecialchars($user_data['academic_year'] . " - " . $user_data['faculty']); ?></p>
</div>
<img class="w-10 h-10 rounded-full bg-slate-200 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAjZ_oSRVSiGbl-3d0SW9fUmXz9Cu1MsAMPA7uZdp3KuIWCiPdAWXp15aOKt9aLa2FkwcUxtBO05z6u-ogifVlXzX56G2KA7UbUdMBSB1uMhIpCG03NhCTr70NwqcdWocj5NSzxeUSFF82mW0AxbY5Ft0tNfNS9NbjtTFERRBKfxxuLeeWGrJSXoPjfm_RGYDBXFDuelpRkwJIobR20MbVLBbgchPC_RKTmJU3n44N8Pwn4XffLrKhZ5N5a0ThzG72QhBaSNGmc0Xew"/>
</div>
</div>
</header>

<main class="ml-72 p-12 min-h-screen">
<div class="max-w-6xl mx-auto space-y-12">
    <header>
        <h1 class="text-4xl font-extrabold text-primary tracking-tight font-headline">Welcome, <?php echo explode(' ', htmlspecialchars($user_data['full_name']))[0]; ?>!</h1>
        <p class="text-slate-500 mt-2 italic font-medium">Track your academic milestones and discover new opportunities.</p>
    </header>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">groups</span>
                </div>
            </div>
            <p class="text-4xl font-black text-slate-800"><?php echo str_pad($club_count, 2, '0', STR_PAD_LEFT); ?></p>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Clubs Joined</p>
        </div>
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">event_note</span>
                </div>
            </div>
            <p class="text-4xl font-black text-slate-800"><?php echo str_pad($event_count, 2, '0', STR_PAD_LEFT); ?></p>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Events Attended</p>
        </div>
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">verified</span>
                </div>
            </div>
            <p class="text-4xl font-black text-slate-800"><?php echo str_pad($ach_count, 2, '0', STR_PAD_LEFT); ?></p>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Achievements Earned</p>
        </div>
    </div>

    <!-- Main Grid: Activity Feed & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start">
        <!-- Recent Activity Feed -->
        <div class="lg:col-span-2 space-y-6">
            <h2 class="text-xl font-bold font-headline text-slate-800">Recent Activity</h2>
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <?php if ($recent_activities->num_rows > 0): ?>
                    <div class="divide-y divide-slate-50">
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                            <div class="p-6 flex items-center gap-6 hover:bg-slate-50/50 transition-colors">
                                <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400">
                                    <span class="material-symbols-outlined"><?php echo $activity['icon']; ?></span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-slate-800 font-bold"><?php echo htmlspecialchars($activity['title']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($activity['action']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars($activity['date']); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <span class="material-symbols-outlined text-slate-200 text-6xl mb-4">inventory_2</span>
                        <p class="text-slate-400 font-medium">No recent activities to show.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Banner -->
        <div class="space-y-6">
            <h2 class="text-xl font-bold font-headline text-slate-800">Quick Actions</h2>
            <div class="signature-gradient p-8 rounded-3xl text-white shadow-lg space-y-6">
                <div>
                    <h3 class="text-2xl font-bold leading-tight mb-2">Grow Your Network Today</h3>
                    <p class="text-blue-100 text-sm opacity-90">Browse diverse clubs and associations tailored to your interests.</p>
                </div>
                <a href="modules/clubs/join.php" class="inline-flex items-center gap-2 bg-white text-primary px-6 py-3 rounded-full font-bold text-sm shadow-xl hover:scale-105 active:scale-95 transition-all">
                    Join a Club
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
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
    if (dropdown && !e.target.closest('.settings-btn') && !e.target.closest('#settings-dropdown')) {
        dropdown.classList.add('hidden');
    }
});
</script>
</body></html>