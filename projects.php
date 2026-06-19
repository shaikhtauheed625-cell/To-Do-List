<?php
$page_title = 'Projects';
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] transition-colors">
    <header class="h-20 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-6 shrink-0 transition-colors">
        <div class="flex items-center gap-4">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Projects</h1>
        </div>
        
        <div class="flex items-center gap-4">
            <button id="theme-toggle" title="Toggle Dark/Light Mode" class="w-10 h-10 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors">
                <i class="ph ph-moon text-xl dark:hidden"></i>
                <i class="ph ph-sun text-xl hidden dark:block"></i>
            </button>
            <button onclick="openProjectModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-plus"></i> New Project
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in" id="projects-grid">
                <p class="text-sm text-gray-500 italic py-8 text-center col-span-full">Loading projects...</p>
            </div>
        </div>
    </div>
</main>

<!-- Add Project Modal Overlay -->
<div id="projectModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[90vh]">
        <!-- Fixed Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Project</h3>
            <button onclick="closeProjectModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <!-- Scrollable Form Body -->
        <form id="createProjectForm" onsubmit="handleCreateProject(event)" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Project Name *</label>
                    <input type="text" id="projectName" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="e.g. Website Overhaul">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea id="projectDesc" rows="4" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="Add project details..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Theme Color</label>
                    <div class="flex gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="projectColor" value="#8B5CF6" checked class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-purple-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="projectColor" value="#3B82F6" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-blue-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="projectColor" value="#10B981" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-emerald-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="projectColor" value="#F59E0B" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-amber-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="projectColor" value="#EF4444" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-rose-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                    </div>
                </div>
            </div>
            <!-- Fixed Footer -->
            <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 shrink-0 bg-gray-50 dark:bg-gray-900/60">
                <button type="button" onclick="closeProjectModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-medium text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-sm shadow-sm">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Project Modal Overlay -->
<div id="editProjectModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in border border-gray-200 dark:border-gray-800 transition-colors flex flex-col max-h-[90vh]">
        <!-- Fixed Header -->
        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Project Details</h3>
            <button onclick="closeEditProjectModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <!-- Scrollable Form Body -->
        <form id="editProjectForm" onsubmit="handleEditProject(event)" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" id="editProjectId">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scrollbar">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Project Name *</label>
                    <input type="text" id="editProjectName" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="e.g. Website Overhaul">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea id="editProjectDesc" rows="4" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white" placeholder="Add project details..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Theme Color</label>
                    <div class="flex gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="editProjectColor" value="#8B5CF6" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-purple-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="editProjectColor" value="#3B82F6" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-blue-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="editProjectColor" value="#10B981" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-emerald-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="editProjectColor" value="#F59E0B" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-amber-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="editProjectColor" value="#EF4444" class="peer sr-only">
                            <div class="w-8 h-8 rounded-full bg-rose-500 border-2 border-transparent peer-checked:border-white transition-all shadow-sm"></div>
                        </label>
                    </div>
                </div>
            </div>
            <!-- Fixed Footer -->
            <div class="p-5 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 shrink-0 bg-gray-50 dark:bg-gray-900/60">
                <button type="button" onclick="closeEditProjectModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors font-medium text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-sm shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    const projectModal = document.getElementById('projectModal');
    const editProjectModal = document.getElementById('editProjectModal');
    const projectsGrid = document.getElementById('projects-grid');

    // Click outside to close dropdowns
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.project-dropdown')) {
            document.querySelectorAll('[id^="project-menu-"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    function toggleProjectMenu(event, id) {
        event.stopPropagation();
        event.preventDefault();
        
        // Hide other open menus
        document.querySelectorAll('[id^="project-menu-"]').forEach(menu => {
            if (menu.id !== `project-menu-${id}`) {
                menu.classList.add('hidden');
            }
        });

        const menu = document.getElementById(`project-menu-${id}`);
        menu.classList.toggle('hidden');
    }

    function openProjectModal() { projectModal.classList.remove('hidden'); }
    function closeProjectModal() { projectModal.classList.add('hidden'); document.getElementById('createProjectForm').reset(); }

    function openEditProjectModal(event, proj) {
        event.stopPropagation();
        event.preventDefault();
        
        // Hide open menu
        document.getElementById(`project-menu-${proj.id}`).classList.add('hidden');

        document.getElementById('editProjectId').value = proj.id;
        document.getElementById('editProjectName').value = proj.name;
        document.getElementById('editProjectDesc').value = proj.description || '';
        
        // Select color radio
        const radios = document.querySelectorAll('input[name="editProjectColor"]');
        radios.forEach(radio => {
            if (radio.value === proj.color) radio.checked = true;
        });

        editProjectModal.classList.remove('hidden');
    }

    function closeEditProjectModal() {
        editProjectModal.classList.add('hidden');
        document.getElementById('editProjectForm').reset();
    }

    async function loadProjects() {
        projectsGrid.innerHTML = `<p class="text-sm text-gray-500 italic py-8 text-center col-span-full">Loading projects...</p>`;
        const response = await fetchAPI('api/projects.php');
        if (response.success && response.data) {
            const projects = response.data;
            if (projects.length === 0) {
                projectsGrid.innerHTML = `
                    <div class="col-span-full flex flex-col items-center justify-center p-12 text-gray-500 dark:text-gray-400">
                        <i class="ph ph-folder text-5xl mb-3 opacity-50"></i>
                        <p class="text-sm">No projects found. Create your first project!</p>
                    </div>`;
                return;
            }

            let html = '';
            projects.forEach(proj => {
                let iconClass = 'ph ph-folder-open';
                if (proj.color === '#8B5CF6') iconClass = 'ph ph-rocket-launch';
                else if (proj.color === '#3B82F6') iconClass = 'ph ph-device-mobile';
                else if (proj.color === '#10B981') iconClass = 'ph ph-chart-line-up';
                else if (proj.color === '#F59E0B') iconClass = 'ph ph-storefront';
                else if (proj.color === '#EF4444') iconClass = 'ph ph-fire';

                // Escape project object safely for passing inside JS function
                const escapedProj = JSON.stringify(proj).replace(/"/g, '&quot;');

                html += `
                    <div onclick="window.location.href='tasks.php'" 
                         onmouseover="this.style.borderColor='${proj.color}'; this.style.boxShadow='0 20px 40px -15px ${proj.color}25, 0 0 15px ${proj.color}15'" 
                         onmouseout="this.style.borderColor=''; this.style.boxShadow=''" 
                         class="bg-white dark:bg-gray-900 rounded-2xl p-6 transition-all duration-300 border border-gray-200 dark:border-gray-800/80 cursor-pointer group hover-lift animate-fade-in flex flex-col justify-between h-[270px]">
                        
                        <div>
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl transition-all duration-300" style="background: ${proj.color}12; color: ${proj.color}">
                                    <i class="${iconClass}"></i>
                                </div>
                                
                                <!-- Floating Dropdown Actions -->
                                <div class="relative project-dropdown">
                                    <button onclick="toggleProjectMenu(event, ${proj.id})" class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 flex items-center justify-center transition-colors">
                                        <i class="ph ph-dots-three-vertical text-xl font-bold"></i>
                                    </button>
                                    <div id="project-menu-${proj.id}" class="hidden absolute right-0 mt-1.5 w-32 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/80 rounded-xl shadow-lg z-30 py-1 overflow-hidden animate-fade-in glass-panel shrink-0">
                                        <button onclick="openEditProjectModal(event, ${escapedProj})" class="w-full text-left px-4 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-blue-500 dark:hover:text-blue-400 transition-colors flex items-center gap-2">
                                            <i class="ph ph-pencil text-sm"></i> Edit
                                        </button>
                                        <button onclick="handleDeleteProject(event, ${proj.id})" class="w-full text-left px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-colors flex items-center gap-2">
                                            <i class="ph ph-trash text-sm"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5 truncate group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">${proj.name}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-3 leading-relaxed mb-4">${proj.description || 'No description provided.'}</p>
                        </div>
                        
                        <div>
                            <div class="mb-1.5 flex justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400">
                                <span>Progress</span>
                                <span style="color: ${proj.color}">${proj.progress}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800/80 rounded-full h-2 mb-4 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-700 ease-out" style="width: ${proj.progress}%; background-color: ${proj.color}"></div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800/80">
                                <div class="flex -space-x-2">
                                    <div class="w-7 h-7 rounded-full border-2 border-white dark:border-gray-900 p-0.5" style="background: ${proj.color}40">
                                        <img class="w-full h-full rounded-full" src="https://ui-avatars.com/api/?name=${encodeURIComponent('<?php echo $_SESSION['username']; ?>')}&background=111827&color=fff&size=48" alt="User">
                                    </div>
                                </div>
                                <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800/50 px-2 py-0.5 rounded border border-gray-100 dark:border-gray-700/50">
                                    <i class="ph ph-list-checks text-xs" style="color: ${proj.color}"></i> ${proj.total_tasks} Tasks
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            projectsGrid.innerHTML = html;
        } else {
            projectsGrid.innerHTML = `<p class="text-sm text-red-500 py-8 text-center col-span-full">Error: ${response.message}</p>`;
        }
    }

    async function handleCreateProject(e) {
        e.preventDefault();
        const name = document.getElementById('projectName').value;
        const description = document.getElementById('projectDesc').value;
        const color = document.querySelector('input[name="projectColor"]:checked').value;

        const result = await fetchAPI('api/projects.php', 'POST', {
            name: name,
            description: description,
            color: color
        });

        if (result.success) {
            closeProjectModal();
            loadProjects();
        } else {
            alert('Error: ' + result.message);
        }
    }

    async function handleEditProject(e) {
        e.preventDefault();
        const id = document.getElementById('editProjectId').value;
        const name = document.getElementById('editProjectName').value;
        const description = document.getElementById('editProjectDesc').value;
        const color = document.querySelector('input[name="editProjectColor"]:checked').value;

        const result = await fetchAPI('api/projects.php', 'PUT', {
            id: id,
            name: name,
            description: description,
            color: color
        });

        if (result.success) {
            closeEditProjectModal();
            loadProjects();
        } else {
            alert('Error: ' + result.message);
        }
    }

    async function handleDeleteProject(event, id) {
        event.stopPropagation();
        event.preventDefault();

        // Close open dropdown
        document.getElementById(`project-menu-${id}`).classList.add('hidden');

        if (confirm('Are you sure you want to delete this project? This will remove all associated tasks!')) {
            const result = await fetchAPI('api/projects.php', 'DELETE', { id: id });
            if (result.success) {
                loadProjects();
            } else {
                alert('Error: ' + result.message);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', loadProjects);
</script>

<?php include 'includes/footer.php'; ?>
