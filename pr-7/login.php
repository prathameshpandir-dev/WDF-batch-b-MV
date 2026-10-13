<?php
// Practical 9: Authentication Gateway using password_verify()
// Reusing Practical 8 Database: studenthub (Table: students)

session_start();
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$username = trim($_POST["username"] ?? $_POST["login-username"] ?? "");
$password = $_POST["password"] ?? $_POST["login-password"] ?? "";

if (empty($username) || empty($password)) {
    echo "<script>alert('Please enter both your Email / PRN and Password.'); window.location.href='login.html';</script>";
    exit;
}

if (!$conn || $conn->connect_error) {
    echo "<script>alert('Database connection error. Please try again.'); window.location.href='login.html';</script>";
    exit;
}

// Prepared Statement to fetch student by email or mobile from studenthub.students
$stmt = $conn->prepare("SELECT id, name, email, password, course, year FROM students WHERE email = ? OR mobile = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Secure verification using password_verify() against stored bcrypt hash
        if (!empty($user["password"]) && password_verify($password, $user["password"])) {
            $_SESSION["bbu_logged_in"] = true;
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            
            echo "<script>
                alert('Sign-in successful! Welcome, " . addslashes($user["name"]) . ".');
                window.location.href = 'dashboard.html';
            </script>";
            exit;
        }
    }
    $stmt->close();
}

// Fallback if not found in MySQL: check if default demo account
if ($username === "SYCS104" && $password === "password123") {
    $_SESSION["bbu_logged_in"] = true;
    echo "<script>alert('Sign-in successful (Demo Account).'); window.location.href='dashboard.html';</script>";
    exit;
}

echo "<script>
    alert('Invalid credentials. Please verify your email/PRN and password.');
    window.location.href = 'login.html';
</script>";
exit;
?>
