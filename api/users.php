<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Only logged in administrators can access this endpoint
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Administrators only.']);
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
            // Fetch all users with counts of their created tasks and projects
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.email, u.role, u.profile_image, u.phone, u.created_at,
                       (SELECT COUNT(*) FROM tasks WHERE created_by = u.id) as tasks_created,
                       (SELECT COUNT(*) FROM projects WHERE created_by = u.id) as projects_created
                FROM users u
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'POST':
            // Admin adding a new user
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['username']) || empty($data['username']) ||
                !isset($data['email']) || empty($data['email']) ||
                !isset($data['password']) || empty($data['password'])) {
                echo json_encode(['success' => false, 'message' => 'Username, Email, and Password are required.']);
                exit;
            }

            $username = sanitize_input($data['username']);
            $email = sanitize_input($data['email']);
            $phone = sanitize_input($data['phone'] ?? '');
            $role = sanitize_input($data['role'] ?? 'employee');
            $password = $data['password'];

            // Validate role
            if (!in_array($role, ['admin', 'manager', 'employee'])) {
                $role = 'employee';
            }

            // Check if email already exists
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->execute([$email]);
            if ($checkEmail->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email address is already registered.']);
                exit;
            }

            // Create user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role, $phone]);
            $newUserId = $pdo->lastInsertId();

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Admin added user', ?)");
            $logStmt->execute([$user_id, "Added $username ($role)"]);

            echo json_encode(['success' => true, 'message' => 'User account created successfully.', 'id' => $newUserId]);
            break;

        case 'PUT':
            // Admin updating user details or role
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'User ID is required.']);
                exit;
            }

            $targetId = intval($data['id']);
            
            // Check if target user exists
            $checkUser = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
            $checkUser->execute([$targetId]);
            $targetUser = $checkUser->fetch();
            if (!$targetUser) {
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }

            $fields = [];
            $params = [];

            if (isset($data['username'])) {
                $fields[] = "username = ?";
                $params[] = sanitize_input($data['username']);
            }
            if (isset($data['email'])) {
                $email = sanitize_input($data['email']);
                // Check if email is already taken by someone else
                $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $checkEmail->execute([$email, $targetId]);
                if ($checkEmail->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Email is already taken by another user.']);
                    exit;
                }
                $fields[] = "email = ?";
                $params[] = $email;
            }
            if (isset($data['phone'])) {
                $fields[] = "phone = ?";
                $params[] = sanitize_input($data['phone']);
            }
            if (isset($data['role'])) {
                $newRole = sanitize_input($data['role']);
                if (in_array($newRole, ['admin', 'manager', 'employee'])) {
                    // Prevent self-demotion if the active admin is changing their own role
                    if ($targetId === $user_id && $newRole !== 'admin') {
                        echo json_encode(['success' => false, 'message' => 'You cannot demote yourself. You must remain an Administrator.']);
                        exit;
                    }
                    $fields[] = "role = ?";
                    $params[] = $newRole;
                }
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($fields)) {
                echo json_encode(['success' => false, 'message' => 'No fields to update.']);
                exit;
            }

            $params[] = $targetId;
            $updateSql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($params);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Admin updated user', ?)");
            $logStmt->execute([$user_id, "Updated details for " . ($data['username'] ?? $targetUser['username'])]);

            echo json_encode(['success' => true, 'message' => 'User details updated successfully.']);
            break;

        case 'DELETE':
            // Admin deleting a user
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'User ID is required.']);
                exit;
            }

            $targetId = intval($data['id']);

            // Prevent self-deletion
            if ($targetId === $user_id) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own Administrator account.']);
                exit;
            }

            // Get username of target for activity log
            $checkUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $checkUser->execute([$targetId]);
            $targetUser = $checkUser->fetch();
            if (!$targetUser) {
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }

            // Delete user (Database Cascade rules will handle Tasks, Projects, Comments, etc.)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$targetId]);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, 'Admin deleted user', ?)");
            $logStmt->execute([$user_id, "Deleted account for " . $targetUser['username']]);

            echo json_encode(['success' => true, 'message' => 'User account deleted successfully.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
