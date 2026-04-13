<?php
// Start session to access logged-in user data
session_start();

// Import database connection configuration
require_once 'includes/db_connect.php'; 

/**
 * AUTHENTICATION CHECK
 * Redirect to login page if session is not active
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

/**
 * PASSWORD UPDATE LOGIC
 * Triggered when the user submits the 'update_password' form
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Input Validation
    if (strlen($new_password) < 8) {
        $error_msg = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } else {
        // Retrieve current hashed password from database to verify
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Use password_verify to check if current password matches DB hash
        if (password_verify($current_password, $user['password'])) {
            // Re-hash the new password using BCRYPT before saving
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $success_msg = "Password updated successfully!";
            } else {
                $error_msg = "Failed to update password. Please try again.";
            }
        } else {
            $error_msg = "Current password is incorrect.";
        }
    }
}

/**
 * FETCH USER PROFILE DATA
 * Retrieve student information for display (Chew Sai Hou, 2305653)
 */
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Format Student ID to 7 digits as per UTAR standards
$student_id = str_pad($user_data['username'], 7, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>User Profile | The Academic Curator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        // Customizing the Tailwind theme to match system branding
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary": "#003f87",
                        "primary-fixed": "#d7e2ff",
                        "on-primary-fixed": "#001a40",
                        "surface": "#f6faff",
                        "on-surface": "#141d23",
                        "surface-container-low": "#ecf5fe",
                        "surface-container-lowest": "#ffffff"
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        /* Custom Gradient for Branding Elements */
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
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

    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="index.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/events/index.php">
            <span class="material-symbols-outlined">event_note</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Events</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/achievements/index.php">
            <span class="material-symbols-outlined">verified</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Achievements</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/merits/index.php">
            <span class="material-symbols-outlined">military_tech</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Merits</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="modules/clubs/index.php">
            <span class="material-symbols-outlined">groups</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
        </a>
    </nav>

    <div class="pt-6 border-t border-slate-200/50 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-blue-600 transition-colors" href="logout.php">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
        </a>
    </div>
</aside>

<header class="flex justify-between items-center h-20 px-8 ml-72 fixed top-0 w-[calc(100%-18rem)] z-40 bg-white/80 backdrop-blur-md border-b border-slate-100">
    <div class="text-sm font-bold text-primary tracking-widest uppercase">Academic Curator | UTAR Student Portal</div>
    <div class="flex items-center gap-3">
        <div class="text-right">
            <p class="text-xs font-bold text-on-surface uppercase"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
            <p class="text-[10px] text-slate-500 uppercase tracking-tighter"><?php echo htmlspecialchars($user_data['faculty'] ?? 'Faculty of ICT'); ?></p>
        </div>
        <img class="w-10 h-10 rounded-full border-2 border-primary-fixed" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAjZ_oSRVSiGbl-3d0SW9fUmXz9Cu1MsAMPA7uZdp3KuIWCiPdAWXp15aOKt9aLa2FkwcUxtBO05z6u-ogifVlXzX56G2KA7UbUdMBSB1uMhIpCG03NhCTr70NwqcdWocj5NSzxeUSFF82mW0AxbY5Ft0tNfNS9NbjtTFERRBKfxxuLeeWGrJSXoPjfm_RGYDBXFDuelpRkwJIobR20MbVLBbgchPC_RKTmJU3n44N8Pwn4XffLrKhZ5N5a0ThzG72QhBaSNGmc0Xew"/>
    </div>
</header>

<main class="ml-72 pt-28 p-12 min-h-screen">
    <div class="max-w-4xl mx-auto space-y-12">
        
        <header>
            <h1 class="text-4xl font-extrabold text-primary tracking-tight font-headline">Account Identity</h1>
            <p class="text-slate-500 mt-2">Managing the academic profile of <strong>CHEW SAI HOU (2305653)</strong>. All data is verified by the university curriculum system.</p>
        </header>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-lg flex items-center gap-3 shadow-sm">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-medium"><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg flex items-center gap-3 shadow-sm">
                <span class="material-symbols-outlined">error</span>
                <span class="font-medium"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <section class="bg-surface-container-lowest rounded-2xl p-10 shadow-sm border border-slate-100 relative overflow-hidden">
            <div class="flex items-center justify-between mb-10 relative z-10">
                <h2 class="text-2xl font-bold text-on-surface font-headline">Profile Details</h2>
                
                <a href="generate_transcript.php" target="_blank" class="flex items-center gap-2 px-6 py-3 bg-slate-800 text-white rounded-full font-bold text-xs shadow-lg hover:bg-black active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                    Generate PDF Transcript
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Full Name</label>
                    <p class="text-lg font-bold text-on-surface"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Student ID</label>
                    <p class="text-lg font-bold text-on-surface"><?php echo htmlspecialchars($student_id); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Programme</label>
                    <p class="text-lg font-bold text-on-surface"><?php echo htmlspecialchars($user_data['programme']); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Academic Year</label>
                    <p class="text-lg font-bold text-on-surface"><?php echo htmlspecialchars($user_data['academic_year'] ?? 'Final Year'); ?></p>
                </div>
                <div class="md:col-span-2 space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">University Email</label>
                    <p class="text-lg font-bold text-on-surface"><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl p-10 shadow-sm border border-slate-100">
            <h2 class="text-2xl font-bold text-on-surface mb-10 font-headline">Security Settings</h2>
            <form method="POST" action="profile.php" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 block">Current Password</label>
                        <input name="current_password" required class="w-full bg-slate-50 border-none rounded-xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all shadow-inner" placeholder="••••••••" type="password"/>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 block">New Password</label>
                        <input name="new_password" required class="w-full bg-slate-50 border-none rounded-xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all shadow-inner" placeholder="••••••••" type="password"/>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 block">Confirm New Password</label>
                        <input name="confirm_password" required class="w-full bg-slate-50 border-none rounded-xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all shadow-inner" placeholder="••••••••" type="password"/>
                    </div>
                </div>
                <div class="pt-4">
                    <button name="update_password" class="w-full md:w-max px-12 py-4 rounded-full signature-gradient text-white font-bold tracking-tight shadow-xl hover:opacity-90 active:scale-95 transition-all flex items-center justify-center gap-3" type="submit">
                        <span class="material-symbols-outlined">lock_reset</span>
                        Update Security Credentials
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>

<script>
    /**
     * UI Toggle: Dropdown Switch
     * Handles the visibility of the settings menu
     */
    function toggleSettingsDropdown(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('settings-dropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    }
</script>
</body>
</html>