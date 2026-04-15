<?php
/**
 * File: edit_event.php (Events Module)
 * Purpose: Update existing campus event details.
 */
session_start();
require_once '../../database/db_connect.php'; 
date_default_timezone_set('Asia/Kuala_Lumpur');

// 1. AUTHENTICATION & ADMIN CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status = "";

// 2. FETCH CURRENT USER DATA FOR SIDEBAR
$u_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$u_stmt = $conn->prepare($u_sql);
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user_data = $u_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Administrator';

// 3. FETCH EXISTING EVENT DATA
$event_id = $_REQUEST['event_id']; 
$query = "SELECT * FROM events WHERE event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

// 4. HANDLE UPDATE LOGIC
if(isset($_POST['new']) && $_POST['new'] == 1){ 
    $event_id = $_POST['event_id']; 
    $event_name = $_POST['event_name']; 
    $event_type = $_POST['event_type']; 
    $event_location = $_POST['event_location']; 
    $event_date = $_POST['event_date']; 
    $event_time = $_POST['event_time']; 
    $register_expired_date = $_POST['register_expired_date'];
    $club_id = $_POST['club_id'];
    $date_record = date("Y-m-d H:i:s");
    $poster = $row['event_poster']; 

    // Handle Poster Upload
    if (!empty($_FILES['event_poster']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES["event_poster"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($file_type, ["jpg", "jpeg", "png", "pdf"])) {
            if (move_uploaded_file($_FILES["event_poster"]["tmp_name"], $target_file)) {
                // Delete old file if exists
                if (!empty($row['event_poster']) && file_exists($target_dir . $row['event_poster'])) {
                    unlink($target_dir . $row['event_poster']);
                }
                $poster = $file_name;
            }
        }
    } 

    $update = "UPDATE events SET 
        date_record=?, event_name=?, event_type=?, event_location=?, 
        event_date=?, event_time=?, register_expired_date=?, 
        club_id=?, event_poster=?
    WHERE event_id=?"; 
    
    $upd_stmt = $conn->prepare($update);
    $upd_stmt->bind_param("sssssssisi", $date_record, $event_name, $event_type, $event_location, $event_date, $event_time, $register_expired_date, $club_id, $poster, $event_id);

    if($upd_stmt->execute()) {
        $status = "Event Updated Successfully!";
        // Refresh local row data after update
        $row['event_poster'] = $poster; 
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Edit Event | Academic Curator</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    
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

<main class="ml-72 p-12 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <header class="mb-12">
            <h1 class="text-5xl font-black font-headline text-primary tracking-tight">Edit Event</h1>
            <p class="text-slate-500 mt-2 text-lg">Modify the details and logistics for <strong><?php echo htmlspecialchars($row['event_name']); ?></strong>.</p>
        </header>

        <?php if($status != ""): ?>
            <div class="mb-8 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 flex items-center justify-between rounded-xl shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="font-bold"><?php echo $status; ?></span>
                </div>
                <a href="index.php" class="text-xs font-black uppercase tracking-widest bg-emerald-600 text-white px-4 py-2 rounded-full hover:bg-emerald-700 transition-all">Back to List</a>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <form method="post" action="" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="new" value="1" />
                <input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Full Event Title</label>
                        <input type="text" name="event_name" class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary transition-all font-bold" value="<?php echo htmlspecialchars($row['event_name']); ?>" required>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Classification</label>
                        <select name="event_type" class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary transition-all font-bold" required>
                            <option value="">-- Select Option --</option>
                            <?php
                            $types = ["Seminar","Workshop","Competition","Volunteering","Club Activity","Sports","Cultural","Leadership"];
                            foreach($types as $type){
                                $selected = ($row['event_type'] == $type) ? 'selected' : '';
                                echo "<option value='$type' $selected>$type</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Hosting Organization</label>
                        <select name="club_id" class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary transition-all font-bold" required>
                            <option value="">-- Select Club --</option>
                            <?php 
                            $club_query = "SELECT club_id, club_name FROM clubs ORDER BY club_name ASC";
                            $club_result = mysqli_query($conn, $club_query);
                            while($club = mysqli_fetch_assoc($club_result)) { 
                                $selected = ($row['club_id'] == $club['club_id']) ? 'selected' : '';
                                echo "<option value='{$club['club_id']}' $selected>{$club['club_name']}</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Event Venue</label>
                        <input type="text" name="event_location" class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary transition-all font-bold" value="<?php echo htmlspecialchars($row['event_location']); ?>" required>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Event Date</label>
                        <input type="date" name="event_date" class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary transition-all font-bold" value="<?php echo $row['event_date']; ?>" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Event Start Time</label>
                        <input type="time" name="event_time" class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary transition-all font-bold" value="<?php echo $row['event_time']; ?>" required>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Registration Deadline</label>
                        <input type="date" name="register_expired_date" class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 focus:ring-2 focus:ring-primary transition-all font-bold" value="<?php echo $row['register_expired_date']; ?>" required>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Update Poster Image</label>
                        <input type="file" name="event_poster" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-primary transition-all" accept=".jpg,.jpeg,.png,.pdf">
                        <p class="text-[10px] font-bold text-slate-400 mt-2 px-1">
                            Current: <span class="text-primary italic"><?php echo !empty($row['event_poster']) ? $row['event_poster'] : 'No File Uploaded'; ?></span>
                        </p>
                    </div>
                </div>

                <div class="pt-8 border-t border-slate-50 flex items-center justify-end gap-6">
                    <a href="index.php" class="text-xs font-black text-slate-400 hover:text-primary transition-all uppercase tracking-widest">Cancel</a>
                    <button type="submit" class="signature-gradient text-white px-12 py-5 rounded-full font-black text-xs uppercase tracking-[0.2em] shadow-xl hover:-translate-y-1 active:scale-95 transition-all">
                        Update Event Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

</body> 
</html>
