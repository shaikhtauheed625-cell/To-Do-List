<?php
$page_title = 'Calendar';
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch all workspace users for assignee selection dropdowns
$usersStmt = $pdo->query("SELECT id, username, profile_image, phone FROM users ORDER BY username ASC");
$users = $usersStmt->fetchAll();

// Fetch all user's projects for project selection & project filtering dropdowns
if (isAdmin()) {
    $projectsStmt = $pdo->prepare("SELECT id, name, color FROM projects ORDER BY name ASC");
    $projectsStmt->execute();
} else {
    $projectsStmt = $pdo->prepare("SELECT id, name, color FROM projects WHERE created_by = ? ORDER BY name ASC");
    $projectsStmt->execute([$user_id]);
}
$projects = $projectsStmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] transition-colors">
    <header class="h-20 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-6 shrink-0 transition-colors z-10">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white tracking-tight flex items-center gap-2">
                <i class="ph ph-calendar-blank text-blue-600 dark:text-blue-500"></i> Workspace Calendar
            </h1>
        </div>
        
        <div class="flex items-center gap-4">
            <button onclick="toggleFilterSidebar()" class="lg:hidden w-10 h-10 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-800 transition-colors" title="Toggle Filters">
                <i class="ph ph-sliders text-xl"></i>
            </button>
            <button id="theme-toggle" title="Toggle Dark/Light Mode" class="w-10 h-10 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-800 transition-colors">
                <i class="ph ph-moon text-xl dark:hidden"></i>
                <i class="ph ph-sun text-xl hidden dark:block"></i>
            </button>
            <button onclick="openTaskModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-all flex items-center gap-2 shadow-md hover:shadow-lg text-sm shrink-0">
                <i class="ph ph-plus"></i> New Event
            </button>
        </div>
    </header>

    <!-- Calendar Workspace Split Layout -->
    <div class="flex-1 flex overflow-hidden relative">
        <!-- Collapsible Left Sidebar for Filters -->
        <aside id="calendar-sidebar" class="w-72 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 p-6 flex flex-col gap-6 overflow-y-auto shrink-0 transition-all duration-300 lg:translate-x-0 lg:static absolute inset-y-0 left-0 -translate-x-full z-20 custom-scrollbar shadow-lg lg:shadow-none">
            <!-- Search bar -->
            <div>
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Search Event</label>
                <div class="relative">
                    <input type="text" id="search-filter" oninput="applyFilters()" placeholder="Search tasks..." class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-800 rounded-lg text-xs bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-base"></i>
                </div>
            </div>

            <!-- View Toggle -->
            <div>
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">View Mode</label>
                <div class="grid grid-cols-2 gap-1 p-1 bg-gray-100 dark:bg-gray-950 rounded-lg border border-gray-200/50 dark:border-gray-800/40">
                    <button id="view-all-btn" onclick="setViewMode('all')" class="py-1.5 text-[11px] font-bold rounded-md transition-all bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-sm">All Tasks</button>
                    <button id="view-mine-btn" onclick="setViewMode('mine')" class="py-1.5 text-[11px] font-bold rounded-md transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white">Assigned Me</button>
                </div>
            </div>

            <!-- Project Filters -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Projects</label>
                    <button onclick="toggleAllProjects(true)" class="text-[10px] text-blue-600 dark:text-blue-400 font-bold hover:underline">Select All</button>
                </div>
                <div class="space-y-1.5 max-h-44 overflow-y-auto custom-scrollbar pr-1" id="project-filters-container">
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="project-filter" value="" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500 bg-transparent">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 bg-gray-400 dark:bg-gray-600"></span>
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium truncate group-hover:text-gray-900 dark:group-hover:text-white">Personal / No Project</span>
                    </label>
                    <?php foreach ($projects as $proj): ?>
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="project-filter" value="<?php echo $proj['id']; ?>" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500 bg-transparent">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: <?php echo $proj['color']; ?>"></span>
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium truncate group-hover:text-gray-900 dark:group-hover:text-white"><?php echo htmlspecialchars($proj['name']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Priority Filters -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Priority</label>
                    <button onclick="toggleAllPriorities(true)" class="text-[10px] text-blue-600 dark:text-blue-400 font-bold hover:underline">Select All</button>
                </div>
                <div class="space-y-1.5">
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="priority-filter" value="low" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-gray-500 focus:ring-gray-500 bg-transparent">
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-white flex items-center gap-1.5"><i class="ph-bold ph-flag text-gray-400 text-xs"></i> Low</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="priority-filter" value="medium" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500 bg-transparent">
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-white flex items-center gap-1.5"><i class="ph-bold ph-flag text-blue-500 text-xs"></i> Medium</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="priority-filter" value="high" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-amber-600 focus:ring-amber-500 bg-transparent">
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-white flex items-center gap-1.5"><i class="ph-bold ph-flag text-amber-500 text-xs"></i> High</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="priority-filter" value="urgent" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-rose-600 focus:ring-rose-500 bg-transparent">
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-white flex items-center gap-1.5"><i class="ph-bold ph-flag text-rose-500 text-xs animate-pulse"></i> Urgent</span>
                    </label>
                </div>
            </div>

            <!-- Status Filters -->
            <div>
                <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">Status</label>
                <div class="space-y-1.5">
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="status-filter" value="todo" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-gray-500 focus:ring-gray-500 bg-transparent">
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-white flex items-center gap-1.5"><i class="ph-bold ph-circle text-gray-400 text-xs"></i> To Do</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="status-filter" value="in_progress" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-yellow-500 focus:ring-yellow-500 bg-transparent">
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-white flex items-center gap-1.5"><i class="ph-bold ph-hourglass text-yellow-500 text-xs"></i> In Progress</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer py-1 px-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800/40 group">
                        <input type="checkbox" name="status-filter" value="completed" checked onchange="applyFilters()" class="rounded border-gray-300 dark:border-gray-700 text-emerald-500 focus:ring-emerald-500 bg-transparent">
                        <span class="text-xs text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-white flex items-center gap-1.5"><i class="ph-bold ph-check-circle text-emerald-500 text-xs"></i> Completed</span>
                    </label>
                </div>
            </div>
        </aside>

        <!-- Sidebar Overlay on Mobile -->
        <div id="sidebar-overlay" onclick="toggleFilterSidebar()" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-15 hidden"></div>

        <!-- Right Side Calendar Wrapper -->
        <div class="flex-1 overflow-hidden p-6 flex flex-col relative">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl border border-gray-200 dark:border-gray-800/80 flex-1 overflow-hidden transition-colors flex flex-col relative shadow-sm">
                <div id='calendar' class="text-gray-800 dark:text-gray-200 flex-1 overflow-hidden fc-premium"></div>
            </div>
        </div>
    </div>
</main>

<!-- Create Event Modal Overlay -->
<div id="taskModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[90vh]">
        <!-- Fixed Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Event</h3>
            <button onclick="closeTaskModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <!-- Scrollable Form Body -->
        <form id="createTaskForm" onsubmit="handleCreateTask(event)" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Event Title *</label>
                    <input type="text" id="taskTitle" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm" placeholder="What is the event?">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea id="taskDesc" rows="3" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm" placeholder="Add some details..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Project</label>
                        <select id="taskProject" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="">Personal (No Project)</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo $proj['id']; ?>"><?php echo htmlspecialchars($proj['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Assignee</label>
                        <select id="taskAssignee" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Priority</label>
                        <select id="taskPriority" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Due Date *</label>
                        <input type="date" id="taskDueDate" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Phone (for WhatsApp)</label>
                        <button type="button" onclick="openContactSelector('taskPhone')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 font-bold bg-transparent border-none outline-none">
                            <i class="ph ph-address-book text-sm"></i> Search Directory
                        </button>
                    </div>
                    <input type="text" id="taskPhone" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm" placeholder="e.g. +1234567890">
                </div>
            </div>
            <!-- Fixed Footer -->
            <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 shrink-0 bg-gray-50 dark:bg-gray-900/60">
                <button type="button" onclick="closeTaskModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-semibold text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-semibold text-sm shadow-sm">Save Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Event Modal Overlay -->
<div id="editTaskModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Event Details</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <!-- Scrollable Form Body -->
        <form id="editTaskForm" onsubmit="handleEditTask(event)" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" id="edit-task-id">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Event Title *</label>
                    <input type="text" id="edit-task-title" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm" placeholder="What is the event?">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea id="edit-task-desc" rows="3" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm" placeholder="Add some details..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Project</label>
                        <select id="edit-task-project" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="">Personal (No Project)</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo $proj['id']; ?>"><?php echo htmlspecialchars($proj['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Assignee</label>
                        <select id="edit-task-assignee" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Priority</label>
                        <select id="edit-task-priority" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Due Date *</label>
                        <input type="date" id="edit-task-due-date" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Phone (for WhatsApp)</label>
                        <button type="button" onclick="openContactSelector('edit-task-phone')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 font-bold bg-transparent border-none outline-none">
                            <i class="ph ph-address-book text-sm"></i> Search Directory
                        </button>
                    </div>
                    <input type="text" id="edit-task-phone" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm" placeholder="e.g. +1234567890">
                </div>
            </div>
            <!-- Fixed Footer -->
            <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 shrink-0 bg-gray-50 dark:bg-gray-900/60">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-semibold text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-semibold text-sm shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Event Details Modal Overlay -->
<div id="eventDetailsModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[85vh]">
        <!-- Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full shrink-0" id="detail-project-color"></span>
                <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider" id="detail-project-name">Project Name</h3>
            </div>
            <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        
        <!-- Content Body -->
        <div class="p-6 overflow-y-auto space-y-5 custom-scrollbar flex-1">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white" id="detail-title">Task Title</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed whitespace-pre-line" id="detail-desc">Task description goes here...</p>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-1">
                <div class="bg-gray-50 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-100 dark:border-gray-800/60">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Priority</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-850 dark:text-white" id="detail-priority-badge">
                        <i class="ph ph-flag"></i> Medium
                    </span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-100 dark:border-gray-800/60">
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Due Date</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-850 dark:text-white">
                        <i class="ph ph-calendar text-blue-500"></i> <span id="detail-due-date">May 27, 2026</span>
                    </span>
                </div>
            </div>

            <!-- Status control -->
            <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-xl border border-gray-100 dark:border-gray-800/60 animate-fade-in">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2.5">Update Status</span>
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="updateEventStatus('todo')" id="status-btn-todo" class="py-1.5 px-2 rounded-lg text-[10px] font-bold transition-all border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-450 hover:border-gray-300">To Do</button>
                    <button onclick="updateEventStatus('in_progress')" id="status-btn-in_progress" class="py-1.5 px-2 rounded-lg text-[10px] font-bold transition-all border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-yellow-600 dark:text-yellow-500 hover:border-yellow-300">In Progress</button>
                    <button onclick="updateEventStatus('completed')" id="status-btn-completed" class="py-1.5 px-2 rounded-lg text-[10px] font-bold transition-all border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-emerald-600 dark:text-emerald-500 hover:border-emerald-300">Completed</button>
                </div>
            </div>

            <!-- Assignee and Creator info -->
            <div class="flex flex-col gap-3.5 pt-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Assignee</span>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full border border-gray-200 dark:border-gray-800 p-0.5" id="detail-assignee-avatar-bg">
                            <img class="w-full h-full rounded-full" id="detail-assignee-avatar" src="https://ui-avatars.com/api/?name=U&background=2563eb&color=fff" alt="Assignee">
                        </div>
                        <span class="font-bold text-gray-800 dark:text-white" id="detail-assignee-name">Unassigned</span>
                    </div>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Creator</span>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full border border-gray-200 dark:border-gray-800 p-0.5" id="detail-creator-avatar-bg">
                            <img class="w-full h-full rounded-full" id="detail-creator-avatar" src="https://ui-avatars.com/api/?name=C&background=111827&color=fff" alt="Creator">
                        </div>
                        <span class="font-bold text-gray-800 dark:text-white" id="detail-creator-name">Admin</span>
                    </div>
                </div>
            </div>

            <!-- WhatsApp follow-up action -->
            <div id="detail-whatsapp-action-container" class="hidden pt-2">
                <button onclick="triggerWhatsAppModal()" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-all font-bold text-xs flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                    <i class="ph-fill ph-whatsapp-logo text-lg"></i> Send WhatsApp Follow-up
                </button>
            </div>
        </div>

        <!-- Footer actions -->
        <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-between gap-3 shrink-0 bg-gray-50 dark:bg-gray-900/60">
            <button onclick="triggerDeleteTask()" class="px-3.5 py-2 border border-rose-200 hover:bg-rose-50 text-rose-600 hover:text-rose-700 dark:border-rose-950/40 dark:hover:bg-rose-950/20 rounded-lg transition-colors font-bold text-xs flex items-center gap-1.5">
                <i class="ph ph-trash"></i> Delete
            </button>
            <div class="flex gap-2">
                <button onclick="closeDetailsModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-bold text-xs">Close</button>
                <button onclick="triggerEditModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-bold text-xs flex items-center gap-1.5 shadow-sm">
                    <i class="ph ph-pencil"></i> Edit Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- WhatsApp Follow-up Modal Overlay -->
<div id="whatsappModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-2 sm:p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[94vh] sm:max-h-[90vh]">
        <!-- Fixed Header -->
        <div class="flex items-center justify-between p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-whatsapp-logo text-emerald-500 text-2xl"></i> WhatsApp Follow-up
            </h3>
            <button onclick="closeWhatsAppModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        
        <!-- Scrollable Body -->
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-5 overflow-y-auto flex-1 custom-scrollbar">
            <!-- Recipient Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipient Contact</label>
                <div class="grid grid-cols-1 gap-2" id="recipientOptions">
                    <!-- Dynamic Radio Buttons go here -->
                </div>
            </div>

            <!-- Custom Phone Number -->
            <div id="customPhoneContainer" class="hidden">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">Custom WhatsApp Phone Number</label>
                <input type="text" id="waCustomPhone" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs outline-none" placeholder="e.g. +1234567890">
                <p class="text-[10px] text-gray-400 mt-1">Include country code (e.g. +15551234567) without spaces or hyphens.</p>
            </div>

            <!-- Predefined Templates -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Follow-up Template</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button onclick="applyTemplate('status')" class="text-left px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-500/10 hover:border-emerald-500/20 hover:text-emerald-500 text-xs text-gray-700 dark:text-gray-300 transition-all font-medium">
                        💬 <strong>Status:</strong> Update status...
                    </button>
                    <button onclick="applyTemplate('friendly')" class="text-left px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-500/10 hover:border-emerald-500/20 hover:text-emerald-500 text-xs text-gray-700 dark:text-gray-300 transition-all font-medium">
                        👋 <strong>Friendly:</strong> Just checking...
                    </button>
                    <button onclick="applyTemplate('urgent')" class="text-left px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-500/10 hover:border-emerald-500/20 hover:text-emerald-500 text-xs text-gray-700 dark:text-gray-300 transition-all font-medium">
                        🚨 <strong>Urgent:</strong> Needs attention!
                    </button>
                    <button onclick="applyTemplate('custom')" class="text-left px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-500/10 hover:border-emerald-500/20 hover:text-emerald-500 text-xs text-gray-700 dark:text-gray-300 transition-all font-medium">
                        ✍️ <strong>Custom Message</strong>
                    </button>
                </div>
            </div>

            <!-- Message Preview/Edit -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Message Content</label>
                <textarea id="waMessageText" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs outline-none" rows="2" sm:rows="3" placeholder="Type your follow-up message..."></textarea>
            </div>
        </div>

        <!-- Fixed Footer -->
        <div class="p-4 sm:p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 shrink-0 bg-gray-50 dark:bg-gray-900/60">
            <button type="button" onclick="closeWhatsAppModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-medium text-sm">Cancel</button>
            <button onclick="sendWhatsAppMessage()" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white rounded-lg transition-all font-semibold flex items-center gap-2 shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:scale-[1.02] text-sm">
                <i class="ph-bold ph-whatsapp-logo text-lg"></i> Send via WhatsApp
            </button>
        </div>
    </div>
</div>

<!-- Contacts Selector Modal Overlay -->
<div id="contactSelectorModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[85vh]">
        <!-- Fixed Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="ph ph-address-book text-blue-600 dark:text-blue-500 text-2xl"></i> Select Contact
            </h3>
            <button onclick="closeContactSelector()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        
        <!-- Search bar -->
        <div class="p-4 border-b border-gray-100 dark:border-gray-800/80 shrink-0 bg-gray-50 dark:bg-gray-950/40">
            <div class="relative">
                <input type="text" id="contactSearchInput" oninput="filterContactsList()" placeholder="Search by name or number..." class="w-full pl-9 pr-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-xs bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-base"></i>
            </div>
        </div>

        <!-- Scrollable Contacts List -->
        <div class="p-4 overflow-y-auto flex-1 custom-scrollbar space-y-3" id="contactsSelectorList">
            <p class="text-xs text-gray-555 italic text-center py-4">Loading directory...</p>
        </div>

        <!-- Directory Quick Add Expandable -->
        <div class="border-t border-gray-200 dark:border-gray-800 shrink-0 bg-gray-50 dark:bg-gray-900/60 p-4">
            <button type="button" onclick="toggleQuickAddContactForm()" class="w-full text-left flex items-center justify-between text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline mb-2">
                <span>➕ Add new client to Directory</span>
                <i class="ph ph-caret-down transition-transform" id="quickAddCaret"></i>
            </button>
            <form id="quickAddContactForm" onsubmit="handleQuickAddContact(event)" class="hidden space-y-2 mt-2">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" id="qaContactName" required placeholder="Name *" class="w-full px-2 py-1.5 border border-gray-200 dark:border-gray-700 rounded-md text-[11px] bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    <input type="text" id="qaContactPhone" required placeholder="Phone *" class="w-full px-2 py-1.5 border border-gray-200 dark:border-gray-700 rounded-md text-[11px] bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="email" id="qaContactEmail" placeholder="Email (optional)" class="w-full px-2 py-1.5 border border-gray-200 dark:border-gray-700 rounded-md text-[11px] bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                    <input type="text" id="qaContactCompany" placeholder="Company (optional)" class="w-full px-2 py-1.5 border border-gray-200 dark:border-gray-700 rounded-md text-[11px] bg-white dark:bg-gray-800 text-gray-900 dark:text-white outline-none">
                </div>
                <button type="submit" class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-[11px] font-bold transition-all shadow-sm">Save & Select Contact</button>
            </form>
        </div>
    </div>
</div>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<script>
    let calendar;
    let allTasks = [];
    let filteredEventsList = [];
    let activeTask = null;
    let currentViewMode = 'all'; // 'all' or 'mine'
    const loggedInUserId = <?php echo $_SESSION['user_id']; ?>;

    // Toast System
    function showToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed bottom-5 right-5 z-[60] flex flex-col gap-3';
            document.body.appendChild(container);
        }

        let toast = document.createElement('div');
        toast.className = `px-4.5 py-3 rounded-xl shadow-xl flex items-center gap-2.5 animate-slide-in border transition-all duration-300 bg-white dark:bg-gray-900 text-sm font-semibold text-gray-800 dark:text-white border-gray-200 dark:border-gray-800`;
        
        let icon = document.createElement('i');
        if (type === 'success') {
            icon.className = 'ph ph-check-circle text-emerald-500 text-lg';
            toast.style.boxShadow = '0 10px 30px -10px rgba(16, 185, 129, 0.15)';
        } else {
            icon.className = 'ph ph-warning-circle text-rose-500 text-lg';
            toast.style.boxShadow = '0 10px 30px -10px rgba(239, 68, 68, 0.15)';
        }

        toast.appendChild(icon);
        
        let textSpan = document.createElement('span');
        textSpan.innerText = message;
        toast.appendChild(textSpan);

        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Toggle Mobile Filter Sidebar
    function toggleFilterSidebar() {
        const sidebar = document.getElementById('calendar-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    // Toggle checked filters helper
    function toggleAllProjects(checked) {
        document.querySelectorAll('input[name="project-filter"]').forEach(checkbox => {
            checkbox.checked = checked;
        });
        applyFilters();
    }
    
    function toggleAllPriorities(checked) {
        document.querySelectorAll('input[name="priority-filter"]').forEach(checkbox => {
            checkbox.checked = checked;
        });
        applyFilters();
    }

    // View toggle helper
    function setViewMode(mode) {
        currentViewMode = mode;
        const allBtn = document.getElementById('view-all-btn');
        const mineBtn = document.getElementById('view-mine-btn');
        
        if (mode === 'all') {
            allBtn.className = 'py-1.5 text-[11px] font-bold rounded-md transition-all bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-sm';
            mineBtn.className = 'py-1.5 text-[11px] font-bold rounded-md transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white';
        } else {
            mineBtn.className = 'py-1.5 text-[11px] font-bold rounded-md transition-all bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-sm';
            allBtn.className = 'py-1.5 text-[11px] font-bold rounded-md transition-all text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white';
        }
        applyFilters();
    }

    async function fetchAndRenderTasks() {
        const response = await fetchAPI('api/tasks.php', 'GET');
        if (response.success && response.data) {
            allTasks = response.data.filter(t => t.due_date);
            applyFilters();
        } else {
            showToast('Failed to load events from server.', 'error');
        }
    }

    function applyFilters() {
        const searchQuery = document.getElementById('search-filter').value.toLowerCase().trim();
        const checkedProjects = Array.from(document.querySelectorAll('input[name="project-filter"]:checked')).map(el => el.value);
        const checkedPriorities = Array.from(document.querySelectorAll('input[name="priority-filter"]:checked')).map(el => el.value);
        const checkedStatuses = Array.from(document.querySelectorAll('input[name="status-filter"]:checked')).map(el => el.value);

        filteredEventsList = allTasks.filter(t => {
            const matchSearch = t.title.toLowerCase().includes(searchQuery) || (t.description && t.description.toLowerCase().includes(searchQuery));
            const matchViewMode = currentViewMode === 'all' || parseInt(t.assigned_to) === loggedInUserId;
            const matchProject = checkedProjects.includes(t.project_id ? String(t.project_id) : "");
            const matchPriority = checkedPriorities.includes(t.priority || "medium");
            const matchStatus = checkedStatuses.includes(t.status || "todo");

            return matchSearch && matchViewMode && matchProject && matchPriority && matchStatus;
        }).map(t => ({
            id: t.id,
            title: t.title,
            start: t.due_date,
            allDay: true,
            // FullCalendar fallbacks
            backgroundColor: t.status === 'completed' ? '#10B981' : (t.project_color || '#2563EB'),
            borderColor: t.status === 'completed' ? '#10B981' : (t.project_color || '#2563EB'),
            extendedProps: {
                description: t.description,
                status: t.status,
                priority: t.priority,
                assigned_to: t.assigned_to,
                assignee_name: t.assignee_name,
                assignee_phone: t.assignee_phone,
                creator_name: t.creator_name,
                creator_phone: t.creator_phone,
                project_id: t.project_id,
                project_name: t.project_name,
                project_color: t.project_color,
                task_phone: t.phone
            }
        }));

        if (calendar) {
            calendar.refetchEvents();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: true,
            events: function(info, successCallback, failureCallback) {
                successCallback(filteredEventsList);
            },
            height: '100%',
            dateClick: function(info) {
                openTaskModal(info.dateStr);
            },
            eventClick: function(info) {
                openDetailsModal(info.event);
            },
            eventDrop: async function(info) {
                const eventId = info.event.id;
                const newDate = info.event.startStr;
                
                const result = await fetchAPI('api/tasks.php', 'PUT', {
                    id: eventId,
                    due_date: newDate
                });

                if (result.success) {
                    showToast(`Event "${info.event.title}" rescheduled to ${newDate} successfully!`, 'success');
                    const task = allTasks.find(t => String(t.id) === String(eventId));
                    if (task) task.due_date = newDate;
                    applyFilters();
                } else {
                    info.revert();
                    showToast('Failed to reschedule: ' + result.message, 'error');
                }
            },
            eventResize: async function(info) {
                const eventId = info.event.id;
                const newDate = info.event.startStr;
                
                const result = await fetchAPI('api/tasks.php', 'PUT', {
                    id: eventId,
                    due_date: newDate
                });

                if (result.success) {
                    showToast(`Event duration updated!`, 'success');
                    const task = allTasks.find(t => String(t.id) === String(eventId));
                    if (task) task.due_date = newDate;
                    applyFilters();
                } else {
                    info.revert();
                    showToast('Failed to update event duration: ' + result.message, 'error');
                }
            },
            eventContent: function(arg) {
                let title = arg.event.title;
                let isCompleted = arg.event.extendedProps.status === 'completed';
                let priority = arg.event.extendedProps.priority;
                let projColor = arg.event.extendedProps.project_color || '#3b82f6';
                let assigneeName = arg.event.extendedProps.assignee_name;

                let el = document.createElement('div');
                el.className = `fc-custom-event p-1 rounded-lg flex flex-col justify-between h-full border-l-4 shadow-xs transition-all duration-300 ${isCompleted ? 'opacity-60 line-through' : ''}`;
                el.style.borderLeftColor = projColor;
                el.style.background = `linear-gradient(135deg, ${projColor}12, ${projColor}05)`;

                let topRow = document.createElement('div');
                topRow.className = 'flex items-center justify-between gap-1';

                let titleEl = document.createElement('span');
                titleEl.className = 'text-[10px] font-bold truncate text-gray-800 dark:text-gray-200';
                titleEl.innerText = title;
                
                let statusIcon = document.createElement('i');
                if (isCompleted) {
                    statusIcon.className = 'ph ph-check-circle text-emerald-500 text-xs shrink-0';
                } else {
                    let flagColor = 'text-gray-400';
                    if (priority === 'urgent') flagColor = 'text-rose-500 animate-pulse';
                    else if (priority === 'high') flagColor = 'text-amber-500';
                    else if (priority === 'medium') flagColor = 'text-blue-500';
                    statusIcon.className = `ph ph-flag ${flagColor} text-[9px] shrink-0`;
                }

                topRow.appendChild(titleEl);
                topRow.appendChild(statusIcon);
                el.appendChild(topRow);

                let bottomRow = document.createElement('div');
                bottomRow.className = 'flex items-center justify-between text-[8px] text-gray-400 font-bold mt-1';

                let projEl = document.createElement('span');
                projEl.className = 'truncate max-w-[80px] opacity-80';
                projEl.innerText = arg.event.extendedProps.project_name || 'Personal';
                projEl.style.color = projColor;

                bottomRow.appendChild(projEl);

                if (assigneeName) {
                    let assignEl = document.createElement('span');
                    assignEl.className = 'shrink-0 px-1 py-0.2 bg-gray-100 dark:bg-gray-850 rounded text-[7px] text-gray-550 dark:text-gray-300 truncate max-w-[50px] border border-gray-200 dark:border-gray-800/40';
                    assignEl.innerText = assigneeName;
                    bottomRow.appendChild(assignEl);
                }

                el.appendChild(bottomRow);
                return { domNodes: [el] };
            }
        });
        
        calendar.render();
        fetchAndRenderTasks();

        // Hook trigger calendar resize when sidebar toggles
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            setTimeout(() => calendar.updateSize(), 300);
        });
    });

    // Create Modal controls
    const taskModal = document.getElementById('taskModal');
    function openTaskModal(dateStr = '') { 
        document.getElementById('createTaskForm').reset();
        if (dateStr) {
            document.getElementById('taskDueDate').value = dateStr;
        }
        taskModal.classList.remove('hidden'); 
    }
    function closeTaskModal() { taskModal.classList.add('hidden'); }

    // Create Event handler
    async function handleCreateTask(e) {
        e.preventDefault();
        const title = document.getElementById('taskTitle').value;
        const desc = document.getElementById('taskDesc').value;
        const projectId = document.getElementById('taskProject').value;
        const assigneeId = document.getElementById('taskAssignee').value;
        const priority = document.getElementById('taskPriority').value;
        const dueDate = document.getElementById('taskDueDate').value;
        const phone = document.getElementById('taskPhone').value;

        const result = await fetchAPI('api/tasks.php', 'POST', {
            title: title,
            description: desc,
            project_id: projectId,
            assigned_to: assigneeId,
            priority: priority,
            due_date: dueDate,
            phone: phone
        });

        if (result.success) {
            closeTaskModal();
            showToast('Event created successfully!', 'success');
            fetchAndRenderTasks();
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    }

    // Event Details Modal Controls
    const eventDetailsModal = document.getElementById('eventDetailsModal');
    function openDetailsModal(event) {
        activeTask = {
            id: event.id,
            title: event.title,
            due_date: event.startStr,
            ...event.extendedProps
        };

        // Populate detail elements
        document.getElementById('detail-title').innerText = activeTask.title;
        document.getElementById('detail-desc').innerText = activeTask.description || 'No description provided.';
        document.getElementById('detail-due-date').innerText = new Date(activeTask.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        
        // Priority
        const priorityBadge = document.getElementById('detail-priority-badge');
        let priorityColorClass = 'text-gray-500 bg-gray-100 dark:bg-gray-850';
        let priorityIconClass = 'text-gray-400';
        if (activeTask.priority === 'urgent') {
            priorityColorClass = 'text-rose-600';
            priorityIconClass = 'text-rose-500';
        } else if (activeTask.priority === 'high') {
            priorityColorClass = 'text-amber-600';
            priorityIconClass = 'text-amber-500';
        } else if (activeTask.priority === 'medium') {
            priorityColorClass = 'text-blue-600';
            priorityIconClass = 'text-blue-500';
        }
        priorityBadge.innerHTML = `<i class="ph ph-flag ${priorityIconClass}"></i> ${activeTask.priority.toUpperCase()}`;
        priorityBadge.className = `inline-flex items-center gap-1.5 text-xs font-bold ${priorityColorClass}`;

        // Project Color Dot and Name
        const pColor = activeTask.project_color || '#cbd5e1';
        document.getElementById('detail-project-color').style.backgroundColor = pColor;
        document.getElementById('detail-project-name').innerText = activeTask.project_name || 'Personal';

        // Set active status button
        ['todo', 'in_progress', 'completed'].forEach(s => {
            const btn = document.getElementById(`status-btn-${s}`);
            if (s === activeTask.status) {
                btn.classList.add('ring-2', 'ring-blue-500', 'border-transparent', 'scale-105');
            } else {
                btn.classList.remove('ring-2', 'ring-blue-500', 'border-transparent', 'scale-105');
            }
        });

        // Assignee
        const assigneeNameEl = document.getElementById('detail-assignee-name');
        const assigneeAvatarEl = document.getElementById('detail-assignee-avatar');
        if (activeTask.assignee_name) {
            assigneeNameEl.innerText = activeTask.assignee_name;
            assigneeAvatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(activeTask.assignee_name)}&background=2563eb&color=fff&size=48`;
        } else {
            assigneeNameEl.innerText = 'Unassigned';
            assigneeAvatarEl.src = `https://ui-avatars.com/api/?name=U&background=cbd5e1&color=64748b&size=48`;
        }

        // Creator
        const creatorNameEl = document.getElementById('detail-creator-name');
        const creatorAvatarEl = document.getElementById('detail-creator-avatar');
        creatorNameEl.innerText = activeTask.creator_name || 'System';
        creatorAvatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(activeTask.creator_name || 'S')}&background=111827&color=fff&size=48`;

        // WhatsApp Container display
        const whatsappAction = document.getElementById('detail-whatsapp-action-container');
        if (activeTask.task_phone || activeTask.assignee_phone || activeTask.creator_phone) {
            whatsappAction.classList.remove('hidden');
        } else {
            whatsappAction.classList.add('hidden');
        }

        eventDetailsModal.classList.remove('hidden');
    }

    function closeDetailsModal() { eventDetailsModal.classList.add('hidden'); }

    // Toggle status inline inside details modal
    async function updateEventStatus(newStatus) {
        if (!activeTask) return;

        const result = await fetchAPI('api/tasks.php', 'PUT', {
            id: activeTask.id,
            status: newStatus
        });

        if (result.success) {
            showToast('Event status updated successfully!', 'success');
            
            // Instantly update the local task copy & model
            const task = allTasks.find(t => String(t.id) === String(activeTask.id));
            if (task) task.status = newStatus;
            
            // Re-render calendar events
            applyFilters();
            
            // Refresh details modal buttons highlights
            activeTask.status = newStatus;
            ['todo', 'in_progress', 'completed'].forEach(s => {
                const btn = document.getElementById(`status-btn-${s}`);
                if (s === newStatus) {
                    btn.classList.add('ring-2', 'ring-blue-500', 'border-transparent', 'scale-105');
                } else {
                    btn.classList.remove('ring-2', 'ring-blue-500', 'border-transparent', 'scale-105');
                }
            });
        } else {
            showToast('Failed to update status: ' + result.message, 'error');
        }
    }

    // Delete Event
    async function triggerDeleteTask() {
        if (!activeTask) return;
        if (confirm(`Are you sure you want to delete event "${activeTask.title}"?`)) {
            const result = await fetchAPI('api/tasks.php', 'DELETE', { id: activeTask.id });
            if (result.success) {
                closeDetailsModal();
                showToast('Event deleted successfully!', 'success');
                allTasks = allTasks.filter(t => String(t.id) !== String(activeTask.id));
                applyFilters();
            } else {
                showToast('Failed to delete event: ' + result.message, 'error');
            }
        }
    }

    // Edit modal triggers
    const editTaskModal = document.getElementById('editTaskModal');
    function triggerEditModal() {
        if (!activeTask) return;
        closeDetailsModal();

        // Populate Edit Form fields
        document.getElementById('edit-task-id').value = activeTask.id;
        document.getElementById('edit-task-title').value = activeTask.title;
        document.getElementById('edit-task-desc').value = activeTask.description || '';
        document.getElementById('edit-task-project').value = activeTask.project_id || '';
        document.getElementById('edit-task-assignee').value = activeTask.assigned_to || '';
        document.getElementById('edit-task-priority').value = activeTask.priority || 'medium';
        document.getElementById('edit-task-due-date').value = activeTask.due_date;
        document.getElementById('edit-task-phone').value = activeTask.task_phone || '';

        editTaskModal.classList.remove('hidden');
    }
    
    function closeEditModal() { editTaskModal.classList.add('hidden'); }

    async function handleEditTask(e) {
        e.preventDefault();
        const id = document.getElementById('edit-task-id').value;
        const title = document.getElementById('edit-task-title').value;
        const desc = document.getElementById('edit-task-desc').value;
        const projectId = document.getElementById('edit-task-project').value;
        const assigneeId = document.getElementById('edit-task-assignee').value;
        const priority = document.getElementById('edit-task-priority').value;
        const dueDate = document.getElementById('edit-task-due-date').value;
        const phone = document.getElementById('edit-task-phone').value;

        const result = await fetchAPI('api/tasks.php', 'PUT', {
            id: id,
            title: title,
            description: desc,
            project_id: projectId,
            assigned_to: assigneeId,
            priority: priority,
            due_date: dueDate,
            phone: phone
        });

        if (result.success) {
            closeEditModal();
            showToast('Event details updated successfully!', 'success');
            fetchAndRenderTasks();
        } else {
            showToast('Failed to edit event details: ' + result.message, 'error');
        }
    }

    // WhatsApp Follow-up modal hooks
    const whatsappModal = document.getElementById('whatsappModal');
    const recipientOptions = document.getElementById('recipientOptions');
    const customPhoneContainer = document.getElementById('customPhoneContainer');
    const waCustomPhone = document.getElementById('waCustomPhone');
    const waMessageText = document.getElementById('waMessageText');

    function triggerWhatsAppModal() {
        closeDetailsModal();
        openWhatsAppModal(activeTask);
    }

    function openWhatsAppModal(task) {
        activeTask = task;
        whatsappModal.classList.remove('hidden');

        // Dynamically build recipient choices based on task info
        let html = '';
        let hasSelected = false;

        // Choice 1: Task Phone (if set on the task directly)
        if (task.task_phone) {
            const selectAttr = !hasSelected ? 'checked' : '';
            hasSelected = true;

            html += `
                <label class="flex items-center justify-between p-2.5 rounded-xl border border-emerald-500/30 bg-emerald-50/10 dark:bg-emerald-950/20 cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-all gap-2">
                    <div class="flex items-center gap-2.5">
                        <input type="radio" name="waRecipient" value="task" ${selectAttr} onchange="toggleCustomPhoneInput(false)" class="w-3.5 h-3.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Task WhatsApp Contact</p>
                            <p class="text-[10px] text-gray-500 font-medium">${task.task_phone}</p>
                        </div>
                    </div>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 shrink-0">Primary</span>
                </label>
            `;
        }

        // Choice 2: Assignee (if exists)
        if (task.assignee_name) {
            const phoneText = task.assignee_phone ? task.assignee_phone : 'No phone saved';
            const disableAttr = task.assignee_phone ? '' : 'disabled';
            const badgeClass = task.assignee_phone ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-gray-500/10 text-gray-400 border-gray-500/10';
            const selectAttr = (task.assignee_phone && !hasSelected) ? 'checked' : '';
            if (task.assignee_phone) hasSelected = true;

            html += `
                <label class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all gap-2">
                    <div class="flex items-center gap-2.5">
                        <input type="radio" name="waRecipient" value="assignee" ${selectAttr} ${disableAttr} onchange="toggleCustomPhoneInput(false)" class="w-3.5 h-3.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">Assignee: ${task.assignee_name}</p>
                            <p class="text-[10px] text-gray-500">${phoneText}</p>
                        </div>
                    </div>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border ${badgeClass} shrink-0">Assignee</span>
                </label>
            `;
        }

        // Choice 3: Task Creator
        if (task.creator_name) {
            const phoneText = task.creator_phone ? task.creator_phone : 'No phone saved';
            const disableAttr = task.creator_phone ? '' : 'disabled';
            const badgeClass = task.creator_phone ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : 'bg-gray-500/10 text-gray-400 border-gray-500/10';
            const selectAttr = (task.creator_phone && !hasSelected) ? 'checked' : '';
            if (task.creator_phone) hasSelected = true;

            html += `
                <label class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all gap-2">
                    <div class="flex items-center gap-2.5">
                        <input type="radio" name="waRecipient" value="creator" ${selectAttr} ${disableAttr} onchange="toggleCustomPhoneInput(false)" class="w-3.5 h-3.5 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">Creator: ${task.creator_name}</p>
                            <p class="text-[10px] text-gray-500">${phoneText}</p>
                        </div>
                    </div>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border ${badgeClass} shrink-0">Creator</span>
                </label>
            `;
        }

        // Choice 4: Custom Contact
        const customSelectAttr = !hasSelected ? 'checked' : '';
        html += `
            <label class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all gap-2">
                <div class="flex items-center gap-2.5">
                    <input type="radio" name="waRecipient" value="custom" ${customSelectAttr} onchange="toggleCustomPhoneInput(true)" class="w-3.5 h-3.5 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <p class="text-xs font-semibold text-gray-900 dark:text-white">Custom Recipient</p>
                        <p class="text-[10px] text-gray-500">Enter phone number manually</p>
                    </div>
                </div>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border bg-purple-500/10 text-purple-400 border-purple-500/20 shrink-0">Custom</span>
            </label>
        `;

        recipientOptions.innerHTML = html;
        toggleCustomPhoneInput(!hasSelected);
        applyTemplate('friendly');
    }

    function closeWhatsAppModal() {
        whatsappModal.classList.add('hidden');
        waCustomPhone.value = '';
    }

    function toggleCustomPhoneInput(show) {
        if (show) {
            customPhoneContainer.classList.remove('hidden');
            waCustomPhone.focus();
        } else {
            customPhoneContainer.classList.add('hidden');
        }
    }

    function applyTemplate(type) {
        if (!activeTask) return;

        let name = 'there';
        const selectedRadio = document.querySelector('input[name="waRecipient"]:checked');
        if (selectedRadio) {
            if (selectedRadio.value === 'assignee') name = activeTask.assignee_name;
            else if (selectedRadio.value === 'creator') name = activeTask.creator_name;
        }

        let message = '';
        if (type === 'friendly') {
            message = `Hey ${name}, just following up on the task: "${activeTask.title}" which is due on ${activeTask.due_date}. Let me know if you need any help with it!`;
        } else if (type === 'status') {
            message = `Hi ${name}, could you please update the status of the task "${activeTask.title}"? Let me know what progress has been made. Thanks!`;
        } else if (type === 'urgent') {
            message = `Urgent follow-up: "${activeTask.title}" is high priority and needs your immediate attention. Please let me know the current status as soon as possible.`;
        } else if (type === 'custom') {
            message = '';
        }

        waMessageText.value = message;
    }

    function sendWhatsAppMessage() {
        if (!activeTask) return;

        const recipientRadio = document.querySelector('input[name="waRecipient"]:checked');
        if (!recipientRadio) {
            showToast('Please select a recipient.', 'error');
            return;
        }

        let phone = '';
        if (recipientRadio.value === 'task') {
            phone = activeTask.task_phone;
        } else if (recipientRadio.value === 'assignee') {
            phone = activeTask.assignee_phone;
        } else if (recipientRadio.value === 'creator') {
            phone = activeTask.creator_phone;
        } else if (recipientRadio.value === 'custom') {
            phone = waCustomPhone.value.trim();
        }

        if (!phone) {
            showToast('Recipient phone number is missing or empty.', 'error');
            if (recipientRadio.value === 'custom') waCustomPhone.focus();
            return;
        }

        const cleanPhone = phone.replace(/[^\d+]/g, '');
        if (cleanPhone.length < 5) {
            showToast('Please enter a valid phone number with country code.', 'error');
            return;
        }

        const messageText = waMessageText.value.trim();
        if (!messageText) {
            showToast('Please enter a message to send.', 'error');
            waMessageText.focus();
            return;
        }

        const encodedMessage = encodeURIComponent(messageText);
        const waUrl = `https://wa.me/${cleanPhone.replace('+', '')}?text=${encodedMessage}`;

        window.open(waUrl, '_blank');
        closeWhatsAppModal();
    }

    // Contacts Directory Selector
    const contactSelectorModal = document.getElementById('contactSelectorModal');
    const contactsSelectorList = document.getElementById('contactsSelectorList');
    const contactSearchInput = document.getElementById('contactSearchInput');
    const quickAddContactForm = document.getElementById('quickAddContactForm');
    const quickAddCaret = document.getElementById('quickAddCaret');
    let directoryContacts = [];
    let targetPhoneInputId = '';

    async function openContactSelector(inputId) {
        targetPhoneInputId = inputId;
        contactSelectorModal.classList.remove('hidden');
        contactSearchInput.value = '';
        contactsSelectorList.innerHTML = `<p class="text-xs text-gray-555 italic text-center py-8">Loading directory...</p>`;
        
        const response = await fetchAPI('api/contacts.php');
        if (response.success && response.data) {
            directoryContacts = response.data;
            renderContactsList(directoryContacts);
        } else {
            contactsSelectorList.innerHTML = `<p class="text-xs text-rose-500 text-center py-8">Failed to load directory contacts.</p>`;
        }
    }

    function closeContactSelector() {
        contactSelectorModal.classList.add('hidden');
        quickAddContactForm.classList.add('hidden');
        quickAddCaret.classList.remove('rotate-180');
        quickAddContactForm.reset();
    }

    function renderContactsList(contacts) {
        if (contacts.length === 0) {
            contactsSelectorList.innerHTML = `
                <div class="flex flex-col items-center justify-center p-6 text-gray-505 text-gray-500">
                    <i class="ph ph-address-book text-3xl mb-1.5 opacity-40"></i>
                    <p class="text-[11px]">No contacts matched.</p>
                </div>`;
            return;
        }

        let html = '';
        contacts.forEach(c => {
            const badgeClass = c.type === 'workspace' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20';
            const badgeLabel = c.type === 'workspace' ? 'Workspace' : (c.company ? c.company : 'Client');
            
            html += `
                <div onclick="selectContact('${c.phone}')" class="flex items-center justify-between p-3 border border-gray-100 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-850 cursor-pointer transition-all duration-200">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold shrink-0">
                            ${c.name.charAt(0).toUpperCase()}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">${c.name}</p>
                            <p class="text-[10px] text-gray-400 flex items-center gap-1"><i class="ph ph-phone"></i> ${c.phone}</p>
                        </div>
                    </div>
                    <span class="text-[8px] font-bold px-2 py-0.5 rounded-full border ${badgeClass} shrink-0 max-w-[80px] truncate">${badgeLabel}</span>
                </div>
            `;
        });
        contactsSelectorList.innerHTML = html;
    }

    function filterContactsList() {
        const query = contactSearchInput.value.toLowerCase().trim();
        const filtered = directoryContacts.filter(c => {
            return c.name.toLowerCase().includes(query) || c.phone.includes(query) || (c.company && c.company.toLowerCase().includes(query));
        });
        renderContactsList(filtered);
    }

    function selectContact(phone) {
        if (targetPhoneInputId) {
            document.getElementById(targetPhoneInputId).value = phone;
        }
        closeContactSelector();
    }

    function toggleQuickAddContactForm() {
        quickAddContactForm.classList.toggle('hidden');
        quickAddCaret.classList.toggle('rotate-180');
    }

    async function handleQuickAddContact(e) {
        e.preventDefault();
        const name = document.getElementById('qaContactName').value;
        const phone = document.getElementById('qaContactPhone').value;
        const email = document.getElementById('qaContactEmail').value;
        const company = document.getElementById('qaContactCompany').value;

        const result = await fetchAPI('api/contacts.php', 'POST', {
            name: name,
            phone: phone,
            email: email,
            company: company
        });

        if (result.success && result.data) {
            selectContact(result.data.phone);
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    }
</script>

<style>
/* Custom FullCalendar CSS */
.fc {
    font-family: 'Outfit', 'Inter', sans-serif !important;
}
.fc .fc-toolbar-title {
    font-size: 1.15rem !important;
    font-weight: 800 !important;
    color: #1e293b;
    letter-spacing: -0.02em;
}
.dark .fc .fc-toolbar-title {
    color: #f8fafc;
}
.fc .fc-button {
    padding: 0.4rem 0.75rem !important;
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    border-radius: 0.625rem !important;
    text-transform: capitalize !important;
    transition: all 0.2s ease-in-out !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}
.fc .fc-button-primary {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #475569 !important;
}
.fc .fc-button-primary:hover {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}
.dark .fc .fc-button-primary {
    background-color: #1e293b !important;
    border: 1px solid #334155 !important;
    color: #cbd5e1 !important;
}
.dark .fc .fc-button-primary:hover {
    background-color: #334155 !important;
    color: #ffffff !important;
}
.fc .fc-button-primary:not(:disabled).fc-button-active {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
    color: #ffffff !important;
    box-shadow: 0 6px 15px -3px rgba(37, 99, 235, 0.2) !important;
}
.dark .fc .fc-button-primary:not(:disabled).fc-button-active {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: #ffffff !important;
}
.fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid {
    border-color: #e2e8f0 !important;
}
.dark .fc-theme-standard td, .dark .fc-theme-standard th, .dark .fc-theme-standard .fc-scrollgrid {
    border-color: #1e293b !important;
}
.fc .fc-daygrid-day-number {
    font-size: 0.72rem !important;
    font-weight: 800 !important;
    color: #64748b !important;
    padding: 6px 8px !important;
}
.dark .fc .fc-daygrid-day-number {
    color: #94a3b8 !important;
}
.fc .fc-daygrid-day.fc-day-today {
    background-color: rgba(37, 99, 235, 0.03) !important;
}
.dark .fc .fc-daygrid-day.fc-day-today {
    background-color: rgba(59, 130, 246, 0.03) !important;
}
.fc .fc-day-today .fc-daygrid-day-number {
    color: #2563eb !important;
    background-color: rgba(37, 99, 235, 0.1) !important;
    border-radius: 0.375rem !important;
}
.dark .fc .fc-day-today .fc-daygrid-day-number {
    color: #3b82f6 !important;
    background-color: rgba(59, 130, 246, 0.1) !important;
}
.fc-col-header-cell-cushion {
    font-size: 0.7rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    color: #475569 !important;
    padding: 8px 0 !important;
}
.dark .fc-col-header-cell-cushion {
    color: #cbd5e1 !important;
}
.fc-h-event {
    background-color: transparent !important;
    border: none !important;
}
.fc-daygrid-event-harness {
    margin-top: 2.5px !important;
    margin-bottom: 2.5px !important;
}
.fc-custom-event {
    cursor: pointer;
}
.fc-custom-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(1rem);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-slide-in {
    animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Custom scrollbars */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 99px;
}
.dark class .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
}
</style>

<?php include 'includes/footer.php'; ?>
