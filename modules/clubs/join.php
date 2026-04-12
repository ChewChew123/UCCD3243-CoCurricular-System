<?php
session_start();
require_once '../../includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// Fetch Current Profile for Sidebar
$user_sql = "SELECT full_name, programme, username FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// Fetch Clubs for Dropdown
$clubs_result = $conn->query("SELECT club_id, club_name FROM clubs ORDER BY club_name ASC");

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_join'])) {
    $club_id = intval($_POST['club_id']);
    $role = $_POST['role'];

    if (empty($club_id) || empty($role)) {
        $error = "Please select both a club and a role.";
    } else {
        // Prevention Logic: Check for existing membership
        $check_sql = "SELECT member_id FROM club_members WHERE user_id = ? AND club_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $club_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "You are already a member of this club!";
        } else {
            // Insert Operation
            $insert_sql = "INSERT INTO club_members (user_id, club_id, member_role, member_status) VALUES (?, ?, ?, 'Active')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iis", $user_id, $club_id, $role);

            if ($insert_stmt->execute()) {
                header("Location: index.php?join=success");
                exit();
            } else {
                $error = "Failed to join club. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Join New Club | Academic Curator</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&amp;family=Inter:wght@100..900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "tertiary": "#722b00",
                        "surface-container": "#e6eff8",
                        "primary": "#003f87",
                        "on-secondary-fixed": "#181c1e",
                        "outline-variant": "#c2c6d4",
                        "on-background": "#141d23",
                        "on-primary-fixed": "#001a40",
                        "on-surface": "#141d23",
                        "surface-container-highest": "#dbe4ed",
                        "surface-container-high": "#e0e9f2",
                        "surface-dim": "#d2dbe4",
                        "surface-container-low": "#ecf5fe",
                        "primary-fixed-dim": "#acc7ff",
                        "on-secondary-fixed-variant": "#43474a",
                        "inverse-on-surface": "#e9f2fb",
                        "error-container": "#ffdad6",
                        "tertiary-fixed-dim": "#ffb694",
                        "tertiary-fixed": "#ffdbcc",
                        "secondary-container": "#dde0e3",
                        "on-tertiary-container": "#ffc2a7",
                        "on-primary-container": "#bbd0ff",
                        "secondary-fixed": "#e0e3e6",
                        "surface-variant": "#dbe4ed",
                        "primary-container": "#0056b3",
                        "surface-container-lowest": "#ffffff",
                        "on-tertiary-fixed": "#351000",
                        "error": "#ba1a1a",
                        "on-surface-variant": "#424752",
                        "tertiary-container": "#983c00",
                        "outline": "#727784",
                        "surface-tint": "#115cb9",
                        "surface-bright": "#f6faff",
                        "on-secondary-container": "#5f6366",
                        "primary-fixed": "#d7e2ff",
                        "background": "#f6faff",
                        "on-primary": "#ffffff",
                        "surface": "#f6faff"
                    },
                    "borderRadius": {
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
        .signature-gradient {
            background: linear-gradient(135deg, #003f87 0%, #0056b3 100%);
        }
        .achievement-bloom {
            position: relative;
            overflow: hidden;
        }
        .achievement-bloom::after {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: #d7e2ff;
            opacity: 0.1;
            border-radius: 50%;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen">

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
<p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($full_name); ?></p>
<p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
</div>
</div>

<!-- New Activity Button -->
<a href="join.php" class="py-3 px-4 bg-blue-50/50 text-blue-800 rounded-full font-bold text-sm border-r-4 border-blue-800 transition-all flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-sm">add</span>
New Activity
</a>

<!-- Navigation -->
<nav class="flex-1 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../index.php">
<span class="material-symbols-outlined">dashboard</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../events/index.php">
<span class="material-symbols-outlined">event_note</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Events</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../achievements/index.php">
<span class="material-symbols-outlined">verified</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Achievements</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../merits/index.php">
<span class="material-symbols-outlined">military_tech</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Merits</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="index.php">
<span class="material-symbols-outlined">groups</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 dark:text-slate-400 hover:text-blue-600 hover:bg-slate-200 dark:hover:bg-slate-800/50" href="../../profile.php">
<span class="material-symbols-outlined">person</span>
<span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
</a>
</nav>

<!-- Logout -->
<div class="pt-6 border-t border-slate-200/50 space-y-2">
<a class="flex items-center gap-3 px-4 py-3 text-slate-500 dark:text-slate-400 hover:text-blue-600 transition-colors" href="../../logout.php">
<span class="material-symbols-outlined">logout</span>
<span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
</a>
</div>
</aside>

<!-- TopAppBar -->
<header class="fixed top-0 right-0 left-72 bg-slate-50 dark:bg-slate-950 flex justify-between items-center px-8 py-4 z-40 border-b border-slate-100 dark:border-slate-800">
<div class="flex-1 text-sm font-bold text-primary tracking-widest uppercase">
    Academic Curator
</div>
<div class="flex items-center gap-6 ml-8">
<div class="flex items-center gap-4">
    <!-- Settings Dropdown -->
    <div class="relative group">
        <button onclick="toggleSettingsDropdown(event)" class="settings-btn p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 rounded-full transition-colors relative">
            <span class="material-symbols-outlined">settings</span>
        </button>
        <!-- Dropdown Menu -->
        <div id="settings-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 py-2 z-50">
            <a href="../../profile.php" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-lg">person</span>
                <span>Account Profile</span>
            </a>
            <div class="h-[1px] bg-slate-100 dark:bg-slate-800 my-1"></div>
            <a href="../../logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <span class="material-symbols-outlined text-lg">logout</span>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</div>
<div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-800"></div>
<img alt="Student profile" class="w-8 h-8 rounded-full border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAgejew68tN4fa7AfAAuJBxX5Q2YjS4oc9LWWPQSJPB0HDurJqBXwFW2ZdBS7zO0W4ECun7GLTsE2RaRYdL8Cvc-Pq2CZHyVz4Xhj1HWCGCM2gSkOWd0saDAtzrud3Ed8CqlF3fGci3ilk4j_WfYVqDDZv4bf4nhlsAwAqTLRWuOrN4Xop7o7FmxWDPOgOhPil6XPpgYfSRDAtcmHDUwb5ClmHNd4oG-T4OCXoVgViBMp6cK95iSQaZUU44LW1UoWfpKKa3DNm6aqU0"/>
</div>
</header>

<!-- Main Content Canvas -->
<main class="ml-72 pt-32 pb-16 px-4">
<div class="max-w-2xl mx-auto w-full">
<!-- Context Header -->
<div class="mb-12 text-center">
<span class="text-primary font-bold tracking-widest uppercase text-[10px] mb-2 block">New Membership</span>
<h1 class="font-headline font-extrabold text-5xl text-on-surface tracking-tight mb-4 text-blue-900">Expand Your Horizon</h1>
<p class="text-slate-500 max-w-md mx-auto text-lg leading-relaxed">Curate your student journey by joining organizations that align with your professional goals.</p>
</div>

<?php if ($error): ?>
    <div class="mb-8 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 flex items-center gap-3 rounded-lg shadow-sm">
        <span class="material-symbols-outlined">warning</span>
        <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<!-- Form Card -->
<div class="achievement-bloom bg-white dark:bg-slate-900 rounded-2xl p-10 shadow-sm border border-slate-100 dark:border-slate-800 relative">
<form method="POST" action="join.php" class="space-y-8 relative z-10 text-left">
<!-- Club Selection -->
<div class="space-y-3">
<label class="block text-xs font-black tracking-widest text-slate-400 uppercase px-1">Select Organization</label>
<div class="relative group">
<select name="club_id" required class="w-full appearance-none bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-6 py-4 text-on-surface focus:ring-2 focus:ring-primary transition-all duration-300">
<option disabled="" selected="" value="">Choose a club...</option>
<?php while ($club = $clubs_result->fetch_assoc()): ?>
    <option value="<?php echo $club['club_id']; ?>"><?php echo htmlspecialchars($club['club_name']); ?></option>
<?php endwhile; ?>
</select>
</div>
</div>
<!-- Role Selection -->
<div class="space-y-3">
<label class="block text-xs font-black tracking-widest text-slate-400 uppercase px-1">Proposed Role</label>
<div class="relative group">
<select name="role" required class="w-full appearance-none bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-6 py-4 text-on-surface focus:ring-2 focus:ring-primary transition-all duration-300">
<option disabled="" selected="" value="">Choose your role...</option>
<option value="Member">Member</option>
<option value="Committee">Committee</option>
<option value="Lead Curator">Lead Curator</option>
<option value="Faculty Liaison">Faculty Liaison</option>
</select>
</div>
</div>
<!-- Visual Accent (Editorial Detail) -->
<div class="bg-blue-50/50 dark:bg-blue-900/10 rounded-xl p-6 flex items-start space-x-4">
<span class="material-symbols-outlined text-blue-600">info</span>
<p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Joining a club as a <strong>Lead Curator</strong> requires a portfolio review by the incumbent committee. Your achievements will be automatically linked.
                        </p>
</div>
<!-- Actions -->
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
<a href="index.php" class="order-2 sm:order-1 text-slate-400 hover:text-primary font-bold text-sm px-8 py-4 transition-colors">
                            Cancel
                        </a>
<button name="confirm_join" class="order-1 sm:order-2 w-full sm:w-auto signature-gradient text-white font-bold px-12 py-4 rounded-full shadow-lg active:scale-95 transition-all outline-none" type="submit">
                            Confirm Join
                        </button>
</div>
</form>
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
    if (dropdown && !e.target.closest('.settings-btn') && !e.target.closest('#settings-dropdown')) {
        dropdown.classList.add('hidden');
    }
});
</script>
</body></html>
