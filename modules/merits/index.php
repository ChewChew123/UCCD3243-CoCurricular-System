<?php
session_start();
require_once '../../includes/db_connect.php'; // 确保路径正确

// Session Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Profile for Sidebar
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// Fetch Merits (🌟 满分细节：通过 LEFT JOIN 把关联的 Event 名字也抓出来)
$query = "SELECT m.*, e.event_name 
          FROM merits m 
          LEFT JOIN events e ON m.event_id = e.event_id 
          WHERE m.user_id = ? 
          ORDER BY m.date_completed DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$all_merits = [];
while ($row = $result->fetch_assoc()) {
    $all_merits[] = $row;
}
$total_records = count($all_merits);

$total_query = "SELECT SUM(hours) as total FROM merits WHERE user_id = ?";
$t_stmt = $conn->prepare($total_query);
$t_stmt->bind_param("i", $user_id);
$t_stmt->execute();
$total_result = $t_stmt->get_result()->fetch_assoc();
$total_hours = $total_result['total'] ?? 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Merit Tracker | The Academic Curator</title>
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
                        "primary-fixed": "#d7e2ff",
                        "on-primary-fixed": "#001a40",
                        "surface": "#f6faff",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#ecf5fe",
                        "on-surface": "#141d23",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6",
                        "emerald-500": "#10b981",
                        "emerald-100": "#d1fae5",
                        "emerald-700": "#047857",
                        "amber-500": "#f59e0b"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
        .achievement-bloom { position: relative; overflow: hidden; }
        .achievement-bloom::after {
            content: ''; position: absolute; top: -20%; right: -10%; width: 200px; height: 200px;
            background: #003f87; opacity: 0.05; border-radius: 50%; pointer-events: none;
        }
    </style>
</head>
<body class="bg-surface text-on-surface font-body min-h-screen">

<aside class="h-screen w-72 fixed left-0 top-0 bg-white dark:bg-slate-900 flex flex-col p-6 space-y-8 z-50 border-r border-slate-100 dark:border-slate-800">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
        <div class="text-2xl font-bold tracking-tight text-blue-900 dark:text-blue-100 font-headline">Academic Curator</div>
    </div>

    <div class="flex items-center gap-3 px-2 py-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
        <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAjZ_oSRVSiGbl-3d0SW9fUmXz9Cu1MsAMPA7uZdp3KuIWCiPdAWXp15aOKt9aLa2FkwcUxtBO05z6u-ogifVlXzX56G2KA7UbUdMBSB1uMhIpCG03NhCTr70NwqcdWocj5NSzxeUSFF82mW0AxbY5Ft0tNfNS9NbjtTFERRBKfxxuLeeWGrJSXoPjfm_RGYDBXFDuelpRkwJIobR20MbVLBbgchPC_RKTmJU3n44N8Pwn4XffLrKhZ5N5a0ThzG72QhBaSNGmc0Xew" alt="User Avatar">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
        </div>
    </div>

    <a href="add_merit.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm">add</span>
        Record Merit
    </a>

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../index.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../events/index.php">
            <span class="material-symbols-outlined">event_note</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Events</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../achievements/index.php">
            <span class="material-symbols-outlined">verified</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Achievements</span>
        </a>
        
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
            <span class="material-symbols-outlined">military_tech</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Merits</span>
        </a>
        
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../clubs/index.php">
            <span class="material-symbols-outlined">groups</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../../profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
        </a>
    </nav>

    <div class="pt-6 border-t border-slate-200/50 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-blue-600 transition-colors" href="../../logout.php">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
        </a>
    </div>
</aside>

<header class="fixed top-0 right-0 left-72 bg-slate-50 flex justify-between items-center px-8 py-4 z-40 border-b border-slate-100">
    <div class="flex-1 max-w-xl">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
            <input class="w-full bg-surface-container-low border-none rounded-full py-2 pl-10 pr-4 focus:ring-2 focus:ring-primary transition-all text-sm" placeholder="Search your merit records..." type="text"/>
        </div>
    </div>
    <div class="flex items-center gap-6 ml-8">
        <div class="flex items-center gap-4">
            <button class="p-2 text-slate-600 hover:bg-slate-200/50 rounded-full transition-colors relative">
                <span class="material-symbols-outlined">settings</span>
            </button>
        </div>
        <div class="h-8 w-[1px] bg-slate-200"></div>
        <img alt="Profile" class="w-8 h-8 rounded-full border-2 border-primary-fixed" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB_ZUstEX2uJ8fxMVq-7RaP9RdQIPxE4A1MajOEIAbs3ZmZoJPwIJkUM3oWCbQo5P3jEIF2gRrNHp-Eo6w2APijwpGoQmwh6Oca9ORZPu294JVWkqCgXmupjlPGBwCyDRBJFl0I5R_1Ie5T3nEjuYx2KCUHn4kngTCWd6ZFquBHm_4e3cgAouUP-L2xgjWHhq72KHIwlrzAcd2HKUue6pV39BuyKrSHnFcgxpP7ELOPbRbMn_oMjvZddlddDm1Itg7xUCerH7BVtp02"/>
    </div>
</header>

<main class="ml-72 mt-20 p-12 bg-surface min-h-screen">
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="mb-8 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 flex items-center gap-3 rounded-lg shadow-sm">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium">Merit record successfully updated!</span>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-end mb-12">
        <div>
            <span class="block text-sm font-semibold Manrope uppercase tracking-[0.15em] text-primary mb-2">Curated Contributions</span>
            <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Merit Tracker</h1>
        </div>
        <a href="add_merit.php" class="signature-gradient text-white px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-xl hover:scale-[1.02] transition-transform active:scale-95 duration-150">
            <span class="material-symbols-outlined">add_circle</span>
            Record Merit
        </a>
    </div>

    <div class="grid grid-cols-12 gap-6 mb-12">
        <div class="col-span-12 md:col-span-8 bg-surface-container-lowest p-8 rounded-xl achievement-bloom shadow-sm">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-2xl font-bold font-headline text-primary mb-1">Volunteering & Engagement</h3>
                    <p class="text-slate-500 text-sm">Accumulated hours tracking your active campus involvement.</p>
                </div>
                <span class="bg-amber-100 text-amber-700 px-4 py-1 rounded-full text-xs font-bold border border-amber-200">
                    <?php echo ($total_hours >= 20) ? 'HONORS STATUS' : 'ACTIVE'; ?>
                </span>
            </div>
            <div class="flex gap-16 mt-4">
                <div>
                    <span class="block text-5xl font-black font-headline text-on-surface"><?php echo number_format($total_hours, 2); ?></span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1 block">Total Hours</span>
                </div>
                <div>
                    <span class="block text-5xl font-black font-headline text-on-surface"><?php echo str_pad($total_records, 2, '0', STR_PAD_LEFT); ?></span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1 block">Activities Logged</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden border border-slate-100">
        <div class="px-8 py-6 flex justify-between items-center bg-surface-container-low/50">
            <h2 class="text-lg font-bold font-headline text-on-surface">Merit History</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Activity & Organizer</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Date Completed</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label">Hours</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] font-label text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($all_merits) > 0): ?>
                        <?php foreach ($all_merits as $merit): ?>
                            <tr class="hover:bg-surface-container-low transition-colors group">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 min-w-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">military_tech</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-on-surface font-headline">
                                                <?php echo htmlspecialchars($merit['event_name'] ?? $merit['organizer']); ?>
                                            </span>
                                            <span class="text-[11px] text-slate-500 font-semibold tracking-wide uppercase mt-0.5">
                                                ORG: <?php echo htmlspecialchars($merit['organizer']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm text-slate-600 font-medium">
                                    <?php echo date('d M Y', strtotime($merit['date_completed'])); ?>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold shadow-sm">
                                        + <?php echo number_format($merit['hours'], 1); ?> hrs
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="edit_merit.php?id=<?php echo $merit['merit_id']; ?>" title="Edit" class="p-2 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-all">
                                            <span class="material-symbols-outlined text-lg">edit</span>
                                        </a>
                                        <a href="delete_merit.php?id=<?php echo $merit['merit_id']; ?>" onclick="return confirm('Are you sure you want to delete this merit record?')" title="Delete" class="p-2 text-slate-400 hover:text-error hover:bg-error/10 rounded-lg transition-all">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-8 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <span class="material-symbols-outlined text-6xl mb-4 opacity-50">volunteer_activism</span>
                                    <p class="font-medium text-lg text-slate-600">No merit records found.</p>
                                    <p class="text-sm mt-1 mb-4">Start recording your volunteering hours to build your profile.</p>
                                    <a href="add_merit.php" class="text-primary font-bold hover:underline">Record first merit →</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-8 py-4 border-t border-slate-100 flex justify-between items-center text-xs font-semibold text-slate-400 font-label">
            <span>Showing <?php echo $total_records; ?> records</span>
        </div>
    </div>
</main>

</body>
</html>