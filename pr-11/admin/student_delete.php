<?php
// ========================================================
// Practical 11: Delete Student Record (Admin Only)
// File: admin/student_delete.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: POST-Only Enforcement, Self-Deletion Protection,
//           Foreign Key Safety, & MySQLi Prepared Statements.
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";
check_auth("admin");

// 1. Strictly enforce POST request method (Reject unsafe GET requests)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: students.php?error=" . urlencode("Invalid request method. Deletion must use POST."));
    exit;
}

// 2. Validate student ID
$studentId = (int)($_POST["student_id"] ?? 0);
if ($studentId <= 0) {
    header("Location: students.php?error=" . urlencode("Invalid student ID."));
    exit;
}

// 3. Security: Prevent administrator from accidentally deleting their own active account
$currentAdminId = (int)($_SESSION["user_id"] ?? 0);
if ($studentId === $currentAdminId) {
    header("Location: students.php?error=" . urlencode("Action Denied: You cannot delete your own active administrator account!"));
    exit;
}

// 4. Check if student exists
$checkStmt = $conn->prepare("SELECT id, name FROM students WHERE id = ? LIMIT 1");
if (!$checkStmt) {
    header("Location: students.php?error=" . urlencode("Database preparation error."));
    exit;
}

$checkStmt->bind_param("i", $studentId);
$checkStmt->execute();
$res = $checkStmt->get_result();
$student = $res->fetch_assoc();
$checkStmt->close();

if (!$student) {
    header("Location: students.php?error=" . urlencode("Student record not found or already deleted."));
    exit;
}

$studentName = $student["name"];

// 5. Delete student record using MySQLi Prepared Statement
// (Child records in registrations and remember_tokens cascade safely per schema)
$delStmt = $conn->prepare("DELETE FROM students WHERE id = ?");
if (!$delStmt) {
    header("Location: students.php?error=" . urlencode("Failed to prepare delete query: " . $conn->error));
    exit;
}

$delStmt->bind_param("i", $studentId);

if ($delStmt->execute()) {
    $delStmt->close();
    header("Location: students.php?msg=" . urlencode("Student '$studentName' (ID #$studentId) was deleted successfully."));
    exit;
} else {
    $errorMsg = $conn->error;
    $delStmt->close();
    header("Location: students.php?error=" . urlencode("Failed to delete student: " . $errorMsg));
    exit;
}
?>
