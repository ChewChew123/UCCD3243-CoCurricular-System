<?php
/**
 * File: profile.php
 * Path: /profile.php
 * Purpose: User profile management and security settings for the Academic Curator system.
 */
session_start();

// 1. Import database connection configuration
require_once 'includes/db_connect.php'; 

/**
 * 2. AUTHENTICATION CHECK
 * Redirect to login page if no active session is found.
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$success_msg = '';
$error_msg = '';

/**
 * 3. PASSWORD UPDATE LOGIC
 * Triggered when the user submits the 'update_password' form.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Input Validation: Minimum length and match check
    if (strlen($new_password) < 8) {
        $error_msg = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } else {
        // Retrieve hashed password from DB for verification
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Verify current password against stored hash
        if (password_verify($current_password, $user['password'])) {
            // Securely hash new password using BCRYPT
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $success_msg = "Security credentials updated successfully!";
            } else {
                $error_msg = "Failed to update database. Please try again.";
            }
        } else {
            $error_msg = "Current password validation failed.";
        }
    }
}

/**
 * 4. FETCH USER PROFILE DATA
 * Retrieve the latest user information from the database.
 */
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Use raw username for the Institutional ID (Removed padding to avoid unwanted zeros)
$display_id = htmlspecialchars($user_data['username']);
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Profile | Academic Curator</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
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

<?php 
$base_path = ""; 
$current_page = "profile"; 
$full_name = $user_data['full_name'];
$programme = $user_data['programme'] ?? 'Curator';
include 'includes/sidebar.php'; 
?>

<header class="flex justify-between items-center h-20 px-8 ml-72 fixed top-0 w-[calc(100%-18rem)] z-40 bg-white/80 backdrop-blur-md border-b border-slate-100">
    <div class="text-sm font-extrabold text-primary tracking-widest uppercase">System Settings & Identity</div>
    <div class="flex items-center gap-4">
        <?php if ($is_admin): ?>
            <span class="bg-blue-900 text-white text-[10px] font-black px-3 py-1 rounded-full tracking-tighter uppercase shadow-lg">Admin Mode</span>
        <?php endif; ?>
        <div class="h-8 w-[1px] bg-slate-200"></div>
        <span class="text-xs font-bold text-slate-500"><?php echo date('D, d M Y'); ?></span>
    </div>
</header>

<main class="ml-72 pt-28 p-12 min-h-screen">
    <div class="max-w-4xl mx-auto space-y-12">
        
        <header>
            <h1 class="text-4xl font-extrabold text-primary tracking-tight font-headline">Account Identity</h1>
            <p class="text-slate-500 mt-2">
                Managing the curated profile of <strong><?php echo strtoupper(htmlspecialchars($user_data['full_name'])); ?> (<?php echo $display_id; ?>)</strong>. 
                <?php echo $is_admin ? "You have administrative access to university-wide records." : "All co-curricular data is verified by the academic curator system."; ?>
            </p>
        </header>

        <?php if ($success_msg): ?>
            <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl flex items-center gap-3 shadow-sm animate-in fade-in slide-in-from-top-4 duration-500">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-bold text-sm"><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-xl flex items-center gap-3 shadow-sm">
                <span class="material-symbols-outlined">error</span>
                <span class="font-bold text-sm"><?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100 relative overflow-hidden">
            <div class="flex items-center justify-between mb-12 relative z-10">
                <h2 class="text-2xl font-bold text-on-surface font-headline flex items-center gap-3">
                    <span class="w-2 h-8 signature-gradient rounded-full"></span>
                    Profile Details
                </h2>
                
                <a href="generate_transcript.php" target="_blank" class="flex items-center gap-2 px-8 py-4 bg-slate-900 text-white rounded-full font-black text-xs uppercase tracking-widest shadow-xl hover:bg-black hover:-translate-y-1 active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    Generate Transcript
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Legal Full Name</label>
                    <p class="text-lg font-extrabold text-slate-800"><?php echo htmlspecialchars($user_data['full_name']); ?></p>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Institutional ID</label>
                    <p class="text-lg font-extrabold text-slate-800"><?php echo $display_id; ?></p>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Academic Programme</label>
                    <p class="text-lg font-extrabold text-slate-800">
                        <?php echo $is_admin ? 'Faculty Operations' : htmlspecialchars($user_data['programme']); ?>
                    </p>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        <?php echo $is_admin ? "Access Level" : "Academic Year"; ?>
                    </label>
                    <p class="text-lg font-extrabold text-primary">
                        <?php 
                            if ($is_admin) {
                                echo "Master Administrator"; 
                            } else {
                                // If database shows 0, display Foundation/Preparatory
                                echo ($user_data['academic_year'] == 0) ? 'Foundation / Preparatory' : 'Year ' . htmlspecialchars($user_data['academic_year']);
                            }
                        ?>
                    </p>
                </div>

                <div class="md:col-span-2 space-y-1 pt-6 border-t border-slate-50">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Registered Email Address</label>
                    <p class="text-lg font-extrabold text-slate-800"><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
            </div>
            
            <div class="absolute -right-12 -bottom-12 opacity-[0.03] pointer-events-none">
                <span class="material-symbols-outlined text-[320px]">badge</span>
            </div>
        </section>

        <section class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
            <h2 class="text-2xl font-bold text-on-surface mb-10 font-headline flex items-center gap-3">
                <span class="w-2 h-8 bg-amber-400 rounded-full"></span>
                Security Credentials
            </h2>
            
            <form method="POST" action="profile.php" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 block">Verify Current Password</label>
                        <input name="current_password" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all shadow-inner" placeholder="••••••••" type="password"/>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 block">New Security Password</label>
                        <input name="new_password" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all shadow-inner" placeholder="••••••••" type="password"/>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3 block">Confirm New Security Password</label>
                        <input name="confirm_password" required class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary transition-all shadow-inner" placeholder="••••••••" type="password"/>
                    </div>
                </div>
                
                <div class="pt-6">
                    <button name="update_password" class="w-full md:w-max px-12 py-5 rounded-full signature-gradient text-white font-black tracking-widest uppercase text-xs shadow-xl hover:shadow-blue-200 hover:-translate-y-1 active:scale-95 transition-all flex items-center justify-center gap-3" type="submit">
                        <span class="material-symbols-outlined text-lg">verified_user</span>
                        Update Security Credentials
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>

</body>
</html>