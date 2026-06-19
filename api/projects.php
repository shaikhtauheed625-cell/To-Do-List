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
            // Check if projects are empty, if so, seed some sample projects
            if (isAdmin()) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects");
                $checkStmt->execute();
            } else {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE created_by = ?");
                $checkStmt->execute([$user_id]);
            }
            $count = $checkStmt->fetch()['count'];

            if ($count == 0) {
                // Auto seed 3 projects
                $seedProjects = [
                    ['Website Redesign', 'Complete overhaul of the corporate website using the new design system.', '#8B5CF6'],
                    ['Mobile App Launch', 'Develop and launch the iOS and Android applications.', '#3B82F6'],
                    ['Marketing Q3', 'Plan and execute the marketing strategy for the upcoming quarter.', '#10B981']
                ];

                $insertStmt = $pdo->prepare("INSERT INTO projects (name, description, color, created_by) VALUES (?, ?, ?, ?)");
                foreach ($seedProjects as $proj) {
                    $insertStmt->execute([$proj[0], $proj[1], $proj[2], $user_id]);
                    $projId = $pdo->lastInsertId();

                    // Seed some tasks in these projects so progress bars look alive and dynamic
                    if ($proj[0] === 'Website Redesign') {
                        $tasks = [
                            ['Design Homepage mockups', 'completed'],
                            ['Client review meeting', 'completed'],
                            ['Develop front-end modules', 'in_progress'],
                            ['Write unit tests', 'todo']
                        ];
                    } elseif ($proj[0] === 'Mobile App Launch') {
                        $tasks = [
                            ['Configure App Store Connect', 'completed'],
                            ['Implement push notification service', 'todo'],
                            ['Alpha testing rollout', 'todo']
                        ];
                    } else {
                        $tasks = [
                            ['Prepare presentation decks', 'completed'],
                            ['Finalize pricing tiers', 'completed'],
                            ['Publish press release', 'completed'],
                            ['Ad budget allocation', 'completed'],
                            ['Kickoff social campaign', 'todo']
                        ];
                    }

                    $taskInsert = $pdo->prepare("INSERT INTO tasks (title, status, project_id, created_by) VALUES (?, ?, ?, ?)");
                    foreach ($tasks as $t) {
                        $taskInsert->execute([$t[0], $t[1], $projId, $user_id]);
                    }
                }
            }

            // Fetch projects with task counts
            if (isAdmin()) {
                $stmt = $pdo->prepare("SELECT p.*, 
                                              COUNT(t.id) as total_tasks,
                                              SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                                       FROM projects p
                                       LEFT JOIN tasks t ON t.project_id = p.id
                                       GROUP BY p.id
                                       ORDER BY p.created_at DESC");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT p.*, 
                                              COUNT(t.id) as total_tasks,
                                              SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                                       FROM projects p
                                       LEFT JOIN tasks t ON t.project_id = p.id
                                       WHERE p.created_by = ?
                                       GROUP BY p.id
                                       ORDER BY p.created_at DESC");
                $stmt->execute([$user_id]);
            }
            $projects = $stmt->fetchAll();

            // Calculate progress percentage
            $formatted = [];
            foreach ($projects as $proj) {
                $total = intval($proj['total_tasks']);
                $completed = intval($proj['completed_tasks']);
                $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

                $formatted[] = [
                    'id' => $proj['id'],
                    'name' => $proj['name'],
                    'description' => $proj['description'],
                    'color' => $proj['color'],
                    'total_tasks' => $total,
                    'completed_tasks' => $completed,
                    'progress' => $progress,
                    'created_at' => $proj['created_at']
                ];
            }

            echo json_encode(['success' => true, 'data' => $formatted]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['name']) || empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'Project name is required']);
                exit;
            }

            $name = sanitize_input($data['name']);
            $description = sanitize_input($data['description'] ?? '');
            $color = sanitize_input($data['color'] ?? '#2563EB');

            $stmt = $pdo->prepare("INSERT INTO projects (name, description, color, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $color, $user_id]);
            $proj_id = $pdo->lastInsertId();

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Created project', ?)");
            $logStmt->execute([$user_id, $name]);

            echo json_encode([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => [
                    'id' => $proj_id,
                    'name' => $name,
                    'description' => $description,
                    'color' => $color,
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'progress' => 0
                ]
            ]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'Project ID is required']);
                exit;
            }

            $id = intval($data['id']);
            $name = sanitize_input($data['name'] ?? '');
            $description = sanitize_input($data['description'] ?? '');
            $color = sanitize_input($data['color'] ?? '#2563EB');

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Project name is required']);
                exit;
            }

            // Verify ownership
            if (isAdmin()) {
                $check = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
                $check->execute([$id]);
            } else {
                $check = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND created_by = ?");
                $check->execute([$id, $user_id]);
            }
            if (!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Project not found or unauthorized']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, color = ? WHERE id = ?");
            $stmt->execute([$name, $description, $color, $id]);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Updated project', ?)");
            $logStmt->execute([$user_id, $name]);

            echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'Project ID is required']);
                exit;
            }

            $id = intval($data['id']);

            // Verify ownership
            if (isAdmin()) {
                $check = $pdo->prepare("SELECT id, name FROM projects WHERE id = ?");
                $check->execute([$id]);
            } else {
                $check = $pdo->prepare("SELECT id, name FROM projects WHERE id = ? AND created_by = ?");
                $check->execute([$id, $user_id]);
            }
            $proj = $check->fetch();
            if (!$proj) {
                echo json_encode(['success' => false, 'message' => 'Project not found or unauthorized']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Deleted project', ?)");
            $logStmt->execute([$user_id, $proj['name']]);

            echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
