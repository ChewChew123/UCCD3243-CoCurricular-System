<?php
session_start();
require_once 'includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Password Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validations
    if (strlen($new_password) < 8) {
        $error_msg = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (password_verify($current_password, $user['password'])) {
            // Update to new password
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

// Fetch User Data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Format Enforcement
$student_id = str_pad($user_data['username'], 7, '0', STR_PAD_LEFT);
$display_email = $user_data['email']; 
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>User Profile | The Academic Curator</title>
<!-- Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@600;700;800&amp;display=swap" rel="stylesheet"/>
<!-- Icons -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "on-background": "#141d23",
                    "primary-fixed": "#d7e2ff",
                    "inverse-on-surface": "#e9f2fb",
                    "surface": "#f6faff",
                    "tertiary-container": "#983c00",
                    "secondary-container": "#dde0e3",
                    "inverse-surface": "#293138",
                    "on-tertiary-fixed": "#351000",
                    "background": "#f6faff",
                    "tertiary-fixed": "#ffdbcc",
                    "on-surface-variant": "#424752",
                    "on-primary-fixed": "#001a40",
                    "tertiary": "#722b00",
                    "tertiary-fixed-dim": "#ffb694",
                    "surface-container-highest": "#dbe4ed",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "outline-variant": "#c2c6d4",
                    "outline": "#727784",
                    "surface-tint": "#115cb9",
                    "surface-container": "#e6eff8",
                    "on-error-container": "#93000a",
                    "secondary-fixed-dim": "#c4c7ca",
                    "on-tertiary-container": "#ffc2a7",
                    "surface-container-high": "#e0e9f2",
                    "surface-variant": "#dbe4ed",
                    "on-primary": "#ffffff",
                    "surface-dim": "#d2dbe4",
                    "secondary": "#5b5f62",
                    "surface-bright": "#f6faff",
                    "surface-container-low": "#ecf5fe",
                    "error-container": "#ffdad6",
                    "on-secondary-fixed-variant": "#43474a",
                    "primary-fixed-dim": "#acc7ff",
                    "on-secondary-container": "#5f6366",
                    "on-secondary-fixed": "#181c1e",
                    "on-primary-fixed-variant": "#004491",
                    "on-tertiary": "#ffffff",
                    "on-surface": "#141d23",
                    "primary-container": "#0056b3",
                    "error": "#ba1a1a",
                    "primary": "#003f87",
                    "inverse-primary": "#acc7ff",
                    "secondary-fixed": "#e0e3e6",
                    "on-secondary": "#ffffff",
                    "on-primary-container": "#bbd0ff",
                    "surface-container-lowest": "#ffffff",
                    "on-error": "#ffffff"
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
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3 { font-family: 'Manrope', sans-serif; }
        .signature-gradient {
            background: linear-gradient(135deg, #003f87 0%, #0056b3 100%);
        }
    </style>
</head>
<body class="bg-surface text-on-surface">
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
<p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
<p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($user_data['programme']); ?></p>
</div>
</div>

<!-- New Activity Button -->
<a href="modules/clubs/join.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-sm">add</span>
New Activity
</a>

<!-- Navigation -->
<nav class="flex-1 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="index.php">
<span class="material-symbols-outlined">dashboard</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="modules/events/index.php">
<span class="material-symbols-outlined">event_note</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Events</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="modules/achievements/index.php">
<span class="material-symbols-outlined">verified</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Achievements</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="modules/clubs/index.php">
<span class="material-symbols-outlined">groups</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="profile.php">
<span class="material-symbols-outlined">person</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
</a>
</nav>

<!-- Logout -->
<div class="pt-6 border-t border-slate-200/50 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-blue-600 transition-colors" href="logout.php">
<span class="material-symbols-outlined">logout</span>
<span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
</a>
</div>
</aside>
<!-- TopAppBar -->
<header class="flex justify-between items-center h-20 px-8 ml-72 w-[calc(100%-18rem)] fixed top-0 sticky z-40 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md shadow-sm dark:shadow-none">
<div class="flex items-center bg-surface-container-low px-4 py-2 rounded-full w-96 transition-all focus-within:ring-2 focus-within:ring-blue-500">
    <span class="material-symbols-outlined text-slate-400 mr-2">search</span>
    <input class="bg-transparent border-none focus:ring-0 text-sm w-full" placeholder="Search portfolio..." type="text"/>
</div>
<div class="flex items-center gap-6">
    <div class="flex items-center gap-4">
        <!-- Settings Dropdown -->
        <div class="relative">
            <button onclick="toggleSettingsDropdown(event)" class="settings-btn p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 rounded-full transition-colors relative">
                <span class="material-symbols-outlined">settings</span>
            </button>
            <!-- Dropdown Menu -->
            <div id="settings-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 py-2 z-50">
                <a href="profile.php" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-lg">person</span>
                    <span>Account Profile</span>
                </a>
                <div class="h-[1px] bg-slate-100 dark:bg-slate-800 my-1"></div>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    <span>Log Out</span>
                </a>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-3 border-l pl-6 border-slate-200">
        <div class="text-right">
            <p class="text-xs font-bold text-on-surface uppercase tracking-tight"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($user_data['academic_year'] . " - " . $user_data['faculty']); ?></p>
        </div>
        <img class="w-10 h-10 rounded-full bg-slate-200 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAjZ_oSRVSiGbl-3d0SW9fUmXz9Cu1MsAMPA7uZdp3KuIWCiPdAWXp15aOKt9aLa2FkwcUxtBO05z6u-ogifVlXzX56G2KA7UbUdMBSB1uMhIpCG03NhCTr70NwqcdWocj5NSzxeUSFF82mW0AxbY5Ft0tNfNS9NbjtTFERRBKfxxuLeeWGrJSXoPjfm_RGYDBXFDuelpRkwJIobR20MbVLBbgchPC_RKTmJU3n44N8Pwn4XffLrKhZ5N5a0ThzG72QhBaSNGmc0Xew"/>
    </div>
</div>
</header>
<!-- Main Content Canvas -->
<main class="ml-72 p-12 min-h-screen">
<div class="max-w-5xl mx-auto space-y-12">
<!-- Page Header -->
<header class="space-y-2">
<nav class="flex items-center gap-2 text-slate-400 text-xs font-semibold uppercase tracking-widest mb-2">
<span>User</span>
<span class="material-symbols-outlined text-[12px]">chevron_right</span>
<span class="text-primary">Profile Settings</span>
</nav>
<h1 class="text-4xl font-extrabold text-primary tracking-tight">Academic Curator</h1>
<p class="text-slate-500 max-w-2xl">Manage your academic identity and security settings. Your profile details are used to generate your final Co-curricular Transcript.</p>
</header>

<!-- Notifications / Messages -->
<?php if ($success_msg): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-lg shadow-sm flex items-center gap-3">
        <span class="material-symbols-outlined">check_circle</span>
        <span class="font-medium"><?php echo $success_msg; ?></span>
    </div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-lg shadow-sm flex items-center gap-3">
        <span class="material-symbols-outlined">error</span>
        <span class="font-medium"><?php echo $error_msg; ?></span>
    </div>
<?php endif; ?>

<!-- Grid Layout -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
<!-- Card A: Profile Details -->
<section class="lg:col-span-12 bg-surface-container-lowest rounded-xl p-8 shadow-sm relative overflow-hidden">
<!-- Achievement Bloom Decorative Element -->
<div class="absolute -top-12 -right-12 w-48 h-48 bg-primary-fixed opacity-10 rounded-full"></div>
<div class="flex items-center justify-between mb-8 relative z-10">
<h2 class="text-2xl font-bold text-on-surface">Profile Details</h2>
<span class="material-symbols-outlined text-primary text-3xl">verified_user</span>
</div>
<div class="space-y-6 relative z-10">
<div class="grid grid-cols-2 gap-8">
<div class="space-y-1">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Full Name</label>
<div class="flex items-center gap-2">
<p class="text-lg font-semibold text-on-surface"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
<span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings: 'FILL' 1;">verified</span>
</div>
</div>
<div class="space-y-1">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Student ID</label>
<p class="text-lg font-semibold text-on-surface"><?php echo htmlspecialchars($student_id); ?></p>
</div>
</div>
<div class="space-y-1">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Programme / Department</label>
<p class="text-lg font-semibold text-on-surface"><?php echo htmlspecialchars($user_data['programme']); ?></p>
</div>
<div class="grid grid-cols-2 gap-8">
<div class="space-y-1">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Academic Year</label>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary">school</span>
<p class="text-lg font-semibold text-on-surface"><?php echo htmlspecialchars($user_data['academic_year']); ?></p>
</div>
</div>
<div class="space-y-1">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Email Address</label>
<p class="text-lg font-semibold text-on-surface"><?php echo htmlspecialchars($display_email); ?></p>
</div>
</div>
</div>
</section>

<!-- Card B: Update Password -->
<section class="lg:col-span-12 bg-white rounded-xl p-8 shadow-sm border border-slate-100">
<h2 class="text-2xl font-bold text-on-surface mb-8">Update Password</h2>
<form class="grid grid-cols-1 md:grid-cols-2 gap-8" method="POST" action="profile.php">
<div class="group md:col-span-2">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2 block px-1">Current Password</label>
<div class="relative">
<input name="current_password" required class="w-full bg-slate-50 border-none rounded-xl px-6 py-4 text-on-surface transition-all focus:bg-white focus:ring-2 focus:ring-primary shadow-inner" placeholder="••••••••" type="password"/>
<span class="material-symbols-outlined absolute right-4 top-4 text-slate-400 cursor-pointer hover:text-primary transition-colors" onclick="togglePassword(this)">visibility</span>
</div>
</div>
<div class="group">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2 block px-1">New Password</label>
<div class="relative">
<input name="new_password" required class="w-full bg-slate-50 border-none rounded-xl px-6 py-4 text-on-surface transition-all focus:bg-white focus:ring-2 focus:ring-primary shadow-inner" placeholder="••••••••" type="password"/>
<span class="material-symbols-outlined absolute right-4 top-4 text-slate-400 cursor-pointer hover:text-primary transition-colors" onclick="togglePassword(this)">visibility</span>
</div>
<p class="text-[10px] text-slate-400 mt-3 px-1 italic">Min. 8 characters with security variety.</p>
</div>
<div class="group">
<label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2 block px-1">Confirm New Password</label>
<div class="relative">
<input name="confirm_password" required class="w-full bg-slate-50 border-none rounded-xl px-6 py-4 text-on-surface transition-all focus:bg-white focus:ring-2 focus:ring-primary shadow-inner" placeholder="••••••••" type="password"/>
<span class="material-symbols-outlined absolute right-4 top-4 text-slate-400 cursor-pointer hover:text-primary transition-colors" onclick="togglePassword(this)">visibility</span>
</div>
</div>
<div class="md:col-span-2 pt-4">
<button name="update_password" class="w-full md:w-max px-12 py-5 rounded-full signature-gradient text-white font-bold tracking-tight text-lg shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-3" type="submit">
<span class="material-symbols-outlined">key</span>
                            Update Password
                        </button>
</div>
</form>
</section>
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
    if (!e.target.closest('.settings-btn') && !e.target.closest('#settings-dropdown')) {
        dropdown.classList.add('hidden');
    }
});

function togglePassword(btn) {
    const input = btn.previousElementSibling;
    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "visibility_off";
    } else {
        input.type = "password";
        btn.textContent = "visibility";
    }
}
</script>
</body></html>
