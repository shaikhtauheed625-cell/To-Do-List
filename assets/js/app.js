document.addEventListener('DOMContentLoaded', () => {
    // Theme Toggle Logic
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;

    // Check for saved theme preference or system preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches) || !savedTheme) {
        htmlElement.classList.add('dark');
        htmlElement.classList.remove('light');
        htmlElement.style.backgroundColor = '#0B1120';
    } else {
        htmlElement.classList.remove('dark');
        htmlElement.classList.add('light');
        htmlElement.style.backgroundColor = '#F8FAFC';
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            htmlElement.classList.toggle('dark');
            htmlElement.classList.toggle('light');
            
            if (htmlElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
                htmlElement.style.backgroundColor = '#0B1120';
            } else {
                localStorage.setItem('theme', 'light');
                htmlElement.style.backgroundColor = '#F8FAFC';
            }
        });
    }

    // Sidebar Toggle Logic for Mobile
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    function toggleSidebar() {
        if (sidebar) sidebar.classList.toggle('-translate-x-full');
        if (sidebarOverlay) sidebarOverlay.classList.toggle('hidden');
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // Initialize Tooltips/Popovers if any (Assuming standard custom UI logic)
});

// Helper for Fetch API (AJAX)
async function fetchAPI(endpoint, method = 'GET', data = null) {
    let finalMethod = method;
    let finalData = data;

    // POST-Tunneling: Convert DELETE and PUT to POST to bypass restrictive firewalls/hosts
    if (method === 'DELETE' || method === 'PUT') {
        finalMethod = 'POST';
        finalData = data ? { ...data, _method: method } : { _method: method };
    }

    const options = {
        method: finalMethod,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (finalData && (finalMethod === 'POST')) {
        options.body = JSON.stringify(finalData);
    }

    try {
        // Base URL is relative to where it's called
        const response = await fetch(endpoint, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Fetch API Error:', error);
        return { success: false, message: error.message };
    }
}

let audioCtx;

// Play success tone for task completion
function playCompletionSound() {
    try {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        // Browsers require audio context to be resumed on user interaction
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }

        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        oscillator.frequency.exponentialRampToValueAtTime(1174.66, audioCtx.currentTime + 0.1); // D6
        
        gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.5, audioCtx.currentTime + 0.05);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 1);
        
        oscillator.start(audioCtx.currentTime);
        oscillator.stop(audioCtx.currentTime + 1);
    } catch(e) {
        console.log("Audio not supported or blocked", e);
    }
}

// Enable browser notifications on user click gesture
function enableBrowserNotifications() {
    if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
            updateNotificationButton(permission);
            if (permission === 'granted') {
                showBrowserNotification('Notifications Enabled!', 'You will now receive task reminders on this device.');
            }
        });
    } else {
        alert('Your browser does not support desktop notifications.');
    }
}

function updateNotificationButton(permission) {
    const btn = document.getElementById('enable-notifications-btn');
    if (!btn) return;
    if (permission === 'granted') {
        btn.innerText = 'Enabled';
        btn.disabled = true;
        btn.className = 'px-4 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold cursor-default';
    } else if (permission === 'denied') {
        btn.innerText = 'Blocked';
        btn.disabled = true;
        btn.className = 'px-4 py-1.5 bg-rose-600 text-white rounded-lg text-xs font-semibold cursor-default';
    } else {
        btn.innerText = 'Enable';
        btn.disabled = false;
        btn.className = 'px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-colors';
    }
}

// Check for due reminders periodically
async function checkReminders() {
    const res = await fetchAPI('api/reminders.php');
    if (res && res.success && res.data && res.data.length > 0) {
        res.data.forEach(reminder => {
            showBrowserNotification(
                `Task Reminder: ${reminder.title}`, 
                reminder.description || 'You have a task reminder now!'
            );
        });
    }
}

function showBrowserNotification(title, body) {
    if (!('Notification' in window)) return;
    
    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: body
        });
    }
}

// Request permission and poll every 30 seconds
document.addEventListener('DOMContentLoaded', () => {
    if ('Notification' in window) {
        updateNotificationButton(Notification.permission);
    }
    
    // Check every 30 seconds
    setInterval(checkReminders, 30000);
    
    // Initial check after 3 seconds
    setTimeout(checkReminders, 3000);
});
