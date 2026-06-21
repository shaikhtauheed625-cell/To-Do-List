<?php
$page_title = 'Notes';
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] transition-colors">
    <header class="h-20 bg-white dark:bg-[#0B1120] border-b border-gray-200 dark:border-gray-800/50 flex items-center justify-between px-6 shrink-0 transition-colors">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Notes</h1>
        </div>
        
        <div class="flex items-center gap-4">
            <button id="theme-toggle" title="Toggle Dark/Light Mode" class="w-10 h-10 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors">
                <i class="ph ph-moon text-xl dark:hidden"></i>
                <i class="ph ph-sun text-xl hidden dark:block"></i>
            </button>
            <button onclick="openNoteModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-plus"></i> New Note
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 animate-fade-in" id="notes-grid">
                <p class="text-sm text-gray-500 italic py-8 text-center col-span-full">Loading notes...</p>
            </div>

                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Note Modal Overlay -->
<div id="noteModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="card-3d bg-white dark:bg-gray-900 w-full max-w-lg overflow-hidden animate-fade-in p-0">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Note</h3>
            <button onclick="closeNoteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <form id="createNoteForm" onsubmit="handleCreateNote(event)" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Note Title *</label>
                <input type="text" id="noteTitle" required class="form-input w-full" placeholder="Enter title...">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Content</label>
                <textarea id="noteContent" rows="5" class="form-input w-full" placeholder="Write your note here..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Color</label>
                <div class="flex gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="noteColor" value="yellow" checked class="peer sr-only">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 border-2 border-transparent peer-checked:border-yellow-500 transition-all"></div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="noteColor" value="blue" class="peer sr-only">
                        <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 border-2 border-transparent peer-checked:border-blue-500 transition-all"></div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="noteColor" value="pink" class="peer sr-only">
                        <div class="w-8 h-8 rounded-full bg-pink-100 dark:bg-pink-900/30 border-2 border-transparent peer-checked:border-pink-500 transition-all"></div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="noteColor" value="green" class="peer sr-only">
                        <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 border-2 border-transparent peer-checked:border-green-500 transition-all"></div>
                    </label>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-800 mt-6">
                <button type="button" onclick="closeNoteModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-medium">Cancel</button>
                <button type="submit" class="btn-primary px-6 py-2">Save Note</button>
            </div>
        </form>
    </div>
</div>

<script>
    const noteModal = document.getElementById('noteModal');
    const notesGrid = document.getElementById('notes-grid');

    function openNoteModal() { noteModal.classList.remove('hidden'); }
    function closeNoteModal() { noteModal.classList.add('hidden'); document.getElementById('createNoteForm').reset(); }

    // Fetch and render notes from DB
    async function loadNotes() {
        notesGrid.innerHTML = `<p class="text-sm text-gray-500 italic py-8 text-center col-span-full">Loading notes...</p>`;
        const response = await fetchAPI('api/notes.php');
        if (response.success && response.data) {
            const notes = response.data;
            if (notes.length === 0) {
                notesGrid.innerHTML = `
                    <div class="col-span-full flex flex-col items-center justify-center p-12 text-gray-500 dark:text-gray-400">
                        <i class="ph ph-notebook text-5xl mb-3 opacity-50"></i>
                        <p class="text-sm">No notes found. Create your first note!</p>
                    </div>`;
                return;
            }

            const colorMap = {
                yellow: {
                    bg: 'bg-yellow-100 dark:bg-yellow-900/30',
                    border: 'border-yellow-200 dark:border-yellow-800/50',
                    textTitle: 'text-yellow-900 dark:text-yellow-100',
                    textContent: 'text-yellow-800 dark:text-yellow-200',
                    textMuted: 'text-yellow-600 dark:text-yellow-400',
                    button: 'text-yellow-600 dark:text-yellow-500'
                },
                blue: {
                    bg: 'bg-blue-100 dark:bg-blue-900/30',
                    border: 'border-blue-200 dark:border-blue-800/50',
                    textTitle: 'text-blue-900 dark:text-blue-100',
                    textContent: 'text-blue-800 dark:text-blue-200',
                    textMuted: 'text-blue-600 dark:text-blue-400',
                    button: 'text-blue-600 dark:text-blue-500'
                },
                pink: {
                    bg: 'bg-pink-100 dark:bg-pink-900/30',
                    border: 'border-pink-200 dark:border-pink-800/50',
                    textTitle: 'text-pink-900 dark:text-pink-100',
                    textContent: 'text-pink-800 dark:text-pink-200',
                    textMuted: 'text-pink-600 dark:text-pink-400',
                    button: 'text-pink-600 dark:text-pink-500'
                },
                green: {
                    bg: 'bg-green-100 dark:bg-green-900/30',
                    border: 'border-green-200 dark:border-green-800/50',
                    textTitle: 'text-green-900 dark:text-green-100',
                    textContent: 'text-green-800 dark:text-green-200',
                    textMuted: 'text-green-600 dark:text-green-400',
                    button: 'text-green-600 dark:text-green-500'
                }
            };

            let html = '';
            notes.forEach(note => {
                const date = new Date(note.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const formattedContent = note.content.replace(/\n/g, '<br>');
                const colors = colorMap[note.color] || colorMap.yellow;
                
                html += `
                    <div class="${colors.bg} rounded-xl p-5 shadow-sm border ${colors.border} hover:shadow-md transition-shadow relative group cursor-pointer h-64 flex flex-col transform hover:-translate-y-1 animate-fade-in">
                        <button onclick="deleteNote(${note.id}, this, event)" class="absolute top-3 right-3 ${colors.button} opacity-0 group-hover:opacity-100 transition-opacity" title="Delete note">
                            <i class="ph ph-trash text-lg"></i>
                        </button>
                        <h3 class="font-bold ${colors.textTitle} mb-2 pr-6">${note.title}</h3>
                        <p class="text-sm ${colors.textContent} flex-1 overflow-hidden">
                            ${formattedContent}
                        </p>
                        <div class="text-xs ${colors.textMuted} mt-4 pt-3 border-t ${colors.border}">
                            ${date}
                        </div>
                    </div>
                `;
            });
            notesGrid.innerHTML = html;
        } else {
            notesGrid.innerHTML = `<p class="text-sm text-red-500 py-8 text-center col-span-full">Error: ${response.message}</p>`;
        }
    }

    // Save Note API call
    async function handleCreateNote(e) {
        e.preventDefault();
        const title = document.getElementById('noteTitle').value;
        const content = document.getElementById('noteContent').value;
        const color = document.querySelector('input[name="noteColor"]:checked').value;

        const result = await fetchAPI('api/notes.php', 'POST', {
            title: title,
            content: content,
            color: color
        });

        if (result.success) {
            closeNoteModal();
            loadNotes(); // Reload notes from DB to keep UI in sync
        } else {
            alert('Error: ' + result.message);
        }
    }

    // Delete Note API call
    async function deleteNote(id, button, event) {
        event.stopPropagation(); // Prevent card clicks if we implement view detail card
        if (confirm('Are you sure you want to delete this note?')) {
            const result = await fetchAPI('api/notes.php', 'DELETE', { id: id });
            if (result.success) {
                // Instantly remove card with dynamic animation
                const card = button.closest('.rounded-xl');
                card.style.transform = 'scale(0.9) translateY(10px)';
                card.style.opacity = '0';
                card.style.transition = 'all 0.3s ease';
                setTimeout(() => card.remove(), 300);
            } else {
                alert('Error: ' + result.message);
            }
        }
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', loadNotes);
</script>

<?php include 'includes/footer.php'; ?>
