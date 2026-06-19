<?php
require_once 'config/config.php';

try {
    // We will set the password back to the default: Admin@123
    $newPassword = 'Admin@123';
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the admin password and role if user exists
    $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ? OR username = ?");
    $stmt->execute([$hash, 'admin@taskflow.pro', 'admin']);

    if ($stmt->rowCount() > 0) {
        echo "<div style='font-family: sans-serif; padding: 20px; max-width: 500px; margin: 50px auto; border: 1px solid #10B981; border-radius: 12px; background-color: #ECFDF5; color: #065F46;'>";
        echo "<h2>Password Reset Successful!</h2>";
        echo "<p>Your password is now reset to: <strong>" . $newPassword . "</strong></p>";
        echo "<p><strong>Security Warning:</strong> Please login at <a href='login' style='color:#2563EB; font-weight:bold;'>login page</a> and immediately <strong>delete this reset.php file</strong> from your cPanel File Manager.</p>";
        echo "</div>";
    } else {
        // If the user doesn't exist, let's insert a fresh Admin account
        $check = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? OR username = ?");
        $check->execute(['admin@taskflow.pro', 'admin']);
        if ($check->fetch()['count'] == 0) {
            $insert = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $insert->execute(['admin', 'admin@taskflow.pro', $hash, 'admin']);
            echo "<div style='font-family: sans-serif; padding: 20px; max-width: 500px; margin: 50px auto; border: 1px solid #3B82F6; border-radius: 12px; background-color: #EFF6FF; color: #1E3A8A;'>";
            echo "<h2>New Admin Account Created!</h2>";
            echo "<p>Created a fresh admin account with:</p>";
            echo "<ul>";
            echo "<li>Email: <strong>admin@taskflow.pro</strong></li>";
            echo "<li>Password: <strong>" . $newPassword . "</strong></li>";
            echo "</ul>";
            echo "<p><strong>Security Warning:</strong> Please login at <a href='login' style='color:#2563EB; font-weight:bold;'>login page</a> and immediately <strong>delete this reset.php file</strong> from your cPanel File Manager.</p>";
            echo "</div>";
        } else {
            echo "<div style='font-family: sans-serif; padding: 20px; max-width: 500px; margin: 50px auto; border: 1px solid #F59E0B; border-radius: 12px; background-color: #FEF3C7; color: #92400E;'>";
            echo "<h2>No Changes Made</h2>";
            echo "<p>The password for <strong>admin@taskflow.pro</strong> is already set to: <strong>" . $newPassword . "</strong></p>";
            echo "<p>Please ensure you are typing it exactly as shown (case-sensitive) on the login page.</p>";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; max-width: 500px; margin: 50px auto; border: 1px solid #EF4444; border-radius: 12px; background-color: #FEF2F2; color: #991B1B;'>";
    echo "<h2>Error Resetting Password</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
