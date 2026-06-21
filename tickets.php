<?php
$page_title = 'Helpdesk Ticket Center';
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$view = sanitize_input($_GET['view'] ?? 'all');
$current_user_role = $_SESSION['role'] ?? 'employee';
$current_user_id = $_SESSION['user_id'];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] text-slate-800 dark:text-gray-200 transition-colors relative">
    
    <!-- Header -->
    <header class="h-20 bg-white dark:bg-[#0B1120] border-b border-gray-250 dark:border-gray-800/50 flex items-center justify-between px-6 shrink-0 z-10 transition-colors">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-855 dark:text-gray-400 dark:hover:text-white transition-colors">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="ph-fill ph-ticket text-cyan-400"></i> Helpdesk Tickets
                </h1>
                <p class="text-xs text-gray-500 font-medium hidden sm:block">Manage customer issues, track SLA resolution deadlines, and collaborate.</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <button id="theme-toggle" title="Toggle Dark/Light Mode" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-550 hover:bg-gray-105 dark:text-gray-400 dark:hover:bg-gray-850 hover:text-gray-800 dark:hover:text-white border border-gray-200 dark:border-gray-800 transition-colors">
                <i class="ph ph-moon text-xl dark:hidden"></i>
                <i class="ph ph-sun text-xl hidden dark:block"></i>
            </button>
            <button onclick="openCreateModal()" class="btn-primary px-4 py-2 text-sm rounded-xl font-semibold transition-all flex items-center gap-2 shadow-lg bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400">
                <i class="ph-bold ph-plus-circle text-lg"></i> Create Ticket
            </button>
        </div>
    </header>

    <!-- Scrollable Content container -->
    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
        <div class="max-w-7xl mx-auto space-y-6 animate-fade-in">
            
            <!-- Standard Dashboard views and KB views switcher -->
            <div id="ticketing-dashboard-content" class="space-y-6">
                
                <!-- SLA KPI Cards Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" id="kpi-cards-grid">
                    <!-- Total Tickets Card -->
                    <div class="glass-panel p-4 rounded-2xl border border-gray-200 dark:border-gray-800 flex items-center gap-4 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent"></div>
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400 shrink-0">
                            <i class="ph-fill ph-ticket text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Tickets</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-0.5" id="stat-total">0</h3>
                        </div>
                    </div>
                    <!-- Open Tickets Card -->
                    <div class="glass-panel p-4 rounded-2xl border border-gray-200 dark:border-gray-800 flex items-center gap-4 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-transparent"></div>
                        <div class="w-12 h-12 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-400 shrink-0">
                            <i class="ph-fill ph-envelope-open text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Open Tickets</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-0.5" id="stat-open">0</h3>
                        </div>
                    </div>
                    <!-- Critical Priority Card -->
                    <div class="glass-panel p-4 rounded-2xl border border-gray-200 dark:border-gray-800 flex items-center gap-4 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent"></div>
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 shrink-0">
                            <i class="ph-fill ph-warning text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Critical Priority</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-0.5" id="stat-critical">0</h3>
                        </div>
                    </div>
                    <!-- SLA Breached Card -->
                    <div class="glass-panel p-4 rounded-2xl border border-gray-200 dark:border-gray-800 flex items-center gap-4 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-rose-500/5 to-transparent"></div>
                        <div class="w-12 h-12 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-455 shrink-0">
                            <i class="ph-fill ph-alarm text-2xl text-rose-400"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">SLA Breached</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-0.5" id="stat-breached">0</h3>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search Controls -->
                <div class="glass-panel p-4 rounded-2xl border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Left: Search and Filters Selector -->
                    <div class="flex flex-wrap items-center gap-3 flex-1">
                        <!-- Search Box -->
                        <div class="relative w-full md:w-72">
                            <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-550 text-lg"></i>
                            <input type="text" id="ticket-search" oninput="debounceSearch()" placeholder="Search ticket #, title..." class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm focus:outline-none focus:border-cyan-500 transition-colors text-gray-850 dark:text-white placeholder-gray-450 dark:placeholder-gray-650">
                        </div>
                        
                        <!-- Quick View Filter Buttons (Desktop & Tablet) -->
                        <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-950 p-1 rounded-xl border border-gray-200 dark:border-gray-800">
                            <button onclick="changeView('all')" id="tab-all" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-gray-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-white">All</button>
                            <button onclick="changeView('open')" id="tab-open" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-gray-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-white">Open</button>
                            <button onclick="changeView('assigned')" id="tab-assigned" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-gray-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-white">Assigned</button>
                            <button onclick="changeView('breached')" id="tab-breached" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-gray-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-white">Breached</button>
                            <button onclick="changeView('closed')" id="tab-closed" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all text-gray-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-white">Closed</button>
                        </div>
                    </div>

                    <!-- Right: Sorting & Display controls -->
                    <div class="flex items-center gap-3 justify-end shrink-0">
                        <select id="ticket-sort-by" onchange="loadTickets()" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-xs rounded-xl py-2 px-3 text-gray-700 dark:text-gray-400 focus:outline-none focus:border-cyan-500 transition-colors">
                            <option value="created_at">Sort By: Date Created</option>
                            <option value="due_date">Sort By: SLA Resolution</option>
                            <option value="priority">Sort By: Priority</option>
                            <option value="status">Sort By: Status</option>
                            <option value="ticket_number">Sort By: ID</option>
                        </select>
                        <select id="ticket-sort-order" onchange="loadTickets()" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-xs rounded-xl py-2 px-3 text-gray-700 dark:text-gray-400 focus:outline-none focus:border-cyan-500 transition-colors">
                            <option value="DESC">Descending</option>
                            <option value="ASC">Ascending</option>
                        </select>
                    </div>
                </div>


                <!-- Tickets Data Grid Table -->
                <div class="glass-panel rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="tickets-table">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-800/80 bg-gray-100/50 dark:bg-gray-950/30 text-gray-550 dark:text-gray-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="py-4 px-4 w-10">
                                        <input type="checkbox" id="select-all-tickets" onchange="toggleSelectAll()" class="w-4 h-4 accent-cyan-500 rounded border-gray-350 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-cyan-500/20">
                                    </th>
                                    <th class="py-4 px-4 w-24">Ticket ID</th>
                                    <th class="py-4 px-6">Ticket Title</th>
                                    <th class="py-4 px-6">Requested By</th>
                                    <th class="py-4 px-6">Assigned To</th>
                                    <th class="py-4 px-6 w-32">Priority</th>
                                    <th class="py-4 px-6 w-36">Status</th>
                                    <th class="py-4 px-6 w-40 text-right">SLA Resolution</th>
                                    <th class="py-4 px-6 w-20 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800/40 text-sm" id="tickets-list-container">
                                <!-- JS items render here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State -->
                    <div id="tickets-empty-state" class="hidden flex flex-col items-center justify-center p-16 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900/60 flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4 border border-gray-200 dark:border-gray-800">
                            <i class="ph-bold ph-ticket text-3xl"></i>
                        </div>
                        <h4 class="text-slate-800 dark:text-white font-bold text-base">No Tickets Found</h4>
                        <p class="text-xs text-gray-550 dark:text-gray-500 max-w-sm mt-1">There are no tickets matching the active query filters. Check back later or create a new ticket.</p>
                    </div>
                </div>
            </div>

            <!-- Knowledge Base SPA Tab View (Searchable articles) -->
            <div id="kb-dashboard-content" class="hidden space-y-6">
                <!-- Search and Title -->
                <div class="glass-panel p-6 rounded-2xl border border-gray-250 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-book-open text-amber-400"></i> Helpdesk Knowledge Base
                        </h3>
                        <p class="text-xs text-gray-500 font-medium">Instantly find guides, tutorials, and support documents to resolve issues faster.</p>
                    </div>
                    <!-- KB Search Box -->
                    <div class="relative w-full sm:w-72">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-550 text-lg"></i>
                        <input type="text" id="kb-search" oninput="filterKB()" placeholder="Search KB articles..." class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-sm focus:outline-none focus:border-amber-500 transition-colors text-gray-800 dark:text-white placeholder-gray-450 dark:placeholder-gray-650">
                    </div>
                </div>

                <!-- KB Articles List -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="kb-articles-grid">
                    <!-- Article 1 -->
                    <div class="glass-panel p-6 rounded-2xl border border-gray-250 dark:border-gray-800 hover-lift flex flex-col justify-between kb-article-card" data-title="resetting user passwords reset lock username password directory">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 text-[10px] font-bold uppercase rounded-md">Administration</span>
                                <span class="text-[10px] text-gray-500">Read time: 2 mins</span>
                            </div>
                            <h4 class="text-slate-800 dark:text-white font-bold text-base tracking-tight">How to Reset User Passwords</h4>
                            <p class="text-xs text-gray-400 leading-relaxed">Administrators can easily reset users' passwords via the User Directory section. Access the directory, click edit on a user account, insert a new password, and verify changes to unlock blocked workspace accounts.</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-800/60 flex justify-end">
                            <button onclick="openKBArticle(1)" class="text-xs text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1.5">Read Guide <i class="ph-bold ph-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Article 2 -->
                    <div class="glass-panel p-6 rounded-2xl border border-gray-250 dark:border-gray-800 hover-lift flex flex-col justify-between kb-article-card" data-title="sla threshold resolution deadline timing rules countdown warning breached yellow red green">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-rose-500/10 border border-rose-500/20 text-rose-455 text-[10px] font-bold uppercase rounded-md">Compliance</span>
                                <span class="text-[10px] text-gray-500">Read time: 3 mins</span>
                            </div>
                            <h4 class="text-slate-800 dark:text-white font-bold text-base tracking-tight">Understanding SLA Response Thresholds</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">Resolution times are strictly calculated based on the due dates configured upon ticket creation. SLA countdowns indicate remaining resolution deadlines using color-coded badges: Green for safe status, Yellow warning for near breach, and Red for breached deadlines.</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-250 dark:border-gray-800/60 flex justify-end">
                            <button onclick="openKBArticle(2)" class="text-xs text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1.5">Read Guide <i class="ph-bold ph-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Article 3 -->
                    <div class="glass-panel p-6 rounded-2xl border border-gray-250 dark:border-gray-800 hover-lift flex flex-col justify-between kb-article-card" data-title="creating managing tasks boards projects assignments priority urgent categories">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold uppercase rounded-md">Workflows</span>
                                <span class="text-[10px] text-gray-500">Read time: 4 mins</span>
                            </div>
                            <h4 class="text-slate-800 dark:text-white font-bold text-base tracking-tight">Creating and Managing Task Boards</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">Boost team efficiency using dynamic task boards. Easily categorize, assign due dates, color-code associated projects, and link internal tasks to workspace contacts to leverage prompt resolution delivery.</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-250 dark:border-gray-800/60 flex justify-end">
                            <button onclick="openKBArticle(3)" class="text-xs text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1.5">Read Guide <i class="ph-bold ph-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- Article 4 -->
                    <div class="glass-panel p-6 rounded-2xl border border-gray-255 dark:border-gray-800 hover-lift flex flex-col justify-between kb-article-card" data-title="deleting tickets access controls administrator privileges protection security">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 bg-purple-500/10 border border-purple-500/20 text-purple-400 text-[10px] font-bold uppercase rounded-md">Security</span>
                                <span class="text-[10px] text-gray-500">Read time: 1 min</span>
                            </div>
                            <h4 class="text-slate-800 dark:text-white font-bold text-base tracking-tight">Restricting Helpdesk Ticket Deletions</h4>
                            <p class="text-xs text-gray-400 leading-relaxed">Audit history protection is vital in enterprise environments. Deletion actions on helpdesk tickets are strictly protected and restricted to administrators to prevent structural data loss and preserve audit integrity logs.</p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-800/60 flex justify-end">
                            <button onclick="openKBArticle(4)" class="text-xs text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1.5">Read Guide <i class="ph-bold ph-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Floating Bulk Action Bar (shows when checklist > 0) -->
        <div class="absolute bottom-4 sm:bottom-6 left-0 right-0 flex justify-center pointer-events-none z-40">
            <div id="bulk-action-bar" class="hidden pointer-events-auto bg-white/95 dark:bg-gray-950/95 backdrop-blur-md border border-gray-200 dark:border-gray-800 rounded-2xl py-2.5 px-4 sm:py-3 sm:px-6 shadow-2xl flex flex-wrap items-center justify-center gap-3 sm:gap-4 animate-fade-in max-w-[95vw] md:max-w-none text-slate-800 dark:text-gray-200">
                <span class="text-xs text-gray-550 dark:text-gray-400 font-bold" id="bulk-select-count">0 tickets selected</span>
                <div class="w-px h-6 bg-gray-200 dark:bg-gray-800"></div>
                
                <select id="bulk-action-status" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-xs rounded-xl py-1.5 px-3 text-slate-700 dark:text-gray-300 outline-none">
                    <option value="">Update Status...</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>

                <select id="bulk-action-assignee" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-xs rounded-xl py-1.5 px-3 text-slate-700 dark:text-gray-300 outline-none">
                    <option value="no_change">Assign To...</option>
                    <option value="">Unassign</option>
                    <!-- Workspace users injected by JS -->
                </select>

                <button onclick="applyBulkActions()" class="px-4 py-1.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white rounded-xl text-xs font-semibold transition-all">Apply</button>
                <button onclick="applyBulkDeleteTickets()" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5"><i class="ph ph-trash"></i> Delete Selected</button>
                <button onclick="clearBulkSelection()" class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-350 font-medium">Cancel</button>
            </div>
        </div>
    </main>

<!-- Create Ticket Form Modal Overlay -->
<div id="ticketCreateModal" class="fixed inset-0 bg-[#070A13]/85 dark:bg-[#070A13]/80 backdrop-blur-sm z-50 items-center justify-center p-4 hidden">
    <div class="card-3d bg-white dark:bg-[#0F172A] w-full max-w-xl overflow-hidden animate-fade-in p-0 border border-gray-200 dark:border-gray-800/80 rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800/60 bg-gray-50 dark:bg-gray-950/20">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <i class="ph-bold ph-plus-circle text-cyan-400"></i> Create New Support Ticket
            </h3>
            <button onclick="closeCreateModal()" class="text-gray-450 hover:text-gray-805 dark:text-gray-400 dark:hover:text-white transition-colors">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <form id="createTicketForm" onsubmit="handleCreateTicket(event)" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Ticket Title *</label>
                    <input type="text" id="create-title" required class="form-input w-full px-4 py-2.5" placeholder="Summarize the support issue...">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Priority Level *</label>
                    <select id="create-priority" required class="form-input w-full px-4 py-2.5">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Assign Agent</label>
                    <select id="create-assignee" class="form-input w-full px-4 py-2.5">
                        <option value="">Unassigned (Open Queue)</option>
                        <!-- Workspace users injected dynamically -->
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">SLA Resolution Deadline *</label>
                    <input type="datetime-local" id="create-due-date" required class="form-input w-full px-4 py-2.5">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Issue Details / Description</label>
                    <textarea id="create-description" rows="4" class="form-input w-full px-4 py-2.5" placeholder="Describe the troubleshooting steps, error logs, and expected behaviors..."></textarea>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-800/60 mt-6">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white rounded-xl text-sm font-semibold transition-colors border border-gray-250 dark:border-gray-850 hover:bg-gray-100 dark:hover:bg-gray-900">Cancel</button>
                <button type="submit" class="btn-primary px-6 py-2.5 text-sm rounded-xl">Generate Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- Detailed View Modal Overlay -->
<div id="ticketDetailModal" class="fixed inset-0 bg-slate-900/60 dark:bg-[#070A13]/90 backdrop-blur-sm z-50 items-center justify-center p-4 hidden">
    <div class="card-3d bg-white dark:bg-[#0F172A] w-full max-w-5xl h-[85vh] flex flex-col overflow-hidden animate-fade-in p-0 border border-gray-200 dark:border-gray-800/80 rounded-2xl shadow-2xl">
        <!-- Top bar -->
        <div class="flex items-center justify-between p-6 border-b border-gray-205 dark:border-gray-800/60 bg-gray-50 dark:bg-gray-950/20 shrink-0">
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-cyan-500/10 border border-cyan-500/25 text-cyan-400 text-xs font-bold rounded-lg tracking-wider" id="detail-ticket-number">#TIC-1001</span>
                <span class="text-slate-800 dark:text-white font-bold text-base truncate max-w-[20rem] md:max-w-xl" id="detail-ticket-title">Loading Ticket...</span>
            </div>
            <button onclick="closeDetailModal()" class="text-gray-450 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>

        <!-- Body content columns split screen -->
        <div class="flex-1 flex flex-col md:flex-row overflow-hidden min-h-0">
            <!-- Left Pane: Info, Details, Editable Parameters -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-6 border-r border-gray-200 dark:border-gray-800/60 space-y-6">
                <!-- Status/Assignee fast adjustments -->
                <div class="grid grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-950/30 p-4 rounded-xl border border-gray-200 dark:border-gray-800/60">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Ticket Status</label>
                        <select id="detail-update-status" class="form-input w-full text-xs font-bold py-1.5 px-3">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Assign Agent</label>
                        <select id="detail-update-assignee" class="form-input w-full text-xs font-bold py-1.5 px-3">
                            <option value="">Unassigned</option>
                            <!-- Injected -->
                        </select>
                    </div>
                </div>

                <!-- Parameters list -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Created By</label>
                        <div class="flex items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff" id="detail-creator-avatar" class="w-6 h-6 rounded-full border border-gray-700">
                            <span class="text-xs text-gray-800 dark:text-white font-medium" id="detail-creator-name">Admin</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Priority</label>
                        <select id="detail-update-priority" class="form-input w-full text-xs py-1 px-2">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">SLA Resolution due</label>
                        <input type="datetime-local" id="detail-update-due" class="form-input w-full text-xs py-1 px-2">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">SLA Status</label>
                        <div id="detail-sla-status" class="inline-flex mt-1">
                            <span class="px-2 py-0.5 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase rounded-md">Calculating...</span>
                        </div>
                    </div>
                </div>

                <!-- Ticket details rich text -->
                <div class="space-y-2">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest border-b border-gray-200 dark:border-gray-800/80 pb-2">Description</label>
                    <div id="detail-description-container" class="space-y-3">
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line" id="detail-ticket-description">No details provided.</p>
                    </div>
                </div>
                <!-- Save Button and Notification Notice -->
                <div class="pt-4 border-t border-gray-200 dark:border-gray-800/60 flex items-center justify-between gap-3">
                    <span id="detail-save-indicator" class="text-xs font-semibold italic flex items-center gap-1.5 min-h-[1.5rem]"></span>
                    <button onclick="saveDetailedFields()" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-md hover:-translate-y-0.5 active:translate-y-0.5">
                        <i class="ph-bold ph-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </div>

            <!-- Right Pane: Collaborative Comments chat thread & Activity logs timeline -->
            <div class="flex-1 md:w-96 overflow-hidden flex flex-col bg-gray-50/50 dark:bg-gray-950/20">
                <!-- Tabs for switching Comments and Activity Logs -->
                <div class="flex border-b border-gray-200 dark:border-gray-800/60 bg-gray-100/80 dark:bg-gray-950/40 shrink-0">
                    <button onclick="switchDetailTab('comments')" id="detail-tab-comments" class="flex-1 py-3 text-center text-xs font-bold border-b-2 border-cyan-500 text-slate-800 dark:text-white flex items-center justify-center gap-2">
                        <i class="ph ph-chat-circle-dots text-sm"></i> Comments
                    </button>
                    <button onclick="switchDetailTab('activity')" id="detail-tab-activity" class="flex-1 py-3 text-center text-xs font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white flex items-center justify-center gap-2">
                        <i class="ph ph-clock text-sm"></i> Activity Timeline
                    </button>
                </div>

                <!-- Comments Thread section -->
                <div id="detail-comments-pane" class="flex-1 flex flex-col min-h-0 overflow-hidden">
                    <!-- Message list -->
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-4 min-h-0" id="detail-comments-list">
                        <!-- Loaded by JS -->
                    </div>

                    <!-- Send comment box -->
                    <div class="p-4 border-t border-gray-250 dark:border-gray-800/60 bg-white dark:bg-gray-950/40 shrink-0">
                        <form onsubmit="handleSendComment(event)" class="relative">
                            <input type="text" id="detail-comment-input" placeholder="Type a message, collaborate..." class="w-full pl-4 pr-12 py-2.5 bg-white dark:bg-gray-900 border border-gray-250 dark:border-gray-800 rounded-xl text-xs focus:outline-none focus:border-cyan-500 transition-colors text-gray-800 dark:text-white">
                            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-400 transition-all flex items-center justify-center">
                                <i class="ph-bold ph-paper-plane-tilt"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Activity logs Timeline section -->
                <div id="detail-activity-pane" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6 hidden min-h-0">
                    <!-- Timeline content loaded by JS -->
                    <div class="relative border-l border-gray-800 ml-3 space-y-6 py-2" id="detail-logs-list">
                        <!-- Loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simple KB Article Viewer Modal -->
<div id="kbArticleModal" class="fixed inset-0 bg-slate-900/60 dark:bg-[#070A13]/90 backdrop-blur-sm z-50 items-center justify-center p-4 hidden">
    <div class="card-3d bg-white dark:bg-[#0F172A] w-full max-w-2xl overflow-hidden animate-fade-in p-0 border border-gray-200 dark:border-gray-800/80 rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between p-6 border-b border-gray-205 dark:border-gray-800/60 bg-gray-50 dark:bg-gray-950/20">
            <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i class="ph-bold ph-book-open text-amber-400"></i> Knowledge Base Article
            </h3>
            <button onclick="closeKBArticleModal()" class="text-gray-450 hover:text-gray-850 dark:text-gray-400 dark:hover:text-white transition-colors">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4" id="kb-article-content">
            <!-- Article Body -->
        </div>
        <div class="p-6 border-t border-gray-250 dark:border-gray-800/60 bg-gray-50 dark:bg-gray-950/20 flex justify-end">
            <button onclick="closeKBArticleModal()" class="px-5 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-xl text-xs font-bold transition-all">Close</button>
        </div>
    </div>
</div>

<script>
    // Configuration states
    const activeUserId = <?php echo $current_user_id; ?>;
    const activeUserRole = "<?php echo $current_user_role; ?>";
    const initialSidebarView = "<?php echo $view; ?>";
    
    // Reactive State Management
    class ReactiveState {
        constructor(initialState) {
            this.state = new Proxy(initialState, {
                set: (target, property, value) => {
                    const oldValue = target[property];
                    target[property] = value;
                    if (oldValue !== value) {
                        this.notify(property, value);
                    }
                    return true;
                }
            });
            this.listeners = {};
        }

        subscribe(property, callback) {
            if (!this.listeners[property]) {
                this.listeners[property] = [];
            }
            this.listeners[property].push(callback);
        }

        notify(property, value) {
            if (this.listeners[property]) {
                this.listeners[property].forEach(callback => callback(value));
            }
        }
    }

    const appState = new ReactiveState({
        activeFilter: initialSidebarView === 'kb' ? 'all' : initialSidebarView,
        tickets: []
    });

    // Backwards-compatible activeView wrapper mapped to reactive state
    Object.defineProperty(window, 'activeView', {
        get() {
            return appState.state.activeFilter;
        },
        set(val) {
            appState.state.activeFilter = val;
        }
    });

    // Subscribe to filter state changes
    appState.subscribe('activeFilter', (value) => {
        updateQuickFilterActiveTab(value);
        renderTicketsTable();
        // Sync URL query state without reloading
        const cleanURL = window.location.protocol + "//" + window.location.host + window.location.pathname + `?view=${value}`;
        window.history.pushState({ path: cleanURL }, '', cleanURL);
    });

    // Subscribe to tickets list changes
    appState.subscribe('tickets', () => {
        renderTicketsTable();
    });

    let selectedTickets = new Set();
    let currentDetailTicketId = null;
    let activeDetailTab = 'comments';
    let usersList = [];
    let searchDebounceTimer = null;

    // SLA Countdown BADGE formatter logic
    function formatSLA(dueDateString, status, updatedAtString = null) {
        const now = new Date();
        const due = new Date(dueDateString);
        
        if (status === 'resolved' || status === 'closed') {
            const resolvedAt = updatedAtString ? new Date(updatedAtString) : now;
            if (resolvedAt > due) {
                return {
                    text: 'SLA Breached',
                    class: 'bg-rose-500/10 border border-rose-500/25 text-rose-400'
                };
            } else {
                return {
                    text: 'SLA Met',
                    class: 'bg-emerald-500/10 border border-emerald-500/25 text-emerald-400'
                };
            }
        }
        
        const differenceMs = due - now;

        if (differenceMs < 0) {
            // SLA breached
            const hoursPast = Math.abs(Math.floor(differenceMs / (1000 * 60 * 60)));
            const text = hoursPast > 24 
                ? `Breached ${Math.floor(hoursPast / 24)}d ago` 
                : `Breached ${hoursPast}h ago`;
            return {
                text: text,
                class: 'bg-rose-500/10 border border-rose-500/25 text-rose-400'
            };
        }

        const remainingHours = Math.floor(differenceMs / (1000 * 60 * 60));
        if (remainingHours <= 12) {
            // Yellow Warning (approaching breach <= 12h)
            const remainingMins = Math.floor((differenceMs % (1000 * 60 * 60)) / (1000 * 60));
            const text = remainingHours > 0 
                ? `${remainingHours}h ${remainingMins}m left`
                : `${remainingMins}m left`;
            return {
                text: text,
                class: 'bg-amber-500/10 border border-amber-500/25 text-amber-400'
            };
        }

        // Green safe
        const remainingDays = Math.floor(remainingHours / 24);
        const text = remainingDays > 0 
            ? `${remainingDays}d left`
            : `${remainingHours}h left`;
        return {
            text: text,
            class: 'bg-emerald-500/10 border border-emerald-500/25 text-emerald-400'
        };
    }

    // Toggle views (SPA switching for standard views and KB tab)
    function changeView(viewType) {
        if (viewType === 'kb') {
            document.getElementById('ticketing-dashboard-content').classList.add('hidden');
            document.getElementById('kb-dashboard-content').classList.remove('hidden');
            // Remove active states from standard filters
            updateQuickFilterActiveTab('');
        } else {
            document.getElementById('ticketing-dashboard-content').classList.remove('hidden');
            document.getElementById('kb-dashboard-content').classList.add('hidden');
            appState.state.activeFilter = viewType;
            loadTickets();
        }
        
        // Sync URL query state without reloading
        const cleanURL = window.location.protocol + "//" + window.location.host + window.location.pathname + `?view=${viewType}`;
        window.history.pushState({ path: cleanURL }, '', cleanURL);
    }

    // Sync CSS active classes for filter tabs
    function updateQuickFilterActiveTab(viewType) {
        const tabs = ['all', 'open', 'assigned', 'breached', 'closed'];
        tabs.forEach(t => {
            const btn = document.getElementById(`tab-${t}`);
            if (btn) {
                if (t === viewType) {
                    btn.classList.add('bg-cyan-500/20', 'text-cyan-400', 'border', 'border-cyan-500/20');
                    btn.classList.remove('text-gray-550', 'dark:text-gray-400');
                } else {
                    btn.classList.remove('bg-cyan-500/20', 'text-cyan-400', 'border', 'border-cyan-500/20');
                    btn.classList.add('text-gray-550', 'dark:text-gray-400');
                }
            }
        });
    }

    // Debounce search input
    function debounceSearch() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            loadTickets();
        }, 300);
    }

    // Render tickets list based on activeFilter
    function renderTicketsTable() {
        const container = document.getElementById('tickets-list-container');
        const emptyState = document.getElementById('tickets-empty-state');
        
        if (!container) return;

        const filter = appState.state.activeFilter;
        const tickets = appState.state.tickets;

        // Apply strict backend status value filtering
        const filteredTickets = tickets.filter(ticket => {
            if (filter === 'all') {
                return true;
            }
            if (filter === 'open') {
                // Mapping Open to "open" status strictly
                return ticket.status === 'open';
            }
            if (filter === 'assigned') {
                // Check if the ticket is assigned to the current user
                return parseInt(ticket.assigned_to) === activeUserId;
            }
            if (filter === 'breached') {
                // Checking SLA properties for Breached: past due date and not resolved/closed
                const now = new Date();
                const due = new Date(ticket.due_date);
                return due < now && ticket.status !== 'resolved' && ticket.status !== 'closed';
            }
            if (filter === 'closed') {
                // Mapping Closed to "resolved" or "closed"
                return ticket.status === 'resolved' || ticket.status === 'closed';
            }
            return true;
        });

        if (filteredTickets.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        let html = '';
        filteredTickets.forEach(ticket => {
            const sla = formatSLA(ticket.due_date, ticket.status, ticket.updated_at);
            const isSelected = selectedTickets.has(ticket.id);
            
            // Priority badges colors
            const priorityColors = {
                low: 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
                medium: 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                high: 'bg-orange-500/10 text-orange-450 border border-orange-500/20',
                critical: 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
            };
            const priorityClass = priorityColors[ticket.priority] || priorityColors.medium;

            // Status badges colors
            const statusColors = {
                open: 'bg-blue-600/15 text-blue-400 border border-blue-600/20',
                in_progress: 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20',
                resolved: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                closed: 'bg-gray-500/10 text-gray-400 border border-gray-800'
            };
            const statusClass = statusColors[ticket.status] || statusColors.open;

            // User photos
            const assignPhoto = ticket.assignee_photo && ticket.assignee_photo !== 'default.png'
                ? `uploads/profiles/${ticket.assignee_photo}`
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(ticket.assignee_name || 'Agent')}&background=06B6D4&color=fff`;
            
            const creatorPhoto = ticket.creator_photo && ticket.creator_photo !== 'default.png'
                ? `uploads/profiles/${ticket.creator_photo}`
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(ticket.creator_name || 'User')}&background=3B82F6&color=fff`;

            const priorityLabel = ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1);
            const statusLabel = ticket.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

            html += `
                <tr class="border-b border-gray-200 dark:border-gray-800/40 hover:bg-gray-150/50 dark:hover:bg-gray-900/10 transition-colors animate-fade-in ${ticket.status === 'closed' ? 'opacity-70' : ''}">
                    <td class="py-3 px-4">
                        <input type="checkbox" onchange="toggleSelectTicket(${ticket.id}, this)" ${isSelected ? 'checked' : ''} class="w-4 h-4 accent-cyan-500 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-cyan-500/20 cursor-pointer ticket-checkbox">
                    </td>
                    <td class="py-3 px-4 font-bold text-xs tracking-wider text-cyan-500 dark:text-cyan-400">
                        <button onclick="openDetailModal(${ticket.id})" class="hover:underline font-mono">${ticket.ticket_number}</button>
                    </td>
                    <td class="py-3 px-6">
                        <div class="font-semibold text-slate-800 dark:text-white cursor-pointer hover:text-cyan-500 dark:hover:text-cyan-400 transition-colors max-w-xs md:max-w-md truncate" onclick="openDetailModal(${ticket.id})">
                            ${ticket.title}
                        </div>
                    </td>
                    <td class="py-3 px-6">
                        <div class="flex items-center gap-2">
                            <img src="${creatorPhoto}" alt="${ticket.creator_name}" class="w-6 h-6 rounded-full border border-gray-200 dark:border-gray-800 object-cover">
                            <span class="text-xs text-slate-650 dark:text-gray-300 font-medium">${ticket.creator_name || 'User'}</span>
                        </div>
                    </td>
                    <td class="py-3 px-6">
                        <div class="flex items-center gap-2">
                            ${ticket.assigned_to 
                                ? `<img src="${assignPhoto}" alt="${ticket.assignee_name}" class="w-6 h-6 rounded-full border border-gray-200 dark:border-gray-800 object-cover">
                                   <span class="text-xs text-slate-855 dark:text-white font-medium">${ticket.assignee_name}</span>`
                                : `<span class="text-xs text-gray-500 italic">Queue Open</span>`
                            }
                        </div>
                    </td>
                    <td class="py-3 px-6">
                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-lg uppercase ${priorityClass}">${priorityLabel}</span>
                    </td>
                    <td class="py-3 px-6">
                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-lg uppercase ${statusClass}">${statusLabel}</span>
                    </td>
                    <td class="py-3 px-6 text-right font-medium">
                        <span class="px-2 py-0.5 text-[10px] font-mono rounded-lg uppercase ${sla.class}">${sla.text}</span>
                    </td>
                    <td class="py-3 px-6 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="openDetailModal(${ticket.id})" title="View Details" class="w-8 h-8 rounded-lg hover:bg-gray-150 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-all">
                                <i class="ph ph-eye text-lg"></i>
                            </button>
                            ${(activeUserRole === 'admin' || ticket.created_by == activeUserId || ticket.assigned_to == activeUserId) 
                                ? `<button onclick="deleteTicket(${ticket.id}, event)" title="Delete ticket" class="w-8 h-8 rounded-lg hover:bg-rose-500/10 text-gray-550 dark:text-gray-400 hover:text-rose-600 dark:hover:text-rose-455 flex items-center justify-center transition-all">
                                       <i class="ph ph-trash text-lg"></i>
                                   </button>` 
                                : ''
                            }
                        </div>
                    </td>
                </tr>
            `;
        });
        container.innerHTML = html;
    }

    // Load tickets list from API
    async function loadTickets() {
        const searchInput = document.getElementById('ticket-search').value;
        const sortBy = document.getElementById('ticket-sort-by').value;
        const sortOrder = document.getElementById('ticket-sort-order').value;

        updateQuickFilterActiveTab(appState.state.activeFilter);
        
        const container = document.getElementById('tickets-list-container');
        const emptyState = document.getElementById('tickets-empty-state');
        
        container.innerHTML = `
            <tr>
                <td colspan="9" class="py-12 text-center text-gray-500 italic">
                    <div class="flex items-center justify-center gap-2">
                        <i class="ph ph-circle-notch text-xl animate-spin text-cyan-400"></i> Loading tickets...
                    </div>
                </td>
            </tr>`;
        emptyState.classList.add('hidden');

        // We fetch ALL tickets from the backend to do strict client-side filtering on the ticket array
        const endpoint = `api/tickets.php?filter_view=all&search=${encodeURIComponent(searchInput)}&sort_by=${sortBy}&sort_order=${sortOrder}`;
        const response = await fetchAPI(endpoint);

        if (response.success && response.data) {
            // Cache users globally for selectors
            usersList = response.users || [];
            populateUserDropdowns();

            // Populate KPI Stats
            updateKPIStats(response.stats);

            // Store in reactive state to trigger renderTicketsTable
            appState.state.tickets = response.data;
        } else {
            container.innerHTML = `
                <tr>
                    <td colspan="9" class="py-8 text-center text-rose-500">
                        Error retrieving tickets: ${response.message}
                    </td>
                </tr>`;
        }
    }

    // Populate Users inside all Dropdowns
    function populateUserDropdowns() {
        const createSelect = document.getElementById('create-assignee');
        const detailSelect = document.getElementById('detail-update-assignee');
        const bulkSelect = document.getElementById('bulk-action-assignee');

        const originalCreateVal = createSelect.value;
        const originalDetailVal = detailSelect.value;
        const originalBulkVal = bulkSelect.value;

        let optionsHTML = '<option value="">Unassigned</option>';
        usersList.forEach(u => {
            optionsHTML += `<option value="${u.id}">${u.username}</option>`;
        });

        // Strip the first '<option value="">Unassigned</option>' (36 chars) when prepending custom options
        createSelect.innerHTML = `<option value="">Unassigned (Open Queue)</option>` + optionsHTML.substring(36);
        detailSelect.innerHTML = optionsHTML;
        bulkSelect.innerHTML = `<option value="no_change">Assign To...</option><option value="">Unassign</option>` + optionsHTML.substring(36);

        createSelect.value = originalCreateVal;
        detailSelect.value = originalDetailVal;
        bulkSelect.value = originalBulkVal;
    }

    // Sync Stats numbers
    function updateKPIStats(stats) {
        if (!stats) return;
        document.getElementById('stat-total').innerText = stats.total || 0;
        document.getElementById('stat-open').innerText = stats.open || 0;
        document.getElementById('stat-critical').innerText = stats.critical || 0;
        document.getElementById('stat-breached').innerText = stats.breached || 0;
    }

    // Checkbox lists toggling
    function toggleSelectTicket(id, element) {
        if (element.checked) {
            selectedTickets.add(id);
        } else {
            selectedTickets.delete(id);
        }
        updateBulkMenuBar();
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('select-all-tickets').checked;
        const checkboxes = document.querySelectorAll('.ticket-checkbox');
        
        checkboxes.forEach(chk => {
            chk.checked = selectAll;
        });

        if (selectAll) {
            // Query matches all rows that have active checkboxes
            const rows = document.querySelectorAll('#tickets-list-container tr');
            rows.forEach((row, i) => {
                const chk = row.querySelector('.ticket-checkbox');
                if (chk) {
                    // Extract ID from onClick or from detailed action id
                    const match = row.innerHTML.match(/openDetailModal\((\d+)\)/);
                    if (match && match[1]) {
                        selectedTickets.add(parseInt(match[1]));
                    }
                }
            });
        } else {
            selectedTickets.clear();
        }
        updateBulkMenuBar();
    }

    function clearBulkSelection() {
        selectedTickets.clear();
        const chks = document.querySelectorAll('.ticket-checkbox');
        chks.forEach(c => c.checked = false);
        const allChk = document.getElementById('select-all-tickets');
        if (allChk) allChk.checked = false;
        updateBulkMenuBar();
    }

    function updateBulkMenuBar() {
        const bar = document.getElementById('bulk-action-bar');
        const count = document.getElementById('bulk-select-count');
        if (selectedTickets.size > 0) {
            bar.classList.remove('hidden');
            count.innerText = `${selectedTickets.size} ticket${selectedTickets.size > 1 ? 's' : ''} selected`;
        } else {
            bar.classList.add('hidden');
        }
    }

    // Apply Bulk actions API
    async function applyBulkActions() {
        const status = document.getElementById('bulk-action-status').value;
        const assignee = document.getElementById('bulk-action-assignee').value;

        if (status === '' && assignee === 'no_change') {
            alert('Please select a Status or Assignee to modify.');
            return;
        }

        const data = {
            ticket_ids: Array.from(selectedTickets),
            bulk_status: status,
            bulk_assigned_to: assignee
        };

        const result = await fetchAPI('api/tickets.php?action=bulk', 'POST', data);
        if (result.success) {
            clearBulkSelection();
            loadTickets();
            playCompletionSound();
        } else {
            alert('Bulk update error: ' + result.message);
        }
    }

    // Apply Bulk Delete tickets API
    async function applyBulkDeleteTickets() {
        if (selectedTickets.size === 0) return;
        if (confirm(`Are you sure you want to permanently delete the ${selectedTickets.size} selected ticket(s)? This cannot be undone.`)) {
            const result = await fetchAPI('api/tickets.php', 'DELETE', { ids: Array.from(selectedTickets) });
            if (result.success) {
                clearBulkSelection();
                loadTickets();
                playCompletionSound();
            } else {
                alert('Bulk deletion error: ' + result.message);
            }
        }
    }

    // Delete a ticket with confirm Prompt
    async function deleteTicket(id, event) {
        if (event) event.stopPropagation();
        if (confirm('Are you sure you want to permanently delete this ticket? This cannot be undone.')) {
            const result = await fetchAPI('api/tickets.php', 'DELETE', { id: id });
            if (result.success) {
                loadTickets();
            } else {
                alert('Deletion error: ' + result.message);
            }
        }
    }

    // Create Modal triggers safely with standard local time components
    function openCreateModal() {
        const modal = document.getElementById('ticketCreateModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Set default due date to 24 hours from now safely in local format
        const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);
        const year = tomorrow.getFullYear();
        const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const day = String(tomorrow.getDate()).padStart(2, '0');
        const hours = String(tomorrow.getHours()).padStart(2, '0');
        const minutes = String(tomorrow.getMinutes()).padStart(2, '0');
        document.getElementById('create-due-date').value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    function closeCreateModal() {
        const modal = document.getElementById('ticketCreateModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.getElementById('createTicketForm').reset();
    }

    // Handle Create ticket form submit
    async function handleCreateTicket(e) {
        e.preventDefault();
        const data = {
            title: document.getElementById('create-title').value,
            priority: document.getElementById('create-priority').value,
            assigned_to: document.getElementById('create-assignee').value,
            due_date: document.getElementById('create-due-date').value,
            description: document.getElementById('create-description').value
        };

        const result = await fetchAPI('api/tickets.php', 'POST', data);
        if (result.success) {
            closeCreateModal();
            loadTickets();
            playCompletionSound();
        } else {
            alert('Error generating ticket: ' + result.message);
        }
    }

    // Detail Modal triggers & switching tabs
    async function openDetailModal(id) {
        currentDetailTicketId = id;
        const modal = document.getElementById('ticketDetailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        switchDetailTab('comments');

        // Fetch detailed data from API
        const response = await fetchAPI(`api/tickets.php?ticket_details_id=${id}`);
        if (response.success && response.ticket) {
            const ticket = response.ticket;
            
            document.getElementById('detail-ticket-number').innerText = ticket.ticket_number;
            document.getElementById('detail-ticket-title').innerText = ticket.title;
            document.getElementById('detail-ticket-description').innerText = ticket.description || 'No description provided.';
            
            // Set update drop downs values (prevent onchange triggering instantly during mapping)
            document.getElementById('detail-update-status').onchange = null;
            document.getElementById('detail-update-assignee').onchange = null;
            document.getElementById('detail-update-priority').onchange = null;
            document.getElementById('detail-update-due').onchange = null;

            document.getElementById('detail-update-status').value = ticket.status;
            document.getElementById('detail-update-assignee').value = ticket.assigned_to || '';
            document.getElementById('detail-update-priority').value = ticket.priority;
            document.getElementById('detail-update-due').value = ticket.due_date.replace(' ', 'T');

            // Set creator information
            document.getElementById('detail-creator-name').innerText = ticket.creator_name || 'User';
            const creatorAvatar = ticket.creator_photo && ticket.creator_photo !== 'default.png'
                ? `uploads/profiles/${ticket.creator_photo}`
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(ticket.creator_name || 'User')}&background=3B82F6&color=fff`;
            document.getElementById('detail-creator-avatar').src = creatorAvatar;

            // SLA Badges mapping
            const sla = formatSLA(ticket.due_date, ticket.status, ticket.updated_at);
            document.getElementById('detail-sla-status').innerHTML = `<span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md ${sla.class}">${sla.text}</span>`;

            // Comments mapping
            renderComments(response.comments);

            // Activity timeline mapping
            renderLogs(response.logs);
        } else {
            alert('Error loading ticket details: ' + response.message);
            closeDetailModal();
        }
    }

    function closeDetailModal() {
        const modal = document.getElementById('ticketDetailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentDetailTicketId = null;
    }

    function switchDetailTab(tab) {
        activeDetailTab = tab;
        const commentsBtn = document.getElementById('detail-tab-comments');
        const activityBtn = document.getElementById('detail-tab-activity');
        const commentsPane = document.getElementById('detail-comments-pane');
        const activityPane = document.getElementById('detail-activity-pane');

        if (tab === 'comments') {
            commentsBtn.classList.add('border-cyan-500', 'text-slate-800', 'dark:text-white');
            commentsBtn.classList.remove('border-transparent', 'text-gray-500');
            activityBtn.classList.add('border-transparent', 'text-gray-500');
            activityBtn.classList.remove('border-cyan-500', 'text-slate-800', 'dark:text-white');
            
            commentsPane.classList.remove('hidden');
            activityPane.classList.add('hidden');
        } else {
            activityBtn.classList.add('border-cyan-500', 'text-slate-800', 'dark:text-white');
            activityBtn.classList.remove('border-transparent', 'text-gray-500');
            commentsBtn.classList.add('border-transparent', 'text-gray-500');
            commentsBtn.classList.remove('border-cyan-500', 'text-slate-800', 'dark:text-white');

            activityPane.classList.remove('hidden');
            commentsPane.classList.add('hidden');
        }
    }

    // Save all fields from the detailed view modal
    async function saveDetailedFields() {
        if (!currentDetailTicketId) return;

        const status = document.getElementById('detail-update-status').value;
        const assigned_to = document.getElementById('detail-update-assignee').value;
        const priority = document.getElementById('detail-update-priority').value;
        const due_date = document.getElementById('detail-update-due').value.replace('T', ' ');

        const saveIndicator = document.getElementById('detail-save-indicator');
        saveIndicator.innerHTML = `<i class="ph ph-circle-notch animate-spin text-cyan-400"></i> Saving...`;

        const data = {
            id: currentDetailTicketId,
            status: status,
            assigned_to: assigned_to,
            priority: priority,
            due_date: due_date
        };

        const response = await fetchAPI('api/tickets.php', 'PUT', data);
        if (response.success) {
            saveIndicator.innerHTML = `<span class="text-emerald-455 text-emerald-400 flex items-center gap-1"><i class="ph-bold ph-check"></i> Saved!</span>`;
            playCompletionSound();
            
            // Reload page list and updates the details modal state
            loadTickets();
            
            // Refresh detailed view parameters & logs
            const detailResponse = await fetchAPI(`api/tickets.php?ticket_details_id=${currentDetailTicketId}`);
            if (detailResponse.success) {
                // Update SLA label with newly returned updated_at
                const sla = formatSLA(detailResponse.ticket.due_date, detailResponse.ticket.status, detailResponse.ticket.updated_at);
                document.getElementById('detail-sla-status').innerHTML = `<span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md ${sla.class}">${sla.text}</span>`;
                
                // Redraw logs
                renderLogs(detailResponse.logs);
            }
            
            // Clear message after 2 seconds
            setTimeout(() => {
                if (saveIndicator.innerText.includes('Saved')) {
                    saveIndicator.innerText = '';
                }
            }, 2000);
        } else {
            saveIndicator.innerHTML = `<span class="text-rose-455 text-rose-400"><i class="ph ph-warning"></i> Error: ${response.message}</span>`;
        }
    }

    // Render comments list
    function renderComments(comments) {
        const container = document.getElementById('detail-comments-list');
        if (!comments || comments.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center p-8 text-center text-gray-500">
                    <i class="ph ph-chat-circle-dots text-3xl mb-2 opacity-40"></i>
                    <p class="text-xs">No comments yet. Collaborate to solve this issue!</p>
                </div>`;
            return;
        }

        let html = '';
        comments.forEach(c => {
            const time = new Date(c.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            const date = new Date(c.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            
            const isMe = parseInt(c.user_id) === activeUserId;
            
            const avatar = c.comment_user_photo && c.comment_user_photo !== 'default.png'
                ? `uploads/profiles/${c.comment_user_photo}`
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(c.comment_user)}&background=${isMe ? '3B82F6' : '06B6D4'}&color=fff`;

            html += `
                <div class="flex gap-3 animate-fade-in ${isMe ? 'flex-row-reverse' : ''}">
                    <img src="${avatar}" alt="${c.comment_user}" class="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-800 object-cover shrink-0">
                    <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} max-w-[75%] space-y-1">
                        <div class="flex items-center gap-2 text-[10px] text-gray-550">
                            <span class="font-bold text-slate-700 dark:text-gray-400">${c.comment_user}</span>
                            <span>&bull;</span>
                            <span>${date} ${time}</span>
                        </div>
                        <div class="p-3 text-xs rounded-2xl ${isMe ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-tr-none' : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-850 text-gray-800 dark:text-gray-200 rounded-tl-none'} shadow-sm">
                            ${c.comment}
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
        // Auto scroll to bottom
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 50);
    }

    // Render activity log
    function renderLogs(logs) {
        const container = document.getElementById('detail-logs-list');
        if (!logs || logs.length === 0) {
            container.innerHTML = `<p class="text-xs text-gray-500 italic p-6 text-center">No activity logged.</p>`;
            return;
        }

        let html = '';
        logs.forEach(l => {
            const time = new Date(l.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            const date = new Date(l.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            
            // Choose nice icons depending on activity keywords
            let icon = 'ph-info';
            let iconColor = 'text-gray-400';
            if (l.action.toLowerCase().includes('created')) {
                icon = 'ph-plus-circle';
                iconColor = 'text-cyan-400';
            } else if (l.action.toLowerCase().includes('status')) {
                icon = 'ph-pulse';
                iconColor = 'text-blue-400';
            } else if (l.action.toLowerCase().includes('assigned')) {
                icon = 'ph-user-focus';
                iconColor = 'text-purple-400';
            } else if (l.action.toLowerCase().includes('comment')) {
                icon = 'ph-chat-circle';
                iconColor = 'text-amber-400';
            }

            html += `
                <div class="relative pl-6 animate-fade-in">
                    <!-- Timeline Dot with dynamic icon -->
                    <div class="absolute -left-3.5 top-0.5 w-7 h-7 rounded-full bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 flex items-center justify-center z-10">
                        <i class="ph-bold ${icon} ${iconColor} text-sm"></i>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs text-slate-800 dark:text-white font-medium">${l.action}</p>
                        <p class="text-[10px] text-gray-500">${l.action_user} &bull; ${date} ${time}</p>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // Send a Comment API
    async function handleSendComment(e) {
        e.preventDefault();
        const input = document.getElementById('detail-comment-input');
        const text = input.value.trim();

        if (text === '' || !currentDetailTicketId) return;

        const data = {
            ticket_id: currentDetailTicketId,
            comment: text
        };

        const response = await fetchAPI('api/tickets.php?action=comment', 'POST', data);
        if (response.success && response.data) {
            input.value = '';
            
            // Re-fetch ticket details completely to update everything and play satisfying sound
            const detailResponse = await fetchAPI(`api/tickets.php?ticket_details_id=${currentDetailTicketId}`);
            if (detailResponse.success) {
                renderComments(detailResponse.comments);
                renderLogs(detailResponse.logs);
                playCompletionSound();
            }
        } else {
            alert('Error adding comment: ' + response.message);
        }
    }

    // Filter articles in Knowledge Base
    function filterKB() {
        const query = document.getElementById('kb-search').value.toLowerCase();
        const cards = document.querySelectorAll('.kb-article-card');
        
        cards.forEach(card => {
            const indexString = card.getAttribute('data-title');
            if (indexString.includes(query)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    // Static structures of KB content
    const kbArticlesData = {
        1: {
            title: 'How to Reset User Passwords',
            category: 'Administration',
            readTime: '2 mins',
            content: `
                <p class="text-sm text-slate-705 dark:text-gray-300 leading-relaxed">Administrators can easily reset users' passwords via the User Directory panel within TaskFlow Pro. Follow these sequential instructions:</p>
                <ol class="list-decimal pl-5 text-sm text-slate-600 dark:text-gray-400 space-y-2 mt-3">
                    <li>Navigate to the <span class="text-slate-800 dark:text-white font-semibold">User Directory</span> from the Administrative sidebar group.</li>
                    <li>Locate the target user account and click the edit/action trigger on the right side of their directory row.</li>
                    <li>Enter a robust, secure password meeting security compliance guidelines (minimum 8 characters, containing capital letters, numbers, and symbols).</li>
                    <li>Click <span class="text-cyan-505 dark:text-cyan-400 font-semibold">Update User</span> to save modifications and automatically validate password hashing checks.</li>
                </ol>
                <div class="mt-4 p-4 bg-blue-50/50 dark:bg-blue-955/20 border border-blue-200 dark:border-blue-900/30 rounded-xl">
                    <p class="text-xs text-blue-600 dark:text-blue-400 font-medium flex items-center gap-1.5"><i class="ph-bold ph-info"></i> Note: Workspace users can reset their passwords autonomously using the 'Forgot Password' recovery triggers on the login interface.</p>
                </div>
            `
        },
        2: {
            title: 'Understanding SLA Response Thresholds',
            category: 'Compliance',
            readTime: '3 mins',
            content: `
                <p class="text-sm text-slate-705 dark:text-gray-300 leading-relaxed">Helpdesk SLA Monitoring helps organizations measure team response velocity and prevent critical customer delays. Resolution deadlines are color-coded in real-time:</p>
                <ul class="list-disc pl-5 text-sm text-slate-600 dark:text-gray-400 space-y-3 mt-3">
                    <li><span class="text-emerald-600 dark:text-emerald-400 font-bold">Green Badge (Safe)</span>: The ticket's resolution deadline is safe (remaining time exceeds 12 hours).</li>
                    <li><span class="text-amber-600 dark:text-amber-400 font-bold">Yellow Badge (Near Breach)</span>: Remaining resolution time is approaching critical thresholds (12 hours or less). Prompt attention is highly recommended!</li>
                    <li><span class="text-rose-600 dark:text-rose-400 font-bold">Red Badge (Breached)</span>: The ticket's resolution deadline has expired. Urgent action must be executed to remediate breached tickets immediately.</li>
                </ul>
                <p class="text-sm text-slate-600 dark:text-gray-400 mt-4 leading-relaxed">SLA targets are generated automatically based on the Due Dates configured upon ticket generation.</p>
            `
        },
        3: {
            title: 'Creating and Managing Task Boards',
            category: 'Workflows',
            readTime: '4 mins',
            content: `
                <p class="text-sm text-slate-705 dark:text-gray-300 leading-relaxed">TaskFlow Pro combines Kanban boards and ticket queues to streamline operations. Master task management using these guidelines:</p>
                <ul class="list-disc pl-5 text-sm text-slate-600 dark:text-gray-400 space-y-2 mt-3">
                    <li>Assign clear ownership by designating team members as assignees on task cards.</li>
                    <li>Map tasks to distinct <span class="text-slate-800 dark:text-white font-semibold">Projects</span> to maintain cohesive reporting and progression tracking metrics.</li>
                    <li>Set correct priority tags (Low, Medium, High, Urgent) to reflect true work weight.</li>
                    <li>Use the board interface to drag tasks dynamically across progression lanes (Todo, In Progress, Completed).</li>
                </ul>
                <div class="mt-4 p-4 bg-emerald-50/50 dark:bg-emerald-955/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl">
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1.5"><i class="ph-bold ph-check-circle"></i> Tip: Completing tasks triggers an elegant success audio tone to keep workspace interactions rewarding!</p>
                </div>
            `
        },
        4: {
            title: 'Restricting Helpdesk Ticket Deletions',
            category: 'Security',
            readTime: '1 min',
            content: `
                <p class="text-sm text-slate-705 dark:text-gray-300 leading-relaxed">Maintaining robust, tamper-proof audit trails is essential for enterprise compliance. TaskFlow Pro safeguards helpdesk archives by implementing strict authorization boundaries:</p>
                <p class="text-sm text-slate-600 dark:text-gray-400 mt-2 leading-relaxed">Only users assigned with <span class="text-slate-800 dark:text-white font-semibold">Administrator Role</span> privileges can perform hard deletions on support tickets. Managers and employee agents are permitted to modify tickets and mark them as resolved or closed, but they are blocked from deleting items to prevent history disruption.</p>
                <p class="text-sm text-slate-600 dark:text-gray-400 mt-2 leading-relaxed">To audit ticket history changes, navigate to the <span class="text-slate-800 dark:text-white font-semibold">Activity Timeline</span> pane inside the detailed view of each ticket.</p>
            `
        }
    };

    function openKBArticle(id) {
        const article = kbArticlesData[id];
        if (!article) return;

        const container = document.getElementById('kb-article-content');
        container.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-0.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[10px] font-bold uppercase rounded-md">${article.category}</span>
                    <span class="text-[10px] text-gray-500">Read time: ${article.readTime}</span>
                </div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-white tracking-tight">${article.title}</h2>
                <div class="border-t border-gray-200 dark:border-gray-800/80 pt-4">
                    ${article.content}
                </div>
            </div>
        `;
        const modal = document.getElementById('kbArticleModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeKBArticleModal() {
        const modal = document.getElementById('kbArticleModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Initial setups on window loading
    document.addEventListener('DOMContentLoaded', () => {
        // Switch between standard filter view or KB view depending on query params
        if (initialSidebarView === 'kb') {
            changeView('kb');
        } else {
            changeView(activeView);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
