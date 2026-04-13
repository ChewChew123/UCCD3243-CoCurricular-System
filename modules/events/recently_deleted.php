<?php
/**
 * File: recently_deleted.php
 * Purpose: Recipient bin for soft-deleted events. Allows restoration or permanent removal.
 */
session_start();
require_once '../../includes/db_connect.php'; 

// 1. AUTHENTICATION & ADMIN CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. FETCH USER DATA FOR SIDEBAR
$u_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$u_stmt = $conn->prepare($u_sql);
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user_data = $u_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Administrator';

// 3. FETCH SOFT-DELETED RECORDS (deleted = 1)
$query = "SELECT e.*, c.club_name FROM events e
          JOIN clubs c ON e.club_id = c.club_id
          WHERE e.deleted = 1
          ORDER BY e.event_id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recycle Bin | Academic Curator</title>
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

<aside class="h-screen w-72 fixed left-0 top-0 bg-white border-r border-slate-100 flex flex-col p-6 space-y-8 z-50">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
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
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../../index.php"><span class="material-symbols-outlined">dashboard</span><span class="text-sm font-semibold uppercase tracking-wider">Overview</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php"><span class="material-symbols-outlined">event_note</span><span class="text-sm font-semibold uppercase tracking-wider">Events</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../achievements/index.php"><span class="material-symbols-outlined">verified</span><span class="text-sm font-semibold uppercase tracking-wider">Achievements</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../merits/index.php"><span class="material-symbols-outlined">military_tech</span><span class="text-sm font-semibold uppercase tracking-wider">Merits</span></a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:bg-slate-100" href="../clubs/index.php"><span class="material-symbols-outlined">groups</span><span class="text-sm font-semibold uppercase tracking-wider">Clubs</span></a>
    </nav>
</aside>

<main class="ml-72 p-12">
    <header class="flex justify-between items-end mb-12">
        <div>
            <h1 class="text-5xl font-black font-headline text-on-surface tracking-tight">Recycle Bin</h1>
            <p class="text-slate-500 mt-2 text-lg">Manage recently deleted event records.</p>
        </div>
        <a href="index.php" class="bg-slate-100 text-slate-600 px-6 py-3 rounded-full font-bold text-sm hover:bg-slate-200 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Events
        </a>
    </header>

    <?php if (isset($_GET['msg'])): ?>
        <div class="mb-8 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 font-bold rounded-xl shadow-sm">
            <?php echo ($_GET['msg'] == 'restored') ? 'Records restored successfully!' : 'Records permanently deleted!'; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="recycle_action.php">
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 w-12"><input type="checkbox" onclick="toggleAll(this)" class="rounded text-primary focus:ring-primary"></th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Event Detail</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date & Time</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Organizer</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Restore</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-8 py-6"><input type="checkbox" name="ids[]" value="<?php echo $row['event_id']; ?>" class="rounded text-primary focus:ring-primary"></td>
                            <td class="px-8 py-6">
                                <span class="font-bold text-slate-800 block"><?php echo htmlspecialchars($row['event_name']); ?></span>
                                <span class="text-[10px] text-slate-400 uppercase tracking-tighter"><?php echo htmlspecialchars($row['event_location']); ?></span>
                            </td>
                            <td class="px-8 py-6"><span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[9px] font-black uppercase"><?php echo htmlspecialchars($row['event_type']); ?></span></td>
                            <td class="px-8 py-6 text-sm text-slate-500 font-medium"><?php echo date('d M Y', strtotime($row['event_date'])); ?><br><span class="text-[10px]"><?php echo $row['event_time']; ?></span></td>
                            <td class="px-8 py-6 text-sm font-bold text-primary"><?php echo htmlspecialchars($row['club_name']); ?></td>
                            <td class="px-8 py-6 text-right">
                                <a href="recycle_action.php?restore=<?php echo $row['event_id']; ?>" class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-700 font-black text-[10px] uppercase tracking-widest">
                                    <span class="material-symbols-outlined text-sm">settings_backup_restore</span> Restore
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($result) == 0): ?>
                            <tr><td colspan="6" class="px-8 py-20 text-center text-slate-300 font-bold uppercase text-[10px] tracking-[0.2em]">Recycle bin is empty</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center">
                <button type="submit" name="action" value="permanent_delete" onclick="return confirm('Permanently remove these records? This cannot be undone.')" class="text-red-600 hover:text-red-700 font-black text-[10px] uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">delete_forever</span> Delete Permanently
                </button>
                <button type="submit" name="action" value="restore" class="signature-gradient text-white px-8 py-3 rounded-full font-black text-[10px] uppercase tracking-widest shadow-lg hover:opacity-90">
                    Bulk Restore Records
                </button>
            </div>
        </div>
    </form>
</main>

<script>
function toggleAll(source) {
    let checkboxes = document.getElementsByName('ids[]');
    for (let i = 0; i < checkboxes.length; i++) checkboxes[i].checked = source.checked;
}
</script>

</body>
</html>