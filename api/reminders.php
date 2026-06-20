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
        // Sync task reminders: ensure every incomplete task for this user has an active reminder
        $syncTasks = $pdo->prepare("
            INSERT INTO reminders (task_id, user_id, reminder_time, type, is_sent)
            SELECT t.id, ?, DATE_ADD(NOW(), INTERVAL 3 HOUR), 'browser', 0
            FROM tasks t
            LEFT JOIN reminders r ON t.id = r.task_id AND r.user_id = ? AND r.is_sent = 0
            WHERE (t.created_by = ? OR t.assigned_to = ?)
              AND t.status != 'completed'
              AND r.id IS NULL
        ");
        $syncTasks->execute([$user_id, $user_id, $user_id, $user_id]);

        // Sync ticket reminders: ensure every open/in_progress ticket for this user has an active reminder
        $syncTickets = $pdo->prepare("
            INSERT INTO reminders (ticket_id, user_id, reminder_time, type, is_sent)
            SELECT t.id, ?, DATE_ADD(NOW(), INTERVAL 3 HOUR), 'browser', 0
            FROM tickets t
            LEFT JOIN reminders r ON t.id = r.ticket_id AND r.user_id = ? AND r.is_sent = 0
            WHERE (t.created_by = ? OR t.assigned_to = ?)
              AND t.status IN ('open', 'in_progress')
              AND r.id IS NULL
        ");
        $syncTickets->execute([$user_id, $user_id, $user_id, $user_id]);

        // Fetch all due, unsent reminders (both tasks and tickets) for the logged-in user
        $stmt = $pdo->prepare("
            SELECT r.id, r.task_id, r.ticket_id, r.reminder_time, 
                   t.title as task_title, t.description as task_desc, t.status as task_status,
                   tk.title as ticket_title, tk.description as ticket_desc, tk.status as ticket_status
            FROM reminders r
            LEFT JOIN tasks t ON r.task_id = t.id
            LEFT JOIN tickets tk ON r.ticket_id = tk.id
            WHERE r.user_id = ? 
              AND r.is_sent = 0 
              AND r.reminder_time <= NOW()
        ");
        $stmt->execute([$user_id]);
        $dueReminders = $stmt->fetchAll();

        $output = [];
        foreach ($dueReminders as $r) {
            if ($r['task_id']) {
                if ($r['task_status'] === 'completed') {
                    // Task is complete, close this reminder sequence
                    $update = $pdo->prepare("UPDATE reminders SET is_sent = 1 WHERE id = ?");
                    $update->execute([$r['id']]);
                } else {
                    // Task is not complete, notify and postpone by 3 hours
                    $update = $pdo->prepare("UPDATE reminders SET reminder_time = DATE_ADD(NOW(), INTERVAL 3 HOUR) WHERE id = ?");
                    $update->execute([$r['id']]);
                    
                    $output[] = [
                        'id' => $r['id'],
                        'task_id' => $r['task_id'],
                        'title' => "Task Reminder: " . $r['task_title'],
                        'description' => $r['task_desc']
                    ];
                }
            } elseif ($r['ticket_id']) {
                if ($r['ticket_status'] === 'resolved' || $r['ticket_status'] === 'closed') {
                    // Ticket is resolved/closed, close this reminder sequence
                    $update = $pdo->prepare("UPDATE reminders SET is_sent = 1 WHERE id = ?");
                    $update->execute([$r['id']]);
                } else {
                    // Ticket is open/in_progress, notify and postpone by 3 hours
                    $update = $pdo->prepare("UPDATE reminders SET reminder_time = DATE_ADD(NOW(), INTERVAL 3 HOUR) WHERE id = ?");
                    $update->execute([$r['id']]);
                    
                    $output[] = [
                        'id' => $r['id'],
                        'ticket_id' => $r['ticket_id'],
                        'title' => "Ticket Reminder: " . $r['ticket_title'],
                        'description' => $r['ticket_desc']
                    ];
                }
            }
        }

        echo json_encode(['success' => true, 'data' => $output]);
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
