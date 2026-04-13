<?php
/**
 * File: index.php (Events Module)
 * Path: /modules/events/index.php
 * Purpose: Central hub for browsing, joining, and managing campus activities.
 */
session_start();
require_once '../../includes/db_connect.php'; 
date_default_timezone_set('Asia/Kuala_Lumpur');

// 1. AUTHENTICATION & ROLE CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// 2. CAPTURE FILTER & SEARCH INPUTS
$search = $_GET['search'] ?? '';
$min_date = $_GET['min_date'] ?? '';
$max_date = $_GET['max_date'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';
$sort_name = $_GET['sort_name'] ?? '';
$sort_date = $_GET['sort_date'] ?? '';

// Pagination logic
$page = $_GET['page'] ?? 1;
$limit = 8; 
$offset = ($page - 1) * $limit;

// 3. FETCH USER PROFILE DATA FOR SIDEBAR
$user_stmt = $conn->prepare("SELECT full_name, programme FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

// 4. FETCH MY JOINED EVENTS (Specifically for the "Registered" section for Students)
$my_events_sql = "SELECT e.*, c.club_name FROM events e 
                  JOIN event_participants ep ON e.event_id = ep.event_id 
                  LEFT JOIN clubs c ON e.club_id = c.club_id
                  WHERE ep.user_id = ? AND e.deleted = 0
                  ORDER BY e.event_date ASC";
$me_stmt = $conn->prepare($my_events_sql);
$me_stmt->bind_param("i", $user_id);
$me_stmt->execute();
$my_events = $me_stmt->get_result();

// 5. DISCOVERY LOGIC: FETCH EVENTS WITH DYNAMIC FILTERS
$where = "WHERE e.deleted = 0";
if (!empty($type_filter)) { $where .= " AND e.event_type = '" . $conn->real_escape_string($type_filter) . "'"; }
if (!empty($search)) { $where .= " AND e.event_name LIKE '%" . $conn->real_escape_string($search) . "%'"; }
if (!empty($min_date) && !empty($max_date)) { $where .= " AND e.event_date BETWEEN '$min_date' AND '$max_date'"; }

// Combined Query: Fetch events and check if the current user has already joined
$sel_query = "SELECT e.*, c.club_name,
             (SELECT COUNT(*) FROM event_participants WHERE event_id = e.event_id AND user_id = ?) as is_joined
             FROM events e
             LEFT JOIN clubs c ON e.club_id = c.club_id
             $where";

// Sorting handlers
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
        .event-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-surface text-on-surface font-body min-h-screen">

<aside class="h-screen w-72 fixed left-0 top-0 bg-white border-r border-slate-100 flex flex-col p-6 space-y-8 z-50 shadow-sm">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
        <div class="text-2xl font-bold tracking-tight text-blue-900 font-headline">Academic Curator</div>
    </div>

    <div class="flex items-center gap-3 px-2 py-4 bg-slate-50 rounded-2xl">
        <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=003f87&color=fff" alt="User">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 truncate"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($user_data['programme']); ?></p>
        </div>
    </div>

    <?php if ($is_admin): ?>
    <a href="add_event.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm">add</span> New Activity
    </a>
    <?php endif; ?>

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../../index.php"><span class="material-symbols-outlined">dashboard</span><span class="text-sm font-semibold uppercase tracking-wider">Overview</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php"><span class="material-symbols-outlined">event_note</span><span class="text-sm font-semibold uppercase tracking-wider">Events</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../achievements/index.php"><span class="material-symbols-outlined">verified</span><span class="text-sm font-semibold uppercase tracking-wider">Achievements</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../merits/index.php"><span class="material-symbols-outlined">military_tech</span><span class="text-sm font-semibold uppercase tracking-wider">Merits</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../clubs/index.php"><span class="material-symbols-outlined">groups</span><span class="text-sm font-semibold uppercase tracking-wider">Clubs</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../../profile.php"><span class="material-symbols-outlined">person</span><span class="text-sm font-semibold uppercase tracking-wider">My Profile</span></a>
    </nav>

    <div class="pt-6 border-t border-slate-200/50">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-red-600 transition-colors" href="../../logout.php">
            <span class="material-symbols-outlined">logout</span><span class="text-xs font-semibold uppercase tracking-wider">Log Out</span>
        </a>
    </div>
</aside>

<main class="ml-72 p-12 min-h-screen">
    <header class="flex justify-between items-end mb-12">
        <div>
            <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Campus Events</h1>
            <p class="text-slate-500 mt-2 text-lg">Browse curated activities and track your co-curricular progress.</p>
        </div>
        <div class="flex gap-4">
            <a href="export_event_excel.php?search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>" class="bg-emerald-50 text-emerald-700 px-6 py-3 rounded-full font-bold text-sm border border-emerald-100 flex items-center gap-2 hover:bg-emerald-100 transition-all">
                <span class="material-symbols-outlined">download</span> Export Records
            </a>
            <?php if ($is_admin): ?>
            <a href="add_event.php" class="signature-gradient text-white px-8 py-3 rounded-full font-bold flex items-center gap-2 shadow-lg hover:opacity-90 transition-all">
                <span class="material-symbols-outlined">add_circle</span> New Event
            </a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (!$is_admin && $my_events->num_rows > 0): ?>
    <section class="mb-16">
        <div class="flex items-center gap-2 mb-6">
            <div class="p-2 bg-blue-100 text-primary rounded-lg">
                <span class="material-symbols-outlined" style="font-size: 20px;">task_alt</span>
            </div>
            <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">My Registered Schedule</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($me = $my_events->fetch_assoc()): ?>
                <div class="bg-blue-900 text-white p-8 rounded-[2.5rem] shadow-xl relative overflow-hidden group">
                    <div class="absolute -top-4 -right-4 p-4 opacity-10 group-hover:rotate-12 transition-transform">
                        <span class="material-symbols-outlined" style="font-size: 120px;">verified</span>
                    </div>
                    
                    <span class="text-[10px] font-black tracking-widest uppercase bg-blue-600/50 backdrop-blur px-3 py-1 rounded-full mb-6 inline-block">Confirmed Attendance</span>
                    
                    <h3 class="text-2xl font-bold mb-2 leading-tight"><?php echo htmlspecialchars($me['event_name']); ?></h3>
                    
                    <p class="text-blue-200 text-sm flex items-center gap-2 mb-8 uppercase font-bold tracking-widest">
                        <span class="material-symbols-outlined text-[16px]">location_on</span>
                        <?php echo htmlspecialchars($me['event_location'] ?? 'Campus Site'); ?>
                    </p>
                    
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-blue-300 uppercase">Happening On</span>
                            <span class="font-bold text-lg"><?php echo date('d M, Y', strtotime($me['event_date'])); ?></span>
                        </div>
                        <a href="event_details.php?event_id=<?php echo $me['event_id']; ?>" class="w-12 h-12 bg-white/20 hover:bg-white/40 rounded-2xl flex items-center justify-center transition-colors">
                            <span class="material-symbols-outlined text-white">visibility</span>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="flex items-center gap-2 mb-6">
        <div class="p-2 bg-slate-100 text-slate-500 rounded-lg">
            <span class="material-symbols-outlined" style="font-size: 20px;">explore</span>
        </div>
        <h2 class="text-xl font-bold text-slate-800 uppercase tracking-wider">Discover Activities</h2>
    </div>

    <section class="mb-12 bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Search Keywords</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter event title..." class="w-full pl-12 pr-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-primary transition-all">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Category</label>
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
                <a href="index.php" class="p-3 bg-slate-100 text-slate-400 rounded-xl hover:text-primary transition-all"><span class="material-symbols-outlined">restart_alt</span></a>
            </div>
        </form>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php if ($all_events->num_rows > 0): ?>
            <?php while($event = $all_events->fetch_assoc()): ?>
            <div class="bg-white rounded-[2.5rem] p-3 border border-slate-100 event-card transition-all duration-300 flex flex-col">
                <div class="w-full h-44 bg-slate-100 rounded-[2rem] mb-6 overflow-hidden relative group">
                    <img src="<?php echo !empty($event['event_poster']) ? 'uploads/'.htmlspecialchars($event['event_poster']) : '../../uploads/default.png'; ?>" 
                         class="w-full h-full object-cover">
                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-[9px] font-black text-primary uppercase z-10">
                        <?php echo htmlspecialchars($event['event_type']); ?>
                    </div>
                    <a href="event_details.php?event_id=<?php echo $event['event_id']; ?>" 
                       class="absolute inset-0 bg-blue-900/0 group-hover:bg-blue-900/40 transition-colors flex items-center justify-center z-0">
                        <span class="material-symbols-outlined text-white opacity-0 group-hover:opacity-100 text-3xl transition-opacity">visibility</span>
                    </a>
                </div>
                
                <div class="px-4 pb-4 flex-1 flex flex-col">
                    <h3 class="font-bold text-slate-800 text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-semibold mb-6">
                        <span class="material-symbols-outlined text-sm">groups</span>
                        <?php echo htmlspecialchars($event['club_name'] ?? 'Academic Admin'); ?>
                    </div>
                    
                    <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-slate-300 uppercase tracking-tighter">Event Date</span>
                            <span class="text-xs font-bold text-slate-700"><?php echo date('d M Y', strtotime($event['event_date'])); ?></span>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="event_details.php?event_id=<?php echo $event['event_id']; ?>" title="View Details" class="p-2.5 bg-slate-50 text-slate-400 rounded-full hover:text-primary transition-all">
                                <span class="material-symbols-outlined text-[20px]">info</span>
                            </a>

                            <?php if (!$is_admin): ?>
                                <?php if ($event['is_joined'] > 0): ?>
                                    <div class="bg-emerald-50 text-emerald-600 px-4 py-2 rounded-full font-black text-[10px] flex items-center gap-1 border border-emerald-100">
                                        <span class="material-symbols-outlined text-xs">check</span> Joined
                                    </div>
                                <?php else: ?>
                                    <a href="process_join_event.php?id=<?php echo $event['event_id']; ?>" 
                                       class="signature-gradient text-white px-6 py-2 rounded-full font-black text-xs shadow-md hover:scale-105 active:scale-95 transition-all">
                                        Join
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="update_event.php?event_id=<?php echo $event['event_id']; ?>" title="Edit Record" class="p-2.5 bg-blue-50 text-blue-600 rounded-full hover:bg-blue-600 hover:text-white transition-all">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </a>
                                <a href="delete_event.php?event_id=<?php echo $event['event_id']; ?>" 
                                   onclick="return confirm('Permanently remove this event record?')" 
                                   title="Delete Record" class="p-2.5 bg-red-50 text-red-600 rounded-full hover:bg-red-600 hover:text-white transition-all">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full py-24 text-center bg-white rounded-[3rem] border border-dashed border-slate-200">
                <span class="material-symbols-outlined text-slate-200 text-6xl mb-4">search_off</span>
                <p class="text-slate-400 font-medium">No events matched your current filters.</p>
                <a href="index.php" class="text-primary font-bold hover:underline mt-2 inline-block">Clear All Filters</a>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>