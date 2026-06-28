<?php
/**
 * api/notifications.php
 * 
 * Lightweight notification feed for the Android app.
 * Returns recent ticket/task activity that the logged-in user
 * hasn't seen yet, so the app can fire native Android notifications.
 *
 * GET  ?since=<unix_timestamp>  → returns unseen events after that time
 * POST {action:"mark_seen", last_seen:<unix_timestamp>}  → acknowledge
 */

require_once '../config/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$method  = $_SERVER['REQUEST_METHOD'];

// POST-tunnel support
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (isset($jsonData['_method'])) {
            $method = strtoupper($jsonData['_method']);
        }
    }
}

try {
    if ($method === 'GET') {

        // Default: look back 2 minutes so the worker (runs every 15 min)
        // doesn't miss events. Client can pass ?since= to narrow the window.
        $sinceTs  = isset($_GET['since']) ? intval($_GET['since']) : (time() - 120);
        $sinceStr = date('Y-m-d H:i:s', $sinceTs);

        $notifications = [];

        // ── 1. Ticket activity that affects this user ───────────────────────
        $stmt = $pdo->prepare("
            SELECT
                tal.id,
                tal.ticket_id,
                tal.action,
                tal.created_at,
                t.ticket_number,
                t.title        AS ticket_title,
                t.priority,
                t.status,
                u.username     AS actor
            FROM ticket_activity_log tal
            JOIN tickets t  ON tal.ticket_id = t.id
            JOIN users   u  ON tal.user_id   = u.id
            WHERE tal.created_at > ?
              AND tal.user_id   != ?
              AND (t.created_by = ? OR t.assigned_to = ?)
            ORDER BY tal.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$sinceStr, $user_id, $user_id, $user_id]);
        $ticketEvents = $stmt->fetchAll();

        foreach ($ticketEvents as $ev) {
            $priority = strtolower($ev['priority'] ?? 'medium');
            $emoji    = match($priority) {
                'critical' => '🚨',
                'high'     => '🔴',
                'medium'   => '🟡',
                default    => '🟢',
            };
            $notifications[] = [
                'id'    => 'ticket_act_' . $ev['id'],
                'title' => $emoji . ' Ticket Updated: ' . $ev['ticket_number'],
                'body'  => $ev['actor'] . ' — ' . $ev['action'] . ' on "' . $ev['ticket_title'] . '"',
                'time'  => $ev['created_at'],
                'type'  => 'ticket',
            ];
        }

        // ── 2. Ticket comments addressed to this user ───────────────────────
        $stmt = $pdo->prepare("
            SELECT
                tc.id,
                tc.ticket_id,
                tc.comment,
                tc.created_at,
                t.ticket_number,
                t.title  AS ticket_title,
                u.username AS commenter
            FROM ticket_comments tc
            JOIN tickets t ON tc.ticket_id = t.id
            JOIN users   u ON tc.user_id   = u.id
            WHERE tc.created_at > ?
              AND tc.user_id   != ?
              AND (t.created_by = ? OR t.assigned_to = ?)
            ORDER BY tc.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$sinceStr, $user_id, $user_id, $user_id]);
        $comments = $stmt->fetchAll();

        foreach ($comments as $c) {
            $notifications[] = [
                'id'    => 'ticket_cmt_' . $c['id'],
                'title' => '💬 New Comment on ' . $c['ticket_number'],
                'body'  => $c['commenter'] . ': "' . mb_strimwidth($c['comment'], 0, 80, '…') . '"',
                'time'  => $c['created_at'],
                'type'  => 'comment',
            ];
        }

        // ── 3. Tasks assigned to me since last check ────────────────────────
        $stmt = $pdo->prepare("
            SELECT id, title, priority, created_at
            FROM tasks
            WHERE assigned_to  = ?
              AND created_by  != ?
              AND created_at   > ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id, $user_id, $sinceStr]);
        $assignedTasks = $stmt->fetchAll();

        foreach ($assignedTasks as $t) {
            $notifications[] = [
                'id'    => 'task_assign_' . $t['id'],
                'title' => '📋 New Task Assigned to You',
                'body'  => '"' . $t['title'] . '" has been assigned to you.',
                'time'  => $t['created_at'],
                'type'  => 'task',
            ];
        }

        // ── 4. Due soon reminders (tasks due in next 60 minutes) ────────────
        $stmt = $pdo->prepare("
            SELECT id, title, due_date
            FROM tasks
            WHERE (created_by = ? OR assigned_to = ?)
              AND status   != 'completed'
              AND due_date  > NOW()
              AND due_date <= DATE_ADD(NOW(), INTERVAL 60 MINUTE)
            ORDER BY due_date ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id, $user_id]);
        $dueSoon = $stmt->fetchAll();

        foreach ($dueSoon as $t) {
            $notifications[] = [
                'id'    => 'due_soon_' . $t['id'],
                'title' => '⏰ Task Due Soon',
                'body'  => '"' . $t['title'] . '" is due at ' . date('H:i', strtotime($t['due_date'])),
                'time'  => $t['due_date'],
                'type'  => 'reminder',
            ];
        }

        // Sort all notifications newest first
        usort($notifications, fn($a, $b) => strcmp($b['time'], $a['time']));

        echo json_encode([
            'success'       => true,
            'count'         => count($notifications),
            'server_time'   => time(),         // client uses this as next ?since=
            'data'          => $notifications,
        ]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
