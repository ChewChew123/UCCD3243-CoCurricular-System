<?php
/**
 * File: club_list.php (Clubs Module)
 * Purpose: Global directory of all clubs and Admin management interface (Disband/Edit).
 */
session_start();
require_once '../../includes/db_connect.php';

$conn->query("ALTER TABLE clubs ADD COLUMN IF NOT EXISTS club_status VARCHAR(20) NOT NULL DEFAULT 'Active'");

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");

    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Fetch User Profile Details for Sidebar
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// Fetch All Clubs with their Active Member Count
// Assuming your clubs table has a 'club_status' column. If not, it defaults to 'Active' via PHP logic below.
$sql = "SELECT c.club_id, c.club_name, c.club_category, c.club_status, 
               (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.club_id AND cm.member_status = 'Active') as active_members 
        FROM clubs c 
        ORDER BY c.club_name ASC";
$clubs_result = $conn->query($sql);

$total_clubs = 0;
$active_clubs = 0;
$disbanded_clubs = 0;
$all_clubs = [];

while ($row = $clubs_result->fetch_assoc()) {
    // Fallback if club_status column is empty or doesn't exist yet
    $status = !empty($row['club_status']) ? $row['club_status'] : 'Active';
    $row['club_status'] = $status;
    
    $all_clubs[] = $row;
    $total_clubs++;
    
    if ($status === 'Active') $active_clubs++;
    if ($status === 'Disbanded') $disbanded_clubs++;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Club Directory | The Academic Curator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
          tailwind.config = {
            theme: {
              extend: {
                colors: {
                    primary: "#003f87",
                    surface: "#f6faff",
                    background: "#f6faff",
                    "on-surface": "#141d23"
                },
                fontFamily: { headline: ["Manrope"], body: ["Inter"] }
              }
            }
          }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
    </style>
</head>
<body class="bg-background text-on-background font-body">

<aside class="h-screen w-72 fixed left-0 top-0 bg-white border-r border-slate-100 shadow-sm flex flex-col p-6 space-y-8 z-50">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white"><span class="material-symbols-outlined">auto_stories</span></div>
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
        <img alt="User profile" class="w-8 h-8 rounded-full border-2 border-primary" src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=003f87&color=fff"/>
    </div>
</header>

<main class="ml-72 mt-20 p-12 bg-surface min-h-screen">
    
    <?php if (isset($_GET['action'])): ?>
        <?php if ($_GET['action'] === 'disbanded'): ?>
            <div class="mb-8 p-4 bg-amber-100 border-l-4 border-amber-500 text-amber-700 flex items-center gap-3 rounded-lg shadow-sm">
                <span class="material-symbols-outlined">warning</span>
                <span class="font-medium">The club has been successfully disbanded. Member statuses have been updated.</span>
            </div>
        <?php elseif ($_GET['action'] === 'restored'): ?>
            <div class="mb-8 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 flex items-center gap-3 rounded-lg shadow-sm">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-medium">The club has been successfully restored to Active status.</span>
            </div>
        <?php elseif ($_GET['action'] === 'error'): ?>
            <div class="mb-8 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 flex items-center gap-3 rounded-lg shadow-sm">
                <span class="material-symbols-outlined">error</span>
                <span class="font-medium">An error occurred while processing your request. Please try again.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="flex justify-between items-end mb-12">
        <div>
            <span class="block text-sm font-semibold uppercase tracking-[0.15em] text-primary mb-2">Institution Infrastructure</span>
            <h1 class="text-5xl font-black font-headline text-slate-800 tracking-tight">Club Directory</h1>
        </div>
        
        <div>
            <?php if ($is_admin): ?>
                <a href="add_club.php" class="bg-primary hover:bg-blue-800 text-white px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-xl transition-all active:scale-95">
                    <span class="material-symbols-outlined">domain_add</span> Register New Club
                </a>
            <?php else: ?>
                <a href="index.php" class="bg-white border border-slate-200 text-slate-600 px-8 py-4 rounded-full font-bold flex items-center gap-3 shadow-sm hover:bg-slate-50 transition-all">
                    <span class="material-symbols-outlined">arrow_back</span> Back to My Memberships
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
            <span class="block text-4xl font-black text-slate-800"><?php echo str_pad($total_clubs, 2, '0', STR_PAD_LEFT); ?></span>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Total Registered</span>
        </div>
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-emerald-100">
            <span class="block text-4xl font-black text-emerald-600"><?php echo str_pad($active_clubs, 2, '0', STR_PAD_LEFT); ?></span>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Active Operations</span>
        </div>
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-amber-100">
            <span class="block text-4xl font-black text-amber-600"><?php echo str_pad($disbanded_clubs, 2, '0', STR_PAD_LEFT); ?></span>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Disbanded / Inactive</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-100">
        <div class="px-8 py-6 flex justify-between items-center bg-slate-50/50">
            <h2 class="text-lg font-bold font-headline text-slate-800">Organizational Roster</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/20">
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Club Details</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Category</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Members</th>
                        <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Operational Status</th>
                        <?php if ($is_admin): ?>
                            <th class="px-8 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Management</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (count($all_clubs) > 0): ?>
                        <?php foreach ($all_clubs as $club): ?>
                        <tr class="hover:bg-slate-50 transition-colors group <?php echo $club['club_status'] === 'Disbanded' ? 'opacity-60 grayscale' : ''; ?>">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg <?php echo $club['club_status'] === 'Disbanded' ? 'bg-slate-100 text-slate-400' : 'bg-blue-50 text-primary'; ?> flex items-center justify-center">
                                        <span class="material-symbols-outlined">domain</span>
                                    </div>
                                    <span class="font-bold text-slate-800 font-headline"><?php echo htmlspecialchars($club['club_name']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-sm text-slate-500 font-medium"><?php echo htmlspecialchars($club['club_category'] ?? 'General'); ?></td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-slate-400 text-sm">person</span>
                                    <span class="font-bold text-slate-700"><?php echo htmlspecialchars($club['active_members']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <?php 
                                    $status_color = ($club['club_status'] == 'Active') ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-slate-500 bg-slate-100 border-slate-200';
                                ?>
                                <span class="px-3 py-1 rounded-full border text-[9px] font-black uppercase tracking-wider <?php echo $status_color; ?>">
                                    <?php echo htmlspecialchars($club['club_status']); ?>
                                </span>
                            </td>
                            
                            <?php if ($is_admin): ?>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <?php if ($club['club_status'] === 'Active'): ?>
                                        <a href="disband_club.php?club_id=<?php echo $club['club_id']; ?>&action=disband" onclick="return confirm('WARNING: Disbanding this club will change its status and may affect current active members. Proceed?')" class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Disband Club">
                                            <span class="material-symbols-outlined text-lg">block</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="disband_club.php?club_id=<?php echo $club['club_id']; ?>&action=restore" onclick="return confirm('Restore this club to Active status?')" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Restore Club">
                                            <span class="material-symbols-outlined text-lg">settings_backup_restore</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="px-8 py-12 text-center text-slate-400 italic">No clubs registered in the system.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>