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
    switch ($method) {
        case 'GET':
            // 1. Fetch registered workspace users who have saved a phone number
            $usersStmt = $pdo->query("SELECT id, username as name, phone, email, 'workspace' as type, NULL as company FROM users WHERE phone IS NOT NULL AND phone != '' ORDER BY username ASC");
            $workspaceContacts = $usersStmt->fetchAll();

            // 2. Fetch custom external directory contacts (created by system seeds or by the active user)
            $contactsStmt = $pdo->prepare("SELECT id, name, phone, email, company, 'client' as type FROM contacts WHERE created_by = ? OR created_by IS NULL ORDER BY name ASC");
            $contactsStmt->execute([$user_id]);
            $externalContacts = $contactsStmt->fetchAll();

            // 3. Collate and merge both lists
            $allContacts = array_merge($workspaceContacts, $externalContacts);

            // Sort merged list alphabetically by name
            usort($allContacts, function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            echo json_encode(['success' => true, 'data' => $allContacts]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['name']) || empty($data['name']) || !isset($data['phone']) || empty($data['phone'])) {
                echo json_encode(['success' => false, 'message' => 'Name and Phone number are required']);
                exit;
            }

            $name = sanitize_input($data['name']);
            $phone = sanitize_input($data['phone']);
            $email = sanitize_input($data['email'] ?? '');
            $company = sanitize_input($data['company'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO contacts (name, phone, email, company, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $company, $user_id]);
            $contactId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Contact added to directory successfully',
                'data' => [
                    'id' => $contactId,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'company' => $company,
                    'type' => 'client'
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
