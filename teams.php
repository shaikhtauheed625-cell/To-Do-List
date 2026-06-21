<?php
$page_title = 'Team Workspace';
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Pass session variables to JS securely -->
<script>
    const CURRENT_USER_ID = <?php echo $_SESSION['user_id']; ?>;
    const CURRENT_USERNAME = <?php echo json_encode($_SESSION['username']); ?>;
</script>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] transition-colors">
    <header class="h-20 bg-white dark:bg-[#0B1120] border-b border-gray-200 dark:border-gray-800/50 flex items-center justify-between px-6 shrink-0 transition-colors">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Team Workspace</h1>
        </div>
        
        <div class="flex items-center gap-4">
            <button id="theme-toggle" title="Toggle Dark/Light Mode" class="w-10 h-10 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors">
                <i class="ph ph-moon text-xl dark:hidden"></i>
                <i class="ph ph-sun text-xl hidden dark:block"></i>
            </button>
            <button id="invite-member-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-user-plus"></i> Invite Member
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 h-full items-start">
            
            <!-- Left Panel (Members & Activity) -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Development Team -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 animate-fade-in">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="ph ph-users text-lg text-blue-500"></i> Development Team
                    </h2>
                    <div class="space-y-3">
                        
                        <!-- Member 1 -->
                        <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 dark:border-gray-800/80 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors" data-member-id="1">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=3B82F6&color=fff" alt="User">
                                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($_SESSION['username']); ?> (You)</p>
                                    <p class="text-[10px] text-gray-500">Admin</p>
                                </div>
                            </div>
                            <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 member-action-btn" data-action="remove">
                                <i class="ph ph-dots-three-vertical text-lg"></i>
                            </button>
                        </div>

                        <!-- Member 2 -->
                        <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 dark:border-gray-800/80 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors" data-member-id="2">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name=Sarah+Jenkins&background=A78BFA&color=fff" alt="User">
                                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-900 dark:text-white">Sarah Jenkins</p>
                                    <p class="text-[10px] text-gray-500">Manager</p>
                                </div>
                            </div>
                            <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 member-action-btn" data-action="remove">
                                <i class="ph ph-dots-three-vertical text-lg"></i>
                            </button>
                        </div>

                        <!-- Member 3 -->
                        <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 dark:border-gray-800/80 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors" data-member-id="3">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <img class="w-8 h-8 rounded-full" src="https://ui-avatars.com/api/?name=Mike+Ross&background=FDBA74&color=fff" alt="User">
                                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-gray-400 border-2 border-white dark:border-gray-900 rounded-full"></span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-900 dark:text-white">Mike Ross</p>
                                    <p class="text-[10px] text-gray-500">Employee</p>
                                </div>
                            </div>
                            <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 member-action-btn" data-action="remove">
                                <i class="ph ph-dots-three-vertical text-lg"></i>
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Activity Stream -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 animate-fade-in" style="animation-delay: 0.1s;">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="ph ph-activity text-lg text-emerald-500"></i> Recent Activity
                    </h2>
                    
                    <div id="activity-timeline" class="space-y-4 relative before:absolute before:top-2 before:bottom-2 before:left-4 before:-translate-x-1/2 before:w-0.5 before:bg-gray-100 dark:before:bg-gray-800">
                        <p class="text-xs text-gray-500 italic text-center py-8">Loading activities...</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel (Realtime Team Chat) -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col h-[500px] lg:h-[650px] overflow-hidden animate-fade-in" style="animation-delay: 0.15s;">
                <!-- Chat Header -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/40">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Team Chat Room</h2>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">Collaborate with the dev team in real time</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-500 font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800">3 Members</span>
                </div>
                
                <!-- Chat Message List -->
                <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50/30 dark:bg-gray-900/10">
                    <p class="text-xs text-gray-400 italic text-center py-8">Loading chat history...</p>
                </div>
                
                <!-- Chat Form Input -->
                <form id="chat-form" onsubmit="handleSendChat(event)" class="p-4 border-t border-gray-200 dark:border-gray-800 flex items-center gap-3 bg-white dark:bg-gray-900">
                    <input type="text" id="chat-input" placeholder="Type a message to the team..." class="flex-1 px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs dark:text-white transition-colors" autocomplete="off" required>
                    <button type="submit" class="w-10 h-10 rounded-xl bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center transition-colors shadow-md shadow-blue-500/10">
                        <i class="ph ph-paper-plane-right text-lg"></i>
                    </button>
                </form>
            </div>

        </div>
    </div>
</main>

<!-- Invite Member Modal -->
<div id="invite-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 w-96">
        <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Invite via WhatsApp</h2>
        <form id="invite-form" class="space-y-4">
            <input type="tel" id="invite-phone" placeholder="Member phone (e.g., 15551234567)" pattern="[0-9]{10,15}" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <div class="flex justify-end gap-2">
                <button type="button" id="invite-cancel" class="px-3 py-1 bg-gray-300 hover:bg-gray-400 rounded">Cancel</button>
                <button type="submit" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded">Send WhatsApp</button>
            </div>
        </form>
    </div>
</div>

<?php 
$custom_js = <<<'EOT'
<script>
document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------
    // Recent Activity Timeline Polling
    // ----------------------------------------------------
    const timeline = document.getElementById('activity-timeline');
    let lastActivityIds = new Set();

    async function pollActivities() {
        const response = await fetchAPI('api/activity.php');
        if (response.success && response.data) {
            const activities = response.data;
            if (activities.length === 0) {
                timeline.innerHTML = `<p class="text-xs text-gray-500 italic text-center py-8">No recent activities found.</p>`;
                return;
            }

            const currentIds = new Set(activities.map(a => a.id));
            const isFirstLoad = lastActivityIds.size === 0;

            let html = '';
            activities.forEach((act, index) => {
                const isNew = !isFirstLoad && !lastActivityIds.has(act.id);
                
                // Icon styling based on action
                let iconClass = 'ph ph-sparkle';
                let bgClass = 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-500';
                if (act.action.toLowerCase().includes('complete')) {
                    iconClass = 'ph ph-check';
                    bgClass = 'bg-blue-100 dark:bg-blue-900/30 text-blue-500';
                } else if (act.action.toLowerCase().includes('delete')) {
                    iconClass = 'ph ph-trash';
                    bgClass = 'bg-rose-100 dark:bg-rose-900/30 text-rose-500';
                } else if (act.action.toLowerCase().includes('start')) {
                    iconClass = 'ph ph-play';
                    bgClass = 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-500';
                } else if (act.action.toLowerCase().includes('note')) {
                    iconClass = 'ph ph-chat-text';
                    bgClass = 'bg-purple-100 dark:bg-purple-900/30 text-purple-500';
                } else if (act.action.toLowerCase().includes('project')) {
                    iconClass = 'ph ph-folder-open';
                    bgClass = 'bg-amber-100 dark:bg-amber-900/30 text-amber-500';
                }

                const delay = isFirstLoad ? (index * 0.05) : 0;
                const animStyle = isFirstLoad 
                    ? `style="animation: fadeIn 0.4s ease-out ${delay}s forwards; opacity: 0; transform: translateY(10px);"`
                    : (isNew ? `style="animation: fadeIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;"` : '');

                html += `
                    <div class="relative flex gap-4 items-start" ${animStyle}>
                        <div class="flex items-center justify-center w-8 h-8 rounded-full border border-white dark:border-gray-900 ${bgClass} shadow shrink-0 z-10">
                            <i class="${iconClass} text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0 p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm transition-colors">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="font-bold text-gray-900 dark:text-white text-xs truncate">${act.username}</span>
                                <time class="text-[10px] font-medium text-gray-400 shrink-0">${act.time_ago}</time>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">${act.action} <span class="text-blue-500 font-semibold">${act.details}</span></p>
                        </div>
                    </div>
                `;
            });

            timeline.innerHTML = html;
            lastActivityIds = currentIds;
        }
    }

    // ----------------------------------------------------
    // Team Chat Polling & Sending
    // ----------------------------------------------------
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    let lastMessageIds = new Set();
    let isInitialScrollDone = false;

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function pollChat() {
        const response = await fetchAPI('api/chat.php');
        if (response.success && response.data) {
            const messages = response.data;
            if (messages.length === 0) {
                chatMessages.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 py-16">
                        <i class="ph ph-chat-circle-dots text-5xl mb-2 opacity-40"></i>
                        <p class="text-xs">No messages yet. Send a message to get started!</p>
                    </div>`;
                return;
            }

            const currentIds = new Set(messages.map(m => m.id));
            
            // Check if there are any new messages
            let hasNewMessage = false;
            messages.forEach(m => {
                if (!lastMessageIds.has(m.id)) {
                    hasNewMessage = true;
                }
            });

            if (!hasNewMessage && isInitialScrollDone) {
                // Don't redraw DOM if no new messages to avoid layout recalculation
                return;
            }

            let html = '';
            messages.forEach(msg => {
                const isMe = Number(msg.user_id) === Number(CURRENT_USER_ID);
                const escapedUsername = encodeURIComponent(msg.username);
                
                if (isMe) {
                    html += `
                        <div class="flex items-start gap-3 justify-end animate-fade-in">
                            <div class="text-right max-w-[80%]">
                                <div class="flex items-center gap-2 mb-1 justify-end">
                                    <span class="text-[9px] text-gray-400">${msg.time_ago}</span>
                                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">You</span>
                                </div>
                                <div class="bg-blue-600 text-white rounded-l-xl rounded-br-xl px-4 py-2 text-xs shadow-md leading-relaxed text-left break-words">
                                    ${msg.message}
                                </div>
                            </div>
                            <img class="w-7 h-7 rounded-full border border-blue-500/20 mt-0.5 shrink-0" src="https://ui-avatars.com/api/?name=${escapedUsername}&background=3B82F6&color=fff" alt="Avatar">
                        </div>
                    `;
                } else {
                    html += `
                        <div class="flex items-start gap-3 justify-start animate-fade-in">
                            <img class="w-7 h-7 rounded-full border border-gray-200 dark:border-gray-800 mt-0.5 shrink-0" src="https://ui-avatars.com/api/?name=${escapedUsername}&background=random" alt="Avatar">
                            <div class="max-w-[80%]">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">${msg.username}</span>
                                    <span class="text-[9px] text-gray-400">${msg.time_ago}</span>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-r-xl rounded-bl-xl px-4 py-2 text-xs text-gray-800 dark:text-gray-200 shadow-sm leading-relaxed break-words">
                                    ${msg.message}
                                </div>
                            </div>
                        </div>
                    `;
                }
            });

            chatMessages.innerHTML = html;
            lastMessageIds = currentIds;
            
            // Auto-scroll to bottom
            scrollToBottom();
            isInitialScrollDone = true;
        }
    }

    window.handleSendChat = async function(e) {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message) return;

        chatInput.value = ''; // Snappy input clear

        const response = await fetchAPI('api/chat.php', 'POST', { message: message });
        if (response.success) {
            // Snappy poll
            await pollChat();
            scrollToBottom();
        } else {
            alert('Failed to send message: ' + response.message);
        }
    };

    // Invite Member handling
    const inviteBtn = document.getElementById('invite-member-btn');
    const inviteModal = document.getElementById('invite-modal');
    const inviteCancel = document.getElementById('invite-cancel');
    const inviteForm = document.getElementById('invite-form');

    if (inviteBtn && inviteModal) {
        inviteBtn.addEventListener('click', () => {
            inviteModal.classList.remove('hidden');
        });
    }
    if (inviteCancel) {
        inviteCancel.addEventListener('click', () => {
            inviteModal.classList.add('hidden');
        });
    }
    if (inviteForm) {
        inviteForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const phone = document.getElementById('invite-phone').value.trim();
            if (!phone) return;
            // Open WhatsApp chat with prefilled invite message
            const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent('You are invited to join the team workspace. Please log in to access it.')}`;
            window.open(waUrl, '_blank');
            // Optionally, you could still call backend to add member record if needed
            // const response = await fetchAPI('api/team_members.php', 'POST', { phone: phone });
            // if (response.success) {
            //     alert('Invitation sent via WhatsApp!');
            // } else {
            //     alert('Failed to send invite: ' + response.message);
            // }
            inviteModal.classList.add('hidden');
        });
    }

    // Member removal handling (delegated)
    document.addEventListener('click', async (e) => {
        const target = e.target.closest('.member-action-btn[data-action="remove"]');
        if (!target) return;
        const memberDiv = target.closest('[data-member-id]');
        if (!memberDiv) return;
        const memberId = memberDiv.getAttribute('data-member-id');
        if (!memberId) return;
        if (!confirm('Are you sure you want to remove this member?')) return;
        const resp = await fetchAPI('api/team_members.php', 'DELETE', { member_id: memberId });
        if (resp.success) {
            alert('Member removed');
            // Remove element from DOM
            memberDiv.remove();
        } else {
            alert('Failed to remove member: ' + resp.message);
        }
    });

    // ----------------------------------------------------
    // Initialize polling
    // ----------------------------------------------------
    pollActivities();
    pollChat();

    setInterval(pollActivities, 5000);
    setInterval(pollChat, 3000);
});
</script>
EOT;
include 'includes/footer.php'; ?>
