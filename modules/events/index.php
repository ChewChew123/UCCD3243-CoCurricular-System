<?php
// Start session to manage user login state
session_start();
// Import database connection configuration
require_once '../../includes/db_connect.php'; 
// Set timezone as per original code
date_default_timezone_set('Asia/Kuala_Lumpur');

/**
 * AUTHENTICATION CHECK
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/**
 * CAPTURE FILTER & SORT INPUTS (Keeping original functionality)
 */
$search = $_GET['search'] ?? '';
$min_date = $_GET['min_date'] ?? '';
$max_date = $_GET['max_date'] ?? '';
$min_time = $_GET['min_time'] ?? '';
$max_time = $_GET['max_time'] ?? '';
$sort_name = $_GET['sort_name'] ?? '';
$sort_date = $_GET['sort_date'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';

// Pagination settings
$page = $_GET['page'] ?? 1;
$limit = 8; // Grid-friendly limit
$offset = ($page - 1) * $limit;

// Fetch User Profile for Sidebar
$user_stmt = $conn->prepare("SELECT full_name, programme FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

// ==========================================
// LOGIC 1: FETCH MY JOINED EVENTS
// ==========================================
$my_events_sql = "SELECT e.*, c.club_name FROM events e 
                  JOIN event_participants ep ON e.event_id = ep.event_id 
                  LEFT JOIN clubs c ON e.club_id = c.club_id
                  WHERE ep.user_id = ? AND e.deleted = 0
                  ORDER BY e.event_date ASC";
$me_stmt = $conn->prepare($my_events_sql);
$me_stmt->bind_param("i", $user_id);
$me_stmt->execute();
$my_events = $me_stmt->get_result();

// ==========================================
// LOGIC 2: DISCOVER EVENTS WITH FILTERS
// ==========================================
$where = "WHERE e.deleted = 0";
if (!empty($type_filter)) { $where .= " AND e.event_type = '$type_filter'"; }
if (!empty($search)) { $where .= " AND e.event_name LIKE '%$search%'"; }
if (!empty($min_date) && !empty($max_date)) { $where .= " AND e.event_date BETWEEN '$min_date' AND '$max_date'"; }
elseif (!empty($min_date)) { $where .= " AND e.event_date >= '$min_date'"; }
elseif (!empty($max_date)) { $where .= " AND e.event_date <= '$max_date'"; }

// Combined Query: Fetch events and check if the current user has already joined
$sel_query = "SELECT e.*, c.club_name,
             (SELECT COUNT(*) FROM event_participants WHERE event_id = e.event_id AND user_id = ?) as is_joined
             FROM events e
             LEFT JOIN clubs c ON e.club_id = c.club_id
             $where";

// Apply Sorting logic
if ($sort_name == 'asc') { $sel_query .= " ORDER BY e.event_name ASC"; }
elseif ($sort_name == 'desc') { $sel_query .= " ORDER BY e.event_name DESC"; }
elseif ($sort_date == 'old') { $sel_query .= " ORDER BY e.event_date ASC"; }
else { $sel_query .= " ORDER BY e.event_date DESC"; }

$sel_query .= " LIMIT $limit OFFSET $offset";

$ae_stmt = $conn->prepare($sel_query);
$ae_stmt->bind_param("i", $user_id);
$ae_stmt->execute();
$all_events = $ae_stmt->get_result();
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Campus Events | Academic Curator</title>
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
                        "on-surface": "#141d23"
                    },
                    "fontFamily": { "headline": ["Manrope"], "body": ["Inter"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
        .event-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-surface text-on-surface font-body">

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
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($user_data['programme']); ?></p>
        </div>
    </div>

    <a href="add_event.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm">add</span>
        New Activity
    </a>

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../index.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Overview</span>
        </a>

        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
            <span class="material-symbols-outlined">event_note</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Events</span>
        </a>

        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../achievements/index.php">
            <span class="material-symbols-outlined">verified</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Achievements</span>
        </a>

        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../merits/index.php">
            <span class="material-symbols-outlined">military_tech</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Merits</span>
        </a>

        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../clubs/index.php">
            <span class="material-symbols-outlined">groups</span>
            <span class="text-sm font-semibold uppercase tracking-wider">Club Memberships</span>
        </a>

        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-sm font-semibold uppercase tracking-wider">My Profile</span>
        </a>
    </nav>

    <div class="pt-6 border-t border-slate-200/50 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-blue-600 transition-colors" href="../../logout.php">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-xs font-semibold uppercase tracking-wider">Log Out</span>
        </a>
    </div>
</aside>

<main class="ml-72 p-12 min-h-screen">
    <header class="flex justify-between items-end mb-12">
        <div>
            <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Campus Events</h1>
            <p class="text-slate-500 mt-2 text-lg">Browse, join, and track your co-curricular activity progress.</p>
        </div>
        <div class="flex gap-4">
            <a href="export_event_excel.php?search=<?php echo $search; ?>&type_filter=<?php echo $type_filter; ?>" 
               class="bg-emerald-50 text-emerald-700 px-6 py-3 rounded-full font-bold text-sm border border-emerald-100 flex items-center gap-2 hover:bg-emerald-100 transition-all">
                <span class="material-symbols-outlined">download</span>
                Export Records
            </a>
            <a href="add_event.php" class="signature-gradient text-white px-8 py-3 rounded-full font-bold flex items-center gap-2 shadow-lg hover:opacity-90 transition-all">
                <span class="material-symbols-outlined">add_circle</span>
                New Event
            </a>
        </div>
    </header>

    <?php if ($my_events->num_rows > 0): ?>
    <section class="mb-16">
        <div class="flex items-center gap-2 mb-6">
            <div class="p-2 bg-blue-100 text-primary rounded-lg">
                <span class="material-symbols-outlined" style="font-size: 20px;">calendar_today</span>
            </div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">My Registered Schedule</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($me = $my_events->fetch_assoc()): ?>
                <div class="bg-blue-900 text-white p-8 rounded-[2.5rem] shadow-xl relative overflow-hidden group">
                    <div class="absolute -top-4 -right-4 p-4 opacity-10 group-hover:rotate-12 transition-transform">
                        <span class="material-symbols-outlined" style="font-size: 120px;">verified</span>
                    </div>
                    <span class="text-[10px] font-black tracking-widest uppercase bg-blue-600/50 backdrop-blur px-3 py-1 rounded-full mb-6 inline-block">Registered</span>
                    <h3 class="text-2xl font-bold mb-2 leading-tight"><?php echo htmlspecialchars($me['event_name']); ?></h3>
                    <p class="text-blue-200 text-sm flex items-center gap-2 mb-8 uppercase font-bold tracking-widest">
                        <span class="material-symbols-outlined text-[16px]">location_on</span>
                        <?php echo htmlspecialchars($me['location'] ?? 'Campus Site'); ?>
                    </p>
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-blue-300 uppercase">Happening On</span>
                            <span class="font-bold"><?php echo date('d M, Y', strtotime($me['event_date'])); ?></span>
                        </div>
                        <a href="event_details.php?event_id=<?php echo $me['event_id']; ?>" class="w-12 h-12 bg-white/20 hover:bg-white/40 rounded-2xl flex items-center justify-center transition-colors group">
                            <span class="material-symbols-outlined text-white">visibility</span>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="mb-12 bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
        <form method="GET" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Search Keywords</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter event name..." class="w-full pl-12 pr-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">Event Category</label>
                    <select name="type_filter" class="w-full bg-slate-50 border-none rounded-xl py-3 focus:ring-2 focus:ring-primary">
                        <option value="">All Categories</option>
                        <?php 
                        $types = ["Seminar", "Workshop", "Competition", "Volunteering", "Club Activity", "Sports", "Cultural", "Leadership"];
                        foreach($types as $t) {
                            $sel = ($type_filter == $t) ? 'selected' : '';
                            echo "<option value='$t' $sel>$t</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-primary text-white font-bold py-3 rounded-xl shadow-md hover:bg-blue-800 transition-all">Apply Filter</button>
                    <a href="index.php" class="p-3 bg-slate-100 text-slate-400 rounded-xl hover:text-primary transition-all">
                        <span class="material-symbols-outlined">restart_alt</span>
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-4 border-t border-slate-50">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">From Date</label>
                    <input type="date" name="min_date" value="<?php echo $min_date; ?>" class="w-full bg-slate-50 border-none rounded-xl py-2 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">To Date</label>
                    <input type="date" name="max_date" value="<?php echo $max_date; ?>" class="w-full bg-slate-50 border-none rounded-xl py-2 text-sm">
                </div>
                <div class="flex items-end gap-4 md:col-span-2">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-400">
                        Sort By:
                        <a href="?sort_name=<?php echo $sort_name == 'asc' ? 'desc' : 'asc'; ?>" class="bg-slate-50 px-3 py-1 rounded-md hover:text-primary">Name</a>
                        <a href="?sort_date=<?php echo $sort_date == 'old' ? 'new' : 'old'; ?>" class="bg-slate-50 px-3 py-1 rounded-md hover:text-primary">Date</a>
                    </label>
                </div>
            </div>
        </form>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php if ($all_events->num_rows > 0): ?>
            <?php while($event = $all_events->fetch_assoc()): ?>
            <div class="bg-white rounded-[2.5rem] p-3 border border-slate-100 event-card transition-all duration-300 flex flex-col">
                <div class="w-full h-44 bg-slate-100 rounded-[2rem] mb-6 overflow-hidden relative group">
                    <?php if (!empty($event['event_poster'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($event['event_poster']); ?>" 
                            alt="Event Poster"
                            class="w-full h-full object-cover absolute inset-0 z-0">
                    <?php else: ?>
                        <img src="../../uploads/default.png" 
                            alt="Default Poster"
                            class="w-full h-full object-cover absolute inset-0 z-0">
                    <?php endif; ?>

                    <a href="event_details.php?event_id=<?php echo $event['event_id']; ?>" 
                    class="absolute inset-0 bg-blue-900/0 group-hover:bg-blue-900/40 transition-colors flex items-center justify-center z-10">
                        <span class="material-symbols-outlined text-white opacity-0 group-hover:opacity-100 text-4xl transition-opacity">
                            visibility
                        </span>
                    </a>

                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-[9px] font-black text-primary uppercase z-20">
                        <?php echo htmlspecialchars($event['event_type']); ?>
                    </div>
                </div>
                
                <div class="px-4 pb-4 flex-1 flex flex-col">
                    <h3 class="font-bold text-slate-800 text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-semibold mb-6">
                        <span class="material-symbols-outlined text-sm">groups</span>
                        <?php echo htmlspecialchars($event['club_name'] ?? 'Admin Hosted'); ?>
                    </div>
                    
                    <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-slate-300 uppercase tracking-tighter font-headline">Event Date</span>
                            <span class="text-xs font-bold text-slate-700"><?php echo date('d M Y', strtotime($event['event_date'])); ?></span>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="event_details.php?event_id=<?php echo $event['event_id']; ?>" 
                               title="View Detail"
                               class="p-2.5 bg-slate-50 text-slate-400 rounded-full hover:text-primary transition-colors">
                                <span class="material-symbols-outlined text-[20px]">info</span>
                            </a>

                            <?php if ($event['is_joined'] > 0): ?>
                                <div class="bg-emerald-50 text-emerald-600 p-2.5 px-4 rounded-full font-bold text-[10px] flex items-center gap-1 border border-emerald-100">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    Joined
                                </div>
                            <?php else: ?>
                                <a href="process_join_event.php?id=<?php echo $event['event_id']; ?>" 
                                   class="signature-gradient text-white px-6 py-2.5 rounded-full font-bold text-xs shadow-md hover:scale-105 active:scale-95 transition-all">
                                    Join
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full py-24 text-center bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                <span class="material-symbols-outlined text-slate-200 text-6xl mb-4">search_off</span>
                <p class="text-slate-400 font-medium">No events found matching your criteria.</p>
                <a href="index.php" class="text-primary font-bold hover:underline mt-2 inline-block">Clear all filters</a>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
