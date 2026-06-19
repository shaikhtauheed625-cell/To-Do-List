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

// Handle different HTTP methods
try {
    switch ($method) {
        case 'GET':
            // Fetch tasks with assignee, creator, and project information
            if (isAdmin()) {
                $stmt = $pdo->prepare("SELECT t.*, 
                                              u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                              u_create.username as creator_name, u_create.phone as creator_phone,
                                              p.name as project_name, p.color as project_color
                                       FROM tasks t 
                                       LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                                       LEFT JOIN users u_create ON t.created_by = u_create.id
                                       LEFT JOIN projects p ON t.project_id = p.id
                                       ORDER BY t.created_at DESC");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT t.*, 
                                              u_assign.username as assignee_name, u_assign.phone as assignee_phone,
                                              u_create.username as creator_name, u_create.phone as creator_phone,
                                              p.name as project_name, p.color as project_color
                                       FROM tasks t 
                                       LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                                       LEFT JOIN users u_create ON t.created_by = u_create.id
                                       LEFT JOIN projects p ON t.project_id = p.id
                                       WHERE t.created_by = ? OR t.assigned_to = ? 
                                       ORDER BY t.created_at DESC");
                $stmt->execute([$user_id, $user_id]);
            }
            $tasks = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $tasks]);
            break;

        case 'POST':
            // Create new task
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['title']) || empty($data['title'])) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }

            $title = sanitize_input($data['title']);
            $description = sanitize_input($data['description'] ?? '');
            $priority = $data['priority'] ?? 'medium';
            $due_date = !empty($data['due_date']) ? $data['due_date'] : null;
            $assigned_to = !empty($data['assigned_to']) ? intval($data['assigned_to']) : null;
            $project_id = !empty($data['project_id']) ? intval($data['project_id']) : null;
            $phone = sanitize_input($data['phone'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, due_date, assigned_to, created_by, phone, project_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $priority, $due_date, $assigned_to, $user_id, $phone, $project_id]);
            $taskId = $pdo->lastInsertId();

            // Insert reminder if specified
            if (!empty($data['reminder_time'])) {
                $reminder_time = sanitize_input($data['reminder_time']);
                $reminderStmt = $pdo->prepare("INSERT INTO reminders (task_id, user_id, reminder_time, type) VALUES (?, ?, ?, 'browser')");
                $reminderStmt->execute([$taskId, $user_id, $reminder_time]);
            }
            
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Created task', ?)");
            $logStmt->execute([$user_id, $title]);

            echo json_encode(['success' => true, 'message' => 'Task created successfully', 'id' => $taskId]);
            break;

        case 'PUT':
            // Update task (status change or dynamic fields update)
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'Task ID is required']);
                exit;
            }

            $id = intval($data['id']);
            
            // Verify ownership
            if (isAdmin()) {
                $check = $pdo->prepare("SELECT id, title FROM tasks WHERE id = ?");
                $check->execute([$id]);
            } else {
                $check = $pdo->prepare("SELECT id, title FROM tasks WHERE id = ? AND (created_by = ? OR assigned_to = ?)");
                $check->execute([$id, $user_id, $user_id]);
            }
            $task = $check->fetch();
            if (!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found or unauthorized']);
                exit;
            }

            $fields = [];
            $params = [];

            if (isset($data['title'])) {
                $fields[] = "title = ?";
                $params[] = sanitize_input($data['title']);
            }
            if (isset($data['description'])) {
                $fields[] = "description = ?";
                $params[] = sanitize_input($data['description']);
            }
            if (isset($data['status'])) {
                $fields[] = "status = ?";
                $params[] = sanitize_input($data['status']);
            }
            if (isset($data['priority'])) {
                $fields[] = "priority = ?";
                $params[] = sanitize_input($data['priority']);
            }
            if (isset($data['due_date'])) {
                $fields[] = "due_date = ?";
                $params[] = !empty($data['due_date']) ? $data['due_date'] : null;
            }
            if (isset($data['assigned_to'])) {
                $fields[] = "assigned_to = ?";
                $params[] = !empty($data['assigned_to']) ? intval($data['assigned_to']) : null;
            }
            if (isset($data['project_id'])) {
                $fields[] = "project_id = ?";
                $params[] = !empty($data['project_id']) ? intval($data['project_id']) : null;
            }
            if (isset($data['phone'])) {
                $fields[] = "phone = ?";
                $params[] = sanitize_input($data['phone']);
            }

            if (!empty($fields)) {
                $params[] = $id;
                $stmt = $pdo->prepare("UPDATE tasks SET " . implode(", ", $fields) . " WHERE id = ?");
                $stmt->execute($params);

                // Log activity
                $action = 'Updated task';
                if (isset($data['status'])) {
                    if ($data['status'] === 'completed') {
                        $action = 'Completed task';
                    } elseif ($data['status'] === 'in_progress') {
                        $action = 'Started task';
                    } elseif ($data['status'] === 'todo') {
                        $action = 'Reset task to todo';
                    }
                }
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $logStmt->execute([$user_id, $action, $task['title']]);
            }

            // Upsert / Delete reminder if reminder_time is provided in the request
            if (isset($data['reminder_time'])) {
                $reminder_time = !empty($data['reminder_time']) ? sanitize_input($data['reminder_time']) : null;

                if ($reminder_time) {
                    // Check if an unsent reminder already exists for this task
                    $checkReminder = $pdo->prepare("SELECT id FROM reminders WHERE task_id = ? AND user_id = ? AND is_sent = 0");
                    $checkReminder->execute([$id, $user_id]);
                    $existingReminder = $checkReminder->fetch();

                    if ($existingReminder) {
                        // Update existing unsent reminder
                        $updateReminder = $pdo->prepare("UPDATE reminders SET reminder_time = ? WHERE id = ?");
                        $updateReminder->execute([$reminder_time, $existingReminder['id']]);
                    } else {
                        // Insert new reminder
                        $insertReminder = $pdo->prepare("INSERT INTO reminders (task_id, user_id, reminder_time, type) VALUES (?, ?, ?, 'browser')");
                        $insertReminder->execute([$id, $user_id, $reminder_time]);
                    }
                } else {
                    // If reminder_time is empty, delete any unsent reminder for this task
                    $deleteReminder = $pdo->prepare("DELETE FROM reminders WHERE task_id = ? AND user_id = ? AND is_sent = 0");
                    $deleteReminder->execute([$id, $user_id]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
            break;


        case 'DELETE':
            // Delete task(s)
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id']) && !isset($data['ids'])) {
                echo json_encode(['success' => false, 'message' => 'Task ID(s) required']);
                exit;
            }

            $ids = isset($data['ids']) ? array_map('intval', $data['ids']) : [intval($data['id'])];
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'No task IDs specified']);
                exit;
            }

            $deletedCount = 0;
            $pdo->beginTransaction();

            foreach ($ids as $id) {
                // Verify ownership
                if (isAdmin()) {
                    $check = $pdo->prepare("SELECT id, title FROM tasks WHERE id = ?");
                    $check->execute([$id]);
                } else {
                    $check = $pdo->prepare("SELECT id, title FROM tasks WHERE id = ? AND created_by = ?");
                    $check->execute([$id, $user_id]);
                }
                $task = $check->fetch();
                if (!$task) {
                    continue; // Skip unauthorized or non-existent tasks
                }

                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $stmt->execute([$id]);

                // Log activity
                $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Deleted task', ?)");
                $logStmt->execute([$user_id, $task['title']]);
                $deletedCount++;
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "$deletedCount task(s) deleted successfully"]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
