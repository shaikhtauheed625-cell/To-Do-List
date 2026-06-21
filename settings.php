<?php
$page_title = 'Settings';
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $whatsapp_apikey = sanitize_input($_POST['whatsapp_apikey'] ?? '');

    if (empty($username) || empty($email)) {
        $error_msg = 'Full Name and Email Address are required.';
    } else {
        try {
            // Check if email already exists for another user
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkEmail->execute([$email, $user_id]);
            if ($checkEmail->fetch()) {
                $error_msg = 'Email address is already in use by another user.';
            } else {
                // Fetch current user record to keep existing profile image if no new one uploaded
                $fetchCurrent = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                $fetchCurrent->execute([$user_id]);
                $currentUser = $fetchCurrent->fetch();
                $profile_image = $currentUser['profile_image'] ?? 'default.png';

                // Handle Profile Photo Upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['profile_image']['tmp_name'];
                    $fileName = $_FILES['profile_image']['name'];
                    $fileSize = $_FILES['profile_image']['size'];
                    $fileType = $_FILES['profile_image']['type'];
                    $fileNameCmps = explode(".", $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));

                    // Allowed file extensions and max size (2MB)
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        $error_msg = 'Invalid file extension. Allowed types: JPG, JPEG, PNG, GIF.';
                    } elseif ($fileSize > 2097152) {
                        $error_msg = 'File size is too large. Maximum limit is 2MB.';
                    } else {
                        // Create upload directory if it doesn't exist
                        $uploadDir = 'uploads/profiles/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Generate unique file name
                        $newFileName = 'user_' . $user_id . '_' . time() . '.' . $fileExtension;
                        $dest_path = $uploadDir . $newFileName;

                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            // Delete old image if it is not default
                            if ($profile_image !== 'default.png' && file_exists($uploadDir . $profile_image)) {
                                @unlink($uploadDir . $profile_image);
                            }
                            $profile_image = $newFileName;
                        } else {
                            $error_msg = 'There was an error moving the uploaded profile picture.';
                        }
                    }
                }

                if (empty($error_msg)) {
                    $update = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, whatsapp_apikey = ?, profile_image = ? WHERE id = ?");
                    $update->execute([$username, $email, $phone, $whatsapp_apikey, $profile_image, $user_id]);
                    
                    // Update session
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['profile_image'] = $profile_image;
                    
                    // Log activity
                    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Updated profile', 'Changed contact details')");
                    $logStmt->execute([$user_id]);

                    $success_msg = 'Profile settings saved successfully!';
                }
            }
        } catch (PDOException $e) {
            $error_msg = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch current user details
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_profile = $user_stmt->fetch();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-[100dvh] overflow-hidden bg-gray-50 dark:bg-[#0B1120] transition-colors">
    <header class="h-20 bg-white dark:bg-[#0B1120] border-b border-gray-255 dark:border-gray-800/50 flex items-center justify-between px-4 sm:px-6 shrink-0 transition-colors">
        <div class="flex items-center gap-2 sm:gap-4 min-w-0">
            <button id="sidebar-toggle" title="Toggle Sidebar" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white shrink-0">
                <i class="ph ph-list text-2xl"></i>
            </button>
            <h1 class="text-base sm:text-xl font-bold text-gray-800 dark:text-white flex items-center gap-1.5 sm:gap-2 min-w-0">
                <i class="ph-fill ph-gear text-cyan-400 shrink-0"></i> <span class="truncate">Settings</span>
            </h1>
        </div>
        
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <button id="theme-toggle" title="Toggle Dark/Light Mode" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-555 hover:bg-gray-105 dark:text-gray-400 dark:hover:bg-gray-850 hover:text-gray-800 dark:hover:text-white border border-gray-200 dark:border-gray-800 transition-colors shrink-0">
                <i class="ph ph-moon text-xl dark:hidden"></i>
                <i class="ph ph-sun text-xl hidden dark:block"></i>
            </button>
            <button type="submit" form="profileSettingsForm" class="p-2.5 sm:px-4 sm:py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors shadow-sm shrink-0 flex items-center gap-2" title="Save Changes">
                <i class="ph ph-floppy-disk text-lg"></i> <span class="hidden sm:inline">Save Changes</span>
            </button>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto space-y-6 animate-fade-in">
            
            <!-- Profile Settings -->
            <form id="profileSettingsForm" method="POST" action="settings.php" enctype="multipart/form-data" class="space-y-6">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Profile Information</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Update your account's profile information, email address, and WhatsApp contact details.</p>
                    </div>

                    <?php if ($success_msg): ?>
                        <div class="mx-6 mt-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl text-sm flex items-start gap-3">
                            <i class="ph ph-check-circle text-xl flex-shrink-0"></i>
                            <p><?php echo $success_msg; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_msg): ?>
                        <div class="mx-6 mt-6 bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-xl text-sm flex items-start gap-3">
                            <i class="ph ph-warning-circle text-xl flex-shrink-0"></i>
                            <p><?php echo $error_msg; ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="p-6 space-y-6">
                        <div class="flex items-center gap-6">
                            <?php
                            $photo_src = 'https://ui-avatars.com/api/?name=' . urlencode($user_profile['username']) . '&background=3b82f6&color=fff&size=128';
                            if (!empty($user_profile['profile_image']) && $user_profile['profile_image'] !== 'default.png') {
                                $photo_src = SITE_URL . '/uploads/profiles/' . $user_profile['profile_image'];
                            }
                            ?>
                            <img id="profile-avatar-img" class="w-20 h-20 rounded-full border-4 border-gray-50 dark:border-gray-800 object-cover" src="<?php echo $photo_src; ?>" alt="Profile Photo">
                            <div>
                                <!-- Hidden File Input -->
                                <input type="file" id="profile_photo_input" name="profile_image" accept="image/*" class="hidden" onchange="previewProfilePhoto(this)">
                                <button type="button" onclick="document.getElementById('profile_photo_input').click()" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors">
                                    Change Photo
                                </button>
                                <p class="text-xs text-gray-500 mt-2">JPG, JPEG, GIF or PNG. Max size of 2MB.</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full Name</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user_profile['username']); ?>" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">WhatsApp Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user_profile['phone'] ?? ''); ?>" placeholder="e.g. +1234567890" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                <p class="text-[10px] text-gray-400 mt-1">Include country code (e.g. +15551234567).</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">WhatsApp API Key (CallMeBot)</label>
                                <input type="text" name="whatsapp_apikey" value="<?php echo htmlspecialchars($user_profile['whatsapp_apikey'] ?? ''); ?>" placeholder="Enter API Key" class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                <p class="text-[10px] text-gray-400 mt-1">Get a free key from <a href="https://www.callmebot.com/" target="_blank" class="text-blue-500 hover:underline">callmebot.com</a>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Preferences -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Preferences</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage your notifications and system preferences.</p>
                </div>
                <div class="p-6 space-y-4">
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Email Notifications</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Receive emails about your task updates.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-800/60 pt-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Browser Notifications</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Enable real-time task alerts on this device.</p>
                        </div>
                        <button type="button" id="enable-notifications-btn" onclick="enableBrowserNotifications()" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-colors">
                            Enable
                        </button>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Completion Sounds</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Play a sound when a task is completed.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                </div>
            </div>

        </div>
    </div>
</main>

<script>
function previewProfilePhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-avatar-img').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
