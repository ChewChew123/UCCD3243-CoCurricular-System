<?php
session_start();
require_once '../../includes/db_connect.php';

// Session Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch User Profile for Sidebar
$user_sql = "SELECT full_name, programme FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_club'])) {
    $club_name = trim($_POST['club_name']);
    $club_category = $_POST['club_category'];
    $club_description = trim($_POST['club_description']);

    if (empty($club_name) || empty($club_category)) {
        $error = "Club name and category are required.";
    } else {
        // Validation: Check if a club with this name already exists
        $check_sql = "SELECT club_id FROM clubs WHERE club_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $club_name);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "A club with this name already exists in the system.";
        } else {
            // Insert the new club into the database
            $insert_sql = "INSERT INTO clubs (club_name, club_category, club_description) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sss", $club_name, $club_category, $club_description);

            if ($insert_stmt->execute()) {
                $success = "Organization established successfully! It is now available in the Join list.";
            } else {
                $error = "Error establishing the organization. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Create Organization | The Academic Curator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
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
                        "surface-container-lowest": "#ffffff",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6",
                        "emerald-500": "#10b981",
                        "emerald-100": "#d1fae5",
                        "emerald-700": "#047857",
                    },
                    "fontFamily": {
                        "headline": ["Manrope"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    }
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

<aside class="h-screen w-72 fixed left-0 top-0 bg-white dark:bg-slate-900 flex flex-col p-6 space-y-8 z-50 border-r border-slate-100 dark:border-slate-800">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
        <div class="text-2xl font-bold tracking-tight text-blue-900 dark:text-blue-100 font-headline">Academic Curator</div>
    </div>
    <div class="flex items-center gap-3 px-2 py-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
        <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAjZ_oSRVSiGbl-3d0SW9fUmXz9Cu1MsAMPA7uZdp3KuIWCiPdAWXp15aOKt9aLa2FkwcUxtBO05z6u-ogifVlXzX56G2KA7UbUdMBSB1uMhIpCG03NhCTr70NwqcdWocj5NSzxeUSFF82mW0AxbY5Ft0tNfNS9NbjtTFERRBKfxxuLeeWGrJSXoPjfm_RGYDBXFDuelpRkwJIobR20MbVLBbgchPC_RKTmJU3n44N8Pwn4XffLrKhZ5N5a0ThzG72QhBaSNGmc0Xew" alt="Avatar">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
        </div>
    </div>
    
    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-slate-500 hover:text-blue-600 hover:bg-slate-200" href="../../index.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800" href="index.php">
            <span class="material-symbols-outlined">groups</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
        </a>
    </nav>
</aside>

<header class="fixed top-0 right-0 left-72 bg-slate-50 flex justify-end items-center px-8 py-4 z-40 border-b border-slate-100">
    <a href="index.php" class="text-sm font-bold text-slate-500 hover:text-primary transition-colors flex items-center gap-2">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Dashboard
    </a>
</header>

<main class="ml-72 mt-20 p-12 max-w-4xl mx-auto">

    <div class="text-center mb-10">
        <span class="block text-sm font-semibold uppercase tracking-[0.15em] text-primary mb-2">SYSTEM ADMINISTRATION</span>
        <h1 class="text-4xl font-black font-headline text-on-surface tracking-tight">Establish New Organization</h1>
        <p class="text-slate-500 mt-2">Register a new club or society into the centralized university database.</p>
    </div>

    <?php if ($error): ?>
        <div class="mb-8 p-4 bg-error-container border-l-4 border-error text-error flex items-center gap-3 rounded-lg shadow-sm">
            <span class="material-symbols-outlined">error</span>
            <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-8 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 flex items-center gap-3 rounded-lg shadow-sm">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium"><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-surface-container-lowest p-8 rounded-2xl shadow-sm border border-slate-100">
        <form method="POST" action="">
            <div class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Official Organization Name <span class="text-error">*</span></label>
                    <input type="text" name="club_name" required placeholder="e.g. IT Society, Debating Club" 
                           class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary transition-all text-sm outline-none font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Category <span class="text-error">*</span></label>
                    <select name="club_category" required class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary transition-all text-sm outline-none font-medium">
                        <option value="" disabled selected>Select category...</option>
                        <option value="Academic">Academic & Educational</option>
                        <option value="Sports">Sports & Recreation</option>
                        <option value="Arts">Arts & Culture</option>
                        <option value="Leadership">Leadership & Uniform</option>
                        <option value="Others">Others</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Description / Objective</label>
                    <textarea name="club_description" rows="4" placeholder="Briefly describe the purpose of this club..." 
                              class="w-full bg-surface-container-low border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary transition-all text-sm outline-none font-medium resize-none"></textarea>
                </div>
            </div>

            <div class="h-[1px] bg-slate-100 my-8"></div>

            <div class="flex items-center justify-between">
                <a href="join.php" class="text-sm font-bold text-slate-400 hover:text-primary transition-colors flex items-center gap-1">
                    Skip & Join Existing
                </a>
                <button type="submit" name="create_club" class="signature-gradient text-white font-bold px-10 py-3 rounded-full shadow-lg hover:opacity-90 active:scale-95 transition-all outline-none flex items-center gap-2">
                    <span class="material-symbols-outlined">add_business</span>
                    Create Organization
                </button>
            </div>
        </form>
    </div>
</main>
</body>
</html>