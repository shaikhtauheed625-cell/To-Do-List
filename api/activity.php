<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
    // Fetch latest 10 activities
    if (isAdmin()) {
        $stmt = $pdo->prepare("SELECT a.*, u.username, u.profile_image 
                               FROM activity_logs a 
                               LEFT JOIN users u ON a.user_id = u.id 
                               ORDER BY a.created_at DESC 
                               LIMIT 10");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT a.*, u.username, u.profile_image 
                               FROM activity_logs a 
                               LEFT JOIN users u ON a.user_id = u.id 
                               WHERE a.user_id = ?
                               ORDER BY a.created_at DESC 
                               LIMIT 10");
        $stmt->execute([$user_id]);
    }
    $activities = $stmt->fetchAll();

    // Map through to add elapsed time
    $formatted_activities = [];
    foreach ($activities as $act) {
        $formatted_activities[] = [
            'id' => $act['id'],
            'user_id' => $act['user_id'],
            'username' => $act['username'] ?? 'System',
            'profile_image' => $act['profile_image'] ?? 'default.png',
            'action' => htmlspecialchars($act['action']),
            'details' => htmlspecialchars($act['details'] ?? ''),
            'time_ago' => time_elapsed_string($act['created_at']),
            'created_at' => $act['created_at']
        ];
    }

    echo json_encode(['success' => true, 'data' => $formatted_activities]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
