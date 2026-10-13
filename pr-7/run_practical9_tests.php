<?php
// ========================================================
// Practical 9: Automated Test Suite (Using studenthub Database)
// Verifies 15 Test Cases Required by College Syllabus
// ========================================================

require_once __DIR__ . "/config/db.php";

echo "========================================================\n";
echo "PRACTICAL 9: AUTOMATED TEST SUITE (DATABASE: studenthub)\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function runPost($postData) {
    $script = __DIR__ . "/_test_exec.php";
    $code = '<?php if (session_status() === PHP_SESSION_NONE) session_start(); $_SERVER["REQUEST_METHOD"] = "POST"; $_POST = ' . var_export($postData, true) . '; ob_start(); include __DIR__ . "/process_registration.php"; $out = ob_get_clean(); echo $out;';
    file_put_contents($script, $code);
    $output = shell_exec('C:\xampp\php\php.exe "' . $script . '"');
    @unlink($script);
    return $output;
}

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

// Baseline valid payload
$timestamp = time();
$validEmail = "student_" . $timestamp . "@testcollege.edu";
$validData = [
    "fullname" => "Aniket Shinde",
    "email" => $validEmail,
    "phone" => "9876543210",
    "course" => "B.Tech Information Technology",
    "year" => "Second Year (SE)",
    "reg-gender" => "Male",
    "password" => "SecurePass@2026",
    "confirmPassword" => "SecurePass@2026",
    "terms" => "1"
];

// Test 1: Valid registration into studenthub.students
$t1 = runPost($validData);
assertTest(1, "Valid registration inserts student into studenthub database", $t1, strpos($t1, "Registration submitted successfully") !== false);

// Test 2: Empty required field (Full Name)
$d2 = $validData; $d2["fullname"] = "";
$t2 = runPost($d2);
assertTest(2, "Empty required field (Name) rejected", $t2, strpos($t2, "Full Name is required") !== false);

// Test 3: Invalid email address
$d3 = $validData; $d3["email"] = "invalid-email-format";
$t3 = runPost($d3);
assertTest(3, "Invalid email address format rejected", $t3, strpos($t3, "valid email address") !== false);

// Test 4: Invalid mobile number
$d4 = $validData; $d4["phone"] = "12345";
$t4 = runPost($d4);
assertTest(4, "Invalid mobile number (short / invalid prefix) rejected", $t4, strpos($t4, "Mobile number must contain exactly 10 digits") !== false);

// Test 5: Password shorter than 8 characters
$d5 = $validData; $d5["password"] = "Sh@1"; $d5["confirmPassword"] = "Sh@1";
$t5 = runPost($d5);
assertTest(5, "Password shorter than 8 characters rejected", $t5, strpos($t5, "at least 8 characters long") !== false);

// Test 6: Password missing special character
$d6 = $validData; $d6["password"] = "Password123"; $d6["confirmPassword"] = "Password123";
$t6 = runPost($d6);
assertTest(6, "Password missing special character rejected", $t6, strpos($t6, "special character") !== false);

// Test 7: Password and confirm password mismatch
$d7 = $validData; $d7["password"] = "SecurePass@2026"; $d7["confirmPassword"] = "MismatchPass@2026";
$t7 = runPost($d7);
assertTest(7, "Password and Confirm Password mismatch rejected", $t7, strpos($t7, "Passwords do not match") !== false);

// Test 8: Course not selected
$d8 = $validData; $d8["course"] = "";
$t8 = runPost($d8);
assertTest(8, "Course or Academic Year not selected rejected", $t8, strpos($t8, "select your enrolled course") !== false);

// Test 9: Gender not selected
$d9 = $validData; $d9["reg-gender"] = "";
$t9 = runPost($d9);
assertTest(9, "Gender not selected rejected", $t9, strpos($t9, "select your gender") !== false);

// Test 10: Terms not accepted
$d10 = $validData; unset($d10["terms"]);
$t10 = runPost($d10);
assertTest(10, "Terms and conditions not accepted rejected", $t10, strpos($t10, "agree to the university IT rules") !== false);

// Test 11: Registering again with the same email (Duplicate Email)
$t11 = runPost($validData); // Submitting identical validData again
assertTest(11, "Duplicate email registration rejected", $t11, strpos($t11, "email is already registered") !== false);

// Test 12: Submitting invalid data directly to backend
$t12 = runPost(["fullname" => "X", "email" => "bad"]);
assertTest(12, "Direct backend submission with invalid data fails safely", $t12, strpos($t12, "Full Name must contain only letters") !== false);

// Test 13: Verifying database contains hashed password rather than plain text
$hashStmt = $conn->prepare("SELECT password FROM students WHERE email = ?");
$hashStmt->bind_param("s", $validEmail);
$hashStmt->execute();
$hashRes = $hashStmt->get_result()->fetch_assoc();
$storedHash = $hashRes["password"] ?? "";
$isHashed = (strpos($storedHash, '$2y$') === 0 && password_verify("SecurePass@2026", $storedHash));
assertTest(13, "Database studenthub.students contains bcrypt password hash (NOT plain-text)", $storedHash, $isHashed);

// Test 14: Verifying successful registration inserts exactly one record
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE email = ?");
$countStmt->bind_param("s", $validEmail);
$countStmt->execute();
$countRes = $countStmt->get_result()->fetch_assoc();
$recordCount = (int)($countRes["total"] ?? 0);
assertTest(14, "Successful registration inserted exactly 1 record in studenthub.students", "Total: $recordCount", $recordCount === 1);

// Test 15: SQL Injection Prevention in prepared statements
$sqliEmail = "sqli" . time() . "@college.edu";
$sqliPayload = [
    "fullname" => "Hacker'; DROP TABLE students; --",
    "email" => $sqliEmail,
    "phone" => "9899001122",
    "course" => "B.Tech Computer Engineering",
    "year" => "Third Year (TE)",
    "reg-gender" => "Male",
    "password" => "Hack@1234",
    "confirmPassword" => "Hack@1234",
    "terms" => "1"
];
$t15 = runPost($sqliPayload);
// Check if table still exists and record was safely handled
$tableCheck = $conn->query("SHOW TABLES LIKE 'students'")->num_rows;
assertTest(15, "Prepared statements prevent SQL Injection attempts safely", $t15, $tableCheck === 1);

echo "\n--------------------------------------------------------\n";
echo "SUMMARY: Total Tests: 15 | Passed: $passCount | Failed: $failCount\n";
echo "========================================================\n";
