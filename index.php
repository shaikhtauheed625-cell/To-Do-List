<?php
$page_title = 'Dashboard';
$load_chartjs = true;
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch Stats
$stats = [
    'total' => 0,
    'completed' => 0,
    'in_progress' => 0,
    'todo' => 0
];

$stmt = null;
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE created_by = ? OR assigned_to = ? GROUP BY status");
    $stmt->execute([$user_id, $user_id]);
}
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}

// Compute dynamic Productivity Score
$prod_score = 0;
if ($stats['total'] > 0) {
    $prod_score = round(($stats['completed'] / $stats['total']) * 100);
} else {
    $prod_score = 100; // Empty baseline is a clean 100
}

$score_desc = 'Resting';
$score_color = 'text-cyan-400';
if ($prod_score >= 80) {
    $score_desc = 'Excellent';
    $score_color = 'text-cyan-400';
} elseif ($prod_score >= 50) {
    $score_desc = 'Good';
    $score_color = 'text-blue-400';
} elseif ($prod_score > 0) {
    $score_desc = 'Building';
    $score_color = 'text-amber-400';
}

$dashoffset = 351.8 * (1 - $prod_score / 100);

// Compute 7 Days completed tasks chart data
$days_data = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days"));
    $labels[] = $day_name;
    
    if (isAdmin()) {
        $cStmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed' AND DATE(updated_at) = ?");
        $cStmt->execute([$date]);
    } else {
        $cStmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed' AND DATE(updated_at) = ? AND (created_by = ? OR assigned_to = ?)");
        $cStmt->execute([$date, $user_id, $user_id]);
    }
    $days_data[] = intval($cStmt->fetch()['count']);
}

$has_real_chart_data = array_sum($days_data) > 0;
if (!$has_real_chart_data) {
    $chart_data = [42, 65, 55, 80, 65, 45, 90]; // Gorgeous default curve
} else {
    $chart_data = [];
    foreach ($days_data as $cnt) {
        $chart_data[] = min(100, $cnt * 20); // 20% weight per completed task
    }
}

// Fetch recent tasks with assignee and creator details
if (isAdmin()) {
    $recent_stmt = $pdo->prepare("SELECT t.*, 
                                         u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                         u_create.username as creator_name, u_create.phone as creator_phone
                                  FROM tasks t
                                  LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                                  LEFT JOIN users u_create ON t.created_by = u_create.id
                                  ORDER BY t.created_at DESC LIMIT 5");
    $recent_stmt->execute();
} else {
    $recent_stmt = $pdo->prepare("SELECT t.*, 
                                         u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                         u_create.username as creator_name, u_create.phone as creator_phone
                                  FROM tasks t
                                  LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                                  LEFT JOIN users u_create ON t.created_by = u_create.id
                                  WHERE t.created_by = ? OR t.assigned_to = ? 
                                  ORDER BY t.created_at DESC LIMIT 5");
    $recent_stmt->execute([$user_id, $user_id]);
}
$recent_tasks = $recent_stmt->fetchAll();

// Fetch overdue tasks for system alerts with assignee and creator details
if (isAdmin()) {
    $overdue_stmt = $pdo->prepare("SELECT t.*, 
                                         u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                         u_create.username as creator_name, u_create.phone as creator_phone
                                  FROM tasks t
                                  LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                                  LEFT JOIN users u_create ON t.created_by = u_create.id
                                  WHERE t.status != 'completed' AND t.due_date < NOW() 
                                  ORDER BY t.priority DESC LIMIT 2");
    $overdue_stmt->execute();
} else {
    $overdue_stmt = $pdo->prepare("SELECT t.*, 
                                         u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                         u_create.username as creator_name, u_create.phone as creator_phone
                                  FROM tasks t
                                  LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                                  LEFT JOIN users u_create ON t.created_by = u_create.id
                                  WHERE t.status != 'completed' AND t.due_date < NOW() AND (t.created_by = ? OR t.assigned_to = ?) 
                                  ORDER BY t.priority DESC LIMIT 2");
    $overdue_stmt->execute([$user_id, $user_id]);
}
$overdue_tasks = $overdue_stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] text-slate-800 dark:text-gray-200 transition-colors">
    <!-- Top Header -->
    <header class="h-20 bg-white dark:bg-[#0B1120] border-b border-gray-250 dark:border-gray-800/50 flex items-center justify-between px-4 sm:px-6 shrink-0 z-10 transition-colors">
        <div class="flex items-center gap-2 sm:gap-4 min-w-0">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors shrink-0">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <h1 class="text-base sm:text-xl font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent flex items-center gap-1.5 sm:gap-2 min-w-0">
                <i class="ph-fill ph-squares-four text-cyan-400 shrink-0"></i> <span class="truncate">Dashboard</span>
            </h1>
        </div>

        <div class="flex items-center gap-3 sm:gap-6 shrink-0">
            <!-- Global Search -->
            <div class="hidden md:flex relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="ph ph-magnifying-glass text-gray-400 group-focus-within:text-cyan-400 transition-colors"></i>
                </div>
                <input type="text" class="block w-64 pl-10 pr-3 py-2 border border-gray-200 dark:border-gray-700/50 rounded-xl bg-white dark:bg-gray-800/50 text-sm focus:ring-2 focus:ring-cyan-500 text-gray-800 dark:text-white transition-all focus:w-80 placeholder-gray-450 dark:placeholder-gray-500 glass-panel" placeholder="Search tasks, docs, / to focus...">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-[10px] bg-gray-150 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded border border-gray-250 dark:border-gray-600">Ctrl+K</span>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <!-- Notifications -->
                <button title="Notifications" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 hover:bg-gray-105 dark:text-gray-400 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-white transition-all relative hover-lift shrink-0">
                    <i class="ph ph-bell text-xl"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-pink-500 rounded-full shadow-[0_0_8px_rgba(236,72,153,0.8)]"></span>
                </button>
                <!-- Quick Add -->
                <a href="tasks.php" class="p-2.5 sm:px-4 sm:py-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white rounded-xl font-medium transition-all flex items-center gap-2 neon-glow-cyan hover-lift shrink-0" title="Quick Add">
                    <i class="ph-bold ph-plus text-sm"></i> <span class="hidden sm:inline">Quick Add</span>
                </a>
                <!-- User Profile -->
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-500 to-blue-500 p-0.5 cursor-pointer hover-lift shrink-0">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=1E293B&color=fff" class="w-full h-full rounded-full border-2 border-white dark:border-[#111827] object-cover">
                </div>
            </div>
        </div>
    </header>

    <!-- Scrollable Dashboard -->
    <div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-8 scroll-smooth">
        <div class="max-w-[1600px] mx-auto space-y-8">
            
            <!-- Welcome Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 animate-fade-in">
                <div>
                    <h2 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Good Morning, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                    <p class="text-gray-400 mt-2 flex items-center gap-2">
                        <i class="ph-fill ph-sparkle text-cyan-400"></i> AI predicts a highly productive day for you.
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 font-medium uppercase tracking-wider"><?php echo date('l, M jS'); ?></p>
                </div>
            </div>

            <!-- ROW 1: Metrics (Widgets 1-4) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 animate-fade-in" style="animation-delay: 0.1s;">
                <!-- Total -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
                    <p class="text-sm font-medium text-gray-400 mb-2">Total Tasks</p>
                    <h3 class="text-4xl font-bold text-slate-800 dark:text-white"><?php echo $stats['total']; ?></h3>
                </div>
                <!-- Completed -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
                    <p class="text-sm font-medium text-gray-400 mb-2">Completed</p>
                    <h3 class="text-4xl font-bold text-emerald-400"><?php echo $stats['completed']; ?></h3>
                </div>
                <!-- In Progress -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/20 rounded-full blur-2xl group-hover:bg-amber-500/30 transition-all"></div>
                    <p class="text-sm font-medium text-gray-400 mb-2">In Progress</p>
                    <h3 class="text-4xl font-bold text-amber-400"><?php echo $stats['in_progress']; ?></h3>
                </div>
                <!-- Pending -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
                    <p class="text-sm font-medium text-gray-400 mb-2">Pending / To Do</p>
                    <h3 class="text-4xl font-bold text-purple-400"><?php echo $stats['todo']; ?></h3>
                </div>
            </div>

            <!-- ROW 2: Charts & Timers (Widgets 5-7) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-fade-in" style="animation-delay: 0.2s;">
                
                <!-- Chart (Widget 5 & 6) -->
                <div class="lg:col-span-7 glass-panel rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-trend-up text-cyan-400"></i> Weekly Progress
                        </h3>
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-1 flex border border-gray-200 dark:border-transparent">
                            <button class="px-3 py-1 text-xs font-medium rounded-md bg-white dark:bg-gray-700 text-slate-800 dark:text-white shadow">Week</button>
                            <button class="px-3 py-1 text-xs font-medium rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white transition-colors">Month</button>
                        </div>
                    </div>
                    <div class="h-64 w-full relative">
                        <canvas id="productivityChart"></canvas>
                    </div>
                </div>

                <!-- Productivity Score (Widget 7) -->
                <div class="lg:col-span-2 glass-panel rounded-2xl p-6 flex flex-col items-center justify-center text-center hover-lift">
                    <h3 class="text-sm font-medium text-gray-400 mb-4">Productivity Score</h3>
                    <div class="relative w-32 h-32 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle cx="64" cy="64" r="56" stroke="rgba(0,0,0,0.05)" class="dark:stroke-white/5" stroke-width="12" fill="none" />
                            <circle cx="64" cy="64" r="56" stroke="url(#gradient)" stroke-width="12" fill="none" stroke-dasharray="351.8" stroke-dashoffset="<?php echo $dashoffset; ?>" stroke-linecap="round" class="transition-all duration-1000 ease-out drop-shadow-[0_0_10px_rgba(6,182,212,0.8)]" />
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#3B82F6" />
                                    <stop offset="100%" stop-color="#06B6D4" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute flex flex-col items-center">
                            <span class="text-3xl font-black text-slate-800 dark:text-white tracking-tighter"><?php echo $prod_score; ?></span>
                            <span class="text-[10px] <?php echo $score_color; ?> font-bold tracking-widest uppercase"><?php echo $score_desc; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Pomodoro Timer (Widget 10) -->
                <div class="lg:col-span-3 glass-panel rounded-2xl p-6 flex flex-col items-center justify-center relative overflow-hidden group">
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-rose-500/10 rounded-full blur-3xl"></div>
                    <h3 class="text-sm font-medium text-gray-400 mb-4 w-full text-left flex items-center justify-between">
                        <span class="flex items-center gap-2"><i class="ph-fill ph-timer text-rose-400"></i> Focus Session</span>
                        <button id="pomodoro-settings" class="text-gray-500 hover:text-gray-700 dark:hover:text-white transition-colors"><i class="ph-fill ph-gear"></i></button>
                    </h3>
                    <div id="pomodoro-display" class="text-5xl font-black tracking-tight text-slate-800 dark:text-white mb-6 tabular-nums dark:drop-shadow-[0_0_15px_rgba(244,63,94,0.3)]">
                        25:00
                    </div>
                    <div class="flex gap-4">
                        <button id="pomodoro-toggle" class="w-12 h-12 rounded-full bg-rose-500/20 text-rose-400 hover:bg-rose-500 hover:text-white flex items-center justify-center transition-all border border-rose-500/30 hover:shadow-[0_0_20px_rgba(244,63,94,0.5)]">
                            <i id="pomodoro-icon" class="ph-fill ph-play text-xl"></i>
                        </button>
                        <button id="pomodoro-reset" class="w-12 h-12 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white flex items-center justify-center transition-all border border-gray-200 dark:border-gray-700">
                            <i class="ph-bold ph-arrow-counter-clockwise text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ROW 3: Lists & Feeds (Widgets 8-10) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in" style="animation-delay: 0.3s;">
                
                <!-- Upcoming Deadlines -->
                <div class="glass-panel rounded-2xl p-6 flex flex-col h-96">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">Upcoming Deadlines</h3>
                        <div class="relative upcoming-deadlines-dropdown">
                            <button onclick="toggleDeadlinesMenu(event)" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/60" title="Deadlines Menu">
                                <i class="ph-bold ph-dots-three text-xl"></i>
                            </button>
                            <div id="deadlines-card-menu" class="hidden absolute right-0 mt-1.5 w-40 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-xl shadow-2xl z-30 py-1 overflow-hidden animate-fade-in glass-panel shrink-0">
                                <a href="tasks.php" class="w-full text-left px-4 py-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center gap-2">
                                    <i class="ph ph-kanban"></i> Go to Board
                                </a>
                                <a href="tasks.php" class="w-full text-left px-4 py-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center gap-2">
                                    <i class="ph ph-plus"></i> Add New Task
                                </a>
                                <button onclick="location.reload()" class="w-full text-left px-4 py-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center gap-2">
                                    <i class="ph ph-arrows-clockwise"></i> Refresh List
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                        <?php if(empty($recent_tasks)): ?>
                            <p class="text-sm text-gray-500 italic">No tasks found.</p>
                        <?php else: ?>
                            <?php foreach($recent_tasks as $task): ?>
                                <div id="dash-task-<?php echo $task['id']; ?>" class="bg-white dark:bg-gray-800/50 border border-gray-250 dark:border-gray-700/50 p-4 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all cursor-pointer group relative flex flex-col justify-between shadow-sm">
                                    <div class="flex justify-between items-start mb-2 pr-6">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors truncate max-w-[180px]" title="<?php echo htmlspecialchars($task['title']); ?>"><?php echo htmlspecialchars($task['title']); ?></p>
                                        <span class="w-2 h-2 rounded-full <?php echo $task['priority'] === 'urgent' ? 'bg-rose-500' : 'bg-blue-500'; ?> shadow-[0_0_5px_currentColor]"></span>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex items-center gap-3 text-[11px] font-medium text-gray-500 flex-wrap">
                                            <span class="flex items-center gap-1"><i class="ph-fill ph-clock"></i> <?php echo $task['due_date'] ? date('M d', strtotime($task['due_date'])) : 'Anytime'; ?></span>
                                            <span class="flex items-center gap-1"><i class="ph-fill ph-tag"></i> <?php echo ucfirst($task['priority'] ?? 'Medium'); ?></span>
                                            <?php if (!empty($task['recurrence']) && $task['recurrence'] !== 'none'): ?>
                                                <span class="flex items-center gap-1 text-purple-400 font-semibold" title="Recurring: <?php echo ucfirst($task['recurrence']); ?>">
                                                    <i class="ph-bold ph-arrows-clockwise animate-spin-slow"></i>
                                                    <?php echo ucfirst($task['recurrence']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Hover actions panel -->
                                        <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity absolute right-3 bottom-3 bg-gray-900/95 backdrop-blur-md px-2 py-1 rounded-lg border border-gray-700/60 shadow-lg shrink-0">
                                            <?php if($task['phone'] || $task['assignee_phone']): ?>
                                            <button onclick="event.stopPropagation(); window.open('https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $task['phone'] ? $task['phone'] : $task['assignee_phone']); ?>', '_blank')" class="w-6 h-6 rounded flex items-center justify-center hover:bg-emerald-500/20 text-emerald-400 transition-all" title="WhatsApp Follow-up">
                                                <i class="ph-fill ph-whatsapp-logo text-xs"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button onclick="event.stopPropagation(); deleteDashboardTask(<?php echo $task['id']; ?>, this)" class="w-6 h-6 rounded flex items-center justify-center hover:bg-rose-500/20 text-rose-400 transition-all" title="Delete Task">
                                                <i class="ph ph-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Team Activity -->
                <div class="glass-panel rounded-2xl p-6 flex flex-col h-96">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">Team Activity</h3>
                        <span class="px-2 py-0.5 bg-cyan-500/20 text-cyan-400 rounded text-[10px] font-bold">LIVE</span>
                    </div>
                    <div class="flex-1 overflow-y-auto pr-2 space-y-4 custom-scrollbar relative" id="team-activity-container">
                        <div class="absolute left-[15px] top-2 bottom-2 w-px bg-gray-250 dark:bg-gray-800 z-0"></div>
                        <div id="team-activity-list" class="space-y-4 relative z-10">
                            <p class="text-sm text-gray-500 italic text-center py-8">Loading activities...</p>
                        </div>
                    </div>
                </div>

                <!-- Dynamic System Alerts -->
                <div class="glass-panel rounded-2xl p-6 flex flex-col h-96 animate-fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">System Alerts</h3>
                        <i class="ph-fill ph-warning-circle text-rose-500 text-lg"></i>
                    </div>
                    <div class="flex-1 overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                        <?php if(!empty($overdue_tasks)): ?>
                            <?php foreach($overdue_tasks as $ot): ?>
                                <div class="bg-rose-500/10 border border-rose-500/20 p-3 rounded-xl flex gap-3 animate-pulse">
                                    <i class="ph-fill ph-warning text-rose-400 mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-medium text-rose-200">Overdue Task Alert</p>
                                        <p class="text-xs text-rose-400/80 mt-1">"<?php echo htmlspecialchars($ot['title']); ?>" is overdue! Due: <?php echo date('M d', strtotime($ot['due_date'])); ?>.</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="bg-blue-500/10 border border-blue-500/20 p-3 rounded-xl flex gap-3">
                            <i class="ph-fill ph-info text-blue-400 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-blue-200">Engine Core Status</p>
                                <p class="text-xs text-blue-400/80 mt-1">All modules nominal. Database latency is 14ms.</p>
                            </div>
                        </div>

                        <div class="bg-emerald-500/10 border border-emerald-500/20 p-3 rounded-xl flex gap-3">
                            <i class="ph-fill ph-shield-check text-emerald-400 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-emerald-200">Security Core</p>
                                <p class="text-xs text-emerald-400/80 mt-1">System firewall active. SSL Certificate active.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<script>
window.dashboardLabels = <?php echo json_encode($labels); ?>;
window.dashboardChartData = <?php echo json_encode($chart_data); ?>;
</script>

<style>
/* Custom Webkit Scrollbar for Panels */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 4px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(6,182,212,0.5); }
</style>

<?php 
$custom_js = <<<'EOT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('productivityChart').getContext('2d');
    
    // Create futuristic gradient for the line
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(6, 182, 212, 0.5)'); // Cyan top
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)'); // Blue bottom fade

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: window.dashboardLabels,
            datasets: [{
                label: 'Productivity',
                data: window.dashboardChartData,
                borderColor: '#06B6D4',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4, // smooth curves
                fill: true,
                pointBackgroundColor: '#0B1120',
                pointBorderColor: '#06B6D4',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#06B6D4',
                pointHoverBorderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 4,
                    usePointStyle: true,
                    titleFont: { family: 'Poppins', size: 13 },
                    bodyFont: { family: 'Poppins', size: 12 }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: '#64748b', font: { family: 'Poppins' } }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                    ticks: { color: '#64748b', font: { family: 'Poppins' }, stepSize: 20 },
                    min: 0,
                    max: 100
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });

    // Live Activity Polling
    const activityList = document.getElementById('team-activity-list');
    let lastActivityIds = new Set();

    async function pollActivities() {
        const response = await fetchAPI('api/activity.php');
        if (response.success && response.data) {
            const activities = response.data;
            if (activities.length === 0) {
                activityList.innerHTML = `<p class="text-sm text-gray-500 italic text-center py-8">No recent activities found.</p>`;
                return;
            }

            // Find new activities to animate
            const currentIds = new Set(activities.map(a => a.id));
            const isFirstLoad = lastActivityIds.size === 0;

            let html = '';
            activities.forEach((act, index) => {
                const isNew = !isFirstLoad && !lastActivityIds.has(act.id);
                
                // Choose icon and design styling based on action
                let iconClass = 'ph-fill ph-sparkle text-cyan-400';
                let bgClass = 'bg-cyan-500/10 border-cyan-500/20';
                if (act.action.toLowerCase().includes('complete')) {
                    iconClass = 'ph-fill ph-check-circle text-emerald-400';
                    bgClass = 'bg-emerald-500/10 border-emerald-500/20';
                } else if (act.action.toLowerCase().includes('delete')) {
                    iconClass = 'ph-fill ph-trash text-rose-400';
                    bgClass = 'bg-rose-500/10 border-rose-500/20';
                } else if (act.action.toLowerCase().includes('start')) {
                    iconClass = 'ph-fill ph-play-circle text-blue-400';
                    bgClass = 'bg-blue-500/10 border-blue-500/20';
                } else if (act.action.toLowerCase().includes('note')) {
                    iconClass = 'ph-fill ph-notebook text-yellow-400';
                    bgClass = 'bg-yellow-500/10 border-yellow-500/20';
                } else if (act.action.toLowerCase().includes('project')) {
                    iconClass = 'ph-fill ph-folder text-purple-400';
                    bgClass = 'bg-purple-500/10 border-purple-500/20';
                }

                const delay = isFirstLoad ? (index * 0.05) : 0;
                const animStyle = isFirstLoad 
                    ? `style="animation: fadeIn 0.4s ease-out ${delay}s forwards; opacity: 0; transform: translateY(10px);"`
                    : (isNew ? `style="animation: fadeIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;"` : '');

                html += `
                    <div class="flex gap-4 relative items-start ${isNew ? 'bg-cyan-500/5 p-2 rounded-xl border border-cyan-500/10' : ''}" ${animStyle}>
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 border ${bgClass}">
                            <i class="${iconClass} text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-600 dark:text-gray-300 truncate"><strong class="text-gray-900 dark:text-white font-medium">${act.username}</strong> ${act.action} <span class="text-cyan-600 dark:text-cyan-400">${act.details}</span></p>
                            <p class="text-[11px] text-gray-500 mt-0.5">${act.time_ago}</p>
                        </div>
                    </div>
                `;
            });

            activityList.innerHTML = html;
            lastActivityIds = currentIds;
        }
    }

    pollActivities();
    setInterval(pollActivities, 5000);
});

// Pomodoro Timer Logic
let pomodoroInterval;
let defaultPomodoroTime = 25 * 60; // Default 25 minutes
let pomodoroTimeLeft = defaultPomodoroTime;
let isPomodoroRunning = false;

document.getElementById('pomodoro-toggle').addEventListener('click', function() {
    const icon = document.getElementById('pomodoro-icon');
    if (isPomodoroRunning) {
        clearInterval(pomodoroInterval);
        isPomodoroRunning = false;
        icon.classList.replace('ph-pause', 'ph-play');
    } else {
        isPomodoroRunning = true;
        icon.classList.replace('ph-play', 'ph-pause');
        pomodoroInterval = setInterval(() => {
            if (pomodoroTimeLeft > 0) {
                pomodoroTimeLeft--;
                updatePomodoroDisplay();
            } else {
                clearInterval(pomodoroInterval);
                isPomodoroRunning = false;
                icon.classList.replace('ph-pause', 'ph-play');
                pomodoroTimeLeft = defaultPomodoroTime;
                updatePomodoroDisplay();
                alert('Focus session complete! Take a break.');
            }
        }, 1000);
    }
});

document.getElementById('pomodoro-reset').addEventListener('click', function() {
    clearInterval(pomodoroInterval);
    isPomodoroRunning = false;
    document.getElementById('pomodoro-icon').classList.replace('ph-pause', 'ph-play');
    pomodoroTimeLeft = defaultPomodoroTime;
    updatePomodoroDisplay();
});

document.getElementById('pomodoro-settings').addEventListener('click', function() {
    const newTime = prompt('Enter focus time in minutes:', Math.floor(defaultPomodoroTime / 60));
    if (newTime !== null && !isNaN(newTime) && newTime > 0) {
        defaultPomodoroTime = parseInt(newTime) * 60;
        if (!isPomodoroRunning) {
            pomodoroTimeLeft = defaultPomodoroTime;
            updatePomodoroDisplay();
        }
    }
});

function updatePomodoroDisplay() {
    const minutes = Math.floor(pomodoroTimeLeft / 60);
    const seconds = pomodoroTimeLeft % 60;
    document.getElementById('pomodoro-display').innerText = 
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

// Toggle upcoming deadlines card menu dropdown
function toggleDeadlinesMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('deadlines-card-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Delete task directly from dashboard view
async function deleteDashboardTask(id, button) {
    if (confirm('Are you sure you want to permanently delete this task?\nThis will remove it from all boards and calendar workspaces.')) {
        const result = await fetchAPI('api/tasks.php', 'DELETE', { id: id });
        if (result.success) {
            const taskCard = document.getElementById(`dash-task-${id}`);
            if (taskCard) {
                taskCard.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    taskCard.remove();
                    // If no more tasks, show baseline message
                    const container = document.querySelector('.glass-panel:nth-child(1) .overflow-y-auto');
                    if (container && container.querySelectorAll('[id^="dash-task-"]').length === 0) {
                        container.innerHTML = '<p class="text-sm text-gray-500 italic py-6 text-center">No tasks found. Create your first task!</p>';
                    }
                }, 300);
            }
        } else {
            alert('Error deleting task: ' + result.message);
        }
    }
}

// Close deadlines card menu dropdown when clicking outside
document.addEventListener('click', (event) => {
    const menu = document.getElementById('deadlines-card-menu');
    if (menu && !menu.classList.contains('hidden')) {
        if (!event.target.closest('.upcoming-deadlines-dropdown')) {
            menu.classList.add('hidden');
        }
    }
});
</script>
EOT;
include 'includes/footer.php'; 
?>
