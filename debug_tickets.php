<?php
require_once 'config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tickets Debugger</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f8f9fa; color: #333; }
        h1, h2 { color: #1d3557; }
        pre { background: #e9ecef; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 14px; border: 1px solid #dee2e6; }
        .box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <h1>Tickets Debugger</h1>
    
    <div class="box">
        <h2>Session Info</h2>
        <pre><?php
        if (isLoggedIn()) {
            echo "Logged In: Yes\n";
            echo "User ID: " . $_SESSION['user_id'] . "\n";
            echo "Username: " . $_SESSION['username'] . "\n";
            echo "Role: " . $_SESSION['role'] . "\n";
        } else {
            echo "Logged In: No\n";
        }
        ?></pre>
    </div>

    <div class="box">
        <h2>DB Query Test</h2>
        <pre><?php
        if (isLoggedIn()) {
            $user_id = intval($_SESSION['user_id']);
            $query = "SELECT t.id, t.ticket_number, t.title, t.created_by, t.assigned_to,
                             u_assign.username as assignee_name,
                             u_create.username as creator_name
                      FROM tickets t 
                      LEFT JOIN users u_assign ON t.assigned_to = u_assign.id
                      LEFT JOIN users u_create ON t.created_by = u_create.id
                      WHERE 1=1";
            $params = [];

            if ($_SESSION['role'] !== 'admin') {
                $query .= " AND (t.created_by = ? OR t.assigned_to = ?)";
                $params[] = $user_id;
                $params[] = $user_id;
            }

            echo "Query: $query\n";
            echo "Params: " . json_encode($params) . "\n\n";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "Returned Tickets Count: " . count($tickets) . "\n\n";
            print_r($tickets);
        } else {
            echo "Please log in first.";
        }
        ?></pre>
    </div>
</body>
</html>
