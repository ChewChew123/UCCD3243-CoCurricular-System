<?php
// Start session to manage user state
session_start();
// Include database connection
require_once 'includes/db_connect.php'; 

/**
 * AUTH CHECK
 * Redirect to login if user is not authenticated
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

/**
 * FETCH USER DATA
 * Retrieve basic profile info for header display
 */
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

/**
 * ADMIN SPECIFIC: GLOBAL STATISTICS & LOOKUP
 */
$selected_student = null;
if ($is_admin) {
    // 1. 全局概览统计 (Admin Only)
    $total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetch_row()[0];
    $total_clubs = $conn->query("SELECT COUNT(*) FROM clubs")->fetch_row()[0];
    $total_events = $conn->query("SELECT COUNT(*) FROM events WHERE deleted = 0")->fetch_row()[0];
    $total_achievements = $conn->query("SELECT COUNT(*) FROM achievements")->fetch_row()[0];

    // 2. 学生个体审查逻辑 (Spotlight Search)
    if (isset($_GET['lookup_id']) && !empty($_GET['lookup_id'])) {
        $sid = $_GET['lookup_id'];
        $s_sql = "SELECT u.*, 
                  (SELECT COUNT(*) FROM club_members WHERE user_id = u.user_id) as c_count,
                  (SELECT COUNT(*) FROM event_participants WHERE user_id = u.user_id) as e_count,
                  (SELECT COUNT(*) FROM achievements WHERE user_id = u.user_id) as a_count,
                  (SELECT SUM(hours) FROM merits WHERE user_id = u.user_id) as m_sum
                  FROM users u WHERE (u.username = ? OR u.user_id = ?) AND u.role = 'student'";
        $s_stmt = $conn->prepare($s_sql);
        $s_stmt->bind_param("ss", $sid, $sid);
        $s_stmt->execute();
        $selected_student = $s_stmt->get_result()->fetch_assoc();
    }

    // 3. 🌟 升级版：管理员全局动态流 (包含新社团/新活动的创建)
    $recent_sql = "
        (SELECT 'groups' as icon, 'Club' as type, c.club_name as title, CONCAT(u.full_name, ' joined') as action, 'Recent' as date 
         FROM club_members cm JOIN clubs c ON cm.club_id = c.club_id JOIN users u ON cm.user_id = u.user_id)
        UNION ALL
        (SELECT 'event_available' as icon, 'Event' as type, e.event_name as title, CONCAT(u.full_name, ' registered') as action, CAST(e.event_date AS CHAR) as date 
         FROM event_participants ep JOIN events e ON ep.event_id = e.event_id JOIN users u ON ep.user_id = u.user_id)
        UNION ALL
        (SELECT 'verified' as icon, 'Achievement' as type, achievement_title as title, CONCAT(u.full_name, ' earned') as action, CAST(date_received AS CHAR) as date 
         FROM achievements a JOIN users u ON a.user_id = u.user_id)
        UNION ALL
        (SELECT 'domain_add' as icon, 'New Club' as type, club_name as title, 'System created new club' as action, 'Recent' as date 
         FROM clubs)
        UNION ALL
        (SELECT 'campaign' as icon, 'New Event' as type, event_name as title, 'System published new event' as action, CAST(event_date AS CHAR) as date 
         FROM events)
        ORDER BY date DESC LIMIT 6
    ";
    $r_stmt = $conn->prepare($recent_sql);
} else {
    /**
     * STUDENT SPECIFIC: PERSONAL STATISTICS
     */
    // Query A: Total Clubs Joined
    $club_stmt = $conn->prepare("SELECT COUNT(*) as count FROM club_members WHERE user_id = ?");
    $club_stmt->bind_param("i", $user_id);
    $club_stmt->execute();
    $club_count = $club_stmt->get_result()->fetch_assoc()['count'];

    // Query B: Total Events Registered
    $event_stmt = $conn->prepare("SELECT COUNT(*) as count FROM event_participants WHERE user_id = ?");
    $event_stmt->bind_param("i", $user_id);
    $event_stmt->execute();
    $event_count = $event_stmt->get_result()->fetch_assoc()['count'];

    // Query C: Total Achievements Earned
    $ach_stmt = $conn->prepare("SELECT COUNT(*) as count FROM achievements WHERE user_id = ?");
    $ach_stmt->bind_param("i", $user_id);
    $ach_stmt->execute();
    $ach_count = $ach_stmt->get_result()->fetch_assoc()['count'];

    // 🌟 升级版：学生动态流 (包含他们自己的记录，以及系统的新社团/新活动广播)
    $recent_sql = "
        (SELECT 'groups' as icon, 'Club' as type, c.club_name as title, 'Joined association' as action, 'Recent' as date 
         FROM club_members cm JOIN clubs c ON cm.club_id = c.club_id WHERE cm.user_id = ?)
        UNION ALL
        (SELECT 'event_available' as icon, 'Event' as type, e.event_name as title, 'Registered for event' as action, CAST(e.event_date AS CHAR) as date 
         FROM event_participants ep JOIN events e ON ep.event_id = e.event_id WHERE ep.user_id = ?)
        UNION ALL
        (SELECT 'verified' as icon, 'Achievement' as type, achievement_title as title, 'Earned certificate' as action, CAST(date_received AS CHAR) as date 
         FROM achievements WHERE user_id = ?)
        UNION ALL
        (SELECT 'domain_add' as icon, 'Platform Update' as type, club_name as title, 'New club available to join' as action, 'Recent' as date 
         FROM clubs)
        UNION ALL
        (SELECT 'campaign' as icon, 'Platform Update' as type, event_name as title, 'New event opened for registration' as action, CAST(event_date AS CHAR) as date 
         FROM events)
        ORDER BY date DESC LIMIT 5
    ";
    // 参数依然只绑定三个问号，不受新增全局数据的影响
    $r_stmt = $conn->prepare($recent_sql);
    $r_stmt->bind_param("iii", $user_id, $user_id, $user_id);
}

$r_stmt->execute();
$recent_activities = $r_stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Overview Dashboard | Academic Curator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>
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
                        "on-surface": "#141d23"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"]
                    }
                }
            },
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
    </style>
</head>
<body class="bg-surface text-on-surface">

<aside class="h-screen w-72 fixed left-0 top-0 bg-white dark:bg-slate-900 flex flex-col p-6 space-y-8 z-50 border-r border-slate-100 dark:border-slate-800">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
        <div class="text-2xl font-bold tracking-tight text-blue-900 dark:text-blue-100 font-headline">Academic Curator</div>
    </div>

    <div class="flex items-center gap-3 px-2 py-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
       <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=003f87&color=fff" alt="User">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($user_data['programme']); ?></p>
        </div>
    </div>

  <?php if (!$is_admin): ?>
    <a href="modules/clubs/join.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm">add</span>
        New Activity
    </a>
  <?php endif; ?>

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/events/index.php">
            <span class="material-symbols-outlined">event_note</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Events</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/achievements/index.php">
            <span class="material-symbols-outlined">verified</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Achievements</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/merits/index.php">
            <span class="material-symbols-outlined">military_tech</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Merits</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/clubs/index.php">
            <span class="material-symbols-outlined">groups</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Clubs</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-sm font-semibold uppercase tracking-wider">My Profile</span>
        </a>
    </nav>

    <div class="pt-6 border-t border-slate-200/50 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-blue-600 transition-colors" href="logout.php">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-xs font-semibold uppercase tracking-wider">Log Out</span>
        </a>
    </div>
</aside>

<header class="flex justify-between items-center h-20 px-8 ml-72 fixed top-0 w-[calc(100%-18rem)] z-40 bg-white/80 backdrop-blur-md border-b border-slate-100">
    <div class="flex items-center gap-4">
        <p class="text-xs font-bold text-primary tracking-widest uppercase">
            <?php echo $is_admin ? "Administrator Console" : "Student Dashboard"; ?>
        </p>
    </div>
    <div class="flex items-center gap-6">
        <div class="text-right border-l pl-6 border-slate-200">
            <p class="text-xs font-bold text-on-surface uppercase"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
            <p class="text-[10px] text-slate-500 uppercase tracking-tighter"><?php echo htmlspecialchars($user_data['faculty']); ?></p>
        </div>
        <img class="w-10 h-10 rounded-full bg-slate-200 object-cover" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=003f87&color=fff"/>
    </div>
</header>

<main class="ml-72 pt-28 p-12 min-h-screen">
    <div class="max-w-6xl mx-auto space-y-12">
        <header>
            <h1 class="text-4xl font-extrabold text-primary tracking-tight font-headline">
                <?php echo $is_admin ? "System Management" : "Welcome back, " . explode(' ', htmlspecialchars($user_data['full_name']))[0] . "!"; ?>
            </h1>
            <p class="text-slate-500 mt-2 italic font-medium">
                <?php echo $is_admin ? "Overseeing institutional milestones and student co-curricular activities." : "Your curated academic journey at a glance."; ?>
            </p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-<?php echo $is_admin ? '4' : '3'; ?> gap-8">
            
            <?php if ($is_admin): ?>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 transition-all">
                    <p class="text-4xl font-black text-slate-800"><?php echo str_pad($total_students, 2, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Total Students</p>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 transition-all">
                    <p class="text-4xl font-black text-slate-800"><?php echo str_pad($total_clubs, 2, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Active Clubs</p>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 transition-all">
                    <p class="text-4xl font-black text-slate-800"><?php echo str_pad($total_events, 2, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Open Events</p>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 transition-all">
                    <p class="text-4xl font-black text-slate-800"><?php echo str_pad($total_achievements, 2, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Milestones Issued</p>
                </div>
            <?php else: ?>
                <a href="modules/clubs/index.php" class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:border-primary/50 hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-2xl">groups</span>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors">arrow_outward</span>
                    </div>
                    <p class="text-4xl font-black text-slate-800"><?php echo str_pad($club_count, 2, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Clubs Joined</p>
                </a>

                <a href="modules/events/index.php" class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:border-primary/50 hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-2xl">event_note</span>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-amber-600 transition-colors">arrow_outward</span>
                    </div>
                    <p class="text-4xl font-black text-slate-800"><?php echo str_pad($event_count, 2, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Events Registered</p>
                </a>

                <a href="modules/achievements/index.php" class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:border-primary/50 hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-2xl">verified</span>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-purple-600 transition-colors">arrow_outward</span>
                    </div>
                    <p class="text-4xl font-black text-slate-800"><?php echo str_pad($ach_count, 2, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Achievements Earned</p>
                </a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start">
            
            <div class="lg:col-span-2 space-y-6">
                <h2 class="text-xl font-bold font-headline text-slate-800">
                    <?php echo $is_admin ? "Global Activity Feed" : "Recent Milestones"; ?>
                </h2>
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
                        <div class="p-12 text-center text-slate-400">
                            <span class="material-symbols-outlined text-6xl mb-4 opacity-20">inventory_2</span>
                            <p class="font-medium">No recent activities found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-8">
                <?php if ($is_admin): ?>
                    <section class="space-y-6">
                        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                            <h3 class="text-sm font-black uppercase tracking-widest text-primary mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">person_search</span> Student Lookup
                            </h3>
                            <form action="index.php" method="GET" class="space-y-3">
                                <input type="text" name="lookup_id" placeholder="Student ID or Username" class="w-full bg-slate-50 border-none rounded-2xl py-3 px-4 text-sm focus:ring-2 focus:ring-primary" value="<?php echo isset($_GET['lookup_id']) ? htmlspecialchars($_GET['lookup_id']) : ''; ?>">
                                <button type="submit" class="w-full signature-gradient text-white py-3 rounded-2xl font-bold text-xs uppercase tracking-widest shadow-lg active:scale-95 transition-all">Inspect Profile</button>
                            </form>
                        </div>

                        <?php if ($selected_student): ?>
                            <div class="signature-gradient p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden">
                                <div class="relative z-10">
                                    <div class="flex justify-between items-start mb-6">
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-[0.2em] opacity-60 mb-1">Student Record</p>
                                            <h4 class="text-2xl font-black font-headline leading-tight"><?php echo htmlspecialchars($selected_student['full_name']); ?></h4>
                                            <p class="text-[10px] opacity-70 font-medium"><?php echo htmlspecialchars($selected_student['programme']); ?></p>
                                        </div>
                                        <div class="bg-white/20 p-2 rounded-xl backdrop-blur-md">
                                            <span class="material-symbols-outlined">verified_user</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/5">
                                            <p class="text-xl font-black"><?php echo $selected_student['c_count']; ?></p>
                                            <p class="text-[9px] uppercase font-bold opacity-50">Clubs</p>
                                        </div>
                                        <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/5">
                                            <p class="text-xl font-black"><?php echo $selected_student['e_count']; ?></p>
                                            <p class="text-[9px] uppercase font-bold opacity-50">Events</p>
                                        </div>
                                        <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/5">
                                            <p class="text-xl font-black"><?php echo $selected_student['a_count']; ?></p>
                                            <p class="text-[9px] uppercase font-bold opacity-50">Awards</p>
                                        </div>
                                        <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-sm border border-white/5">
                                            <p class="text-xl font-black"><?php echo number_format($selected_student['m_sum'] ?? 0); ?></p>
                                            <p class="text-[9px] uppercase font-bold opacity-50">Merits</p>
                                        </div>
                                    </div>

                                    <div class="mt-8 pt-6 border-t border-white/10 space-y-3">
                                        <p class="text-[9px] font-black uppercase tracking-widest opacity-40">Administrative Shortcuts</p>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="modules/achievements/index.php?search=<?php echo urlencode($selected_student['full_name']); ?>" class="bg-white text-primary px-4 py-2 rounded-full text-[9px] font-black uppercase tracking-widest hover:bg-slate-100 transition-colors">Manage Achievements</a>
                                            <a href="modules/merits/index.php?search=<?php echo urlencode($selected_student['full_name']); ?>" class="bg-white text-primary px-4 py-2 rounded-full text-[9px] font-black uppercase tracking-widest hover:bg-slate-100 transition-colors">Audit Merits</a>
                                        </div>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined absolute -right-6 -bottom-6 text-[140px] opacity-10 rotate-12">school</span>
                            </div>
                        <?php elseif (isset($_GET['lookup_id'])): ?>
                            <div class="p-6 bg-red-50 text-red-600 rounded-2xl text-xs font-bold border border-red-100">
                                No student found with that ID or Username.
                            </div>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <section class="space-y-6">
                        <h2 class="text-xl font-bold font-headline text-slate-800">Explore</h2>
                        <div class="signature-gradient p-8 rounded-[2.5rem] text-white shadow-xl space-y-6 relative overflow-hidden">
                            <div class="relative z-10">
                                <h3 class="text-2xl font-bold leading-tight mb-2">Expand Your Horizon</h3>
                                <p class="text-blue-100 text-sm opacity-90 mb-6">Connect with new clubs and organizations that match your career path.</p>
                                <a href="modules/clubs/index.php" class="inline-flex items-center gap-2 bg-white text-primary px-6 py-3 rounded-full font-bold text-sm shadow-xl hover:scale-105 active:scale-95 transition-all">
                                    Join Clubs
                                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                </a>
                            </div>
                            <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-[100px] opacity-10">explore</span>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

</body>
</html>