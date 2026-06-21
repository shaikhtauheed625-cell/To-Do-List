<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - task.n1space.com' : 'task.n1space.com'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/images/favicon.png?v=1">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN for rapid development) -->
    <script>
        // Prevent white flash on page load by checking theme synchronously
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            document.documentElement.style.backgroundColor = '#0B1120'; // Force dark bg immediately
        } else if (savedTheme === 'light') {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
            document.documentElement.style.backgroundColor = '#F8FAFC'; // Force light bg immediately
        } else {
            // Default to dark for this SaaS when no preference is saved
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            document.documentElement.style.backgroundColor = '#0B1120';
            localStorage.setItem('theme', 'dark'); // Enforce dark theme default
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#0B1120',
                        accent: '#06B6D4',
                        success: '#10B981',
                        surface: '#1E293B',
                        'dark-bg': '#0B1120',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    
    <!-- Chart.js (Optional, loaded on needed pages) -->
    <?php if(isset($load_chartjs) && $load_chartjs): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body class="antialiased h-[100dvh] flex overflow-hidden bg-gray-50 dark:bg-[#0B1120] text-slate-800 dark:text-gray-200">
