<!-- Mobile Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-20 hidden lg:hidden transition-opacity"></div>

<?php
$sidebarPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'] ?? 'User') . '&background=3B82F6&color=fff';
if (!empty($_SESSION['profile_image']) && $_SESSION['profile_image'] !== 'default.png') {
    $sidebarPhoto = SITE_URL . '/uploads/profiles/' . $_SESSION['profile_image'];
}
?>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-30 w-64 transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0 flex flex-col h-[100dvh] bg-[#0B1120]/95 backdrop-blur-2xl transition-transform duration-300">
    
    <!-- Fixed Branded Header -->
    <div class="flex items-center justify-center h-20 border-b border-gray-200 dark:border-gray-800/50 relative overflow-hidden group cursor-pointer shrink-0">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="flex items-center justify-center px-4 relative z-10 hover-lift w-full">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo.png?v=2" alt="task.n1space.com" class="h-14 w-auto object-contain">
        </div>
    </div>

    <!-- Scrollable Navigation middle section -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1.5">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-3 px-4">Menu</p>
        
        <a href="<?php echo SITE_URL; ?>/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-squares-four text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-cyan-400' : 'group-hover:text-cyan-400 transition-colors'; ?>"></i>
            Dashboard
        </a>
        
        <a href="<?php echo SITE_URL; ?>/tasks.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'tasks.php') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-kanban text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'tasks.php') ? 'text-cyan-400' : 'group-hover:text-blue-400 transition-colors'; ?>"></i>
            Board & Tasks
        </a>

        <a href="<?php echo SITE_URL; ?>/projects.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'projects.php') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-folder-notch-open text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'projects.php') ? 'text-cyan-400' : 'group-hover:text-purple-400 transition-colors'; ?>"></i>
            Projects
        </a>

        <a href="<?php echo SITE_URL; ?>/calendar.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'calendar.php') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-calendar-blank text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'calendar.php') ? 'text-cyan-400' : 'group-hover:text-emerald-400 transition-colors'; ?>"></i>
            Calendar
        </a>

        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-6 mb-3 px-4">Ticketing</p>
        
        <a href="<?php echo SITE_URL; ?>/tickets.php?view=all" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? 'all') == 'all') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-bold ph-list text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? 'all') == 'all') ? 'text-cyan-400' : 'group-hover:text-cyan-400 transition-colors'; ?>"></i>
            All Tickets
        </a>

        <a href="<?php echo SITE_URL; ?>/tickets.php?view=open" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'open') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-bold ph-envelope-open text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'open') ? 'text-blue-400' : 'group-hover:text-blue-400 transition-colors'; ?>"></i>
            Open Tickets
        </a>

        <a href="<?php echo SITE_URL; ?>/tickets.php?view=assigned" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'assigned') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-bold ph-user-focus text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'assigned') ? 'text-purple-400' : 'group-hover:text-purple-400 transition-colors'; ?>"></i>
            Assigned Tickets
        </a>

        <a href="<?php echo SITE_URL; ?>/tickets.php?view=breached" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'breached') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-rose-455 font-semibold border border-rose-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-bold ph-warning text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'breached') ? 'text-rose-400' : 'group-hover:text-rose-400 transition-colors'; ?>"></i>
            SLA Breached
        </a>

        <a href="<?php echo SITE_URL; ?>/tickets.php?view=closed" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'closed') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-bold ph-archive text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'closed') ? 'text-emerald-400' : 'group-hover:text-emerald-400 transition-colors'; ?>"></i>
            Closed Tickets
        </a>

        <a href="<?php echo SITE_URL; ?>/tickets.php?view=kb" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'kb') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-bold ph-book-open text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'tickets.php' && ($_GET['view'] ?? '') == 'kb') ? 'text-amber-400' : 'group-hover:text-amber-400 transition-colors'; ?>"></i>
            Knowledge Base
        </a>
        
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-6 mb-3 px-4">Workspace</p>

        <a href="<?php echo SITE_URL; ?>/teams.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'teams.php') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-users-three text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'teams.php') ? 'text-cyan-400' : 'group-hover:text-orange-400 transition-colors'; ?>"></i>
            Team Chat
        </a>
        
        <a href="<?php echo SITE_URL; ?>/notes.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'notes.php') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-notebook text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'notes.php') ? 'text-cyan-400' : 'group-hover:text-yellow-400 transition-colors'; ?>"></i>
            Notes & Docs
        </a>

        <a href="<?php echo SITE_URL; ?>/applications.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'applications.php') ? 'bg-gradient-to-r from-violet-500/20 to-purple-500/10 text-violet-400 font-semibold border border-violet-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-files text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'applications.php') ? 'text-violet-400' : 'group-hover:text-violet-400 transition-colors'; ?>"></i>
            Applications
        </a>

        <?php if (isAdmin()): ?>
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-6 mb-3 px-4">Administrative</p>
        
        <a href="<?php echo SITE_URL; ?>/users.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all group <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'bg-gradient-to-r from-blue-500/20 to-cyan-500/10 text-cyan-400 font-semibold border border-cyan-500/20' : 'text-gray-400 hover:bg-white/5 hover:text-white hover:translate-x-1'; ?>">
            <i class="ph-fill ph-users text-xl <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'text-cyan-400' : 'group-hover:text-cyan-400 transition-colors'; ?>"></i>
            User Directory
        </a>
        <?php endif; ?>
    </nav>

    <!-- Fixed Bottom Actions -->
    <div class="p-4 border-t border-gray-800/50 relative shrink-0">
        <!-- Profile Dropdown Menu -->
        <div id="profile-dropdown" class="absolute bottom-20 left-4 right-4 bg-gray-950/95 backdrop-blur-2xl border border-gray-800 rounded-xl p-2 hidden shadow-2xl animate-fade-in z-50">
            <a href="<?php echo SITE_URL; ?>/settings.php" class="flex items-center gap-3 px-3 py-2.5 text-xs text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors font-medium">
                <i class="ph-fill ph-gear text-base text-gray-550"></i> Settings
            </a>
            <hr class="border-gray-800/60 my-1">
            <a href="<?php echo SITE_URL; ?>/logout.php" class="flex items-center gap-3 px-3 py-2.5 text-xs text-rose-500 hover:text-white hover:bg-rose-600 rounded-lg transition-colors font-bold">
                <i class="ph-bold ph-sign-out text-base"></i> Sign Out
            </a>
        </div>

        <div onclick="toggleProfileDropdown(event)" class="glass-panel p-3 rounded-xl mb-3 hover-lift cursor-pointer flex items-center gap-3 active:scale-95 transition-transform" title="Account Menu">
            <img src="<?php echo $sidebarPhoto; ?>" alt="User" class="w-9 h-9 rounded-full border border-gray-700 shrink-0 object-cover">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></p>
                <p class="text-[10px] text-cyan-400 font-medium tracking-wide uppercase truncate"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></p>
            </div>
            <i id="profile-dropdown-caret" class="ph-bold ph-caret-up text-gray-500 text-xs transition-transform duration-200"></i>
        </div>
        
        <div class="flex gap-2">
            <a href="<?php echo SITE_URL; ?>/settings.php" title="Settings" class="flex-1 flex justify-center items-center py-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition-colors">
                <i class="ph-fill ph-gear text-lg"></i>
            </a>
            <a href="<?php echo SITE_URL; ?>/logout.php" title="Logout" class="flex-1 flex justify-center items-center py-2 rounded-lg text-rose-500 hover:text-white hover:bg-rose-500 transition-colors">
                <i class="ph-bold ph-sign-out text-lg"></i>
            </a>
        </div>
    </div>
</aside>

<script>
function toggleProfileDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('profile-dropdown');
    const caret = document.getElementById('profile-dropdown-caret');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
        if (caret) {
            caret.classList.toggle('rotate-180');
        }
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', (event) => {
    const dropdown = document.getElementById('profile-dropdown');
    const caret = document.getElementById('profile-dropdown-caret');
    if (dropdown && !dropdown.classList.contains('hidden')) {
        // Only close if click is outside the dropdown itself
        if (!dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
            if (caret) {
                caret.classList.remove('rotate-180');
            }
        }
    }
});
</script>
