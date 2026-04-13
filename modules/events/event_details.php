<?php
/**
 * File: event_details.php (Events Module)
 * Path: /modules/events/event_details.php
 * Purpose: Detailed view of a specific event with role-based participation controls.
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
$event_id = $_GET['event_id'] ?? 0;

// 2. FETCH USER PROFILE FOR SIDEBAR
$user_stmt = $conn->prepare("SELECT full_name, programme FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// 3. FETCH EVENT DETAILS
$query = "SELECT e.*, c.club_name 
          FROM events e 
          LEFT JOIN clubs c ON e.club_id = c.club_id 
          WHERE e.event_id = ? AND e.deleted = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    die("Event not found or has been removed.");
}

// 4. FETCH PARTICIPANT COUNT & USER STATUS
// Count total participants
$count_query = "SELECT COUNT(*) AS total FROM event_participants WHERE event_id = ?";
$c_stmt = $conn->prepare($count_query);
$c_stmt->bind_param("i", $event_id);
$c_stmt->execute();
$participant_count = $c_stmt->get_result()->fetch_assoc()['total'];

// Check if current user (Student) has joined
$is_joined = false;
if (!$is_admin) {
    $check_sql = "SELECT 1 FROM event_participants WHERE event_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $event_id, $user_id);
    $check_stmt->execute();
    $is_joined = $check_stmt->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo htmlspecialchars($row['event_name']); ?> | Academic Curator</title>
    
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
        <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=003f87&color=fff" alt="User Avatar">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 truncate"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
        </div>
    </div>

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../../index.php"><span class="material-symbols-outlined">dashboard</span><span class="text-sm font-semibold uppercase tracking-wider">Overview</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php"><span class="material-symbols-outlined">event_note</span><span class="text-sm font-semibold uppercase tracking-wider">Events</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../achievements/index.php"><span class="material-symbols-outlined">verified</span><span class="text-sm font-semibold uppercase tracking-wider">Achievements</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../merits/index.php"><span class="material-symbols-outlined">military_tech</span><span class="text-sm font-semibold uppercase tracking-wider">Merits</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../clubs/index.php"><span class="material-symbols-outlined">groups</span><span class="text-sm font-semibold uppercase tracking-wider">Clubs</span></a>
    </nav>
</aside>

<main class="ml-72 p-12">
    <header class="mb-12">
        <a href="index.php" class="text-xs font-black uppercase tracking-widest text-slate-400 hover:text-primary transition-all flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Return to Discovery
        </a>
        <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight"><?php echo htmlspecialchars($row['event_name']); ?></h1>
        <p class="text-slate-500 mt-2 text-lg">Hosted by <strong><?php echo htmlspecialchars($row['club_name'] ?? 'Academic Admin'); ?></strong></p>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <div class="lg:col-span-2 space-y-10">
            <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm flex justify-center items-center relative overflow-hidden group">
                <?php if (!empty($row['event_poster'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($row['event_poster']); ?>" 
                         class="h-64 w-auto object-cover rounded-[2rem] cursor-pointer hover:opacity-90 transition-opacity" 
                         data-bs-toggle="modal" 
                         data-bs-target="#posterModal"
                         alt="Event Poster">
                <?php else: ?>
                    <div class="h-64 w-auto flex-1 bg-slate-100 flex flex-col items-center justify-center rounded-[2rem] text-slate-400">
                        <span class="material-symbols-outlined text-5xl mb-2">image_not_supported</span>
                        <p class="text-xs font-bold uppercase tracking-widest">No Poster Available</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-3xl border border-slate-100 text-center">
                    <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Type</span>
                    <span class="text-sm font-bold text-primary"><?php echo htmlspecialchars($row['event_type']); ?></span>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 text-center">
                    <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Date</span>
                    <span class="text-sm font-bold text-slate-800"><?php echo date('d M Y', strtotime($row['event_date'])); ?></span>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 text-center">
                    <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Time</span>
                    <span class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($row['event_time']); ?></span>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 text-center">
                    <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Attending</span>
                    <span class="text-sm font-bold text-emerald-600"><?php echo $participant_count; ?> registered</span>
                </div>
            </div>

            <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Venue & Logistics</h4>
                <div class="flex items-start gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">location_on</span>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($row['event_location']); ?></p>
                        <p class="text-xs text-slate-400 font-medium">Please arrive 15 minutes before the start time.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <span class="material-symbols-outlined">event_busy</span>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-slate-800"><?php echo date('d M Y', strtotime($row['register_expired_date'])); ?></p>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-widest font-black">Registration Deadline</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm sticky top-12">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-8 text-center">Engagement Tools</h4>
                
                <div class="flex flex-col gap-4">
                    <?php if ($is_admin): ?>
                        <a href="update_event.php?event_id=<?php echo $row['event_id']; ?>" class="signature-gradient text-white w-full py-4 rounded-full font-black text-xs uppercase tracking-widest text-center shadow-lg hover:opacity-90">Edit Event Details</a>
                        <a href="view_participants.php?event_id=<?php echo $row['event_id']; ?>" class="bg-slate-50 text-slate-600 w-full py-4 rounded-full font-black text-xs uppercase tracking-widest text-center border border-slate-100 hover:bg-slate-100">Manage Participants (<?php echo $participant_count; ?>)</a>
                    <?php else: ?>
                        <?php if ($is_joined): ?>
                            <div class="bg-emerald-50 text-emerald-600 w-full py-4 rounded-full font-black text-xs uppercase tracking-widest text-center border border-emerald-100 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">verified</span> Already Joined
                            </div>
                            <a href="leave_event.php?event_id=<?php echo $row['event_id']; ?>" onclick="return confirm('Withdraw from this event?')" class="text-red-500 w-full py-4 rounded-full font-black text-[10px] uppercase tracking-widest text-center hover:bg-red-50 transition-all">Cancel Registration</a>
                        <?php else: ?>
                            <a href="process_join_event.php?id=<?php echo $row['event_id']; ?>" onclick="return confirm('Confirm participation?')" class="signature-gradient text-white w-full py-4 rounded-full font-black text-xs uppercase tracking-widest text-center shadow-lg hover:opacity-90">Join This Event</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-8 pt-8 border-t border-slate-50">
                    <div class="flex justify-between items-center text-[10px] font-black uppercase text-slate-400 tracking-tighter">
                        <span>System ID</span>
                        <span class="text-slate-800">#EV-<?php echo $row['event_id']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black uppercase text-slate-400 tracking-tighter mt-2">
                        <span>Logged On</span>
                        <span class="text-slate-800"><?php echo date('d M Y', strtotime($row['date_record'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="posterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> <div class="modal-content bg-slate-900 border-0 rounded-[2rem] overflow-hidden shadow-2xl mx-auto max-w-lg"> <div class="modal-body p-0 relative">
                <button type="button" class="btn-close btn-close-white absolute top-6 right-6 z-50" data-bs-dismiss="modal"></button>
                <img src="uploads/<?php echo $row['event_poster']; ?>" class="w-full h-auto max-h-[80vh] object-contain">
                <div class="p-6 bg-slate-900 flex justify-between items-center">
                    <span class="text-white text-xs font-bold uppercase tracking-widest"><?php echo htmlspecialchars($row['event_name']); ?></span>
                    <a href="uploads/<?php echo $row['event_poster']; ?>" download class="text-emerald-400 text-[10px] font-black uppercase tracking-widest hover:underline">Download Poster</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>