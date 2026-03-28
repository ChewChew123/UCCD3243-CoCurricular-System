<?php
// 文件路径: /index.php
session_start();

// 1. 安全检查：没登录的直接踢回登录页
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Co-curricular System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo"><strong>UTAR</strong> Co-curricular</div>
        <div class="nav-links">
            <span>Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
            <a href="logout.php" class="logout-btn" style="margin-left:20px;">Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        
        <div class="welcome-section">
            <h1>Student Dashboard</h1>
            <p>Select a module below to manage your records and tracking.</p>
        </div>

        <div class="module-grid">
            
            <div class="module-card">
                <h3>🏆 Achievements</h3>
                <p>Track your awards, certificates, and academic honors.</p>
                <a href="modules/achievements/index.php" class="btn-enter">Manage</a>
            </div>

            <div class="module-card">
                <h3>📅 Events</h3>
                <p>Register and track participation in university events.</p>
                <a href="modules/events/index.php" class="btn-enter">Manage</a>
            </div>

            <div class="module-card">
                <h3>🤝 Clubs & Societies</h3>
                <p>Manage your club memberships and leadership roles.</p>
                <a href="modules/clubs/index.php" class="btn-enter">Manage</a>
            </div>

            <div class="module-card">
                <h3>⭐ Merit Hours</h3>
                <p>Record your volunteer hours and community service.</p>
                <a href="modules/merits/index.php" class="btn-enter">Manage</a>
            </div>

        </div>

    </div>

</body>
</html>