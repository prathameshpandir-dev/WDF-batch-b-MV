<?php
// ========================================================
// Practical 10: Central Authentication & Session Protection
// File: php/auth_check.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Session Verification, 15-Min Inactivity Timeout,
//           Role-Based Access Control, Cache Prevention,
//           and Automatic Remember-Me Token Re-authentication.
// ========================================================

// 1. Prevent Browser Caching of Protected Pages (Back-Button Protection)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 2. Start PHP Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Include central database connection
require_once dirname(__DIR__) . "/config/db.php";

// Helper function to resolve project root URL/path
function get_base_url() {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($scriptName, 'Student_Portal_HTML_Pages');
    if ($pos !== false) {
        return substr($scriptName, 0, $pos + strlen('Student_Portal_HTML_Pages')) . '/';
    }
    return './';
}

// 4. Check Remember-Me Cookie if session is not active
if (empty($_SESSION["bbu_logged_in"]) && isset($_COOKIE["bbu_remember_token"])) {
    $cookie_data = explode(":", $_COOKIE["bbu_remember_token"]);
    if (count($cookie_data) === 2) {
        $cookie_user_id = (int)$cookie_data[0];
        $cookie_raw_token = $cookie_data[1];
        $cookie_token_hash = hash("sha256", $cookie_raw_token);

        // Verify token against database table remember_tokens
        if ($conn && !$conn->connect_error) {
            $stmt = $conn->prepare("SELECT r.id AS token_id, s.id, s.name, s.email, s.role, s.course, s.year 
                                    FROM remember_tokens r 
                                    JOIN students s ON r.user_id = s.id 
                                    WHERE r.user_id = ? AND r.token_hash = ? AND r.expires_at > NOW() 
                                    LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("is", $cookie_user_id, $cookie_token_hash);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    // Valid persistent token: Restore user session
                    session_regenerate_id(true);
                    $_SESSION["bbu_logged_in"] = true;
                    $_SESSION["user_id"] = (int)$user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["user_role"] = strtolower(trim($user["role"] ?? "student"));
                    $_SESSION["user_course"] = $user["course"] ?? "";
                    $_SESSION["user_year"] = $user["year"] ?? "";
                    $_SESSION["last_activity"] = time();

                    // Security: Rotate Remember-Me Token after use
                    $new_raw_token = bin2hex(random_bytes(32));
                    $new_token_hash = hash("sha256", $new_raw_token);
                    $new_expires_at = date("Y-m-d H:i:s", time() + (86400 * 30)); // 30 days

                    $rot_stmt = $conn->prepare("UPDATE remember_tokens SET token_hash = ?, expires_at = ? WHERE id = ?");
                    if ($rot_stmt) {
                        $rot_stmt->bind_param("ssi", $new_token_hash, $new_expires_at, $user["token_id"]);
                        $rot_stmt->execute();
                        $rot_stmt->close();

                        // Update client cookie
                        $is_https = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on";
                        setcookie("bbu_remember_token", $user["id"] . ":" . $new_raw_token, [
                            "expires" => time() + (86400 * 30),
                            "path" => "/",
                            "httponly" => true,
                            "samesite" => "Lax",
                            "secure" => $is_https
                        ]);
                    }
                } else {
                    // Invalid or expired token: delete cookie
                    setcookie("bbu_remember_token", "", time() - 3600, "/");
                    unset($_COOKIE["bbu_remember_token"]);
                }
                $stmt->close();
            }
        }
    } else {
        setcookie("bbu_remember_token", "", time() - 3600, "/");
        unset($_COOKIE["bbu_remember_token"]);
    }
}

// 5. Inactivity Timeout Check (15 Minutes = 900 Seconds)
$timeout_duration = 900; // 15 minutes in seconds
$session_remaining_seconds = $timeout_duration;

if (!empty($_SESSION["bbu_logged_in"])) {
    $now = time();
    $last_activity = $_SESSION["last_activity"] ?? $now;
    $inactive_time = $now - $last_activity;

    if ($inactive_time > $timeout_duration) {
        // User exceeded 15 minutes of inactivity: Destroy session
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        // Clear remember-me cookie on timeout
        if (isset($_COOKIE["bbu_remember_token"])) {
            setcookie("bbu_remember_token", "", time() - 3600, "/");
            unset($_COOKIE["bbu_remember_token"]);
        }

        // Redirect to login page with timeout explanation
        $dir = basename(dirname($_SERVER['PHP_SELF'] ?? ''));
        $login_url = in_array($dir, ['php', 'admin']) ? '../login.html' : 'login.html';
        header("Location: " . $login_url . "?msg=timeout");
        exit;
    }

    // Remaining seconds computed from previous last_activity
    $session_remaining_seconds = max(0, $timeout_duration - $inactive_time);

    // Refresh last activity for current valid request
    $_SESSION["last_activity"] = $now;
} else {
    $session_remaining_seconds = 0;
}

/**
 * Primary Authorization Check Function
 * Usage:
 *   check_auth();               // Allows any authenticated user (student or admin)
 *   check_auth('admin');        // Requires admin role
 *   check_auth('student');      // Requires student role
 */
function check_auth($required_role = null) {
    $dir = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

    // A. Check if user is logged in
    if (empty($_SESSION["bbu_logged_in"]) || empty($_SESSION["user_id"])) {
        $login_url = in_array($dir, ['php', 'admin']) ? '../login.html' : 'login.html';
        header("Location: " . $login_url . "?error=" . urlencode("Authentication required. Please log in first."));
        exit;
    }

    // B. Check Role-Based Access if a specific role is demanded
    if ($required_role !== null) {
        $user_role = strtolower($_SESSION["user_role"] ?? "student");
        $required_role = strtolower($required_role);

        if ($user_role !== $required_role) {
            // If student attempts to access admin dashboard or admin pages
            if ($required_role === "admin") {
                $student_dash = in_array($dir, ['php', 'admin']) ? '../dashboard.php' : 'dashboard.php';
                header("Location: " . $student_dash . "?error=" . urlencode("Access Denied: Admin privileges required."));
                exit;
            } else {
                $dash_url = in_array($dir, ['php', 'admin']) ? '../admin_dashboard.php' : 'admin_dashboard.php';
                header("Location: " . $dash_url . "?error=" . urlencode("Access Denied: Insufficient permissions."));
                exit;
            }
        }
    }
}
?>
