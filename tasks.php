<?php
$page_title = 'My Tasks';
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch all tasks with assignee and creator information
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT t.*, c.name as category_name, p.name as project_name,
                                  u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                  u_create.username as creator_name, u_create.phone as creator_phone
                           FROM tasks t 
                           LEFT JOIN categories c ON t.category_id = c.id 
                           LEFT JOIN projects p ON t.project_id = p.id 
                           LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                           LEFT JOIN users u_create ON t.created_by = u_create.id
                           ORDER BY t.created_at DESC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT t.*, c.name as category_name, p.name as project_name,
                                  u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                  u_create.username as creator_name, u_create.phone as creator_phone
                           FROM tasks t 
                           LEFT JOIN categories c ON t.category_id = c.id 
                           LEFT JOIN projects p ON t.project_id = p.id 
                           LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                           LEFT JOIN users u_create ON t.created_by = u_create.id
                           WHERE t.created_by = ? OR t.assigned_to = ? 
                           ORDER BY t.created_at DESC");
    $stmt->execute([$user_id, $user_id]);
}
$tasks = $stmt->fetchAll();

// Fetch all users to populate assignee selection
$users_stmt = $pdo->prepare("SELECT id, username, phone FROM users ORDER BY username ASC");
$users_stmt->execute();
$all_users = $users_stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] transition-colors relative">
    <!-- Top Header -->
    <header class="h-20 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-6 shrink-0 transition-colors">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <a href="index.php" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors" title="Go to Dashboard">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white">My Tasks</h1>
            </a>
        </div>

        <div class="flex items-center gap-4">
            <button id="theme-toggle" class="w-10 h-10 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors">
                <i class="ph ph-moon text-xl dark:hidden"></i>
                <i class="ph ph-sun text-xl hidden dark:block"></i>
            </button>
            <button onclick="openTaskModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-plus"></i> Add Task
            </button>
        </div>
    </header>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-4 mb-6">
                <div class="flex-1 min-w-[200px] relative">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="searchTask" placeholder="Search tasks..." class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm dark:text-white transition-colors">
                </div>
                <select class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 transition-colors">
                    <option value="">All Status</option>
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <!-- Task Board / List -->
            <div class="task-card bg-white dark:bg-gray-900 overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="p-4 w-10">
                                    <input type="checkbox" id="select-all-tasks" onchange="toggleSelectAllTasks(this.checked)" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                </th>
                                <th class="p-4 font-medium">Task Name</th>
                                <th class="p-4 font-medium">Status</th>
                                <th class="p-4 font-medium">Priority</th>
                                <th class="p-4 font-medium">Due Date</th>
                                <th class="p-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800" id="taskList">
                            <?php if(empty($tasks)): ?>
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="ph ph-clipboard-text text-4xl mb-3 text-gray-400"></i>
                                            <p>No tasks found. Create your first task!</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($tasks as $task): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group animate-fade-in">
                                        <td class="p-4 w-10">
                                            <input type="checkbox" data-id="<?php echo $task['id']; ?>" onchange="toggleSelectTask(<?php echo $task['id']; ?>, this.checked)" class="task-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                        </td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?> onchange="updateTaskStatus(<?php echo $task['id']; ?>, this.checked, this)" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                                <div>
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <p class="font-medium text-gray-900 dark:text-white <?php echo $task['status'] === 'completed' ? 'line-through text-gray-400 dark:text-gray-500' : ''; ?>">
                                                            <?php echo htmlspecialchars($task['title']); ?>
                                                        </p>
                                                        <?php if ($task['assignee_name']): ?>
                                                            <span class="inline-flex items-center gap-1 text-[9px] bg-blue-500/10 text-blue-400 font-semibold px-2 py-0.5 rounded-full border border-blue-500/20">
                                                                <i class="ph ph-user"></i> <?php echo htmlspecialchars($task['assignee_name']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($task['phone']): ?>
                                                            <span class="inline-flex items-center gap-1 text-[9px] bg-emerald-500/10 text-emerald-400 font-semibold px-2 py-0.5 rounded-full border border-emerald-500/20" title="Task WhatsApp Number">
                                                                <i class="ph-bold ph-whatsapp-logo"></i> <?php echo htmlspecialchars($task['phone']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if($task['description']): ?>
                                                        <p class="text-xs text-gray-500 truncate max-w-xs mt-0.5"><?php echo htmlspecialchars($task['description']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <select onchange="updateTaskStatusDropdown(<?php echo $task['id']; ?>, this.value, this)" class="text-xs border-none bg-transparent focus:ring-0 cursor-pointer text-gray-700 dark:text-gray-300 font-medium">
                                                <option value="todo" <?php echo $task['status'] === 'todo' ? 'selected' : ''; ?>>To Do</option>
                                                <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 text-[10px] rounded-full font-medium badge-<?php echo $task['priority']; ?> uppercase tracking-wide">
                                                <?php echo $task['priority']; ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                            <?php if($task['due_date']): ?>
                                                <div class="flex items-center gap-1.5 <?php echo (strtotime($task['due_date']) < time() && $task['status'] !== 'completed') ? 'text-red-500 font-medium' : ''; ?>">
                                                    <i class="ph ph-calendar-blank"></i>
                                                    <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                                </div>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="flex items-center justify-end gap-2 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                                <!-- WhatsApp Follow-up button -->
                                                <button onclick="openWhatsAppModal(<?php echo htmlspecialchars(json_encode([
                                                    'id' => $task['id'],
                                                    'title' => $task['title'],
                                                    'due_date' => $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'Anytime',
                                                    'assignee_name' => $task['assignee_name'],
                                                    'assignee_phone' => $task['assignee_phone'],
                                                    'creator_name' => $task['creator_name'],
                                                    'creator_phone' => $task['creator_phone'],
                                                    'task_phone' => $task['phone'],
                                                ])); ?>)" class="w-8 h-8 rounded hover:bg-emerald-500/10 hover:text-emerald-500 flex items-center justify-center text-gray-500 transition-all hover:scale-110" title="WhatsApp Follow-up">
                                                    <i class="ph-fill ph-whatsapp-logo text-lg text-emerald-500"></i>
                                                </button>
                                                <button onclick="openEditTaskModal(<?php echo htmlspecialchars(json_encode($task)); ?>)" class="w-8 h-8 rounded hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center text-gray-550 dark:text-gray-400 hover:text-blue-500 transition-colors" title="Edit">
                                                    <i class="ph ph-pencil-simple text-lg"></i>
                                                </button>
                                                <button onclick="deleteTask(<?php echo $task['id']; ?>, this)" class="w-8 h-8 rounded hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600 flex items-center justify-center text-gray-500 transition-colors" title="Delete">
                                                    <i class="ph ph-trash text-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <!-- Floating Bulk Action Bar (shows when checked tasks > 0) -->
    <div class="absolute bottom-4 sm:bottom-6 left-0 right-0 flex justify-center pointer-events-none z-40">
        <div id="tasks-bulk-action-bar" class="hidden pointer-events-auto bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl py-2.5 px-4 sm:py-3 sm:px-6 shadow-2xl flex flex-wrap items-center justify-center gap-3 sm:gap-4 animate-fade-in max-w-[95vw] md:max-w-none">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-bold" id="tasks-bulk-select-count">0 tasks selected</span>
            <div class="w-px h-6 bg-gray-255 dark:bg-gray-800"></div>
            <button onclick="applyBulkDeleteTasks()" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 shadow-md">
                <i class="ph ph-trash"></i> Delete Selected
            </button>
            <button onclick="clearTasksBulkSelection()" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium">Cancel</button>
        </div>
    </div>
</main>

<!-- Add Task Modal Overlay -->
<div id="taskModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[90vh]">
        <!-- Fixed Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Task</h3>
            <button onclick="closeTaskModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        
        <!-- Scrollable Body Form -->
        <form id="createTaskForm" onsubmit="handleFormSubmit(event)" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Task Title *</label>
                    <input type="text" id="taskTitle" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="What needs to be done?">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea id="taskDesc" rows="3" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="Add some details..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Assignee</label>
                    <select id="taskAssignee" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="">Unassigned</option>
                        <?php foreach ($all_users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?><?php echo !empty($u['phone']) ? ' (' . htmlspecialchars($u['phone']) . ')' : ' (No phone)'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Task WhatsApp Number (Optional)</label>
                        <button type="button" onclick="openContactSelector('taskPhone')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 font-bold">
                            <i class="ph ph-address-book text-sm"></i> Search Directory
                        </button>
                    </div>
                    <input type="text" id="taskPhone" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="e.g. +15551234567">
                    <p class="text-[10px] text-gray-400 mt-1">Direct contact number for external clients or reminders.</p>
                </div>


                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Priority</label>
                        <select id="taskPriority" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Due Date</label>
                        <input type="date" id="taskDueDate" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Fixed Footer -->
            <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 shrink-0 bg-gray-50 dark:bg-gray-900/60">
                <button type="button" onclick="closeTaskModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-medium text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-sm shadow-sm">Create Task</button>
            </div>
        </form>
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
            <p class="text-xs text-gray-550 italic text-center py-4">Loading directory...</p>
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

<?php 
$custom_js = <<<'EOT'
<script>
    // Modal controls
    const taskModal = document.getElementById('taskModal');
    let editingTaskId = null;

    // Bulk task selection and action states
    let selectedTasks = new Set();

    function toggleSelectTask(id, isChecked) {
        if (isChecked) {
            selectedTasks.add(id);
        } else {
            selectedTasks.delete(id);
        }
        updateTasksBulkMenuBar();
    }

    function toggleSelectAllTasks(isChecked) {
        const checkboxes = document.querySelectorAll('.task-checkbox');
        checkboxes.forEach(chk => {
            chk.checked = isChecked;
            const id = parseInt(chk.getAttribute('data-id'));
            if (id) {
                if (isChecked) {
                    selectedTasks.add(id);
                } else {
                    selectedTasks.delete(id);
                }
            }
        });
        updateTasksBulkMenuBar();
    }

    function clearTasksBulkSelection() {
        selectedTasks.clear();
        const chks = document.querySelectorAll('.task-checkbox');
        chks.forEach(c => c.checked = false);
        const allChk = document.getElementById('select-all-tasks');
        if (allChk) allChk.checked = false;
        updateTasksBulkMenuBar();
    }

    function updateTasksBulkMenuBar() {
        const bar = document.getElementById('tasks-bulk-action-bar');
        const count = document.getElementById('tasks-bulk-select-count');
        if (selectedTasks.size > 0) {
            bar.classList.remove('hidden');
            count.innerText = `${selectedTasks.size} task${selectedTasks.size > 1 ? 's' : ''} selected`;
        } else {
            bar.classList.add('hidden');
        }
    }

    async function applyBulkDeleteTasks() {
        if (selectedTasks.size === 0) return;
        if (confirm(`Are you sure you want to delete the ${selectedTasks.size} selected task(s)?`)) {
            const result = await fetchAPI('api/tasks.php', 'DELETE', { ids: Array.from(selectedTasks) });
            if (result.success) {
                clearTasksBulkSelection();
                window.location.reload();
            } else {
                alert('Bulk deletion error: ' + result.message);
            }
        }
    }

    function openTaskModal() { 
        taskModal.classList.remove('hidden'); 
    }

    function closeTaskModal() { 
        taskModal.classList.add('hidden'); 
        document.getElementById('createTaskForm').reset(); 
        editingTaskId = null;
        const headerTitle = document.querySelector('#taskModal h3');
        if (headerTitle) headerTitle.innerText = 'Create New Task';
        const submitBtn = document.querySelector('#createTaskForm button[type="submit"]');
        if (submitBtn) submitBtn.innerText = 'Create Task';
    }

    function openEditTaskModal(task) {
        editingTaskId = task.id;
        
        // Populate form fields
        document.getElementById('taskTitle').value = task.title || '';
        document.getElementById('taskDesc').value = task.description || '';
        document.getElementById('taskPriority').value = task.priority || 'medium';
        document.getElementById('taskDueDate').value = task.due_date ? task.due_date.substring(0, 10) : '';
        document.getElementById('taskAssignee').value = task.assigned_to || '';
        document.getElementById('taskPhone').value = task.phone || '';

        // Change modal labels
        const headerTitle = document.querySelector('#taskModal h3');
        if (headerTitle) headerTitle.innerText = 'Edit Task';
        const submitBtn = document.querySelector('#createTaskForm button[type="submit"]');
        if (submitBtn) submitBtn.innerText = 'Save Changes';

        taskModal.classList.remove('hidden');
    }

    // Form submit dispatcher (handles both create and update)
    async function handleFormSubmit(e) {
        e.preventDefault();
        const title = document.getElementById('taskTitle').value;
        const desc = document.getElementById('taskDesc').value;
        const priority = document.getElementById('taskPriority').value;
        const dueDate = document.getElementById('taskDueDate').value;
        const assignee = document.getElementById('taskAssignee').value;
        const phone = document.getElementById('taskPhone').value;

        const payload = {
            title: title,
            description: desc,
            priority: priority,
            due_date: dueDate,
            assigned_to: assignee,
            phone: phone
        };

        let result;
        if (editingTaskId) {
            payload.id = editingTaskId;
            result = await fetchAPI('api/tasks.php', 'PUT', payload);
        } else {
            result = await fetchAPI('api/tasks.php', 'POST', payload);
        }

        if (result.success) {
            closeTaskModal();
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    }

    // Update Status API Call (Checkbox)
    async function updateTaskStatus(id, isChecked, element) {
        const status = isChecked ? 'completed' : 'todo';
        if (status === 'completed') playCompletionSound();
        
        // Optimistic UI update
        const pTag = element.closest('td').querySelector('p.font-medium');
        if (isChecked) {
            pTag.classList.add('line-through', 'text-gray-400', 'dark:text-gray-500');
        } else {
            pTag.classList.remove('line-through', 'text-gray-400', 'dark:text-gray-500');
        }

        await fetchAPI('api/tasks.php', 'PUT', { id: id, status: status });
    }

    // Update Status API Call (Dropdown)
    async function updateTaskStatusDropdown(id, status, element) {
        if (status === 'completed') playCompletionSound();
        
        // Optimistic UI update for dropdown (update checkbox and text)
        const tr = element.closest('tr');
        const checkbox = tr.querySelector('input[type="checkbox"]');
        const pTag = tr.querySelector('p.font-medium');
        
        if (status === 'completed') {
            checkbox.checked = true;
            pTag.classList.add('line-through', 'text-gray-400', 'dark:text-gray-500');
        } else {
            checkbox.checked = false;
            pTag.classList.remove('line-through', 'text-gray-400', 'dark:text-gray-500');
        }

        await fetchAPI('api/tasks.php', 'PUT', { id: id, status: status });
    }

    // Delete Task API Call
    async function deleteTask(id, element) {
        if (confirm('Are you sure you want to delete this task?')) {
            const result = await fetchAPI('api/tasks.php', 'DELETE', { id: id });
            if (result.success) {
                // Remove the row from the table without reloading
                if (element) {
                    element.closest('tr').remove();
                }
            } else {
                alert('Error: ' + result.message);
            }
        }
    }

    // ----------------------------------------------------
    // WhatsApp Follow-up Functionality
    // ----------------------------------------------------
    let activeTask = null;
    const whatsappModal = document.getElementById('whatsappModal');
    const recipientOptions = document.getElementById('recipientOptions');
    const customPhoneContainer = document.getElementById('customPhoneContainer');
    const waCustomPhone = document.getElementById('waCustomPhone');
    const waMessageText = document.getElementById('waMessageText');

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

        // Choice 4: Custom Contact (always enabled)
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
        
        // Apply default template
        applyTemplate('friendly');
    }

    function closeWhatsAppModal() {
        whatsappModal.classList.add('hidden');
        waCustomPhone.value = '';
        activeTask = null;
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
            else if (selectedRadio.value === 'task') name = 'there';
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
            alert('Please select a recipient.');
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
            alert('Recipient phone number is missing or empty.');
            if (recipientRadio.value === 'custom') waCustomPhone.focus();
            return;
        }

        // Normalize phone number (digits only, keep leading + if present)
        const cleanPhone = phone.replace(/[^\d+]/g, '');
        if (cleanPhone.length < 5) {
            alert('Please enter a valid phone number with country code (e.g. +15551234567).');
            return;
        }

        const messageText = waMessageText.value.trim();
        if (!messageText) {
            alert('Please enter a message to send.');
            waMessageText.focus();
            return;
        }

        // Compile WhatsApp click-to-chat URL
        const encodedMessage = encodeURIComponent(messageText);
        const waUrl = `https://wa.me/${cleanPhone.replace('+', '')}?text=${encodedMessage}`;

        // Open in a new tab
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
                <div class="flex flex-col items-center justify-center p-6 text-gray-500">
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
            alert('Error: ' + result.message);
        }
    }
</script>
EOT;
include 'includes/footer.php'; 
?>
