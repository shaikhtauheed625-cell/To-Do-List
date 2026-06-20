<?php
session_start();

// Database Configuration
if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'taskflow_pro');
}

// Dynamic Site URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Clean trailing slashes and normalize API/Auth/Includes deep directories
    $dir = preg_replace('/(\/api|\/auth|\/includes)$/i', '', rtrim($dir, '/'));
}
define('SITE_URL', rtrim($protocol . $domainName . $dir, '/'));

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Auto-migration: Check if 'phone' column exists in 'users' table
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if (!$checkColumn->fetch()) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
        }
    } catch (PDOException $ex) {
        // Ignore column addition error if it happens to exist/fail
    }

    // Auto-migration: Check if 'phone' column exists in 'tasks' table
    try {
        $checkColumnTask = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'phone'");
        if (!$checkColumnTask->fetch()) {
            $pdo->exec("ALTER TABLE tasks ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
        }
    } catch (PDOException $ex) {
        // Ignore
    }

    // Auto-migration: Create contacts table if missing
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `contacts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `company` VARCHAR(100) DEFAULT NULL,
            `created_by` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
        )");

        // Seed contact directory if completely empty
        $checkContacts = $pdo->query("SELECT COUNT(*) as count FROM contacts");
        if ($checkContacts->fetch()['count'] == 0) {
            // Seed a few external business contacts
            $seedContacts = [
                ['John Doe (Client)', '+15550199', 'johndoe@acmecorp.com', 'Acme Corp'],
                ['Jane Smith (Product Owner)', '+15550244', 'janesmith@techflow.io', 'TechFlow Inc'],
                ['Acme Support Helpline', '+15550188', 'support@acmecorp.com', 'Acme Corp'],
                ['WhatsApp Demo Contact', '+15550100', 'demo@whatsapp.com', 'Meta']
            ];
            $insertContact = $pdo->prepare("INSERT INTO contacts (name, phone, email, company) VALUES (?, ?, ?, ?)");
            foreach ($seedContacts as $contact) {
                $insertContact->execute($contact);
            }
        }
    } catch (PDOException $ex) {
        // Ignore
    }

    // Auto-migration: Create ticket tables if missing
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tickets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ticket_number` VARCHAR(20) NOT NULL UNIQUE,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `status` ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            `due_date` DATETIME NOT NULL,
            `created_by` INT NOT NULL,
            `assigned_to` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_comments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ticket_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `comment` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_activity_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ticket_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `action` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        )");
    } catch (PDOException $ex) {
        // Ignore
    }

    // Auto-migration: Ensure admin user exists with password 'Admin@123'
    try {
        $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkAdmin->execute(['admin@taskflow.pro']);
        $adminUser = $checkAdmin->fetch();
        
        $adminPasswordHash = password_hash('Admin@123', PASSWORD_DEFAULT);
        
        if (!$adminUser) {
            $insertAdmin = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@taskflow.pro', ?, 'admin')");
            $insertAdmin->execute([$adminPasswordHash]);
        } else {
            $updateAdmin = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = 'admin@taskflow.pro'");
            $updateAdmin->execute([$adminPasswordHash]);
        }
    } catch (PDOException $ex) {
        // Ignore
    }
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Global functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit;
}

// Get Site Settings
function getSetting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : null;
}
?>
