<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$method = $_SERVER['REQUEST_METHOD'];

// POST-Tunneling: Allow overriding the HTTP method using _method or action (bypasses restrictive firewalls)
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            if (isset($jsonData['_method'])) {
                $method = strtoupper($jsonData['_method']);
            } elseif (isset($jsonData['action']) && in_array(strtolower($jsonData['action']), ['put', 'update', 'delete'])) {
                if (strtolower($jsonData['action']) === 'delete') {
                    $method = 'DELETE';
                } else {
                    $method = 'PUT';
                }
            }
        }
    }
}

try {
    switch ($method) {
        case 'GET':
            // Check if we are fetching comments or activity logs for a specific ticket
            if (isset($_GET['ticket_details_id'])) {
                $ticketId = intval($_GET['ticket_details_id']);

                // Fetch ticket details
                $stmt = $pdo->prepare("SELECT t.*, 
                                              u_assign.username as assignee_name, u_assign.profile_image as assignee_photo,
                                              u_create.username as creator_name, u_create.profile_image as creator_photo
                                       FROM tickets t 
                                       LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                                       LEFT JOIN users u_create ON t.created_by = u_create.id
                                       WHERE t.id = ?");
                $stmt->execute([$ticketId]);
                $ticket = $stmt->fetch();

                if (!$ticket) {
                    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
                    exit;
                }

                // Check authorization for non-admin users (RBAC security filter)
                if ($_SESSION['role'] !== 'admin' && intval($ticket['created_by']) !== $user_id && intval($ticket['assigned_to']) !== $user_id) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized access to ticket details']);
                    exit;
                }

                // Fetch comments
                $commentStmt = $pdo->prepare("SELECT c.*, u.username as comment_user, u.profile_image as comment_user_photo
                                              FROM ticket_comments c
                                              LEFT JOIN users u ON c.user_id = u.id
                                              WHERE c.ticket_id = ?
                                              ORDER BY c.created_at ASC");
                $commentStmt->execute([$ticketId]);
                $comments = $commentStmt->fetchAll();

                // Fetch activity log
                $logStmt = $pdo->prepare("SELECT l.*, u.username as action_user
                                          FROM ticket_activity_log l
                                          LEFT JOIN users u ON l.user_id = u.id
                                          WHERE l.ticket_id = ?
                                          ORDER BY l.created_at DESC");
                $logStmt->execute([$ticketId]);
                $logs = $logStmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'ticket' => $ticket,
                    'comments' => $comments,
                    'logs' => $logs
                ]);
                exit;
            }

            // Normal ticket listing
            $search = sanitize_input($_GET['search'] ?? '');
            $filter_view = sanitize_input($_GET['filter_view'] ?? 'all');
            $sort_by = sanitize_input($_GET['sort_by'] ?? 'created_at');
            $sort_order = sanitize_input($_GET['sort_order'] ?? 'DESC');

            // Build query
            $query = "SELECT t.*, 
                             u_assign.username as assignee_name, u_assign.profile_image as assignee_photo,
                             u_create.username as creator_name, u_create.profile_image as creator_photo
                      FROM tickets t 
                      LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                      LEFT JOIN users u_create ON t.created_by = u_create.id
                      WHERE 1=1";
            $params = [];

            // Apply search
            if (!empty($search)) {
                $query .= " AND (t.title LIKE ? OR t.description LIKE ? OR t.ticket_number LIKE ?)";
                $searchWildcard = "%" . $search . "%";
                $params[] = $searchWildcard;
                $params[] = $searchWildcard;
                $params[] = $searchWildcard;
            }

            // Apply filter views
            switch ($filter_view) {
                case 'open':
                    // Open or newly created, or unassigned (but not resolved/closed)
                    $query .= " AND t.status IN ('open', 'in_progress') AND (t.assigned_to IS NULL OR t.status = 'open')";
                    break;
                case 'assigned':
                    // Assigned to me
                    $query .= " AND t.assigned_to = ?";
                    $params[] = $user_id;
                    break;
                case 'closed':
                    $query .= " AND t.status IN ('resolved', 'closed')";
                    break;
                case 'breached':
                    // Breached tickets (past due date and not resolved/closed)
                    $query .= " AND t.due_date < NOW() AND t.status NOT IN ('resolved', 'closed')";
                    break;
                case 'high_critical':
                    $query .= " AND t.priority IN ('high', 'critical')";
                    break;
                case 'all':
                default:
                    // No additional filter
                    break;
            }

            // Apply Role-Based Access Control (RBAC) scoping for non-admin users
            if ($_SESSION['role'] !== 'admin') {
                $query .= " AND (t.created_by = ? OR t.assigned_to = ?)";
                $params[] = $user_id;
                $params[] = $user_id;
            }

            // Allowed sorting fields
            $allowedSorts = ['created_at', 'due_date', 'priority', 'status', 'ticket_number'];
            if (!in_array($sort_by, $allowedSorts)) {
                $sort_by = 'created_at';
            }
            $sort_order = (strtoupper($sort_order) === 'ASC') ? 'ASC' : 'DESC';

            // Special sort for priority: critical -> high -> medium -> low
            if ($sort_by === 'priority') {
                $query .= " ORDER BY CASE t.priority 
                             WHEN 'critical' THEN 4
                             WHEN 'high' THEN 3
                             WHEN 'medium' THEN 2
                             WHEN 'low' THEN 1
                             ELSE 0 END " . $sort_order;
            } elseif ($sort_by === 'status') {
                // open -> in_progress -> resolved -> closed
                $query .= " ORDER BY CASE t.status 
                             WHEN 'open' THEN 1
                             WHEN 'in_progress' THEN 2
                             WHEN 'resolved' THEN 3
                             WHEN 'closed' THEN 4
                             ELSE 5 END " . $sort_order;
            } else {
                $query .= " ORDER BY t." . $sort_by . " " . $sort_order;
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll();

            // Fetch users for assignees dropdown list
            $usersStmt = $pdo->query("SELECT id, username, profile_image FROM users ORDER BY username ASC");
            $usersList = $usersStmt->fetchAll();

            // Calculate KPI statistics scoped by role
            if ($_SESSION['role'] === 'admin') {
                $stats = [
                    'total' => $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
                    'open' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn(),
                    'in_progress' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn(),
                    'completed' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed')")->fetchColumn(),
                    'critical' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE priority = 'critical' AND status NOT IN ('resolved', 'closed')")->fetchColumn(),
                    'breached' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE due_date < NOW() AND status NOT IN ('resolved', 'closed')")->fetchColumn()
                ];
            } else {
                $statStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE (created_by = ? OR assigned_to = ?)");
                $statStmt->execute([$user_id, $user_id]);
                $total = $statStmt->fetchColumn();

                $statStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = 'open' AND (created_by = ? OR assigned_to = ?)");
                $statStmt->execute([$user_id, $user_id]);
                $open = $statStmt->fetchColumn();

                $statStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress' AND (created_by = ? OR assigned_to = ?)");
                $statStmt->execute([$user_id, $user_id]);
                $in_progress = $statStmt->fetchColumn();

                $statStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed') AND (created_by = ? OR assigned_to = ?)");
                $statStmt->execute([$user_id, $user_id]);
                $completed = $statStmt->fetchColumn();

                $statStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE priority = 'critical' AND status NOT IN ('resolved', 'closed') AND (created_by = ? OR assigned_to = ?)");
                $statStmt->execute([$user_id, $user_id]);
                $critical = $statStmt->fetchColumn();

                $statStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE due_date < NOW() AND status NOT IN ('resolved', 'closed') AND (created_by = ? OR assigned_to = ?)");
                $statStmt->execute([$user_id, $user_id]);
                $breached = $statStmt->fetchColumn();

                $stats = [
                    'total' => $total,
                    'open' => $open,
                    'in_progress' => $in_progress,
                    'completed' => $completed,
                    'critical' => $critical,
                    'breached' => $breached
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $tickets,
                'users' => $usersList,
                'stats' => $stats
            ]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = sanitize_input($_GET['action'] ?? $data['action'] ?? '');

            if ($action === 'comment') {
                // Add a collaborative comment
                if (!isset($data['ticket_id']) || empty($data['ticket_id']) || !isset($data['comment']) || empty(trim($data['comment']))) {
                    echo json_encode(['success' => false, 'message' => 'Ticket ID and Comment are required']);
                    exit;
                }

                $ticketId = intval($data['ticket_id']);
                $commentText = sanitize_input($data['comment']);

                // Verify ticket exists
                $check = $pdo->prepare("SELECT id, ticket_number, created_by, assigned_to FROM tickets WHERE id = ?");
                $check->execute([$ticketId]);
                $ticket = $check->fetch();
                if (!$ticket) {
                    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
                    exit;
                }

                // Check authorization for non-admin users (RBAC comment safety filter)
                if ($_SESSION['role'] !== 'admin' && intval($ticket['created_by']) !== $user_id && intval($ticket['assigned_to']) !== $user_id) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized to comment on this ticket']);
                    exit;
                }

                // Insert comment
                $commentStmt = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
                $commentStmt->execute([$ticketId, $user_id, $commentText]);
                $commentId = $pdo->lastInsertId();

                // Log Activity
                $logStmt = $pdo->prepare("INSERT INTO ticket_activity_log (ticket_id, user_id, action) VALUES (?, ?, 'Added a comment')");
                $logStmt->execute([$ticketId, $user_id]);

                // Fetch newly created comment with user details
                $fetchComment = $pdo->prepare("SELECT c.*, u.username as comment_user, u.profile_image as comment_user_photo
                                               FROM ticket_comments c
                                               LEFT JOIN users u ON c.user_id = u.id
                                               WHERE c.id = ?");
                $fetchComment->execute([$commentId]);
                $commentData = $fetchComment->fetch();

                echo json_encode([
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'data' => $commentData
                ]);
                exit;

            } elseif ($action === 'bulk') {
                // Bulk action capability
                if (!isset($data['ticket_ids']) || !is_array($data['ticket_ids']) || empty($data['ticket_ids'])) {
                    echo json_encode(['success' => false, 'message' => 'Ticket IDs are required']);
                    exit;
                }

                $ticketIds = array_map('intval', $data['ticket_ids']);
                $bulkStatus = sanitize_input($data['bulk_status'] ?? '');
                $bulkAssignee = isset($data['bulk_assigned_to']) ? ($data['bulk_assigned_to'] === '' ? null : intval($data['bulk_assigned_to'])) : 'no_change';

                $pdo->beginTransaction();

                foreach ($ticketIds as $tId) {
                    // Fetch existing details for logging
                    $check = $pdo->prepare("SELECT id, status, assigned_to, created_by FROM tickets WHERE id = ?");
                    $check->execute([$tId]);
                    $ticket = $check->fetch();
                    if (!$ticket) continue;

                    // Check authorization for non-admin users (RBAC bulk safety filter)
                    if ($_SESSION['role'] !== 'admin' && intval($ticket['created_by']) !== $user_id && intval($ticket['assigned_to']) !== $user_id) {
                        continue;
                    }

                    $fields = [];
                    $params = [];
                    $logActions = [];

                    if (!empty($bulkStatus) && $bulkStatus !== $ticket['status']) {
                        $fields[] = "status = ?";
                        $params[] = $bulkStatus;
                        $logActions[] = "Bulk status updated to " . ucfirst(str_replace('_', ' ', $bulkStatus));
                    }

                    if ($bulkAssignee !== 'no_change' && $bulkAssignee !== $ticket['assigned_to']) {
                        $fields[] = "assigned_to = ?";
                        $params[] = $bulkAssignee;

                        if ($bulkAssignee === null) {
                            $logActions[] = "Bulk unassigned ticket";
                        } else {
                            $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                            $uStmt->execute([$bulkAssignee]);
                            $uName = $uStmt->fetchColumn() ?: 'Unknown';
                            $logActions[] = "Bulk assigned to " . $uName;
                        }
                    }

                    if (!empty($fields)) {
                        $params[] = $tId;
                        $upStmt = $pdo->prepare("UPDATE tickets SET " . implode(", ", $fields) . " WHERE id = ?");
                        $upStmt->execute($params);

                        // Insert logs
                        $logStmt = $pdo->prepare("INSERT INTO ticket_activity_log (ticket_id, user_id, action) VALUES (?, ?, ?)");
                        foreach ($logActions as $act) {
                            $logStmt->execute([$tId, $user_id, $act]);
                        }
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Bulk update completed successfully']);
                exit;

            } else {
                // Create a new ticket
                if (!isset($data['title']) || empty(trim($data['title'])) || !isset($data['due_date']) || empty($data['due_date'])) {
                    echo json_encode(['success' => false, 'message' => 'Title and Due Date are required']);
                    exit;
                }

                $title = sanitize_input($data['title']);
                $description = sanitize_input($data['description'] ?? '');
                $priority = sanitize_input($data['priority'] ?? 'medium');
                $due_date = sanitize_input($data['due_date']);
                $assigned_to = !empty($data['assigned_to']) ? intval($data['assigned_to']) : null;

                // Auto-generate ticket number #TIC-xxxx
                // Find current maximum ID to determine new number cleanly
                $maxIdStmt = $pdo->query("SELECT MAX(id) as max_id FROM tickets");
                $maxIdRow = $maxIdStmt->fetch();
                $nextNum = ($maxIdRow && $maxIdRow['max_id']) ? ($maxIdRow['max_id'] + 1001) : 1001;
                $ticketNumber = "#TIC-" . $nextNum;

                $stmt = $pdo->prepare("INSERT INTO tickets (ticket_number, title, description, priority, due_date, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ticketNumber, $title, $description, $priority, $due_date, $assigned_to, $user_id]);
                $newTicketId = $pdo->lastInsertId();

                // Log Activity
                $logStmt = $pdo->prepare("INSERT INTO ticket_activity_log (ticket_id, user_id, action) VALUES (?, ?, 'Ticket created')");
                $logStmt->execute([$newTicketId, $user_id]);

                if ($assigned_to) {
                    $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $uStmt->execute([$assigned_to]);
                    $uName = $uStmt->fetchColumn();
                    if ($uName) {
                        $logStmt = $pdo->prepare("INSERT INTO ticket_activity_log (ticket_id, user_id, action) VALUES (?, ?, ?)");
                        $logStmt->execute([$newTicketId, $user_id, "Assigned ticket to " . $uName]);
                    }
                }

                echo json_encode(['success' => true, 'message' => 'Ticket ' . $ticketNumber . ' created successfully', 'id' => $newTicketId]);
                exit;
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'Ticket ID is required']);
                exit;
            }

            $id = intval($data['id']);

            // Fetch existing details for differential log mapping
            $check = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
            $check->execute([$id]);
            $ticket = $check->fetch();
            if (!$ticket) {
                echo json_encode(['success' => false, 'message' => 'Ticket not found']);
                exit;
            }

            // Check authorization for non-admin users (RBAC update safety filter)
            if ($_SESSION['role'] !== 'admin' && intval($ticket['created_by']) !== $user_id && intval($ticket['assigned_to']) !== $user_id) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized to modify this ticket']);
                exit;
            }

            $fields = [];
            $params = [];
            $logActions = [];

            if (isset($data['title'])) {
                $newTitle = sanitize_input($data['title']);
                if ($newTitle !== $ticket['title']) {
                    $fields[] = "title = ?";
                    $params[] = $newTitle;
                    $logActions[] = "Title updated";
                }
            }

            if (isset($data['description'])) {
                $newDesc = sanitize_input($data['description']);
                if ($newDesc !== $ticket['description']) {
                    $fields[] = "description = ?";
                    $params[] = $newDesc;
                    $logActions[] = "Description updated";
                }
            }

            if (isset($data['status'])) {
                $newStatus = sanitize_input($data['status']);
                if ($newStatus !== $ticket['status']) {
                    $fields[] = "status = ?";
                    $params[] = $newStatus;
                    
                    $oldFriendly = ucfirst(str_replace('_', ' ', $ticket['status']));
                    $newFriendly = ucfirst(str_replace('_', ' ', $newStatus));
                    $logActions[] = "Status changed from " . $oldFriendly . " to " . $newFriendly;
                }
            }

            if (isset($data['priority'])) {
                $newPriority = sanitize_input($data['priority']);
                if ($newPriority !== $ticket['priority']) {
                    $fields[] = "priority = ?";
                    $params[] = $newPriority;
                    $logActions[] = "Priority changed from " . ucfirst($ticket['priority']) . " to " . ucfirst($newPriority);
                }
            }

            if (isset($data['due_date'])) {
                $newDueDate = sanitize_input($data['due_date']);
                if ($newDueDate !== $ticket['due_date']) {
                    $fields[] = "due_date = ?";
                    $params[] = $newDueDate;
                    $logActions[] = "Due date updated to " . date('Y-m-d H:i', strtotime($newDueDate));
                }
            }

            if (isset($data['assigned_to'])) {
                // If it is sent as empty string/null
                $newAssigned = !empty($data['assigned_to']) ? intval($data['assigned_to']) : null;
                if ($newAssigned !== ($ticket['assigned_to'] ? intval($ticket['assigned_to']) : null)) {
                    $fields[] = "assigned_to = ?";
                    $params[] = $newAssigned;

                    if ($newAssigned === null) {
                        $logActions[] = "Ticket unassigned";
                    } else {
                        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $uStmt->execute([$newAssigned]);
                        $uName = $uStmt->fetchColumn() ?: 'Unknown';
                        $logActions[] = "Assigned ticket to " . $uName;
                    }
                }
            }

            if (!empty($fields)) {
                $params[] = $id;
                $stmt = $pdo->prepare("UPDATE tickets SET " . implode(", ", $fields) . " WHERE id = ?");
                $stmt->execute($params);

                // Write activity logs
                $logStmt = $pdo->prepare("INSERT INTO ticket_activity_log (ticket_id, user_id, action) VALUES (?, ?, ?)");
                foreach ($logActions as $act) {
                    $logStmt->execute([$id, $user_id, $act]);
                }

                echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
            } else {
                echo json_encode(['success' => true, 'message' => 'No changes made']);
            }
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id']) && !isset($data['ids'])) {
                echo json_encode(['success' => false, 'message' => 'Ticket ID(s) required']);
                exit;
            }

            $ids = isset($data['ids']) ? array_map('intval', $data['ids']) : [intval($data['id'])];
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'No ticket IDs specified']);
                exit;
            }

            $deletedCount = 0;
            $pdo->beginTransaction();

            foreach ($ids as $id) {
                $check = $pdo->prepare("SELECT id, ticket_number, created_by, assigned_to FROM tickets WHERE id = ?");
                $check->execute([$id]);
                $ticket = $check->fetch();

                if (!$ticket) {
                    continue;
                }

                // Check authorization: Admin can delete any. Employee/Manager can only delete if creator or assignee.
                if ($_SESSION['role'] !== 'admin' && intval($ticket['created_by']) !== $user_id && intval($ticket['assigned_to']) !== $user_id) {
                    continue;
                }

                $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
                $stmt->execute([$id]);
                $deletedCount++;
            }

            $pdo->commit();

            if ($deletedCount === 0 && !empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized or tickets not found']);
            } else {
                echo json_encode(['success' => true, 'message' => "$deletedCount ticket(s) deleted successfully"]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
