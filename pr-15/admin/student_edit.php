<?php
// ========================================================
// Practical 11: Edit Student Record (Admin Only)
// File: admin/student_edit.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Load Record by PK, Validate Inputs, Preserve Unchanged Fields,
//           Optional Password Update, and MySQLi Prepared Statements.
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";
require_once dirname(__DIR__) . "/php/audit_helper.php";
check_auth("admin");

$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");

// 1. Validate incoming student ID
$studentId = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($studentId <= 0) {
    header("Location: students.php?error=" . urlencode("Invalid student ID."));
    exit;
}

$error = "";

// 2. Fetch existing student record using MySQLi Prepared Statement
$stmt = $conn->prepare("SELECT id, name, email, mobile, course, year, role, gender FROM students WHERE id = ? LIMIT 1");
if (!$stmt) {
    header("Location: students.php?error=" . urlencode("Database query error."));
    exit;
}

$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: students.php?error=" . urlencode("Student record not found for ID #$studentId."));
    exit;
}

// 3. Handle Form Submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $role = trim($_POST["role"] ?? "student");
    $gender = trim($_POST["gender"] ?? "Other");
    $newPassword = $_POST["new_password"] ?? "";

    // Server-Side Validations
    if ($name === "" || strlen($name) < 3) {
        $error = "Student name is required and must be at least 3 characters.";
    } elseif ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "A valid university email address is required.";
    } elseif ($mobile === "" || !preg_match("/^[0-9]{10}$/", $mobile)) {
        $error = "Mobile number must be exactly 10 digits.";
    } elseif ($course === "") {
        $error = "Please select an enrolled degree course.";
    } elseif ($year === "") {
        $error = "Please select an academic year.";
    } elseif ($newPassword !== "" && strlen($newPassword) < 8) {
        $error = "New password must be at least 8 characters long if provided.";
    } else {
        // Check for duplicate email excluding current student ID
        $chkStmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
        if ($chkStmt) {
            $chkStmt->bind_param("si", $email, $studentId);
            $chkStmt->execute();
            $chkStmt->store_result();

            if ($chkStmt->num_rows > 0) {
                $error = "Email address '$email' is already registered to another user.";
                $chkStmt->close();
            } else {
                $chkStmt->close();

                // Build prepared update statement (with or without password change)
                if ($newPassword !== "") {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $upStmt = $conn->prepare("UPDATE students 
                                              SET name = ?, email = ?, mobile = ?, course = ?, year = ?, role = ?, gender = ?, password = ? 
                                              WHERE id = ?");
                    $upStmt->bind_param("ssssssssi", $name, $email, $mobile, $course, $year, $role, $gender, $hashedPassword, $studentId);
                } else {
                    $upStmt = $conn->prepare("UPDATE students 
                                              SET name = ?, email = ?, mobile = ?, course = ?, year = ?, role = ?, gender = ? 
                                              WHERE id = ?");
                    $upStmt->bind_param("sssssssi", $name, $email, $mobile, $course, $year, $role, $gender, $studentId);
                }

                if ($upStmt->execute()) {
                    $upStmt->close();

                    // Practical 14: Log administrative action
                    log_audit_action($conn, (int)($_SESSION["user_id"] ?? 0), "UPDATE_STUDENT", "Updated student #$studentId ($name)", $studentId);

                    header("Location: students.php?msg=" . urlencode("Student '$name' (ID #$studentId) updated successfully!"));
                    exit;
                } else {
                    $error = "Update failed: " . $conn->error;
                    $upStmt->close();
                }
            }
        }
    }

    // Retain submitted values on validation error
    $student["name"] = $name;
    $student["email"] = $email;
    $student["mobile"] = $mobile;
    $student["course"] = $course;
    $student["year"] = $year;
    $student["role"] = $role;
    $student["gender"] = $gender;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student #<?php echo (int)$student['id']; ?> - The Backbenchers University</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <!-- Header Section -->
    <header>
        <div class="container header-inner">
            <a href="../index.html" class="header-brand">
                <img src="../images/logo.svg" alt="University Logo" class="header-logo">
                <div class="header-title">
                    <h1>THE BACKBENCHERS UNIVERSITY</h1>
                    <p>Office of Academic Administration &bull; Practical 11</p>
                </div>
            </a>
            <div class="header-quick-info">
                <span class="badge-tag">👤 <?php echo $adminName; ?></span>
                <span class="session-timer-badge" id="session-timer-badge">
                    ⏱️ Session: <span id="session-timer-clock" data-remaining="<?php echo $session_remaining_seconds ?? 900; ?>">14:59</span>
                </span>
                <a href="../php/logout.php" style="background:#ef4444; color:#fff; padding:6px 14px; border-radius:4px; text-decoration:none; font-weight:600; font-size:12px;">
                    🔒 Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label"><span>Admin Menu</span></div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="../admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="students.php" class="active">Student Management</a></li>
                    <li><a href="events.php">Event Management</a></li>
                    <li><a href="../records.php">Student Records</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl); max-width: 760px;">

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h2>✏️ Edit Student Record (ID #<?php echo (int)$student['id']; ?>)</h2>
                    <p style="color: var(--text-muted); font-size: var(--font-xs); margin-top: 4px;">
                        Update student record details and enrolled course
                    </p>
                </div>
                <a href="students.php" class="btn btn-outline btn-sm">&laquo; Back to Student List</a>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background-color: var(--danger-bg); border-left: 4px solid var(--danger); color: var(--danger); padding: var(--space-md); margin-bottom: var(--space-md); border-radius: var(--radius-sm);">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="student_edit.php">
                <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">

                <div class="d-flex flex-col gap-md">

                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="name">Student Full Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>

                    <!-- Email & Mobile Row -->
                    <div class="grid-2-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                        <div class="form-group">
                            <label for="email">University Email <span style="color: var(--danger);">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mobile">Mobile Number (10 digits) <span style="color: var(--danger);">*</span></label>
                            <input type="tel" id="mobile" name="mobile" class="form-control" pattern="[0-9]{10}" maxlength="10" value="<?php echo htmlspecialchars($student['mobile']); ?>" required>
                        </div>
                    </div>

                    <!-- Course & Academic Year Row -->
                    <div class="grid-2-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                        <div class="form-group">
                            <label for="course">Degree Course <span style="color: var(--danger);">*</span></label>
                            <select id="course" name="course" class="form-control" required>
                                <option value="B.Tech Information Technology" <?php if ($student['course'] === "B.Tech Information Technology") echo 'selected'; ?>>B.Tech Information Technology</option>
                                <option value="B.Tech Computer Engineering" <?php if ($student['course'] === "B.Tech Computer Engineering") echo 'selected'; ?>>B.Tech Computer Engineering</option>
                                <option value="B.Tech AI & Data Science" <?php if ($student['course'] === "B.Tech AI & Data Science") echo 'selected'; ?>>B.Tech AI & Data Science</option>
                                <option value="B.Tech Mechanical Engineering" <?php if ($student['course'] === "B.Tech Mechanical Engineering") echo 'selected'; ?>>B.Tech Mechanical Engineering</option>
                                <option value="B.Tech Electronics & Telecom" <?php if ($student['course'] === "B.Tech Electronics & Telecom") echo 'selected'; ?>>B.Tech Electronics & Telecom</option>
                                <option value="MCA" <?php if ($student['course'] === "MCA") echo 'selected'; ?>>MCA (Master of Computer Applications)</option>
                                <option value="Administration" <?php if ($student['course'] === "Administration") echo 'selected'; ?>>Administration (Staff)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="year">Academic Year <span style="color: var(--danger);">*</span></label>
                            <select id="year" name="year" class="form-control" required>
                                <option value="First Year (FE)" <?php if ($student['year'] === "First Year (FE)") echo 'selected'; ?>>First Year (FE)</option>
                                <option value="Second Year (SE)" <?php if ($student['year'] === "Second Year (SE)") echo 'selected'; ?>>Second Year (SE)</option>
                                <option value="Third Year (TE)" <?php if ($student['year'] === "Third Year (TE)") echo 'selected'; ?>>Third Year (TE)</option>
                                <option value="Final Year (BE)" <?php if ($student['year'] === "Final Year (BE)") echo 'selected'; ?>>Final Year (BE)</option>
                                <option value="Staff" <?php if ($student['year'] === "Staff") echo 'selected'; ?>>Staff / Faculty</option>
                            </select>
                        </div>
                    </div>

                    <!-- Role & Gender Row -->
                    <div class="grid-2-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                        <div class="form-group">
                            <label for="role">User Role <span style="color: var(--danger);">*</span></label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="student" <?php if (strtolower($student['role']) === "student") echo 'selected'; ?>>Student</option>
                                <option value="admin" <?php if (strtolower($student['role']) === "admin") echo 'selected'; ?>>Administrator</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender <span style="color: var(--danger);">*</span></label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="Male" <?php if ($student['gender'] === "Male") echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if ($student['gender'] === "Female") echo 'selected'; ?>>Female</option>
                                <option value="Other" <?php if ($student['gender'] === "Other" || empty($student['gender'])) echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Password Update (Optional) -->
                    <div class="form-group">
                        <label for="new_password">Change Password (Leave blank to keep existing password)</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password only if changing" minlength="8">
                        <small style="color: var(--text-muted); font-size: 11px;">
                            If left blank, the student's current hashed password remains unchanged.
                        </small>
                    </div>

                    <!-- Submit Actions -->
                    <div class="d-flex justify-between align-center" style="margin-top: var(--space-md); border-top: 1px solid var(--border-color); padding-top: var(--space-md);">
                        <a href="students.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-md">💾 Save Changes</button>
                    </div>

                </div>
            </form>
        </div>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Office of Academic Administration.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script src="../session_timer.js"></script>
</body>
</html>
