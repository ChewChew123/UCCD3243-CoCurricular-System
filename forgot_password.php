<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';
$success = '';
$step = 1; // 1: Identify User & Answer Security Question, 2: Reset Password
$user_id_to_reset = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_security'])) {
        $identifier = $_POST['identifier'];
        $answer = $_POST['security_answer'];

        $sql = "SELECT user_id, security_answer FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($answer, $user['security_answer'])) {
                $_SESSION['reset_user_id'] = $user['user_id'];
                $step = 2;
            } else {
                $error = "Incorrect security answer!";
            }
        } else {
            $error = "User not found!";
        }
    } elseif (isset($_POST['reset_password'])) {
        if (isset($_SESSION['reset_user_id'])) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $user_id = $_SESSION['reset_user_id'];

            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_password, $user_id);

            if ($stmt->execute()) {
                $success = "Password reset successfully! <a href='login.php' class='font-bold underline'>Login now</a>";
                unset($_SESSION['reset_user_id']);
                $step = 3; // Success state
            } else {
                $error = "Failed to reset password: " . $conn->error;
                $step = 2;
            }
        } else {
            header("Location: forgot_password.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Forgot Password | Academic Curator</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "surface-tint": "#115cb9",
              "surface-variant": "#dbe4ed",
              "on-error": "#ffffff",
              "secondary-fixed-dim": "#c4c7ca",
              "surface-container-lowest": "#ffffff",
              "outline-variant": "#c2c6d4",
              "outline": "#727784",
              "secondary": "#5b5f62",
              "surface": "#f6faff",
              "on-secondary-fixed": "#181c1e",
              "background": "#f6faff",
              "on-surface": "#141d23",
              "primary-fixed-dim": "#acc7ff",
              "on-tertiary": "#ffffff",
              "inverse-on-surface": "#e9f2fb",
              "surface-container": "#e6eff8",
              "on-secondary": "#ffffff",
              "tertiary-fixed-dim": "#ffb694",
              "primary": "#003f87",
              "on-background": "#141d23",
              "primary-container": "#0056b3",
              "on-secondary-container": "#5f6366",
              "surface-dim": "#d2dbe4",
              "on-surface-variant": "#424752",
              "secondary-container": "#dde0e3",
              "surface-bright": "#f6faff",
              "inverse-surface": "#293138",
              "on-tertiary-fixed-variant": "#7b2f00",
              "surface-container-high": "#e0e9f2",
              "on-primary-fixed": "#001a40",
              "on-error-container": "#93000a",
              "error": "#ba1a1a",
              "surface-container-low": "#ecf5fe",
              "tertiary": "#722b00",
              "primary-fixed": "#d7e2ff",
              "tertiary-fixed": "#ffdbcc",
              "on-primary-fixed-variant": "#004491",
              "on-tertiary-container": "#ffc2a7",
              "on-secondary-fixed-variant": "#43474a",
              "inverse-primary": "#acc7ff",
              "tertiary-container": "#983c00",
              "on-primary-container": "#bbd0ff",
              "error-container": "#ffdad6",
              "on-primary": "#ffffff",
              "surface-container-highest": "#dbe4ed",
              "secondary-fixed": "#e0e3e6",
              "on-tertiary-fixed": "#351000"
            },
            fontFamily: {
              "headline": ["Manrope"],
              "body": ["Inter"],
              "label": ["Inter"]
            },
            borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
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
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">
<main class="flex-grow flex items-center justify-center py-12 px-6">
<div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-0 overflow-hidden rounded-xl shadow-2xl bg-surface-container-lowest">
<!-- Left Side: Editorial context -->
<div class="relative hidden lg:flex flex-col justify-between p-12 overflow-hidden signature-gradient text-white">
<div class="relative z-10">
<span class="font-headline text-xs uppercase tracking-widest font-bold opacity-60 mb-4 block">Institutional Excellence</span>
<h1 class="font-headline text-5xl font-extrabold leading-tight tracking-tighter">
                        Restore Your <br/> Access
                    </h1>
<p class="mt-6 text-lg font-light opacity-90 max-w-md">
                        Get back to curating your collegiate legacy. Reconnect with your achievements and continue your academic journey.
                    </p>
</div>
<!-- Decorative Element -->
<div class="absolute -bottom-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
</div>

<!-- Right Side: Reset Form -->
<div class="p-8 md:p-12 lg:p-16 flex flex-col justify-center">
<div class="mb-10">
<h3 class="font-headline text-3xl font-bold text-primary mb-2">Forgot Password</h3>
<p class="text-on-surface-variant font-medium">Enter your details to reset your password and regain access to your student portal.</p>
</div>

<?php if ($error): ?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($step == 1): ?>
<form action="forgot_password.php" method="POST" class="space-y-6">
    <div>
        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="identifier">Student ID or Email Address</label>
        <input class="w-full py-3 px-4 bg-surface-container-low border-b border-outline-variant/20 rounded-t-lg font-medium text-on-surface focus:outline-none focus:border-primary focus:bg-white transition-all" id="identifier" name="identifier" placeholder="e.g. 1900234 or student@1utar.my" required type="text"/>
    </div>

    <div class="p-6 bg-slate-50 rounded-xl border border-slate-100">
        <div class="flex items-center gap-2 mb-4 text-primary">
            <span class="material-symbols-outlined text-sm">security</span>
            <span class="text-xs uppercase tracking-widest font-bold">Security Verification</span>
        </div>
        <p class="text-sm text-on-surface-variant mb-4">Please answer your pre-set security question to continue.</p>
        <div class="bg-white p-4 rounded-lg border-l-4 border-primary mb-4">
            <p class="text-sm italic font-medium">"What was the name of your first childhood pet?"</p>
        </div>
        <input class="w-full py-3 px-4 bg-white border border-slate-200 rounded-lg font-medium text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" id="security_answer" name="security_answer" placeholder="Your answer" required type="text"/>
    </div>

    <button name="verify_security" class="w-full signature-gradient text-white py-4 rounded-full font-headline font-extrabold text-sm tracking-widest uppercase shadow-xl hover:opacity-90 active:scale-95 transition-all flex items-center justify-center gap-2" type="submit">
        Verify & Continue
        <span class="material-symbols-outlined">arrow_forward</span>
    </button>
</form>
<?php elseif ($step == 2): ?>
<form action="forgot_password.php" method="POST" class="space-y-6">
    <h4 class="font-headline font-bold text-lg text-primary">Set New Password</h4>
    <div>
        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="new_password">New Password</label>
        <input class="w-full py-3 px-4 bg-surface-container-low border-b border-outline-variant/20 rounded-t-lg font-medium text-on-surface focus:outline-none focus:border-primary" id="new_password" name="new_password" placeholder="••••••••" required type="password"/>
    </div>
    <button name="reset_password" class="w-full signature-gradient text-white py-4 rounded-full font-headline font-extrabold text-sm tracking-widest uppercase shadow-xl hover:opacity-90 active:scale-95 transition-all" type="submit">
        Reset Password
    </button>
</form>
<?php endif; ?>

<div class="mt-8 text-center">
    <a href="login.php" class="text-sm font-bold text-on-surface-variant hover:text-primary flex items-center justify-center gap-2 transition-colors">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Back to Login
    </a>
</div>
</div>
</div>
</main>
<footer class="w-full py-8 mt-auto border-t border-slate-100">
<div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
    <p>© 2024 The Academic Curator. All Rights Reserved.</p>
    <div class="flex gap-6">
        <a href="#" class="hover:text-primary transition-colors">Privacy Policy</a>
        <a href="#" class="hover:text-primary transition-colors">Support</a>
        <a href="#" class="hover:text-primary transition-colors">Institution Directory</a>
    </div>
</div>
</footer>
</body></html>
