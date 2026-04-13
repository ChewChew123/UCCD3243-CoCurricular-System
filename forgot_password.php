<?php
/**
 * File: forgot_password.php
 * Purpose: Secure multi-step password recovery using dynamic security questions.
 */
session_start();
require_once 'includes/db_connect.php';

$error = '';
$success = '';
$step = 1; // 1: Identify User, 2: Answer Question, 3: Reset Password, 4: Success
$display_question = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // STEP 1: Find User & Get their specific question
    if (isset($_POST['find_user'])) {
        $identifier = trim($_POST['identifier']);
        $sql = "SELECT user_id, security_question FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $_SESSION['reset_user_id'] = $user['user_id'];
            $_SESSION['reset_question'] = $user['security_question'];
            $step = 2;
        } else {
            $error = "No account found with that Student ID or Email.";
        }
    } 
    // STEP 2: Verify the answer
    elseif (isset($_POST['verify_answer'])) {
        $answer = trim($_POST['security_answer']);
        $user_id = $_SESSION['reset_user_id'];

        $sql = "SELECT security_answer FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($answer, $user['security_answer'])) {
            $step = 3;
        } else {
            $error = "Incorrect security answer. Please try again.";
            $step = 2;
        }
    }
    // STEP 3: Save new password
    elseif (isset($_POST['reset_password'])) {
        if (isset($_SESSION['reset_user_id'])) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $user_id = $_SESSION['reset_user_id'];

            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_password, $user_id);

            if ($stmt->execute()) {
                $success = "Your password has been restored.";
                unset($_SESSION['reset_user_id'], $_SESSION['reset_question']);
                $step = 4;
            } else {
                $error = "Database error. Please contact admin.";
                $step = 3;
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
    <title>Restore Access | Academic Curator</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { "primary": "#003f87", "surface": "#f6faff" }, fontFamily: { "headline": ["Manrope"], "body": ["Inter"] } } }
        }
    </script>
    <style>
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
    </style>
</head>
<body class="bg-surface font-body text-slate-900 min-h-screen flex flex-col">

<main class="flex-grow flex items-center justify-center py-12 px-6">
    <div class="max-w-5xl w-full grid grid-cols-1 lg:grid-cols-2 overflow-hidden rounded-[2.5rem] shadow-2xl bg-white border border-slate-100">
        
        <div class="relative hidden lg:flex flex-col justify-between p-16 signature-gradient text-white">
            <div class="relative z-10">
                <span class="text-xs uppercase tracking-[0.3em] font-bold opacity-60 mb-4 block">Institutional Security</span>
                <h1 class="font-headline text-5xl font-black leading-tight tracking-tighter">Restore Your Access</h1>
                <p class="mt-6 text-blue-100/80 leading-relaxed">Verification ensures the integrity of your academic profile. Follow the steps to regain entry to your dashboard.</p>
            </div>
            <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div class="p-10 md:p-16 flex flex-col justify-center bg-white">
            <div class="mb-10">
                <h3 class="font-headline text-3xl font-black text-primary mb-2">Account Recovery</h3>
                <p class="text-slate-400 font-medium text-sm">Step <?php echo $step; ?> of 3: Verification Process</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-xs font-bold rounded-lg flex items-center gap-3">
                    <span class="material-symbols-outlined text-sm">error</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-xs font-bold rounded-lg flex items-center gap-3">
                    <span class="material-symbols-outlined text-sm">check_circle</span> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 px-1">Student ID or Email</label>
                        <input type="text" name="identifier" class="w-full py-4 px-5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary transition-all font-medium" placeholder="Enter your credentials" required>
                    </div>
                    <button name="find_user" type="submit" class="w-full signature-gradient text-white py-4 rounded-full font-black text-xs tracking-widest uppercase shadow-xl hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                        Find Account <span class="material-symbols-outlined text-sm">search</span>
                    </button>
                </form>

            <?php elseif ($step == 2): ?>
                <form method="POST" class="space-y-6">
                    <div class="p-6 bg-blue-50 rounded-[1.5rem] border border-blue-100">
                        <span class="text-[10px] font-black text-primary uppercase tracking-widest block mb-3">Security Challenge</span>
                       <p class="text-slate-700 font-bold italic mb-6"><?php echo htmlspecialchars($_SESSION['reset_question'] ?? 'What was the name of your first childhood pet?'); ?></p>
                        <input type="text" name="security_answer" class="w-full py-4 px-5 bg-white border-none rounded-xl focus:ring-2 focus:ring-primary transition-all shadow-sm" placeholder="Type your answer here" required autofocus>
                    </div>
                    <button name="verify_answer" type="submit" class="w-full signature-gradient text-white py-4 rounded-full font-black text-xs tracking-widest uppercase shadow-xl flex items-center justify-center gap-2">
                        Verify Identity <span class="material-symbols-outlined text-sm">verified_user</span>
                    </button>
                </form>

            <?php elseif ($step == 3): ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">New Secure Password</label>
                        <input type="password" name="new_password" class="w-full py-4 px-5 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary transition-all" placeholder="••••••••" required autofocus>
                    </div>
                    <button name="reset_password" type="submit" class="w-full signature-gradient text-white py-4 rounded-full font-black text-xs tracking-widest uppercase shadow-xl flex items-center justify-center gap-2">
                        Save New Password <span class="material-symbols-outlined text-sm">lock_reset</span>
                    </button>
                </form>

            <?php elseif ($step == 4): ?>
                <div class="text-center py-6">
                    <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'wght' 700">check</span>
                    </div>
                    <h4 class="text-2xl font-black text-slate-800 mb-2">Success!</h4>
                    <p class="text-slate-500 mb-8 text-sm">Your access has been restored. You can now login with your new password.</p>
                    <a href="login.php" class="inline-block bg-primary text-white px-10 py-4 rounded-full font-black text-xs uppercase tracking-widest shadow-lg hover:bg-blue-800 transition-all">Go to Login</a>
                </div>
            <?php endif; ?>

            <?php if ($step < 4): ?>
            <div class="mt-10 text-center">
                <a href="login.php" class="text-xs font-black text-slate-400 hover:text-primary flex items-center justify-center gap-2 transition-colors uppercase tracking-widest">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Return to Login
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="w-full py-8 mt-auto border-t border-slate-100 bg-white">
    <div class="max-w-7xl mx-auto px-6 flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-slate-400">
        <p>© 2026 Academic Curator System</p>
        <div class="flex gap-6">
            <a href="#" class="hover:text-primary">Support</a>
            <a href="#" class="hover:text-primary">Policy</a>
        </div>
    </div>
</footer>

</body>
</html>