<?php
// ========================================================
// Practical 10: Secure Logout Handler
// File: php/logout.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Session Variable Clearing, Session Destruction,
//           Session Cookie Deletion, Remember-Me Token Revocation,
//           and Safe Redirect with Confirmation Message.
// ========================================================

// 1. Start session to access session data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Revoke Remember-Me Token from Database if user was logged in
require_once dirname(__DIR__) . "/config/db.php";

if (!empty($_SESSION["user_id"]) && $conn && !$conn->connect_error) {
    $userId = (int)$_SESSION["user_id"];
    $delStmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($delStmt) {
        $delStmt->bind_param("i", $userId);
        $delStmt->execute();
        $delStmt->close();
    }
}

// 3. Clear remember-me cookie from client browser
if (isset($_COOKIE["bbu_remember_token"])) {
    setcookie("bbu_remember_token", "", time() - 3600, "/");
    unset($_COOKIE["bbu_remember_token"]);
}

// 4. Clear all session variables
$_SESSION = [];

// 5. Delete session cookie from client browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 6. Destroy PHP session on the server
session_destroy();

// 7. Redirect to login page with confirmation message
$isPhpDir = (basename(dirname($_SERVER['PHP_SELF'])) === 'php');
$login_url = $isPhpDir ? "../login.html?msg=logged_out" : "login.html?msg=logged_out";
header("Location: " . $login_url);
exit;
?>
