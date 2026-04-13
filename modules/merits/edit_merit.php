<?php
// File path: modules/merits/edit_merit.php
session_start();
require_once '../../includes/db_connect.php'; // 确保路径正确

// 1. 🌟 严格权限拦截：只有 Admin 可以进入编辑页面
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 2. Fetch record with ID
if (isset($_GET['id'])) {
    $merit_id = $_GET['id'];
    
    // 🌟 修改：移除 AND user_id = ?，关联 users 表显示学生名字
    $query = "SELECT m.*, u.full_name, u.username 
              FROM merits m 
              JOIN users u ON m.user_id = u.user_id 
              WHERE m.merit_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $merit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        header("Location: index.php?error=notfound");
        exit();
    }
    
    // Fetch events for the dropdown
    $event_sql = "SELECT event_id, event_name FROM events WHERE deleted = 0 ORDER BY event_date DESC";
    $events_result = $conn->query($event_sql);
    
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Merit Record | Academic Curator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#003f87", surface: "#f6faff" },
                    fontFamily: { headline: ["Manrope"], body: ["Inter"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
        .input-elegant {
            background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; 
            padding: 0.75rem 1rem; width: 100%; transition: all 0.2s; color: #1e293b;
        }
        .input-elegant:focus {
            background-color: #ffffff; border-color: #003f87; outline: none; box-shadow: 0 0 0 3px rgba(0,63,135,0.1);
        }
        .achievement-bloom { position: relative; overflow: hidden; }
        .achievement-bloom::after {
            content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px;
            background: linear-gradient(135deg, #3b82f6 0%, #10b981 100%); opacity: 0.08; border-radius: 50%; pointer-events: none;
        }
    </style>
</head>
<body class="bg-surface font-body text-slate-800 min-h-screen flex flex-col">

    <nav class="bg-white border-b border-slate-200 px-6 py-4 fixed w-full top-0 z-50 shadow-sm flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 signature-gradient rounded-lg flex items-center justify-center text-white">
                <span class="material-symbols-outlined text-sm">military_tech</span>
            </div>
            <span class="font-headline font-bold text-primary tracking-tight">Academic Curator</span>
        </div>
        <a href="index.php" class="text-sm font-bold text-slate-500 hover:text-primary flex items-center gap-2 transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Cancel Editing
        </a>
    </nav>

    <main class="flex-grow pt-28 pb-16 px-6 flex justify-center items-center">
        <div class="w-full max-w-3xl bg-white rounded-[2rem] shadow-xl border border-slate-100 achievement-bloom">
            
            <div class="p-10 md:p-14">
                <div class="text-center mb-10">
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-inner border border-blue-100">
                        <span class="material-symbols-outlined text-3xl">edit_note</span>
                    </div>
                    <h1 class="font-headline text-3xl font-black text-slate-900 tracking-tight">Modify Merit Data</h1>
                    <p class="text-slate-500 mt-2 font-medium">Update the verified participation hours for this record.</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 flex items-start gap-3 rounded-r-xl">
                        <span class="material-symbols-outlined mt-0.5">error</span>
                        <div class="font-bold text-sm">
                            <?php 
                                if ($_GET['error'] == 'empty') echo "Warning: All required fields must be filled!";
                                if ($_GET['error'] == 'duplicate') echo "Warning: A similar record already exists!";
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="process_edit_merit.php" method="POST" class="space-y-6">
                    <input type="hidden" name="merit_id" value="<?php echo $data['merit_id']; ?>">

                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl mb-6 flex items-center gap-4">
                        <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-500">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hours Awarded To</p>
                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($data['full_name']); ?> <span class="text-slate-500 font-normal">(<?php echo htmlspecialchars($data['username']); ?>)</span></p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Linked Campus Event</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">event</span>
                            <select name="event_id" class="input-elegant pl-12">
                                <option value="">-- Independent Activity (Off-campus) --</option>
                                <?php if($events_result && $events_result->num_rows > 0): ?>
                                    <?php while($ev = $events_result->fetch_assoc()): ?>
                                        <option value="<?php echo $ev['event_id']; ?>" <?php echo ($data['event_id'] == $ev['event_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($ev['event_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Organizer</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">corporate_fare</span>
                            <input type="text" name="organizer" class="input-elegant pl-12" value="<?php echo htmlspecialchars($data['organizer']); ?>" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Hours Earned</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-amber-500">schedule</span>
                                <input type="number" step="0.01" min="0" name="hours" class="input-elegant pl-12 font-bold text-amber-600" value="<?php echo $data['hours']; ?>" required>
                            </div>
                        </div>
                        <div>
                            <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Date Completed</label>
                            <div class="relative">
                                <input type="date" name="date_completed" class="input-elegant text-slate-600" value="<?php echo $data['date_completed']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Description / Task Details</label>
                        <textarea name="merit_description" class="input-elegant" rows="4"><?php echo htmlspecialchars($data['merit_description']); ?></textarea>
                    </div>

                    <div class="pt-6 flex flex-col sm:flex-row gap-4">
                        <button type="submit" class="flex-1 signature-gradient text-white py-4 rounded-xl font-headline font-black tracking-widest uppercase shadow-xl hover:shadow-2xl hover:-translate-y-1 active:scale-95 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">save</span> Update Merit Record
                        </button>
                        <a href="index.php" class="px-8 py-4 rounded-xl font-headline font-bold uppercase tracking-widest text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors flex items-center justify-center">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </main>

</body>
</html>