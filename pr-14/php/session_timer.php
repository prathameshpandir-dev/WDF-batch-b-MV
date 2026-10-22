<?php
// ========================================================
// Practical 10: Session Timer & Activity Synchronization
// File: php/session_timer.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Description: Returns remaining session seconds and resets
//              the 15-minute inactivity deadline upon user activity.
// ========================================================

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$timeout_duration = 900; // 15 minutes = 900 seconds

// 1. Verify active session exists
if (empty($_SESSION["bbu_logged_in"]) || empty($_SESSION["user_id"])) {
    echo json_encode([
        "status" => "expired",
        "remaining" => 0,
        "message" => "No active session."
    ]);
    exit;
}

$now = time();
$last_activity = $_SESSION["last_activity"] ?? $now;
$inactive_time = $now - $last_activity;

// 2. Check if inactivity deadline has expired
if ($inactive_time > $timeout_duration) {
    // Terminate session on server
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    if (isset($_COOKIE["bbu_remember_token"])) {
        setcookie("bbu_remember_token", "", time() - 3600, "/");
        unset($_COOKIE["bbu_remember_token"]);
    }

    echo json_encode([
        "status" => "expired",
        "remaining" => 0,
        "message" => "Session expired due to inactivity."
    ]);
    exit;
}

// 3. Handle activity reset
// When user clicks, types, or interacts, client sends action=reset
$action = $_POST["action"] ?? $_GET["action"] ?? "";

if ($action === "reset") {
    $_SESSION["last_activity"] = $now;
    $remaining = $timeout_duration;
} else {
    // Querying remaining time without resetting
    $remaining = max(0, $timeout_duration - $inactive_time);
}

$mins = floor($remaining / 60);
$secs = $remaining % 60;
$formatted = sprintf("%02d:%02d", $mins, $secs);

echo json_encode([
    "status" => "active",
    "remaining" => (int)$remaining,
    "formatted" => $formatted
]);
exit;
?>
