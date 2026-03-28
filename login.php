<?php

session_start();
require_once 'includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT user_id, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $username;

            if (isset($_POST['remember-me'])) {
                setcookie("remember_user", $username, time() + (30 * 24 * 60 * 60), "/");
                setcookie("remember_pass", $_POST['password'], time() + (30 * 24 * 60 * 60), "/"); 
            } else {
                setcookie("remember_user", "", time() - 3600, "/");
                setcookie("remember_pass", "", time() - 3600, "/");
            }

            header("Location: index.php");
            exit();
        }
    }
    $error = "Invalid username or password!"; 
}
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Login | Academic Curator</title>
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
      .glass-effect {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
      }
      .ambient-shadow {
        box-shadow: 0 20px 40px rgba(20, 29, 37, 0.06);
      }
    </style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">
<main class="flex-grow flex items-center justify-center px-4 py-12 lg:py-24 relative overflow-hidden">
<!-- Background Decorative Elements -->
<div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-primary/5 blur-3xl"></div>
<div class="absolute -bottom-48 -right-48 w-[32rem] h-[32rem] rounded-full bg-primary-container/10 blur-3xl"></div>
<div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-0 ambient-shadow rounded-2xl overflow-hidden bg-surface-container-lowest">
<!-- Left Side: Editorial Branding & Image -->
<div class="hidden lg:flex flex-col justify-center p-12 bg-surface-container-low relative overflow-hidden">
<div class="z-10">
<div class="space-y-6">
<h1 class="font-headline text-5xl font-extrabold text-on-surface leading-tight tracking-tight">
                        Elevate Your <br/>
<span class="text-primary-container">Student Identity.</span>
</h1>
<p class="text-on-surface-variant text-lg max-w-sm leading-relaxed">
                        Join the premier platform for managing co-curricular achievements and building your professional portfolio.
                    </p>
</div>
</div>
<!-- Decorative Bloom -->
<div class="absolute top-1/2 -right-20 w-64 h-64 rounded-full bg-primary/5 -translate-y-1/2"></div>
</div>
<!-- Right Side: Login Form -->
<div class="p-8 lg:p-16 flex flex-col justify-center">
<div class="mb-10">
<span class="font-label text-xs font-bold uppercase tracking-widest text-primary-container mb-2 block">Student Access Portal</span>
<h2 class="font-headline text-3xl font-bold text-on-surface">Welcome back</h2>
<p class="text-on-surface-variant mt-2">Please enter your institutional credentials to continue.</p>
</div>

<?php if (isset($_GET['register']) && $_GET['register'] == 'success'): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        Registration successful! Please login.
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<form action="login.php" class="space-y-6" method="POST">
<div>
<label class="block text-sm font-semibold text-on-surface mb-2" for="username">Student ID</label>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-outline text-xl">badge</span>
</div>
<input class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-outline/60 focus:ring-0 focus:bg-surface-container-lowest transition-all duration-200" id="username" name="username" placeholder="e.g. 1900234" required="" type="text" value="<?php echo isset($_COOKIE['remember_user']) ? $_COOKIE['remember_user'] : ''; ?>"/>
<div class="absolute bottom-0 left-0 h-0.5 w-0 bg-primary transition-all duration-300 peer-focus:w-full"></div>
</div>
</div>
<div>
<div class="flex justify-between items-center mb-2">
<label class="block text-sm font-semibold text-on-surface" for="password">Password</label>
<a class="text-xs font-bold text-primary hover:underline" href="forgot_password.php">Forgot password?</a>
</div>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-outline text-xl">lock</span>
</div>
<input class="block w-full pl-12 pr-12 py-4 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-outline/60 focus:ring-0 focus:bg-surface-container-lowest transition-all duration-200" id="password" name="password" placeholder="••••••••" required="" type="password" value="<?php echo isset($_COOKIE['remember_pass']) ? $_COOKIE['remember_pass'] : ''; ?>"/>
<span id="togglePassword" class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline cursor-pointer hover:text-primary transition-colors">visibility</span>
</div>
</div>
<div class="flex items-center">
<input class="w-5 h-5 rounded-md border-outline-variant text-primary focus:ring-primary/20 transition-all cursor-pointer" id="remember-me" name="remember-me" type="checkbox" <?php echo isset($_COOKIE['remember_user']) ? 'checked' : ''; ?>/>
<label class="ml-3 text-sm font-medium text-on-surface-variant cursor-pointer select-none" for="remember-me">Remember Me</label>
</div>
<button class="w-full signature-gradient text-white py-4 rounded-full font-bold text-lg ambient-shadow hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2 group" type="submit">
                    Sign In
                    <span class="material-symbols-outlined transition-transform group-hover:translate-x-1">arrow_forward</span>
</button>
</form>
<div class="mt-10 text-center">
<p class="text-on-surface-variant">
                    New student? 
                    <a class="text-primary font-bold hover:underline ml-1" href="register.php">Create an account</a>
</p>
</div>
<!-- Security Note -->
<div class="mt-12 flex items-center justify-center gap-2 opacity-40 grayscale">
<span class="material-symbols-outlined text-sm">verified_user</span>
<span class="text-[10px] uppercase tracking-widest font-bold">Encrypted Institutional Login</span>
</div>
</div>
</div>
</main>
<!-- Footer -->
<footer class="w-full py-8 mt-auto bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800">
<div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
<div class="font-inter text-xs text-slate-500 dark:text-slate-400">
            © 2024 The Academic Curator. All Rights Reserved.
        </div>
<div class="flex gap-6">
<a class="font-inter text-xs text-slate-400 dark:text-slate-500 hover:text-blue-500 transition-colors" href="#">Privacy Policy</a>
<a class="font-inter text-xs text-slate-400 dark:text-slate-500 hover:text-blue-500 transition-colors" href="#">Terms of Service</a>
<a class="font-inter text-xs text-slate-400 dark:text-slate-500 hover:text-blue-500 transition-colors" href="#">Support</a>
<a class="font-inter text-xs text-slate-400 dark:text-slate-500 hover:text-blue-500 transition-colors" href="#">Institution Directory</a>
</div>
</div>
</footer>
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.textContent = type === 'password' ? 'visibility' : 'visibility_off';
});
</script>
</body></html>