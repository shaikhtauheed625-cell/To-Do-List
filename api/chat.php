<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Self-healing database check
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `team_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `message` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database initialization error: ' . $e->getMessage()]);
    exit;
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

try {
    switch ($method) {
        case 'GET':
            // Fetch messages
            $stmt = $pdo->prepare("SELECT tm.*, u.username, u.profile_image 
                                   FROM team_messages tm 
                                   LEFT JOIN users u ON tm.user_id = u.id 
                                   ORDER BY tm.created_at ASC 
                                   LIMIT 50");
            $stmt->execute();
            $messages = $stmt->fetchAll();

            $formatted_messages = [];
            foreach ($messages as $msg) {
                $formatted_messages[] = [
                    'id' => $msg['id'],
                    'user_id' => $msg['user_id'],
                    'username' => $msg['username'] ?? 'System',
                    'profile_image' => $msg['profile_image'] ?? 'default.png',
                    'message' => htmlspecialchars($msg['message']),
                    'time_ago' => time_elapsed_string($msg['created_at']),
                    'created_at' => $msg['created_at']
                ];
            }

            echo json_encode(['success' => true, 'data' => $formatted_messages]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['message']) || empty(trim($data['message']))) {
                echo json_encode(['success' => false, 'message' => 'Message content is required']);
                exit;
            }

            $message = sanitize_input($data['message']);

            $stmt = $pdo->prepare("INSERT INTO team_messages (user_id, message) VALUES (?, ?)");
            $stmt->execute([$user_id, $message]);
            $msg_id = $pdo->lastInsertId();

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Sent a team message', ?)");
            $logStmt->execute([$user_id, mb_strimwidth($message, 0, 30, '...')]);

            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $msg_id,
                    'user_id' => $user_id,
                    'username' => $_SESSION['username'],
                    'profile_image' => $_SESSION['profile_image'] ?? 'default.png',
                    'message' => htmlspecialchars($message),
                    'time_ago' => 'just now',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
