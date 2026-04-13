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

// Check for ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$member_id = intval($_GET['id']);

// Fetch Current Profile for Sidebar
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// Fetch Membership Data (Security: Check ownership)
$sql = "SELECT cm.*, c.club_name FROM club_members cm JOIN clubs c ON cm.club_id = c.club_id WHERE cm.member_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Record not found or does not belong to user
    header("Location: index.php");
    exit();
}

$membership = $result->fetch_assoc();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_role = $_POST['role'];
    $new_status = $_POST['status'];

    if (empty($new_role) || empty($new_status)) {
        $error = "All fields are required.";
    } else {
        // Only Admin can update the record regardless of user ownership
        $update_sql = "UPDATE club_members SET member_role = ?, member_status = ? WHERE member_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $new_role, $new_status, $member_id);

        if ($update_stmt->execute()) {
            header("Location: index.php?edit=success");
            exit();
        } else {
            $error = "Failed to update membership. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Edit Club Membership | The Academic Curator</title>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                      "surface-container": "#e6eff8",
                      "inverse-on-surface": "#e9f2fb",
                      "secondary-fixed-dim": "#c4c7ca",
                      "background": "#f6faff",
                      "surface-tint": "#115cb9",
                      "primary": "#003f87",
                      "on-tertiary-container": "#ffc2a7",
                      "on-surface-variant": "#424752",
                      "primary-fixed-dim": "#acc7ff",
                      "tertiary": "#722b00",
                      "surface-dim": "#d2dbe4",
                      "on-tertiary": "#ffffff",
                      "on-surface": "#141d23",
                      "secondary-fixed": "#e0e3e6",
                      "surface-variant": "#dbe4ed",
                      "on-secondary": "#ffffff",
                      "on-primary-container": "#bbd0ff",
                      "primary-fixed": "#d7e2ff",
                      "surface": "#f6faff",
                      "on-error-container": "#93000a",
                      "error": "#ba1a1a",
                      "outline-variant": "#c2c6d4",
                      "inverse-primary": "#acc7ff",
                      "surface-container-lowest": "#ffffff",
                      "on-secondary-fixed": "#181c1e",
                      "outline": "#727784",
                      "on-tertiary-fixed-variant": "#7b2f00",
                      "inverse-surface": "#293138",
                      "on-secondary-fixed-variant": "#43474a",
                      "tertiary-fixed": "#ffdbcc",
                      "on-primary-fixed-variant": "#004491",
                      "on-error": "#ffffff",
                      "on-background": "#141d23",
                      "surface-bright": "#f6faff",
                      "on-primary": "#ffffff",
                      "surface-container-low": "#ecf5fe",
                      "tertiary-container": "#983c00",
                      "secondary": "#5b5f62",
                      "on-secondary-container": "#5f6366",
                      "on-tertiary-fixed": "#351000",
                      "tertiary-fixed-dim": "#ffb694",
                      "surface-container-high": "#e0e9f2",
                      "surface-container-highest": "#dbe4ed",
                      "primary-container": "#0056b3",
                      "secondary-container": "#dde0e3",
                      "on-primary-fixed": "#001a40",
                      "error-container": "#ffdad6"
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
      </style>
</head>
<body class="bg-background font-body text-on-surface">

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
<a href="join.php" class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
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
<a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
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
    Membership Management
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
<main class="pt-32 pb-12 pl-72 min-h-screen">
<div class="max-w-4xl mx-auto px-6 lg:px-12 text-center sm:text-left">
    <!-- Breadcrumb / Header -->
    <div class="mb-12 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-4xl lg:text-5xl font-headline font-extrabold text-primary tracking-tight">Edit Membership</h1>
            <p class="text-slate-500 mt-2">Adjust your role or status for <span class="text-primary font-bold"><?php echo htmlspecialchars($membership['club_name']); ?></span></p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-full border border-blue-100 dark:border-blue-800">
            <span class="text-blue-700 dark:text-blue-300 text-xs font-black uppercase tracking-widest leading-none">Record #<?php echo $member_id; ?></span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="mb-8 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 flex items-center gap-3 rounded-lg shadow-sm">
            <span class="material-symbols-outlined">warning</span>
            <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 lg:p-12 shadow-sm border border-slate-100 dark:border-slate-800">
        <form method="POST" action="edit.php?id=<?php echo $member_id; ?>" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-left">
                <!-- Role Selection -->
                <div class="space-y-3">
                    <label class="block text-xs font-black tracking-widest text-slate-400 uppercase">Your Professional Role</label>
                    <div class="relative">
                        <select name="role" required class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-4 focus:ring-2 focus:ring-primary transition-all">
                            <option value="Member" <?php echo $membership['member_role'] == 'Member' ? 'selected' : ''; ?>>Member</option>
                            <option value="Committee" <?php echo $membership['member_role'] == 'Committee' ? 'selected' : ''; ?>>Committee</option>
                            <option value="Lead Curator" <?php echo $membership['member_role'] == 'Lead Curator' ? 'selected' : ''; ?>>Lead Curator</option>
                            <option value="Faculty Liaison" <?php echo $membership['member_role'] == 'Faculty Liaison' ? 'selected' : ''; ?>>Faculty Liaison</option>
                        </select>
                    </div>
                </div>

                <!-- Status Selection -->
                <div class="space-y-3 font-body">
                    <label class="block text-xs font-black tracking-widest text-slate-400 uppercase font-headline">Membership Status</label>
                    <div class="relative">
                        <select name="status" required class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-xl px-4 py-4 focus:ring-2 focus:ring-primary transition-all">
                            <option value="Active" <?php echo $membership['member_status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Pending" <?php echo $membership['member_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Inactive" <?php echo $membership['member_status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Visual Accent (Editorial Detail) -->
            <div class="bg-blue-50/50 dark:bg-blue-900/10 rounded-xl p-6 flex items-start space-x-4 text-left">
                <span class="material-symbols-outlined text-blue-600">info</span>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Changes to your role will be reflected on your public curator profile. <strong>Inactive</strong> status will hide this club from your "Active Roles" count but preserve your history.
                </p>
            </div>

            <div class="h-[1px] bg-slate-100 dark:bg-slate-800 my-6"></div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-4">
                <a href="index.php" class="font-bold text-slate-400 hover:text-primary transition-colors px-6">Cancel</a>
                <button type="submit" class="w-full sm:w-auto signature-gradient text-white font-bold px-12 py-4 rounded-full shadow-lg hover:opacity-90 active:scale-95 transition-all outline-none">
                    Save Changes
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
</body>
</html>
