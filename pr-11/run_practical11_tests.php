<?php
// ========================================================
// Practical 11: Automated Test Suite (Database: studenthub)
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Testing Student Management CRUD, Search, Filter, Pagination & RBAC
// ========================================================

require_once __DIR__ . "/config/db.php";

echo "========================================================\n";
echo "PRACTICAL 11: AUTOMATED TEST SUITE (DATABASE: studenthub)\n";
echo "Testing: Student CRUD, Search, Filter, Pagination & RBAC\n";
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
        echo "        Output: " . trim((string)$result) . "\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// Test 1: Minimum 20 student records available
// -------------------------------------------------------------
$r1 = $conn->query("SELECT COUNT(*) AS total FROM students");
$totalStudents = $r1 ? (int)$r1->fetch_assoc()["total"] : 0;
assertTest(1, "Database studenthub.students has at least 20 student records (Found: $totalStudents)", $totalStudents, $totalStudents >= 20);

// -------------------------------------------------------------
// Test 2: Unauthenticated user accessing admin/students.php is redirected to login
// -------------------------------------------------------------
$t2_script = __DIR__ . "/_test_p11_t2.php";
file_put_contents($t2_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SESSION = [];
require __DIR__ . "/php/auth_check.php";
check_auth("admin");
');
$t2_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t2_script . '"');
@unlink($t2_script);
$t2_cond = (strpos($t2_out, "Location: ../login.html") !== false) && (strpos($t2_out, "Authentication+required") !== false);
assertTest(2, "Unauthenticated user accessing admin/students.php redirected to ../login.html", $t2_out, $t2_cond);

// -------------------------------------------------------------
// Test 3: Student user accessing admin/students.php is blocked with Access Denied redirect
// -------------------------------------------------------------
$t3_script = __DIR__ . "/_test_p11_t3.php";
file_put_contents($t3_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 1;
$_SESSION["user_name"] = "Rahul Sharma";
$_SESSION["user_role"] = "student";
$_SESSION["last_activity"] = time();

require __DIR__ . "/php/auth_check.php";
check_auth("admin");
');
$t3_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t3_script . '"');
@unlink($t3_script);
$t3_cond = (strpos($t3_out, "Location: ../dashboard.php") !== false) && (strpos($t3_out, "Access+Denied") !== false);
assertTest(3, "Student accessing admin/students.php blocked with Access Denied redirect to ../dashboard.php", $t3_out, $t3_cond);

// -------------------------------------------------------------
// Test 4: Authenticated admin can view student records page
// -------------------------------------------------------------
$t4_script = __DIR__ . "/_test_p11_t4.php";
file_put_contents($t4_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["REQUEST_METHOD"] = "GET";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_name"] = "System Administrator";
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

require __DIR__ . "/admin/students.php";
');
$t4_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t4_script . '"');
@unlink($t4_script);
$t4_cond = (strpos($t4_out, "Student Management System") !== false) && 
           (strpos($t4_out, "table") !== false) &&
           (strpos($t4_out, "Anjali Desai") !== false);
assertTest(4, "Admin can view student management table displaying real student records", substr((string)$t4_out, 0, 150), $t4_cond);

// -------------------------------------------------------------
// Test 5: Admin can add a new student with valid data and bcrypt password hash
// -------------------------------------------------------------
$testEmail = "test_student_" . time() . "@backbenchers.edu.in";
$t5_script = __DIR__ . "/_test_p11_t5.php";
file_put_contents($t5_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/student_add.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/student_add.php";
$_SERVER["REQUEST_METHOD"] = "POST";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

$_POST = [
    "name" => "Suresh Raina",
    "email" => "' . $testEmail . '",
    "mobile" => "9898765432",
    "course" => "B.Tech Information Technology",
    "year" => "Second Year (SE)",
    "role" => "student",
    "gender" => "Male",
    "password" => "SureshPass@123"
];

require __DIR__ . "/admin/student_add.php";
');
$t5_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t5_script . '"');
@unlink($t5_script);

// Verify insert in database
$checkStmt = $conn->prepare("SELECT id, name, email, password FROM students WHERE email = ?");
$checkStmt->bind_param("s", $testEmail);
$checkStmt->execute();
$newStudent = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

$t5_cond = !empty($newStudent) && 
           ($newStudent["name"] === "Suresh Raina") &&
           password_verify("SureshPass@123", $newStudent["password"]);
assertTest(5, "Admin can add new student with bcrypt hashed password (verified with password_verify)", $t5_out, $t5_cond);

$createdStudentId = $newStudent ? (int)$newStudent["id"] : 0;

// -------------------------------------------------------------
// Test 6: Duplicate email rejected when adding student
// -------------------------------------------------------------
$t6_script = __DIR__ . "/_test_p11_t6.php";
file_put_contents($t6_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/student_add.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/student_add.php";
$_SERVER["REQUEST_METHOD"] = "POST";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

$_POST = [
    "name" => "Duplicate Person",
    "email" => "' . $testEmail . '",
    "mobile" => "9898765432",
    "course" => "B.Tech Information Technology",
    "year" => "Second Year (SE)",
    "role" => "student",
    "gender" => "Male",
    "password" => "Password@123"
];

require __DIR__ . "/admin/student_add.php";
');
$t6_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t6_script . '"');
@unlink($t6_script);
$t6_cond = (strpos($t6_out, "already exists") !== false);
assertTest(6, "Duplicate email registration rejected with friendly validation error", $t6_out, $t6_cond);

// -------------------------------------------------------------
// Test 7: Admin can edit an existing student record
// -------------------------------------------------------------
$t7_script = __DIR__ . "/_test_p11_t7.php";
file_put_contents($t7_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/student_edit.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/student_edit.php";
$_SERVER["REQUEST_METHOD"] = "POST";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

$_POST = [
    "id" => ' . $createdStudentId . ',
    "name" => "Suresh K. Raina",
    "email" => "' . $testEmail . '",
    "mobile" => "9123456789",
    "course" => "B.Tech Computer Engineering",
    "year" => "Third Year (TE)",
    "role" => "student",
    "gender" => "Male",
    "new_password" => "" // Blank password preserves existing hash
];

require __DIR__ . "/admin/student_edit.php";
');
$t7_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t7_script . '"');
@unlink($t7_script);

$checkStmt2 = $conn->prepare("SELECT id, name, mobile, course, year, password FROM students WHERE id = ?");
$checkStmt2->bind_param("i", $createdStudentId);
$checkStmt2->execute();
$updatedStudent = $checkStmt2->get_result()->fetch_assoc();
$checkStmt2->close();

$t7_cond = !empty($updatedStudent) &&
           ($updatedStudent["name"] === "Suresh K. Raina") &&
           ($updatedStudent["course"] === "B.Tech Computer Engineering") &&
           ($updatedStudent["year"] === "Third Year (TE)") &&
           password_verify("SureshPass@123", $updatedStudent["password"]); // Password preserved
assertTest(7, "Admin can edit student details while preserving existing password hash", $t7_out, $t7_cond);

// -------------------------------------------------------------
// Test 8: Self-deletion by active administrator blocked
// -------------------------------------------------------------
$t8_script = __DIR__ . "/_test_p11_t8.php";
file_put_contents($t8_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/student_delete.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/student_delete.php";
$_SERVER["REQUEST_METHOD"] = "POST";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

$_POST = ["student_id" => 8]; // Trying to delete own account (ID 8)
require __DIR__ . "/admin/student_delete.php";
');
$t8_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t8_script . '"');
@unlink($t8_script);
$t8_cond = (strpos($t8_out, "cannot+delete+your+own+active+administrator+account") !== false);
assertTest(8, "Admin self-deletion attempt blocked safely with protection alert", $t8_out, $t8_cond);

// -------------------------------------------------------------
// Test 9: Deletion via GET request rejected (POST-only enforcement)
// -------------------------------------------------------------
$t9_script = __DIR__ . "/_test_p11_t9.php";
file_put_contents($t9_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/student_delete.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/student_delete.php";
$_SERVER["REQUEST_METHOD"] = "GET";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

$_GET = ["student_id" => ' . $createdStudentId . '];
require __DIR__ . "/admin/student_delete.php";
');
$t9_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t9_script . '"');
@unlink($t9_script);
$t9_cond = (strpos($t9_out, "Invalid+request+method") !== false);
assertTest(9, "GET request to student_delete.php rejected (POST-only requirement enforced)", $t9_out, $t9_cond);

// -------------------------------------------------------------
// Test 10: Admin can delete student via POST
// -------------------------------------------------------------
$t10_script = __DIR__ . "/_test_p11_t10.php";
file_put_contents($t10_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/student_delete.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/student_delete.php";
$_SERVER["REQUEST_METHOD"] = "POST";
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

$_POST = ["student_id" => ' . $createdStudentId . '];
require __DIR__ . "/admin/student_delete.php";
');
$t10_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t10_script . '"');
@unlink($t10_script);

$checkStmt3 = $conn->prepare("SELECT id FROM students WHERE id = ?");
$checkStmt3->bind_param("i", $createdStudentId);
$checkStmt3->execute();
$delRes = $checkStmt3->get_result();
$isDeleted = ($delRes->num_rows === 0);
$checkStmt3->close();

$t10_cond = $isDeleted && (strpos($t10_out, "deleted+successfully") !== false);
assertTest(10, "Admin can delete student via POST request (record removed from database)", $t10_out, $t10_cond);

// -------------------------------------------------------------
// Test 11: Search by student name
// -------------------------------------------------------------
$t11_script = __DIR__ . "/_test_p11_t11.php";
file_put_contents($t11_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["REQUEST_METHOD"] = "GET";
$_GET = ["search" => "Rahul Sharma"];
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

require __DIR__ . "/admin/students.php";
');
$t11_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t11_script . '"');
@unlink($t11_script);
$t11_cond = (strpos($t11_out, "Rahul Sharma") !== false) && (strpos($t11_out, "rahul.sharma@backbenchers.edu.in") !== false);
assertTest(11, "Search by name returns matching student record using MySQLi prepared query", substr((string)$t11_out, 0, 150), $t11_cond);

// -------------------------------------------------------------
// Test 12: Search by student email
// -------------------------------------------------------------
$t12_script = __DIR__ . "/_test_p11_t12.php";
file_put_contents($t12_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["REQUEST_METHOD"] = "GET";
$_GET = ["search" => "amit.verma@backbenchers.edu.in"];
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

require __DIR__ . "/admin/students.php";
');
$t12_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t12_script . '"');
@unlink($t12_script);
$t12_cond = (strpos($t12_out, "Amit Verma") !== false);
assertTest(12, "Search by email returns matching student record", substr((string)$t12_out, 0, 150), $t12_cond);

// -------------------------------------------------------------
// Test 13: Filter by degree course
// -------------------------------------------------------------
$t13_script = __DIR__ . "/_test_p11_t13.php";
file_put_contents($t13_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["REQUEST_METHOD"] = "GET";
$_GET = ["course" => "MCA"];
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

require __DIR__ . "/admin/students.php";
');
$t13_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t13_script . '"');
@unlink($t13_script);
$t13_cond = (strpos($t13_out, "MCA") !== false) && (strpos($t13_out, "Ananya Nair") !== false);
assertTest(13, "Filter by course isolates students enrolled in selected course", substr((string)$t13_out, 0, 150), $t13_cond);

// -------------------------------------------------------------
// Test 14: Search and Filter work together simultaneously
// -------------------------------------------------------------
$t14_script = __DIR__ . "/_test_p11_t14.php";
file_put_contents($t14_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["REQUEST_METHOD"] = "GET";
$_GET = ["search" => "Ananya", "course" => "MCA"];
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

require __DIR__ . "/admin/students.php";
');
$t14_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t14_script . '"');
@unlink($t14_script);
$t14_cond = (strpos($t14_out, "Ananya Nair") !== false) && (strpos($t14_out, "Rahul Sharma") === false);
assertTest(14, "Search and filter work simultaneously and narrow results properly", substr((string)$t14_out, 0, 150), $t14_cond);

// -------------------------------------------------------------
// Test 15: Empty search state displays 'No students found.'
// -------------------------------------------------------------
$t15_script = __DIR__ . "/_test_p11_t15.php";
file_put_contents($t15_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["REQUEST_METHOD"] = "GET";
$_GET = ["search" => "NonExistentStudent123456789"];
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

require __DIR__ . "/admin/students.php";
');
$t15_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t15_script . '"');
@unlink($t15_script);
$t15_cond = (strpos($t15_out, "No students found.") !== false);
assertTest(15, "Empty search result displays clean user-friendly 'No students found.' message", substr((string)$t15_out, 0, 150), $t15_cond);

// -------------------------------------------------------------
// Test 16: Server-side pagination computes correct pages
// -------------------------------------------------------------
$t16_script = __DIR__ . "/_test_p11_t16.php";
file_put_contents($t16_script, '<?php
$_SERVER["SCRIPT_NAME"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["PHP_SELF"] = "/Student_Portal_HTML_Pages/admin/students.php";
$_SERVER["REQUEST_METHOD"] = "GET";
$_GET = ["page" => 1];
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION["bbu_logged_in"] = true;
$_SESSION["user_id"] = 8;
$_SESSION["user_role"] = "admin";
$_SESSION["last_activity"] = time();

require __DIR__ . "/admin/students.php";
');
$t16_out = shell_exec('C:\xampp\php\php-cgi.exe "' . $t16_script . '"');
@unlink($t16_script);
$t16_cond = (strpos($t16_out, "Showing Page") !== false) && (strpos($t16_out, "students.php?page=2") !== false);
assertTest(16, "Server-side pagination divides records into pages with working Next/Previous links", substr((string)$t16_out, 0, 150), $t16_cond);

echo "\n--------------------------------------------------------\n";
echo "PRACTICAL 11 SUMMARY: Total Tests: " . ($passCount + $failCount) . " | Passed: $passCount | Failed: $failCount\n";
echo "========================================================\n";
?>
