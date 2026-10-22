<?php
// ========================================================
// Practical 13: RESTful PHP JSON API for Student CRUD
// File: api/students.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: JSON Responses, Proper HTTP Status Codes,
//           MySQLi Prepared Statements, RBAC Protection,
//           Server Validation, and Audit Logging.
// Supported Methods: GET (List/Read), POST (Create),
//                    PUT (Update), DELETE (Remove)
// ========================================================

// 1. Set pure JSON headers and disable caching
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 2. Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Include central database connection and audit logger
require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/php/audit_helper.php";

// Helper for consistent JSON output
function sendResponse($statusCode, $success, $message, $data = null) {
    http_response_code($statusCode);
    $payload = [
        "success" => $success,
        "message" => $message
    ];
    if ($data !== null) {
        $payload["data"] = $data;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Check Database Connection
if (!$conn || $conn->connect_error) {
    sendResponse(500, false, "Database connection failure: " . ($conn ? $conn->connect_error : "Unavailable"));
}

// 5. Strict Role-Based Authentication Check (Practical 10 RBAC)
if (empty($_SESSION["bbu_logged_in"]) || empty($_SESSION["user_id"])) {
    sendResponse(401, false, "Authentication required. Please log in first.");
}

$currentRole = strtolower(trim($_SESSION["user_role"] ?? ""));
if ($currentRole !== "admin") {
    sendResponse(403, false, "Access Denied: Administrator role required to perform student operations.");
}

$adminId = (int)$_SESSION["user_id"];

// 6. Resolve Request Method (supports method override via _method parameter)
$method = strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");

// Parse request body for JSON payloads
$rawInput = file_get_contents("php://input");
$inputData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}
if (!empty($_POST)) {
    $inputData = array_merge($inputData, $_POST);
}

if (isset($_GET["_method"])) {
    $method = strtoupper(trim($_GET["_method"]));
} elseif (isset($inputData["_method"])) {
    $method = strtoupper(trim($inputData["_method"]));
}

// ========================================================
// METHOD: GET (Retrieve records)
// ========================================================
if ($method === "GET") {
    // A. Single student by ID
    if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
        $studentId = (int)$_GET["id"];
        $stmt = $conn->prepare("SELECT id, name, email, mobile, course, year, role, gender, created_at FROM students WHERE id = ? LIMIT 1");
        if (!$stmt) {
            sendResponse(500, false, "Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $res = $stmt->get_result();
        $student = $res->fetch_assoc();
        $stmt->close();

        if ($student) {
            sendResponse(200, true, "Student record retrieved successfully.", $student);
        } else {
            sendResponse(404, false, "Student record with ID #$studentId was not found.");
        }
    }

    // B. List students with optional search & filter
    $search = trim($_GET["search"] ?? "");
    $courseFilter = trim($_GET["course"] ?? "");
    $yearFilter = trim($_GET["year"] ?? "");

    $whereClauses = ["1=1"];
    $params = [];
    $types = "";

    if ($search !== "") {
        $whereClauses[] = "(name LIKE ? OR email LIKE ? OR mobile LIKE ? OR course LIKE ?)";
        $sw = "%" . $search . "%";
        $params[] = $sw;
        $params[] = $sw;
        $params[] = $sw;
        $params[] = $sw;
        $types .= "ssss";
    }

    if ($courseFilter !== "" && $courseFilter !== "all") {
        $whereClauses[] = "course = ?";
        $params[] = $courseFilter;
        $types .= "s";
    }

    if ($yearFilter !== "" && $yearFilter !== "all") {
        $whereClauses[] = "year = ?";
        $params[] = $yearFilter;
        $types .= "s";
    }

    $sql = "SELECT id, name, email, mobile, course, year, role, gender, created_at 
            FROM students 
            WHERE " . implode(" AND ", $whereClauses) . " 
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendResponse(500, false, "Query preparation failed: " . $conn->error);
    }

    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    sendResponse(200, true, "Students retrieved successfully.", [
        "count" => count($students),
        "students" => $students
    ]);
}

// ========================================================
// METHOD: POST (Create new student)
// ========================================================
elseif ($method === "POST") {
    $name = trim($inputData["name"] ?? "");
    $email = trim($inputData["email"] ?? "");
    $mobile = trim($inputData["mobile"] ?? "");
    $course = trim($inputData["course"] ?? "");
    $year = trim($inputData["year"] ?? "");
    $role = trim($inputData["role"] ?? "student");
    $gender = trim($inputData["gender"] ?? "Other");
    $password = $inputData["password"] ?? "";

    // 1. Server-side field validations
    if ($name === "" || strlen($name) < 3) {
        sendResponse(400, false, "Student name is required and must be at least 3 characters.");
    }
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, false, "A valid university email address is required.");
    }
    if ($mobile === "" || !preg_match("/^[0-9]{10}$/", $mobile)) {
        sendResponse(400, false, "Mobile number must be exactly 10 digits.");
    }
    if ($course === "") {
        sendResponse(400, false, "Please select an enrolled degree course.");
    }
    if ($year === "") {
        sendResponse(400, false, "Please select an academic year.");
    }
    if (strlen($password) < 8) {
        sendResponse(400, false, "Password must be at least 8 characters long.");
    }

    // 2. Check for duplicate email in students table
    $chkStmt = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    if (!$chkStmt) {
        sendResponse(500, false, "Database error: " . $conn->error);
    }
    $chkStmt->bind_param("s", $email);
    $chkStmt->execute();
    $chkStmt->store_result();

    if ($chkStmt->num_rows > 0) {
        $chkStmt->close();
        sendResponse(409, false, "A student or user with email '$email' already exists.");
    }
    $chkStmt->close();

    // 3. Hash password using bcrypt
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 4. Insert student record via MySQLi Prepared Statement
    $insStmt = $conn->prepare("INSERT INTO students (name, email, mobile, password, course, year, role, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$insStmt) {
        sendResponse(500, false, "Failed to prepare insert query: " . $conn->error);
    }

    $insStmt->bind_param("ssssssss", $name, $email, $mobile, $hashedPassword, $course, $year, $role, $gender);
    if ($insStmt->execute()) {
        $newId = $insStmt->insert_id;
        $insStmt->close();

        // 5. Practical 14: Record Audit Log
        log_audit_action($conn, $adminId, "ADD_STUDENT", "Added new student '$name' ($email)", $newId);

        sendResponse(201, true, "Student '$name' (ID #$newId) added successfully.", [
            "id" => $newId,
            "name" => $name,
            "email" => $email,
            "mobile" => $mobile,
            "course" => $course,
            "year" => $year,
            "role" => $role,
            "gender" => $gender
        ]);
    } else {
        $err = $conn->error;
        $insStmt->close();
        sendResponse(500, false, "Database insertion failed: " . $err);
    }
}

// ========================================================
// METHOD: PUT (Update student)
// ========================================================
elseif ($method === "PUT") {
    $studentId = (int)($inputData["id"] ?? $_GET["id"] ?? 0);
    if ($studentId <= 0) {
        sendResponse(400, false, "Valid student ID is required for update.");
    }

    // Verify existing student
    $checkStmt = $conn->prepare("SELECT id, name FROM students WHERE id = ? LIMIT 1");
    if (!$checkStmt) {
        sendResponse(500, false, "Database error: " . $conn->error);
    }
    $checkStmt->bind_param("i", $studentId);
    $checkStmt->execute();
    $existRes = $checkStmt->get_result();
    $existingStudent = $existRes->fetch_assoc();
    $checkStmt->close();

    if (!$existingStudent) {
        sendResponse(404, false, "Student record with ID #$studentId not found.");
    }

    $name = trim($inputData["name"] ?? "");
    $email = trim($inputData["email"] ?? "");
    $mobile = trim($inputData["mobile"] ?? "");
    $course = trim($inputData["course"] ?? "");
    $year = trim($inputData["year"] ?? "");
    $role = trim($inputData["role"] ?? "student");
    $gender = trim($inputData["gender"] ?? "Other");
    $newPassword = $inputData["password"] ?? $inputData["new_password"] ?? "";

    // Validations
    if ($name === "" || strlen($name) < 3) {
        sendResponse(400, false, "Student name is required and must be at least 3 characters.");
    }
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, false, "A valid university email address is required.");
    }
    if ($mobile === "" || !preg_match("/^[0-9]{10}$/", $mobile)) {
        sendResponse(400, false, "Mobile number must be exactly 10 digits.");
    }
    if ($course === "") {
        sendResponse(400, false, "Please select an enrolled degree course.");
    }
    if ($year === "") {
        sendResponse(400, false, "Please select an academic year.");
    }
    if ($newPassword !== "" && strlen($newPassword) < 8) {
        sendResponse(400, false, "Password must be at least 8 characters long if provided.");
    }

    // Duplicate email check excluding current student ID
    $dupStmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
    if (!$dupStmt) {
        sendResponse(500, false, "Database error: " . $conn->error);
    }
    $dupStmt->bind_param("si", $email, $studentId);
    $dupStmt->execute();
    $dupStmt->store_result();

    if ($dupStmt->num_rows > 0) {
        $dupStmt->close();
        sendResponse(409, false, "Email address '$email' is already registered to another user.");
    }
    $dupStmt->close();

    // Perform Update
    if ($newPassword !== "") {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $upStmt = $conn->prepare("UPDATE students SET name = ?, email = ?, mobile = ?, course = ?, year = ?, role = ?, gender = ?, password = ? WHERE id = ?");
        $upStmt->bind_param("ssssssssi", $name, $email, $mobile, $course, $year, $role, $gender, $hashed, $studentId);
    } else {
        $upStmt = $conn->prepare("UPDATE students SET name = ?, email = ?, mobile = ?, course = ?, year = ?, role = ?, gender = ? WHERE id = ?");
        $upStmt->bind_param("sssssssi", $name, $email, $mobile, $course, $year, $role, $gender, $studentId);
    }

    if ($upStmt->execute()) {
        $upStmt->close();

        // Practical 14: Record Audit Log
        log_audit_action($conn, $adminId, "UPDATE_STUDENT", "Updated student #$studentId details ($name)", $studentId);

        sendResponse(200, true, "Student record #$studentId updated successfully.", [
            "id" => $studentId,
            "name" => $name,
            "email" => $email,
            "mobile" => $mobile,
            "course" => $course,
            "year" => $year,
            "role" => $role,
            "gender" => $gender
        ]);
    } else {
        $err = $conn->error;
        $upStmt->close();
        sendResponse(500, false, "Update query execution failed: " . $err);
    }
}

// ========================================================
// METHOD: DELETE (Delete student)
// ========================================================
elseif ($method === "DELETE") {
    $studentId = (int)($inputData["id"] ?? $_GET["id"] ?? 0);
    if ($studentId <= 0) {
        sendResponse(400, false, "Valid student ID is required for deletion.");
    }

    // Security check: Admin cannot delete their own active account
    if ($studentId === $adminId) {
        sendResponse(400, false, "Action Denied: You cannot delete your own active administrator account!");
    }

    // Check if record exists
    $chkStmt = $conn->prepare("SELECT id, name FROM students WHERE id = ? LIMIT 1");
    if (!$chkStmt) {
        sendResponse(500, false, "Database error: " . $conn->error);
    }
    $chkStmt->bind_param("i", $studentId);
    $chkStmt->execute();
    $student = $chkStmt->get_result()->fetch_assoc();
    $chkStmt->close();

    if (!$student) {
        sendResponse(404, false, "Student record with ID #$studentId not found or already deleted.");
    }

    $studentName = $student["name"];

    // Delete student record
    $delStmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    if (!$delStmt) {
        sendResponse(500, false, "Query preparation failed: " . $conn->error);
    }

    $delStmt->bind_param("i", $studentId);
    if ($delStmt->execute()) {
        $delStmt->close();

        // Practical 14: Record Audit Log
        log_audit_action($conn, $adminId, "DELETE_STUDENT", "Deleted student #$studentId ($studentName)", $studentId);

        sendResponse(200, true, "Student '$studentName' (ID #$studentId) deleted successfully.", [
            "id" => $studentId
        ]);
    } else {
        $err = $conn->error;
        $delStmt->close();
        sendResponse(500, false, "Deletion failed: " . $err);
    }
}

// Method Not Allowed
else {
    sendResponse(405, false, "HTTP Method '$method' not allowed.");
}
?>
