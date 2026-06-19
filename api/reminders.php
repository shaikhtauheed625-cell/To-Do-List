<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Fetch all due, unsent reminders for the logged-in user
        $stmt = $pdo->prepare("SELECT r.id, r.task_id, r.reminder_time, t.title, t.description 
                               FROM reminders r
                               JOIN tasks t ON r.task_id = t.id
                               WHERE r.user_id = ? AND r.is_sent = 0 AND r.reminder_time <= NOW()");
        $stmt->execute([$user_id]);
        $reminders = $stmt->fetchAll();

        if (!empty($reminders)) {
            // Collect reminder IDs to mark them as sent
            $ids = array_map(function($r) { return $r['id']; }, $reminders);
            
            // Mark reminders as sent
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $updateStmt = $pdo->prepare("UPDATE reminders SET is_sent = 1 WHERE id IN ($inQuery)");
            $updateStmt->execute($ids);
        }

        echo json_encode(['success' => true, 'data' => $reminders]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
