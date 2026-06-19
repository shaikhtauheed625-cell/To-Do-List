<?php
$page_title = 'User Management';
require_once 'config/config.php';

// Strictly restrict access to Administrator role
if (!isLoggedIn()) {
    redirect('/login.php');
}
if (!isAdmin()) {
    redirect('/index.php');
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] text-slate-800 dark:text-gray-200 transition-colors">
    <!-- Top Header -->
    <header class="h-20 bg-white dark:bg-gray-900 border-b border-gray-250 dark:border-gray-800/80 flex items-center justify-between px-6 shrink-0 z-10 transition-colors">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <h1 class="text-xl font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent flex items-center gap-2">
                <i class="ph ph-users text-2xl text-cyan-400"></i> User Directory
            </h1>
        </div>

        <div class="flex items-center gap-4">
            <button onclick="openAddUserModal()" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white rounded-xl font-medium transition-all flex items-center gap-2 shadow-[0_0_15px_rgba(6,182,212,0.4)] hover-lift">
                <i class="ph ph-user-plus-bold"></i> Add User
            </button>
        </div>
    </header>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8 scroll-smooth custom-scrollbar">
        <div class="max-w-[1600px] mx-auto space-y-8">
            
            <!-- Statistics Widgets -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 animate-fade-in">
                <!-- Total -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift border border-gray-250 dark:border-gray-800/80">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center border border-blue-500/20">
                            <i class="ph-bold ph-users text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Users</p>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-total">0</h3>
                        </div>
                    </div>
                </div>
                <!-- Admins -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift border border-gray-250 dark:border-gray-800/80">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-cyan-500/10 rounded-full blur-2xl group-hover:bg-cyan-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center border border-cyan-500/20">
                            <i class="ph-bold ph-shield-check text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Admins</p>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-admins">0</h3>
                        </div>
                    </div>
                </div>
                <!-- Managers -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift border border-gray-250 dark:border-gray-800/80">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center border border-purple-500/20">
                            <i class="ph-bold ph-user-gear text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Managers</p>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-managers">0</h3>
                        </div>
                    </div>
                </div>
                <!-- Employees -->
                <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group hover-lift border border-gray-250 dark:border-gray-800/80">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center border border-emerald-500/20">
                            <i class="ph-bold ph-briefcase text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Employees</p>
                            <h3 class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-employees">0</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Directory Card -->
            <div class="glass-panel rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden animate-fade-in" style="animation-delay: 0.15s;">
                <!-- Table Toolbar -->
                <div class="p-6 border-b border-gray-250 dark:border-gray-800 flex flex-col sm:flex-row gap-4 items-center justify-between bg-white/40 dark:bg-gray-900/40">
                    <div class="relative w-full sm:w-80 group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="ph ph-magnifying-glass text-gray-500 group-focus-within:text-cyan-400 transition-colors text-base"></i>
                        </div>
                        <input type="text" id="searchUser" oninput="filterUsers()" placeholder="Search users by name, email, phone..." class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-950/60 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white placeholder-gray-450 dark:placeholder-gray-500 transition-all outline-none">
                    </div>
                    
                    <div class="flex gap-2 w-full sm:w-auto shrink-0 justify-end">
                        <select id="roleFilter" onchange="filterUsers()" class="px-3.5 py-2 bg-white dark:bg-gray-950/60 border border-gray-200 dark:border-gray-800 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none">
                            <option value="">All Roles</option>
                            <option value="admin">Administrators</option>
                            <option value="manager">Managers</option>
                            <option value="employee">Employees</option>
                        </select>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 dark:bg-gray-950/50 border-b border-gray-250 dark:border-gray-800 text-[10px] uppercase font-bold tracking-wider text-gray-500">
                                <th class="p-4.5">User Identity</th>
                                <th class="p-4.5">Role</th>
                                <th class="p-4.5">WhatsApp / Phone</th>
                                <th class="p-4.5 text-center">Activity Metrics</th>
                                <th class="p-4.5">Joined Date</th>
                                <th class="p-4.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800/80" id="userList">
                            <!-- Populated dynamically via JS -->
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <i class="ph ph-spinner text-3xl animate-spin text-cyan-400 mb-2"></i>
                                        <p class="text-xs">Loading user directory...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- ADD USER MODAL -->
<div id="addUserModal" class="fixed inset-0 bg-gray-950/85 dark:bg-gray-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-5 border-b border-gray-250 dark:border-gray-800 shrink-0 bg-gray-50 dark:bg-gray-950/40">
            <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2"><i class="ph ph-user-plus text-cyan-400 text-lg"></i> Create User Account</h3>
            <button onclick="closeAddUserModal()" class="text-gray-450 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        
        <form onsubmit="handleAddUser(event)" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">Full Name *</label>
                    <input type="text" id="addUsername" required class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none" placeholder="e.g. John Doe">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">Email Address *</label>
                    <input type="email" id="addEmail" required class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none" placeholder="e.g. john@taskflow.pro">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">WhatsApp / Phone</label>
                    <input type="text" id="addPhone" class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none" placeholder="e.g. +15551234567">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">Role Permission</label>
                        <select id="addRole" class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-850 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none">
                            <option value="employee" selected>Employee</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">Password *</label>
                        <input type="password" id="addPassword" required class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none" placeholder="••••••••">
                    </div>
                </div>
            </div>
            
            <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 bg-gray-50 dark:bg-gray-950/30 shrink-0">
                <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 border border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-850 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white rounded-xl text-xs font-bold transition-all">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white rounded-xl text-xs font-bold transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)]">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div id="editUserModal" class="fixed inset-0 bg-gray-950/85 dark:bg-gray-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-5 border-b border-gray-255 dark:border-gray-800 shrink-0 bg-gray-50 dark:bg-gray-950/40">
            <h3 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2"><i class="ph ph-pencil-simple text-cyan-400 text-lg"></i> Update User Profile</h3>
            <button onclick="closeEditUserModal()" class="text-gray-455 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition-colors">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        
        <form onsubmit="handleEditUser(event)" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" id="editUserId">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">Full Name *</label>
                    <input type="text" id="editUsername" required class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none">
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">Email Address *</label>
                    <input type="email" id="editEmail" required class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">WhatsApp / Phone</label>
                    <input type="text" id="editPhone" class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none" placeholder="e.g. +15551234567">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">Role Permission</label>
                        <select id="editRole" class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-850 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none">
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-gray-400 mb-1.5">New Password (Optional)</label>
                        <input type="password" id="editPassword" class="w-full px-3.5 py-2.5 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm text-gray-800 dark:text-white outline-none" placeholder="Leave blank to keep same">
                    </div>
                </div>
            </div>
            
            <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 bg-gray-50 dark:bg-gray-950/30 shrink-0">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 border border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-850 text-gray-550 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white rounded-xl text-xs font-bold transition-all">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white rounded-xl text-xs font-bold transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)]">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    let allUsers = [];
    const activeAdminId = <?php echo $_SESSION['user_id']; ?>;

    // Toast Notification System helper
    function showAdminToast(message, type = 'success') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed bottom-5 right-5 z-[60] flex flex-col gap-3';
            document.body.appendChild(container);
        }
        let toast = document.createElement('div');
        toast.className = `px-4.5 py-3 rounded-xl shadow-xl flex items-center gap-2.5 animate-slide-in border bg-gray-900 border-gray-800 text-sm font-semibold text-white`;
        let icon = document.createElement('i');
        if (type === 'success') {
            icon.className = 'ph ph-check-circle text-cyan-400 text-lg';
            toast.style.boxShadow = '0 10px 30px -10px rgba(6, 182, 212, 0.15)';
        } else {
            icon.className = 'ph ph-warning-circle text-rose-500 text-lg';
            toast.style.boxShadow = '0 10px 30px -10px rgba(244, 63, 94, 0.15)';
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

    // Modal Control functions
    function openAddUserModal() {
        document.getElementById('addUserModal').classList.remove('hidden');
    }
    function closeAddUserModal() {
        document.getElementById('addUserModal').classList.add('hidden');
        document.getElementById('addUsername').value = '';
        document.getElementById('addEmail').value = '';
        document.getElementById('addPhone').value = '';
        document.getElementById('addPassword').value = '';
        document.getElementById('addRole').value = 'employee';
    }
    function openEditUserModal(userJson) {
        const user = JSON.parse(decodeURIComponent(userJson));
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editPhone').value = user.phone || '';
        document.getElementById('editRole').value = user.role;
        document.getElementById('editPassword').value = '';
        document.getElementById('editUserModal').classList.remove('hidden');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    // Fetch and Load Directory
    async function loadUserDirectory() {
        const userList = document.getElementById('userList');
        try {
            const response = await fetchAPI('api/users.php', 'GET');
            if (response.success && response.data) {
                allUsers = response.data;
                renderUserTable(allUsers);
                updateStats(allUsers);
            } else {
                userList.innerHTML = `
                    <tr>
                        <td colspan="6" class="p-8 text-center text-rose-500">
                            <i class="ph ph-warning-circle text-3xl mb-2"></i>
                            <p class="text-xs">Failed to fetch user directory: ${response.message}</p>
                        </td>
                    </tr>
                `;
            }
        } catch (e) {
            userList.innerHTML = `
                <tr>
                    <td colspan="6" class="p-8 text-center text-rose-500">
                        <i class="ph ph-warning-circle text-3xl mb-2"></i>
                        <p class="text-xs">Network Connection Error. Please refresh.</p>
                    </td>
                </tr>
            `;
        }
    }

    function renderUserTable(users) {
        const userList = document.getElementById('userList');
        if (users.length === 0) {
            userList.innerHTML = `
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-500">
                        <i class="ph ph-smiley-blank text-3xl mb-2 text-gray-600"></i>
                        <p class="text-xs">No registered users matching filters.</p>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        users.forEach(u => {
            const isSelf = u.id === activeAdminId;
            const selfBadge = isSelf ? ' <span class="text-[9px] bg-blue-500/20 text-blue-400 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider shrink-0 border border-blue-500/30">You</span>' : '';
            
            // Generate elegant badges for roles
            let roleBadge = '';
            if (u.role === 'admin') {
                roleBadge = '<span class="inline-flex items-center gap-1 text-[9px] bg-cyan-500/10 text-cyan-400 font-bold border border-cyan-500/20 px-2 py-0.5 rounded-full uppercase tracking-wide shrink-0"><i class="ph-bold ph-shield-check"></i> Admin</span>';
            } else if (u.role === 'manager') {
                roleBadge = '<span class="inline-flex items-center gap-1 text-[9px] bg-purple-500/10 text-purple-400 font-bold border border-purple-500/20 px-2 py-0.5 rounded-full uppercase tracking-wide shrink-0"><i class="ph-bold ph-user-gear"></i> Manager</span>';
            } else {
                roleBadge = '<span class="inline-flex items-center gap-1 text-[9px] bg-gray-500/10 text-gray-400 font-bold border border-gray-500/20 px-2 py-0.5 rounded-full uppercase tracking-wide shrink-0"><i class="ph-bold ph-briefcase"></i> Employee</span>';
            }

            // Encoded string to pass into modal safely
            const escapedJson = encodeURIComponent(JSON.stringify(u));
            const formattedDate = new Date(u.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            html += `
                <tr class="hover:bg-gray-150 dark:hover:bg-gray-800/20 transition-colors group">
                    <td class="p-4.5">
                        <div class="flex items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(u.username)}&background=1e293b&color=2563eb&size=48" class="w-9 h-9 rounded-full border border-gray-800 shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">${u.username}</p>
                                    ${selfBadge}
                                </div>
                                <p class="text-[10px] text-gray-500 truncate mt-0.5">${u.email}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-4.5">${roleBadge}</td>
                    <td class="p-4.5 text-xs text-gray-300">
                        ${u.phone ? `
                            <a href="https://wa.me/${u.phone.replace(/[^0-9]/g, '')}" target="_blank" class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold px-2 py-0.5 rounded text-[10px] hover:scale-105 transition-transform" title="Message via WhatsApp">
                                <i class="ph-bold ph-whatsapp-logo text-xs"></i> ${u.phone}
                            </a>
                        ` : '<span class="text-gray-600 italic text-[11px]">- no contact -</span>'}
                    </td>
                    <td class="p-4.5 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <span class="text-[10px] font-semibold text-gray-650 dark:text-gray-400 bg-gray-50 dark:bg-gray-950/60 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-800" title="Created Tasks">
                                📋 ${u.tasks_created} Tasks
                            </span>
                            <span class="text-[10px] font-semibold text-gray-650 dark:text-gray-400 bg-gray-50 dark:bg-gray-950/60 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-800" title="Created Projects">
                                📁 ${u.projects_created} Projects
                            </span>
                        </div>
                    </td>
                    <td class="p-4.5 text-xs text-gray-400">${formattedDate}</td>
                    <td class="p-4.5 text-right shrink-0">
                        <div class="flex items-center justify-end gap-1.5">
                            <button onclick="openEditUserModal('${escapedJson}')" class="w-8 h-8 rounded-lg hover:bg-cyan-500/10 hover:text-cyan-400 text-gray-500 flex items-center justify-center transition-all hover:scale-110" title="Edit Profile & Permissions">
                                <i class="ph ph-pencil-simple text-base"></i>
                            </button>
                            ${isSelf ? `
                                <div class="w-8 h-8 text-gray-700 flex items-center justify-center cursor-not-allowed" title="Cannot delete active admin account">
                                    <i class="ph ph-trash text-base"></i>
                                </div>
                            ` : `
                                <button onclick="deleteUser(${u.id}, '${u.username}')" class="w-8 h-8 rounded-lg hover:bg-rose-500/10 hover:text-rose-500 text-gray-500 flex items-center justify-center transition-all hover:scale-110" title="Delete User">
                                    <i class="ph ph-trash text-base"></i>
                                </button>
                            `}
                        </div>
                    </td>
                </tr>
            `;
        });
        userList.innerHTML = html;
    }

    function updateStats(users) {
        document.getElementById('stat-total').innerText = users.length;
        document.getElementById('stat-admins').innerText = users.filter(u => u.role === 'admin').length;
        document.getElementById('stat-managers').innerText = users.filter(u => u.role === 'manager').length;
        document.getElementById('stat-employees').innerText = users.filter(u => u.role === 'employee').length;
    }

    // Filter Users
    function filterUsers() {
        const query = document.getElementById('searchUser').value.toLowerCase().trim();
        const role = document.getElementById('roleFilter').value;

        const filtered = allUsers.filter(u => {
            const matchSearch = u.username.toLowerCase().includes(query) || 
                                u.email.toLowerCase().includes(query) || 
                                (u.phone && u.phone.toLowerCase().includes(query));
            const matchRole = !role || u.role === role;
            return matchSearch && matchRole;
        });

        renderUserTable(filtered);
    }

    // Handlers for Add, Edit, Delete Actions
    async function handleAddUser(e) {
        e.preventDefault();
        const username = document.getElementById('addUsername').value;
        const email = document.getElementById('addEmail').value;
        const phone = document.getElementById('addPhone').value;
        const role = document.getElementById('addRole').value;
        const password = document.getElementById('addPassword').value;

        const result = await fetchAPI('api/users.php', 'POST', {
            username, email, phone, role, password
        });

        if (result.success) {
            showAdminToast(`Account for "${username}" created successfully!`, 'success');
            closeAddUserModal();
            loadUserDirectory();
        } else {
            showAdminToast(result.message, 'error');
        }
    }

    async function handleEditUser(e) {
        e.preventDefault();
        const id = document.getElementById('editUserId').value;
        const username = document.getElementById('editUsername').value;
        const email = document.getElementById('editEmail').value;
        const phone = document.getElementById('editPhone').value;
        const role = document.getElementById('editRole').value;
        const password = document.getElementById('editPassword').value;

        const result = await fetchAPI('api/users.php', 'PUT', {
            id, username, email, phone, role, password
        });

        if (result.success) {
            showAdminToast(`Updated profile details for "${username}"!`, 'success');
            closeEditUserModal();
            loadUserDirectory();
        } else {
            showAdminToast(result.message, 'error');
        }
    }

    async function deleteUser(id, username) {
        if (confirm(`⚠️ WARNING: Are you sure you want to permanently delete the user account for "${username}"?\nThis action will also cascade and remove all their individual tasks, notes, comments, and activities! This cannot be undone.`)) {
            const result = await fetchAPI('api/users.php', 'DELETE', { id });
            if (result.success) {
                showAdminToast(`Deleted user account for "${username}" successfully.`, 'success');
                loadUserDirectory();
            } else {
                showAdminToast(result.message, 'error');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', loadUserDirectory);
</script>

<?php include 'includes/footer.php'; ?>
