<?php
/**
 * File: register.php
 * Purpose: Handle new user registration and account creation.
 */
require_once 'includes/db_connect.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $programme = $_POST['programme'];
    $academic_year = $_POST['academic_year']; // New field added
    
    // Process security answer: lowercase and trim whitespace
    $raw_answer = strtolower(trim($_POST['security_answer']));
    $security_answer = password_hash($raw_answer, PASSWORD_DEFAULT);
    
    // Check if the user already exists in the database
    $check_sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "Username or Email already exists!";
    } else {
        // Admin verification logic and safety mechanism
        $admin_code = isset($_POST['admin_code']) ? trim($_POST['admin_code']) : '';
        
        // If an invite code is entered but incorrect, block the registration
        if (!empty($admin_code) && $admin_code !== 'UTAR_ADMIN') {
            $error = "Invalid Admin Invite Code! Please leave it blank if you are a student.";
        } else {
            // Correct code results in 'admin', empty results in 'student'
            $role = ($admin_code === 'UTAR_ADMIN') ? 'admin' : 'student';

            // Insert user data including academic_year into the database
            $sql = "INSERT INTO users (username, password, full_name, email, programme, academic_year, security_answer, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // Updated bind_param: "sssssiss" (i for integer academic_year)
            $stmt->bind_param("sssssiss", $username, $password, $full_name, $email, $programme, $academic_year, $security_answer, $role);

            if ($stmt->execute()) {
                header("Location: login.php?register=success");
                exit();
            } else {
                $error = "Registration failed. Please try again or contact the administrator.";
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
    <title>Register | Academic Curator</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;400;500;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "surface-tint": "#115cb9", "surface-variant": "#dbe4ed", "on-error": "#ffffff", "secondary-fixed-dim": "#c4c7ca",
              "surface-container-lowest": "#ffffff", "outline-variant": "#c2c6d4", "outline": "#727784", "secondary": "#5b5f62",
              "surface": "#f6faff", "on-secondary-fixed": "#181c1e", "background": "#f6faff", "on-surface": "#141d23",
              "primary-fixed-dim": "#acc7ff", "on-tertiary": "#ffffff", "inverse-on-surface": "#e9f2fb", "surface-container": "#e6eff8",
              "on-secondary": "#ffffff", "tertiary-fixed-dim": "#ffb694", "primary": "#003f87", "on-background": "#141d23",
              "primary-container": "#0056b3", "on-secondary-container": "#5f6366", "surface-dim": "#d2dbe4", "on-surface-variant": "#424752",
              "secondary-container": "#dde0e3", "surface-bright": "#f6faff", "inverse-surface": "#293138", "on-tertiary-fixed-variant": "#7b2f00",
              "surface-container-high": "#e0e9f2", "on-primary-fixed": "#001a40", "on-error-container": "#93000a", "error": "#ba1a1a",
              "surface-container-low": "#ecf5fe", "tertiary": "#722b00", "primary-fixed": "#d7e2ff", "tertiary-fixed": "#ffdbcc",
              "on-primary-fixed-variant": "#004491", "on-tertiary-container": "#ffc2a7", "on-secondary-fixed-variant": "#43474a",
              "inverse-primary": "#acc7ff", "tertiary-container": "#983c00", "on-primary-container": "#bbd0ff", "error-container": "#ffdad6",
              "on-primary": "#ffffff", "surface-container-highest": "#dbe4ed", "secondary-fixed": "#e0e3e6", "on-tertiary-fixed": "#351000"
            },
            fontFamily: { "headline": ["Manrope"], "body": ["Inter"], "label": ["Inter"] },
            borderRadius: {"DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"},
          },
        },
      }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .signature-gradient { background: linear-gradient(135deg, #003f87 0%, #0056b3 100%); }
        .input-minimal:focus { background-color: #ffffff; border-bottom: 2px solid #003f87; outline: none; box-shadow: none; }
        .input-minimal { background-color: #ecf5fe; border: none; border-bottom: 1px solid rgba(194, 198, 212, 0.2); transition: all 0.2s ease; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col">

<main class="flex-grow flex items-center justify-center py-12 px-6">
    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-0 overflow-hidden rounded-xl shadow-2xl bg-surface-container-lowest">
        
        <div class="relative hidden lg:flex flex-col justify-between p-12 overflow-hidden signature-gradient text-white">
            <div class="relative z-10">
                <h2 class="font-headline text-sm uppercase tracking-widest font-bold opacity-80 mb-4">Academic Curator</h2>
                <h1 class="font-headline text-5xl font-extrabold leading-tight tracking-tighter">Curate your <br/> collegiate legacy.</h1>
                <p class="mt-6 text-lg font-light opacity-90 max-w-md">Join an elite network of students documenting their co-curricular excellence through a curated portfolio experience.</p>
            </div>
            <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -left-20 w-64 h-64 bg-primary-container/20 rounded-full blur-2xl"></div>
        </div>

        <div class="p-8 md:p-12 lg:p-16 flex flex-col justify-center">
            <div class="mb-10">
                <h3 class="font-headline text-3xl font-bold text-primary mb-2">Create Account</h3>
                <p class="text-on-surface-variant font-medium">Enter your details to begin your academic curation.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-error-container text-on-error-container p-3 rounded-lg mb-6 font-medium text-sm border-l-4 border-error shadow-sm">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" class="space-y-5" method="POST">
                
                <div class="group">
                    <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="full_name">Full Name</label>
                    <input class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0" id="full_name" name="full_name" placeholder="e.g. Alexander Hamilton" required type="text"/>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="group">
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="username">Student ID</label>
                        <input class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0" id="username" name="username" placeholder="e.g. 1900234" required type="text"/>
                    </div>
                    <div class="group">
                        <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="academic_year">Academic Year</label>
                        <select class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0" id="academic_year" name="academic_year" required>
                            <option value="0">Foundation</option>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                            <option value="4">NON-student</option>
                        </select>
                    </div>
                </div>

                <div class="group">
                    <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="programme">Programme</label>
                    <select class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0 appearance-none" id="programme" name="programme">
                        <option value="Bachelor of Computer Science">Bachelor of Computer Science</option>
                        <option value="Bachelor of Information Systems">Bachelor of Information Systems</option>
                        <option value="Bachelor of Information Technology">Bachelor of Information Technology</option>
                        <option value="Bachelor of Software Engineering">Bachelor of Software Engineering</option>
                        <option value="Bachelor of Engineering">Bachelor of Engineering</option>
                        <option value="Bachelor of Business Administration">Bachelor of Business Administration</option>
                        <option value="4">NON-student</option>
                        <option value="Other">Other Programme</option>
                    </select>
                </div>

                <div class="group">
                    <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="email">Institutional Email</label>
                    <input class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0" id="email" name="email" placeholder="e.g. student@1utar.my" required type="email"/>
                </div>

                <div class="group">
                    <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="password">Password</label>
                    <div class="relative">
                        <input class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0 pr-12" id="password" name="password" placeholder="••••••••" required type="password"/>
                        <span id="togglePassword" class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline cursor-pointer hover:text-primary transition-colors">visibility</span>
                    </div>
                </div>

                <div class="group">
                    <label class="block font-headline text-xs font-bold uppercase tracking-wider text-outline mb-1" for="security_answer">Security Question</label>
                    <div class="bg-surface-container-low p-3 rounded-t-lg border-b border-outline-variant/20 mb-1">
                        <p class="text-xs text-on-surface-variant font-medium italic">"What was the name of your first childhood pet?"</p>
                    </div>
                    <input class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0" id="security_answer" name="security_answer" placeholder="Your Answer" required type="text"/>
                </div>

                <div class="text-right mt-2">
                    <button type="button" id="toggleAdminBtn" class="text-[10px] text-slate-400 hover:text-primary font-bold uppercase tracking-wider transition-colors cursor-pointer">
                        + Staff / Admin Registration
                    </button>
                </div>

                <div id="adminCodeSection" class="hidden group border-t border-dashed border-outline-variant pt-4 mt-2">
                    <label class="block font-headline text-xs font-bold uppercase tracking-wider text-error mb-1" for="admin_code">
                        Admin Invite Code <span class="text-slate-400 lowercase font-normal">(Leave blank for students)</span>
                    </label>
                    <div class="relative">
                        <input class="input-minimal w-full py-3 px-4 rounded-t-lg font-medium text-on-surface focus:ring-0 pr-12" id="admin_code" name="admin_code" placeholder="Enter authorization code" type="password"/>
                        <span id="toggleAdminCode" class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline cursor-pointer hover:text-error transition-colors">visibility</span>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-1 italic">Only for authorized administrators.</div>
                </div>

                <div class="pt-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-3">
                        <input class="mt-1 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" id="terms" required type="checkbox"/>
                        <label class="text-xs text-on-surface-variant leading-relaxed cursor-pointer" for="terms">
                            I agree to the <a class="text-primary font-bold hover:underline" href="#">Terms of Service</a>.
                        </label>
                    </div>
                    <button class="signature-gradient text-white px-8 py-3 rounded-full font-headline font-extrabold text-sm tracking-widest uppercase shadow-xl hover:opacity-90 active:scale-95 transition-all whitespace-nowrap" type="submit">
                        Register Now
                    </button>
                </div>

                <div class="pt-6 border-t border-outline-variant/10 text-center">
                    <p class="text-sm text-on-surface-variant">
                        Already part of the curriculum? <a class="text-primary font-bold ml-1 hover:underline" href="login.php">Sign In</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</main>

<footer class="w-full py-8 mt-auto border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <p class="font-body text-xs text-slate-500">© 2026 The Academic Curator. All Rights Reserved.</p>
    </div>
</footer>

<script>
// Toggle main password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.textContent = type === 'password' ? 'visibility' : 'visibility_off';
});

// Toggle Admin Code visibility
document.getElementById('toggleAdminCode').addEventListener('click', function() {
    const adminCodeInput = document.getElementById('admin_code');
    const type = adminCodeInput.getAttribute('type') === 'password' ? 'text' : 'password';
    adminCodeInput.setAttribute('type', type);
    this.textContent = type === 'password' ? 'visibility' : 'visibility_off';
});

// Show/Hide Admin Invitation Code field
document.getElementById('toggleAdminBtn').addEventListener('click', function() {
    const adminSection = document.getElementById('adminCodeSection');
    const adminInput = document.getElementById('admin_code');
    const toggleIcon = document.getElementById('toggleAdminCode');
    
    if (adminSection.classList.contains('hidden')) {
        // Show field
        adminSection.classList.remove('hidden');
        this.textContent = '- Hide Admin Registration';
        adminInput.focus(); 
    } else {
        // Hide field and reset values
        adminSection.classList.add('hidden');
        this.textContent = '+ Staff / Admin Registration';
        adminInput.value = ''; 
        adminInput.setAttribute('type', 'password'); 
        toggleIcon.textContent = 'visibility';
    }
});
</script>
</body>
</html>