<?php
session_start();
require_once '../../includes/db_connect.php';

// Session Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Profile
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// Fetch Memberships
$sql = "SELECT cm.member_id, c.club_name, c.club_category, cm.member_role, cm.member_status 
        FROM club_members cm 
        JOIN clubs c ON cm.club_id = c.club_id 
        WHERE cm.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$memberships = $stmt->get_result();

// Stats calculation
$active_roles = 0;
$pending_apps = 0;
$all_memberships = [];

while ($row = $memberships->fetch_assoc()) {
    $all_memberships[] = $row;
    if ($row['member_status'] == 'Active') $active_roles++;
    if ($row['member_status'] == 'Pending') $pending_apps++; // Note: 'Pending' not in original ENUM but user mockup has it
}

$total_clubs = count($all_memberships);
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Club Membership Dashboard | The Academic Curator</title>
<!-- Fonts: Manrope & Inter -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@700;800&amp;display=swap" rel="stylesheet"/>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .signature-gradient {
            background: linear-gradient(135deg, #003f87 0%, #0056b3 100%);
        }
        .achievement-bloom {
            position: relative;
            overflow: hidden;
        }
        .achievement-bloom::after {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: #003f87;
            opacity: 0.05;
            border-radius: 50%;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body">
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
<p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($full_name); ?></p>
<p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
</div>
</div>

<!-- New Activity Button -->
<a href="join.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-sm">add</span>
New Activity
</a>

<!-- Navigation -->
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
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
<span class="material-symbols-outlined">groups</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../profile.php">
<span class="material-symbols-outlined">person</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
</a>
</nav>

<!-- Logout -->
<div class="pt-6 border-t border-slate-200/50 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-blue-600 transition-colors" href="../../logout.php">
<span class="material-symbols-outlined">logout</span>
<span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
</a>
</div>
</aside>
<!-- TopAppBar -->
<header class="fixed top-0 right-0 left-72 bg-slate-50 dark:bg-slate-950 flex justify-between items-center px-8 py-4 z-40 border-b border-slate-100 dark:border-slate-800">
<div class="flex-1 max-w-xl">
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
<input class="w-full bg-surface-container-low border-none rounded-full py-2 pl-10 pr-4 focus:ring-2 focus:ring-primary transition-all text-sm" placeholder="Search curated clubs..." type="text"/>
</div>
</div>
<div class="flex items-center gap-6 ml-8">
<div class="flex items-center gap-4">
    <!-- Settings Dropdown -->
    <div class="relative group">
        <button onclick="toggleSettingsDropdown(event)" class="settings-btn p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 rounded-full transition-colors relative">
            <span class="material-symbols-outlined">settings</span>
        </button>
        <!-- Dropdown Menu -->
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
<!-- Main Canvas -->
<main class="ml-72 mt-20 p-12 bg-surface min-h-screen">
    
    <?php if (isset($_GET['delete'])): ?>
        <?php if ($_GET['delete'] == 'success'): ?>
            <div class="mb-8 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 flex items-center gap-3 rounded-lg shadow-sm">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-medium">You have successfully left the club. Your membership has been removed.</span>
            </div>
        <?php elseif ($_GET['delete'] == 'error'): ?>
            <div class="mb-8 p-4 bg-error-container border-l-4 border-error text-error flex items-center gap-3 rounded-lg shadow-sm">
                <span class="material-symbols-outlined">error</span>
                <span class="font-medium">Error: <?php echo htmlspecialchars($_GET['msg'] ?? 'Deletion failed'); ?></span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['join']) && $_GET['join'] == 'success'): ?>
        <div class="mb-8 p-4 bg-primary-fixed border-l-4 border-primary text-on-primary-fixed flex items-center gap-3 rounded-lg shadow-sm">
            <span class="material-symbols-outlined">stars</span>
            <span class="font-medium">Welcome to the organization! Your membership is now active.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit']) && $_GET['edit'] == 'success'): ?>
        <div class="mb-8 p-4 bg-secondary-container border-l-4 border-secondary text-on-secondary-container flex items-center gap-3 rounded-lg shadow-sm border-none">
            <span class="material-symbols-outlined">edit_note</span>
            <span class="font-medium">Membership details updated successfully.</span>
        </div>
    <?php endif; ?>

<!-- Header Section -->
<div class="flex justify-between items-end mb-12">
<div>
<span class="block text-sm font-semibold Manrope uppercase tracking-[0.15em] text-primary mb-2">Curated Connections</span>
<h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Club Memberships</h1>
</div>
<a href="join.php" class="signature-gradient text-white px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-xl hover:scale-[1.02] transition-transform active:scale-95 duration-150">
<span class="material-symbols-outlined">add_circle</span>
                Join New Club
            </a>
</div>
<!-- Bento Highlights (Asymmetric Layout) -->
<div class="grid grid-cols-12 gap-6 mb-12">
<!-- Active Status Card -->
<div class="col-span-12 md:col-span-8 bg-surface-container-lowest p-8 rounded-xl achievement-bloom shadow-sm">
<div class="flex justify-between items-start mb-6">
<div>
<h3 class="text-2xl font-bold font-headline text-primary mb-1">Membership Vitality</h3>
<p class="text-slate-500 text-sm">Engagement health across your active associations</p>
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
<!-- Main Data Table Container -->
<div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
<div class="px-8 py-6 flex justify-between items-center bg-surface-container-low/50">
<h2 class="text-lg font-bold font-headline text-on-surface">Registered Associations</h2>
<div class="flex gap-2">
<button class="p-2 text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined">filter_list</span>
</button>
<button class="p-2 text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined">download</span>
</button>
</div>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-slate-50/50">
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Club Name</th>
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Role</th>
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Category</th>
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Status</th>
<th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label text-right">Action</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-100">
<?php if (count($all_memberships) > 0): ?>
    <?php foreach ($all_memberships as $club): 
        $status_color = 'text-slate-400';
        $dot_color = 'bg-slate-400';
        $pulse = '';
        
        if ($club['member_status'] == 'Active') {
            $status_color = 'text-emerald-600';
            $dot_color = 'bg-emerald-600';
        } elseif ($club['member_status'] == 'Pending') {
            $status_color = 'text-amber-600';
            $dot_color = 'bg-amber-600';
            $pulse = 'animate-pulse';
        }

        $icon = 'groups';
        $icon_bg = 'bg-blue-100';
        $icon_text = 'text-blue-700';

        if (stripos($club['club_name'], 'Robotics') !== false) { $icon = 'precision_manufacturing'; $icon_bg = 'bg-blue-100'; $icon_text = 'text-blue-700'; }
        elseif (stripos($club['club_name'], 'Debate') !== false) { $icon = 'forum'; $icon_bg = 'bg-orange-100'; $icon_text = 'text-orange-700'; }
        elseif (stripos($club['club_name'], 'Football') !== false) { $icon = 'sports_football'; $icon_bg = 'bg-emerald-100'; $icon_text = 'text-emerald-700'; }
        elseif (stripos($club['club_name'], 'Art') !== false) { $icon = 'palette'; $icon_bg = 'bg-purple-100'; $icon_text = 'text-purple-700'; }
        elseif (stripos($club['club_name'], 'Entrepreneurship') !== false) { $icon = 'insights'; $icon_bg = 'bg-blue-100'; $icon_text = 'text-blue-700'; }
    ?>
    <tr class="hover:bg-surface-container-low transition-colors group">
    <td class="px-8 py-6">
    <div class="flex items-center gap-4">
    <div class="w-10 h-10 rounded-lg <?php echo $icon_bg; ?> flex items-center justify-center <?php echo $icon_text; ?>">
    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;"><?php echo $icon; ?></span>
    </div>
    <span class="font-bold text-on-surface font-headline"><?php echo htmlspecialchars($club['club_name']); ?></span>
    </div>
    </td>
    <td class="px-8 py-6 text-sm text-slate-600 font-medium"><?php echo htmlspecialchars($club['member_role']); ?></td>
    <td class="px-8 py-6">
    <span class="px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-[10px] font-bold uppercase tracking-wider"><?php echo htmlspecialchars($club['club_category']); ?></span>
    </td>
    <td class="px-8 py-6">
    <div class="flex items-center gap-2 <?php echo $status_color; ?>">
    <span class="w-1.5 h-1.5 <?php echo $dot_color; ?> rounded-full <?php echo $pulse; ?>"></span>
    <span class="text-xs font-bold uppercase tracking-wide"><?php echo htmlspecialchars($club['member_status']); ?></span>
    </div>
    </td>
    <td class="px-8 py-6 text-right">
    <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
    <a href="edit.php?id=<?php echo $club['member_id']; ?>" class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all">
    <span class="material-symbols-outlined text-lg">edit</span>
    </a>
    <a href="delete_membership.php?id=<?php echo $club['member_id']; ?>" onclick="return confirm('Are you sure you want to leave this club?')" class="p-2 text-slate-400 hover:text-error hover:bg-error/10 rounded-lg transition-all">
    <span class="material-symbols-outlined text-lg">delete</span>
    </a>
    </div>
    </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="px-8 py-12 text-center text-slate-400 font-medium italic">
            You are not currently a member of any clubs. Explore and join some!
        </td>
    </tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="px-8 py-4 border-t border-slate-100 flex justify-between items-center text-xs font-semibold text-slate-400 font-label">
<span>Showing <?php echo count($all_memberships); ?> clubs</span>
<div class="flex gap-4">
<button class="hover:text-primary transition-colors disabled:opacity-30" disabled="">Previous</button>
<button class="hover:text-primary transition-colors">Next</button>
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

// Close when clicking outside
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('settings-dropdown');
    if (dropdown && !e.target.closest('.settings-btn') && !e.target.closest('#settings-dropdown')) {
        dropdown.classList.add('hidden');
    }
});
</script>
</body></html>