<?php
// Practical 9: Secure User Registration with MySQLi Database Insert,
// Duplicate Email Check, and Password Hashing (bcrypt)
// Reusing Practical 8 Database: studenthub (Table: students)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";

// 1. Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: registration.html");
    exit;
}

// 2. CSRF Token verification (if token is submitted in form)
if (isset($_POST["csrf_token"]) && !empty($_SESSION["csrf_token"])) {
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])) {
        respondError("Invalid or expired security token. Please try again.");
    }
}

// 3. Retrieve and sanitize form data
$fullname = trim($_POST["fullname"] ?? $_POST["full_name"] ?? $_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["phone"] ?? $_POST["mobile"] ?? "");
$course = trim($_POST["course"] ?? "");
$year = trim($_POST["year"] ?? $_POST["academic_year"] ?? "");
$gender = trim($_POST["reg-gender"] ?? $_POST["gender"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$terms = isset($_POST["terms"]);

// Helper function to return error response (browser alert + redirect)
function respondError($message) {
    $_SESSION["reg_error"] = $message;
    echo "<script>
        alert('" . addslashes($message) . "');
        window.location.href = 'registration.html?error=" . urlencode($message) . "';
    </script>";
    exit;
}

// 4. Server-Side Validations

// Full Name Validation
if ($fullname === "") {
    respondError("Full Name is required.");
}
if (!preg_match("/^[a-zA-Z\s]{3,100}$/", $fullname)) {
    respondError("Full Name must contain only letters and spaces (minimum 3 characters).");
}

// Email Validation
if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondError("Please enter a valid email address.");
}

// Mobile Number Validation (10 digits starting with 6-9)
if ($mobile === "" || !preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
    respondError("Mobile number must contain exactly 10 digits and start with 6, 7, 8, or 9.");
}

// Course Selection Validation
if ($course === "") {
    respondError("Please select your enrolled course.");
}

// Academic Year Validation
if ($year === "") {
    respondError("Please select your academic year.");
}

// Gender Selection Validation
if ($gender === "" || !in_array($gender, ["Male", "Female", "Other"])) {
    respondError("Please select your gender.");
}

// Password Validation (min 8 chars, uppercase, lowercase, digit, special char)
if ($password === "") {
    respondError("Password is required.");
}
if (strlen($password) < 8) {
    respondError("Password must be at least 8 characters long.");
}
if (!preg_match('/[A-Z]/', $password)) {
    respondError("Password must contain at least one uppercase letter (A-Z).");
}
if (!preg_match('/[a-z]/', $password)) {
    respondError("Password must contain at least one lowercase letter (a-z).");
}
if (!preg_match('/[0-9]/', $password)) {
    respondError("Password must contain at least one number (0-9).");
}
if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
    respondError("Password must contain at least one special character (!@#$%^&*...).");
}

// Confirm Password Match
if ($password !== $confirmPassword) {
    respondError("Passwords do not match.");
}

// Terms and Conditions Acceptance
if (!$terms) {
    respondError("You must agree to the university IT rules and code of conduct.");
}

// 5. Database Duplicate Email Check using MySQLi Prepared Statement on studenthub.students
if (!$conn || $conn->connect_error) {
    respondError("Database connection failed. Please ensure MySQL is running in XAMPP.");
}

$check_stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
if (!$check_stmt) {
    respondError("Database query preparation failed.");
}
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    respondError("This email is already registered. Please use another email.");
}
$check_stmt->close();

// 6. Secure Password Hashing using PHP password_hash() (bcrypt)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 7. Insert Student Record into studenthub.students using MySQLi Prepared Statement
$insert_stmt = $conn->prepare("INSERT INTO students (name, email, mobile, password, course, year, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$insert_stmt) {
    respondError("Database insert preparation failed.");
}
$insert_stmt->bind_param("sssssss", $fullname, $email, $mobile, $hashed_password, $course, $year, $gender);

if ($insert_stmt->execute()) {
    $new_id = $insert_stmt->insert_id;
    $insert_stmt->close();

    // 8. Sync with registrations.json (Practical 7 compatibility)
    $dataDir = __DIR__ . "/data";
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    $jsonFile = $dataDir . "/registrations.json";
    $registrations = [];
    if (file_exists($jsonFile)) {
        $content = file_get_contents($jsonFile);
        if ($content) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) $registrations = $decoded;
        }
    }
    $registrations[] = [
        "id" => $new_id,
        "name" => htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"),
        "fullname" => htmlspecialchars($fullname, ENT_QUOTES, "UTF-8"),
        "email" => htmlspecialchars($email, ENT_QUOTES, "UTF-8"),
        "mobile" => htmlspecialchars($mobile, ENT_QUOTES, "UTF-8"),
        "course" => htmlspecialchars($course, ENT_QUOTES, "UTF-8"),
        "year" => htmlspecialchars($year, ENT_QUOTES, "UTF-8"),
        "gender" => htmlspecialchars($gender, ENT_QUOTES, "UTF-8"),
        "registered_at" => date("Y-m-d H:i:s")
    ];
    @file_put_contents($jsonFile, json_encode($registrations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    @file_put_contents(__DIR__ . "/registrations.json", json_encode($registrations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    // Success response
    $_SESSION["reg_success"] = "Registration successful! Your account has been securely created in database 'studenthub'.";
    echo "<script>
        alert('Registration submitted successfully! Your account has been securely created in the database.');
        window.location.href = 'registration.html?success=1';
    </script>";
    exit;

} else {
    // Handle duplicate-key error or other database error safely
    if ($conn->errno === 1062) {
        $insert_stmt->close();
        respondError("This email is already registered. Please use another email.");
    } else {
        $insert_stmt->close();
        respondError("Database error occurred while saving registration. Please try again.");
    }
}
?>
