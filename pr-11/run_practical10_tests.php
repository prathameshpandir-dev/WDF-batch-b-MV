<?php
// ========================================================
// Practical 10: Automated Test Suite (Database: studenthub)
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Testing 13 Comprehensive Syllabus & Practical 10 Test Cases
// ========================================================

require_once __DIR__ . "/config/db.php";

echo "========================================================\n";
echo "PRACTICAL 10: AUTOMATED TEST SUITE (DATABASE: studenthub)\n";
echo "Testing: Multi-User Login, Sessions, RBAC, Timeout, & Timer\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($testNum, $description, $result, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] Test $testNum: $description\n";
        $passCount++;
    } else {
        echo " [FAIL] Test $testNum: $description\n";
        echo "        Output: " . trim($result) . "\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// Test 1: Login with valid student credentials (Student 1)
// -------------------------------------------------------------
$t1_script = __DIR__ . "/_test_t1.php";
file_put_contents($t1_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/php/login_process.php";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["username" => "rahul.sharma@backbenchers.edu.in", "password" => "Password@123"];
register_shutdown_function(function() {
    echo "SESSION_ROLE:" . ($_SESSION["user_role"] ?? "") . "\n";
    echo "SESSION_USER_ID:" . ($_SESSION["user_id"] ?? "") . "\n";
    echo "SESSION_NAME:" . ($_SESSION["user_name"] ?? "") . "\n";
    echo "SESSION_LOGGED:" . (!empty($_SESSION["bbu_logged_in"]) ? "YES" : "NO") . "\n";
});
require __DIR__ . "/php/login_process.php";
');
$t1_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t1_script . '"');
@unlink($t1_script);
$t1_cond = (strpos($t1_out, "dashboard.php") !== false) &&
           (strpos($t1_out, "SESSION_ROLE:student") !== false) &&
           (strpos($t1_out, "SESSION_USER_ID:1") !== false) &&
           (strpos($t1_out, "SESSION_NAME:Rahul Sharma") !== false) &&
           (strpos($t1_out, "SESSION_LOGGED:YES") !== false);
assertTest(1, "Login with Student 1 credentials redirects to dashboard.php with exact user ID and name", $t1_out, $t1_cond);

// -------------------------------------------------------------
// Test 2: Login with another registered student (Student 2 - Amit Verma)
// -------------------------------------------------------------
$t2_script = __DIR__ . "/_test_t2.php";
file_put_contents($t2_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/php/login_process.php";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["username" => "amit.verma@backbenchers.edu.in", "password" => "Password@123"];
register_shutdown_function(function() {
    echo "SESSION_ROLE:" . ($_SESSION["user_role"] ?? "") . "\n";
    echo "SESSION_USER_ID:" . ($_SESSION["user_id"] ?? "") . "\n";
    echo "SESSION_NAME:" . ($_SESSION["user_name"] ?? "") . "\n";
    echo "SESSION_LOGGED:" . (!empty($_SESSION["bbu_logged_in"]) ? "YES" : "NO") . "\n";
});
require __DIR__ . "/php/login_process.php";
');
$t2_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t2_script . '"');
@unlink($t2_script);
$t2_cond = (strpos($t2_out, "dashboard.php") !== false) &&
           (strpos($t2_out, "SESSION_ROLE:student") !== false) &&
           (strpos($t2_out, "SESSION_USER_ID:2") !== false) &&
           (strpos($t2_out, "SESSION_NAME:Amit Verma") !== false);
assertTest(2, "Login with Student 2 (Amit Verma) sets Student 2's own information, NOT Student 1", $t2_out, $t2_cond);

// -------------------------------------------------------------
// Test 3: Login with valid admin credentials
// -------------------------------------------------------------
$t3_script = __DIR__ . "/_test_t3.php";
file_put_contents($t3_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/php/login_process.php";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["username" => "admin@backbenchers.edu.in", "password" => "Password@123"];
register_shutdown_function(function() {
    echo "SESSION_ROLE:" . ($_SESSION["user_role"] ?? "") . "\n";
    echo "SESSION_NAME:" . ($_SESSION["user_name"] ?? "") . "\n";
    echo "SESSION_LOGGED:" . (!empty($_SESSION["bbu_logged_in"]) ? "YES" : "NO") . "\n";
});
require __DIR__ . "/php/login_process.php";
');
$t3_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t3_script . '"');
@unlink($t3_script);
$t3_cond = (strpos($t3_out, "admin_dashboard.php") !== false) &&
           (strpos($t3_out, "SESSION_ROLE:admin") !== false) &&
           (strpos($t3_out, "SESSION_LOGGED:YES") !== false);
assertTest(3, "Login with valid admin credentials redirects to admin_dashboard.php with role 'admin'", $t3_out, $t3_cond);

// -------------------------------------------------------------
// Test 4: Login with incorrect password
// -------------------------------------------------------------
$t4_script = __DIR__ . "/_test_t4.php";
file_put_contents($t4_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/php/login_process.php";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["username" => "rahul.sharma@backbenchers.edu.in", "password" => "WrongPassword@999"];
register_shutdown_function(function() {
    echo "SESSION_LOGGED:" . (!empty($_SESSION["bbu_logged_in"]) ? "YES" : "NO") . "\n";
});
require __DIR__ . "/php/login_process.php";
');
$t4_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t4_script . '"');
@unlink($t4_script);
$t4_cond = (strpos($t4_out, "error=Invalid+email+or+password") !== false) &&
           (strpos($t4_out, "SESSION_LOGGED:NO") !== false);
assertTest(4, "Login with incorrect password rejected with 'Invalid email or password' and no session created", $t4_out, $t4_cond);

// -------------------------------------------------------------
// Test 5: Login with non-existent email
// -------------------------------------------------------------
$t5_script = __DIR__ . "/_test_t5.php";
file_put_contents($t5_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/php/login_process.php";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["username" => "nonexistent_ghost_user@backbenchers.edu.in", "password" => "AnyPass@123"];
register_shutdown_function(function() {
    echo "SESSION_LOGGED:" . (!empty($_SESSION["bbu_logged_in"]) ? "YES" : "NO") . "\n";
});
require __DIR__ . "/php/login_process.php";
');
$t5_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t5_script . '"');
@unlink($t5_script);
$t5_cond = (strpos($t5_out, "error=Invalid+email+or+password") !== false) &&
           (strpos($t5_out, "SESSION_LOGGED:NO") !== false);
assertTest(5, "Login with non-existent email displays exact same generic error without revealing existence", $t5_out, $t5_cond);

// -------------------------------------------------------------
// Test 6: Submit empty login form
// -------------------------------------------------------------
$t6_script = __DIR__ . "/_test_t6.php";
file_put_contents($t6_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/php/login_process.php";
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["username" => "", "password" => ""];
require __DIR__ . "/php/login_process.php";
');
$t6_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t6_script . '"');
@unlink($t6_script);
$t6_cond = (strpos($t6_out, "error=Please+enter+your+email+and+password") !== false);
assertTest(6, "Submit empty login form rejected with required field prompt", $t6_out, $t6_cond);

// -------------------------------------------------------------
// Test 7: Open protected dashboard without logging in
// -------------------------------------------------------------
$t7_script = __DIR__ . "/_test_t7.php";
file_put_contents($t7_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/dashboard.php";
require __DIR__ . "/php/auth_check.php";
check_auth();
');
$t7_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t7_script . '"');
@unlink($t7_script);
$t7_cond = (strpos($t7_out, "Location: login.html") !== false);
assertTest(7, "Unauthenticated user opening protected page is redirected to login.html", $t7_out, $t7_cond);

// -------------------------------------------------------------
// Test 8: Attempt to access admin dashboard as a student
// -------------------------------------------------------------
$t8_script = __DIR__ . "/_test_t8.php";
file_put_contents($t8_script, '<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 1;
$_SESSION["user_name"] = "Rahul Sharma";
$_SESSION["user_role"] = "student";
$_SESSION["last_activity"] = time();

$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin_dashboard.php";
require __DIR__ . "/php/auth_check.php";
check_auth("admin");
');
$t8_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t8_script . '"');
@unlink($t8_script);
$t8_cond = (strpos($t8_out, "dashboard.php") !== false) && (strpos($t8_out, "error=Access+Denied") !== false);
assertTest(8, "Student attempting to access admin dashboard is blocked with Access Denied redirect", $t8_out, $t8_cond);

// -------------------------------------------------------------
// Test 9: Log out and attempt to access dashboard again
// -------------------------------------------------------------
$t9_script = __DIR__ . "/_test_t9.php";
file_put_contents($t9_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/php/logout.php";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 1;

require __DIR__ . "/php/logout.php";
');
$t9_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t9_script . '"');
@unlink($t9_script);
$t9_cond = (strpos($t9_out, "msg=logged_out") !== false);
assertTest(9, "Logout completely destroys session and redirects to login.html with msg=logged_out", $t9_out, $t9_cond);

// -------------------------------------------------------------
// Test 10: Inactivity timeout after 15 minutes (900 seconds)
// -------------------------------------------------------------
$t10_script = __DIR__ . "/_test_t10.php";
file_put_contents($t10_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/dashboard.php";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 1;
// Simulate last activity 16 minutes ago (960 seconds ago)
$_SESSION["last_activity"] = time() - 960;

require __DIR__ . "/php/auth_check.php";
');
$t10_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t10_script . '"');
@unlink($t10_script);
$t10_cond = (strpos($t10_out, "Location: login.html?msg=timeout") !== false);
assertTest(10, "Inactivity beyond 15 minutes destroys session and redirects with msg=timeout", $t10_out, $t10_cond);

// -------------------------------------------------------------
// Test 11: Verify Practical 9 bcrypt password hashes with password_verify()
// -------------------------------------------------------------
$stmt = $conn->prepare("SELECT password FROM students WHERE email = ? LIMIT 1");
$stEmail = "rahul.sharma@backbenchers.edu.in";
$stmt->bind_param("s", $stEmail);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$storedHash = $r["password"] ?? "";
$stmt->close();

$t11_verified = (!empty($storedHash) && password_verify("Password@123", $storedHash));
assertTest(11, "Existing Practical 9 bcrypt hashes verify correctly with password_verify()", $storedHash, $t11_verified);

// -------------------------------------------------------------
// Test 12: Visible Session Timer Endpoint (Check remaining & Reset on user activity)
// -------------------------------------------------------------
$t12a_script = __DIR__ . "/_test_t12a.php";
file_put_contents($t12a_script, '<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 1;
// Simulate 3 minutes already elapsed (720 seconds remaining)
$_SESSION["last_activity"] = time() - 180;
$_POST = [];
require __DIR__ . "/php/session_timer.php";
');
$t12a_out = shell_exec('C:\xampp\php\php.exe "' . $t12a_script . '"');
@unlink($t12a_script);
$d1 = json_decode($t12a_out, true);

$t12b_script = __DIR__ . "/_test_t12b.php";
file_put_contents($t12b_script, '<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 1;
$_SESSION["last_activity"] = time() - 180;
// Trigger activity reset
$_POST = ["action" => "reset"];
require __DIR__ . "/php/session_timer.php";
');
$t12b_out = shell_exec('C:\xampp\php\php.exe "' . $t12b_script . '"');
@unlink($t12b_script);
$d2 = json_decode($t12b_out, true);

$t12_cond = (($d1["remaining"] ?? 0) === 720) && (($d2["remaining"] ?? 0) === 900) && (($d2["formatted"] ?? "") === "15:00");
$t12_res = "Initial remaining: " . ($d1["remaining"] ?? 0) . "s | After user reset: " . ($d2["remaining"] ?? 0) . "s (" . ($d2["formatted"] ?? "") . ")";
assertTest(12, "Session Timer: returns partial remaining time on load, and resets to 15:00 upon user activity", $t12_res, $t12_cond);

// -------------------------------------------------------------
// Test 13: Remember-Me token generation, verification, rotation, & revocation
// -------------------------------------------------------------
$t13_script = __DIR__ . "/_test_t13.php";
file_put_contents($t13_script, '<?php
require __DIR__ . "/config/db.php";

$userId = 1;
$testRawToken = bin2hex(random_bytes(32));
$testHash = hash("sha256", $testRawToken);
$testExpires = date("Y-m-d H:i:s", time() + 86400 * 30);

$ins = $conn->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
$ins->bind_param("iss", $userId, $testHash, $testExpires);
$ins->execute();
$tokenId = $ins->insert_id;
$ins->close();

$_COOKIE["bbu_remember_token"] = "1:" . $testRawToken;
$_SESSION = [];

require __DIR__ . "/php/auth_check.php";
$restoredSession = !empty($_SESSION["bbu_logged_in"]) && ($_SESSION["user_id"] == 1);

$rotQ = $conn->query("SELECT token_hash FROM remember_tokens WHERE id = $tokenId");
$rotRow = $rotQ->fetch_assoc();
$newHash = $rotRow["token_hash"] ?? "";
$tokenRotated = (!empty($newHash) && $newHash !== $testHash);

register_shutdown_function(function() use ($restoredSession, $tokenRotated, $conn) {
    $afterLogout = $conn->query("SELECT COUNT(*) AS total FROM remember_tokens WHERE user_id = 1")->fetch_assoc()["total"];
    echo "RESTORED:" . ($restoredSession ? "YES" : "NO") . " ";
    echo "ROTATED:" . ($tokenRotated ? "YES" : "NO") . " ";
    echo "REVOKED:" . ($afterLogout == 0 ? "YES" : "NO") . "\n";
});

require __DIR__ . "/php/logout.php";
');
$t13_out = shell_exec('C:\xampp\php\php.exe "' . $t13_script . '"');
@unlink($t13_script);
$t13_cond = (strpos($t13_out, "RESTORED:YES") !== false) &&
            (strpos($t13_out, "ROTATED:YES") !== false) &&
            (strpos($t13_out, "REVOKED:YES") !== false);
assertTest(13, "Remember-Me: Token generated, verified via SHA-256, rotated on use, and revoked on logout", $t13_out, $t13_cond);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: Total Tests: 13 | Passed: $passCount | Failed: $failCount\n";
echo "========================================================\n";
