<?php
require_once 'config/config.php';

// Unset all session variables
$_SESSION = array();

// If session cookies are used, delete the session cookie explicitly
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
redirect('/login.php');
exit;
?>
