<?php
// You MUST start the session to be able to destroy it
session_start();

// 1. Unset all session variables
$_SESSION = array();

// 2. If it's desired to kill the session, also delete the session cookie.
// This is what tells the browser to "forget" the connection ID.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session on the server side
session_destroy();

// 4. Redirect to login page
header("Location: login");
exit;