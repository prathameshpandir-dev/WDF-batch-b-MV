<?php
// ========================================================
// Practical 10: Secure Login Processing
// File: php/login_process.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: POST validation, Prepared Statements, password_verify(),
//           session_regenerate_id(true), Role-Based Redirection,
//           and Secure Remember-Me Token Generation.
// ========================================================

// 1. Ensure request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $login_url = (basename(dirname($_SERVER['PHP_SELF'])) === 'php') ? '../login.html' : 'login.html';
    header("Location: " . $login_url);
    exit;
}

// 2. Start session to manage authentication state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Include central database connection
require_once dirname(__DIR__) . "/config/db.php";

// Helper function to return user-friendly error response
function loginError($message) {
    // Clear any previous session variables upon failed login
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    // Clear any existing persistent remember-me cookie on failed login
    if (isset($_COOKIE["bbu_remember_token"])) {
        setcookie("bbu_remember_token", "", time() - 3600, "/");
        unset($_COOKIE["bbu_remember_token"]);
    }

    $login_url = (basename(dirname($_SERVER['PHP_SELF'])) === 'php') ? '../login.html' : 'login.html';
    header("Location: " . $login_url . "?error=" . urlencode($message));
    exit;
}

// 4. Read and sanitize submitted form fields
$identifier = trim($_POST["username"] ?? $_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$remember = isset($_POST["remember"]);

// 5. Check that required fields are not empty
if ($identifier === "" || $password === "") {
    loginError("Please enter your email and password.");
}

// 6. Check database connection
if (!$conn || $conn->connect_error) {
    loginError("Database connection failed. Please ensure MySQL is running in XAMPP.");
}

// 7. Find matching user using MySQLi Prepared Statement (email or mobile)
$sql = "SELECT id, name, email, mobile, password, role, course, year FROM students WHERE email = ? OR mobile = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    loginError("Database error during login. Please try again.");
}

$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// 8. Verify user existence and password hash using password_verify()
if (!$user || empty($user["password"]) || !password_verify($password, $user["password"])) {
    // Generic security message without revealing whether email exists
    loginError("Invalid email or password.");
}

// 9. Session Fixation Protection: Regenerate session ID
session_regenerate_id(true);

// 10. Store authenticated user information in session from database row
$dbRole = strtolower(trim($user["role"] ?? "student"));
if ($dbRole !== "admin") {
    $dbRole = "student";
}

$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["user_role"] = $dbRole;
$_SESSION["user_course"] = $user["course"] ?? "";
$_SESSION["user_year"] = $user["year"] ?? "";
$_SESSION["last_activity"] = time(); // Initialize 15-min inactivity timer

// 11. Handle Remember-Me Feature (Secure Random Token Hashing)
if ($remember) {
    $raw_token = bin2hex(random_bytes(32));
    $token_hash = hash("sha256", $raw_token);
    $expires_at = date("Y-m-d H:i:s", time() + (86400 * 30));

    $rem_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    if ($rem_stmt) {
        $rem_stmt->bind_param("iss", $user["id"], $token_hash, $expires_at);
        $rem_stmt->execute();
        $rem_stmt->close();

        $is_https = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on";
        setcookie("bbu_remember_token", $user["id"] . ":" . $raw_token, [
            "expires" => time() + (86400 * 30),
            "path" => "/",
            "httponly" => true,
            "samesite" => "Lax",
            "secure" => $is_https
        ]);
    }
}

// 12. Role-Based Redirection:
// - Student account -> student dashboard (dashboard.php)
// - Admin account   -> admin dashboard (admin_dashboard.php)
$isPhpDir = (basename(dirname($_SERVER['PHP_SELF'])) === 'php');

if ($dbRole === "admin") {
    $targetUrl = $isPhpDir ? "../admin_dashboard.php" : "admin_dashboard.php";
} else {
    $targetUrl = $isPhpDir ? "../dashboard.php" : "dashboard.php";
}

header("Location: " . $targetUrl);
exit;
?>
