<?php
/**
 * Shared Sidebar Component
 * Requires: $base_path, $current_page, $full_name, $programme
 */
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$active_class = "bg-blue-50/50 text-blue-800 font-bold border-r-4 border-blue-800";
$inactive_class = "text-slate-500 hover:text-blue-600 hover:bg-slate-100";
?>
<aside class="h-screen w-72 fixed left-0 top-0 bg-white border-r border-slate-100 flex flex-col p-6 space-y-8 z-50 shadow-sm">
    <!-- Logo -->
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 signature-gradient rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined">auto_stories</span>
        </div>
        <div class="text-2xl font-bold tracking-tight text-blue-900 font-headline">Academic Curator</div>
    </div>

    <!-- Profile Mini-Card -->
    <div class="flex items-center gap-3 px-2 py-4 bg-slate-50 rounded-2xl">
        <img class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" 
             src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=003f87&color=fff" 
             alt="User">
        <div class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 truncate"><?php echo htmlspecialchars($full_name); ?></p>
            <p class="text-[10px] font-bold text-primary uppercase tracking-wider truncate"><?php echo htmlspecialchars($programme); ?></p>
        </div>
    </div>

    <!-- New Activity Button (Students Only) -->
    <?php if (!$is_admin): ?>
        <a href="<?php echo $base_path; ?>modules/clubs/join.php" 
           class="py-3 px-4 signature-gradient text-white rounded-full font-bold text-sm shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span>
            New Activity
        </a>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="flex-1 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo ($current_page == 'overview') ? $active_class : $inactive_class; ?>" 
           href="<?php echo $base_path; ?>index.php">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo ($current_page == 'events') ? $active_class : $inactive_class; ?>" 
           href="<?php echo $base_path; ?>modules/events/index.php">
            <span class="material-symbols-outlined">event_note</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Events</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo ($current_page == 'achievements') ? $active_class : $inactive_class; ?>" 
           href="<?php echo $base_path; ?>modules/achievements/index.php">
            <span class="material-symbols-outlined">verified</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Achievements</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo ($current_page == 'merits') ? $active_class : $inactive_class; ?>" 
           href="<?php echo $base_path; ?>modules/merits/index.php">
            <span class="material-symbols-outlined">military_tech</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Merits</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo ($current_page == 'clubs') ? $active_class : $inactive_class; ?>" 
           href="<?php echo $base_path; ?>modules/clubs/index.php">
            <span class="material-symbols-outlined">groups</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">Club Memberships</span>
        </a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo ($current_page == 'profile') ? $active_class : $inactive_class; ?>" 
           href="<?php echo $base_path; ?>profile.php">
            <span class="material-symbols-outlined">person</span>
            <span class="text-sm font-semibold Manrope uppercase tracking-wider">My Profile</span>
        </a>
    </nav>

    <!-- Logout -->
    <div class="pt-6 border-t border-slate-200/50 space-y-2">
        <a class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-blue-600 transition-colors" 
           href="<?php echo $base_path; ?>logout.php">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-xs font-semibold Manrope uppercase tracking-wider">Log Out</span>
        </a>
    </div>
</aside>
