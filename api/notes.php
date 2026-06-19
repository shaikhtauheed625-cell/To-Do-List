<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// POST-Tunneling: Allow overriding the HTTP method using _method or action (bypasses restrictive firewalls)
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            if (isset($jsonData['_method'])) {
                $method = strtoupper($jsonData['_method']);
            } elseif (isset($jsonData['action'])) {
                if (strtolower($jsonData['action']) === 'delete') {
                    $method = 'DELETE';
                } elseif (strtolower($jsonData['action']) === 'put' || strtolower($jsonData['action']) === 'update') {
                    $method = 'PUT';
                }
            }
        }
    }
}

try {
    switch ($method) {
        case 'GET':
            // Fetch notes
            if (isAdmin()) {
                $stmt = $pdo->prepare("SELECT n.*, u.username as owner_name FROM notes n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$user_id]);
            }
            $notes = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $notes]);
            break;

        case 'POST':
            // Create new note
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['title']) || empty($data['title'])) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }

            $title = sanitize_input($data['title']);
            $content = sanitize_input($data['content'] ?? '');
            $color = sanitize_input($data['color'] ?? 'yellow');

            $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $content, $color]);
            $note_id = $pdo->lastInsertId();

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Created note', ?)");
            $logStmt->execute([$user_id, $title]);

            echo json_encode([
                'success' => true, 
                'message' => 'Note created successfully', 
                'data' => [
                    'id' => $note_id,
                    'title' => $title,
                    'content' => $content,
                    'color' => $color,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
            break;

        case 'DELETE':
            // Delete note
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'Note ID is required']);
                exit;
            }

            $id = $data['id'];
            
            // Verify ownership
            if (isAdmin()) {
                $check = $pdo->prepare("SELECT title FROM notes WHERE id = ?");
                $check->execute([$id]);
            } else {
                $check = $pdo->prepare("SELECT title FROM notes WHERE id = ? AND user_id = ?");
                $check->execute([$id, $user_id]);
            }
            $note = $check->fetch();
            if (!$note) {
                echo json_encode(['success' => false, 'message' => 'Note not found or unauthorized']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
            $stmt->execute([$id]);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Deleted note', ?)");
            $logStmt->execute([$user_id, $note['title']]);

            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
