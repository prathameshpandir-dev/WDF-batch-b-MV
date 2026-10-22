<?php
// Student Records - Institutional Directory
// The Backbenchers University - Office of Academic Affairs

require_once __DIR__ . "/php/auth_check.php";
check_auth("admin");

$students = [];
$errorMsg = "";

if ($conn && !$conn->connect_error) {
    $result = $conn->query("SELECT id, name, email, mobile, password, course, year, role, gender, created_at FROM students ORDER BY id ASC");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $result->free();
    } else {
        $errorMsg = "Unable to retrieve student records. Please contact system administrator.";
    }
} else {
    $errorMsg = "Database connection is currently unavailable. Please verify server status.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - The Backbenchers University</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }
        [data-theme="dark"] .status-badge {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        .hash-code {
            font-family: monospace;
            font-size: 11px;
            color: var(--text-muted);
            cursor: help;
        }
        .table th {
            font-weight: 600;
            color: var(--text-primary);
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header>
        <div class="container header-inner">
            <a href="index.html" class="header-brand">
                <img src="images/logo.svg" alt="University Logo" class="header-logo">
                <div class="header-title">
                    <h1>THE BACKBENCHERS UNIVERSITY</h1>
                    <p>Accredited NAAC A++ | Autonomous Engineering Institute</p>
                </div>
            </a>
            <div class="header-quick-info">
                <span class="badge-tag">Office of the Registrar</span>
                <span class="session-timer-badge" style="display:inline-flex; align-items:center; gap:6px; background:rgba(245,158,11,0.2); color:#fbbf24; border:1px solid rgba(245,158,11,0.4); padding:4px 10px; border-radius:9999px; font-size:12px; font-weight:600;">
                    ⏱️ Session: <span id="session-timer-clock" data-remaining="<?php echo $session_remaining_seconds ?? 900; ?>">14:59</span>
                </span>
                <button class="theme-toggle-btn" aria-label="Toggle Dark/Light theme">🌙 <span>Dark</span></button>
                <a href="php/logout.php" style="background-color:#ef4444; color:#fff; padding:6px 14px; border-radius:4px; text-decoration:none; font-weight:600; font-size:12px;">
                    🔒 Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label">
                <span>Navigation Menu</span>
            </div>
            <button class="nav-toggle-btn" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-menu" id="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="admin/students.php">Student Management</a></li>
                    <li><a href="admin/events.php">Event Management</a></li>
                    <li><a href="admin/audit_logs.php">Audit Logs</a></li>
                    <li><a href="dashboard.php">Student Dashboard</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li class="nav-dropdown">
                        <button class="nav-dropdown-btn">Student Portal ▾</button>
                        <ul class="nav-dropdown-menu">
                            <li><a href="profile.html">Student Profile</a></li>
                            <li><a href="attendance.html">Attendance</a></li>
                            <li><a href="academics.html">Academics &amp; Grades</a></li>
                            <li><a href="timetable.html">Class Timetable</a></li>
                            <li><a href="assignments.html">Assignments</a></li>
                            <li><a href="fees.html">Fee Payment</a></li>
                            <li><a href="library.html">Digital Library</a></li>
                            <li><a href="placements.html">Placements</a></li>
                            <li><a href="settings.html">Account Settings</a></li>
                            <li><a href="records.php" class="active">Student Records</a></li>
                        </ul>
                    </li>
                    <li><a href="contact.html">Contact</a></li>
                    <li><a href="feedback.html">Feedback</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl);">

        <!-- Page Title & Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-md); margin-bottom: var(--space-lg);">
            <div>
                <h2 style="font-size: var(--font-2xl); margin: 0 0 4px 0; color: var(--text-primary);">🎓 Enrolled Student Records</h2>
                <p style="color: var(--text-muted); margin: 0; font-size: var(--font-sm);">
                    Institutional Student Information System &bull; Enrolled Batches
                </p>
            </div>
            <div style="display: flex; gap: var(--space-sm); align-items: center;">
                <a href="registration.html" class="btn btn-accent btn-sm">+ Register New Student &raquo;</a>
            </div>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-md); padding: 14px 18px; margin-bottom: var(--space-lg); color: var(--danger); font-size: var(--font-sm);">
                <strong>Notice:</strong> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Key Metrics Cards (Native Dashboard Grid) -->
        <div class="dash-grid" style="margin-bottom: var(--space-xl);">
            <div class="metric-card">
                <div class="metric-icon">
                    <img src="images/profile.svg" alt="Students">
                </div>
                <div class="metric-details">
                    <h4>Total Enrolled</h4>
                    <div class="metric-value"><?php echo count($students); ?></div>
                    <div class="metric-sub">Active Registered Students</div>
                </div>
            </div>

            <div class="metric-card accent">
                <div class="metric-icon">
                    <img src="images/assignment.svg" alt="Academic Year">
                </div>
                <div class="metric-details">
                    <h4>Academic Session</h4>
                    <div class="metric-value">2025-26</div>
                    <div class="metric-sub">Odd / Even Semesters</div>
                </div>
            </div>

            <div class="metric-card success">
                <div class="metric-icon">
                    <img src="images/attendance.svg" alt="Status">
                </div>
                <div class="metric-details">
                    <h4>Account Security</h4>
                    <div class="metric-value">Secured</div>
                    <div class="metric-sub">Bcrypt Password Hashing</div>
                </div>
            </div>
        </div>

        <!-- Student Records Table Card -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h3 style="margin: 0; font-size: var(--font-lg);">Enrolled Students Directory</h3>
                    <p style="color: var(--text-muted); font-size: var(--font-xs); margin: 2px 0 0 0;">
                        Verified student credentials &amp; institutional enrollment details
                    </p>
                </div>
                <span class="badge badge-primary"><?php echo count($students); ?> Registered</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse; font-size: var(--font-xs);">
                        <thead>
                            <tr style="background: var(--bg-alt); text-align: left; border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 12px 14px;">ID</th>
                                <th style="padding: 12px 14px;">Full Name</th>
                                <th style="padding: 12px 14px;">Email Address</th>
                                <th style="padding: 12px 14px;">Contact Number</th>
                                <th style="padding: 12px 14px;">Department / Course</th>
                                <th style="padding: 12px 14px;">Academic Year</th>
                                <th style="padding: 12px 14px;">Gender</th>
                                <th style="padding: 12px 14px;">Account Status</th>
                                <th style="padding: 12px 14px;">Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        No student records found in the database. <br>
                                        <a href="registration.html" style="color: var(--accent); text-decoration: underline; font-weight: 600; display: inline-block; margin-top: 8px;">
                                            Register a new student &raquo;
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 12px 14px; font-weight: 700; color: var(--text-muted);">
                                            #<?php echo htmlspecialchars($s['id']); ?>
                                        </td>
                                        <td style="padding: 12px 14px; font-weight: 600; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($s['name']); ?>
                                        </td>
                                        <td style="padding: 12px 14px; color: var(--secondary);">
                                            <?php echo htmlspecialchars($s['email']); ?>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <?php echo htmlspecialchars($s['mobile']); ?>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <?php echo htmlspecialchars($s['course']); ?>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <?php echo htmlspecialchars($s['year']); ?>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <?php echo htmlspecialchars($s['gender']); ?>
                                        </td>
                                        <td style="padding: 12px 14px;">
                                            <span class="status-badge" title="Password Hash: <?php echo htmlspecialchars($s['password'] ?? 'Not set'); ?>">
                                                ● Active &amp; Encrypted
                                            </span>
                                        </td>
                                        <td style="padding: 12px 14px; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($s['created_at']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer style="margin-top: auto; padding: var(--space-md) 0; border-top: 1px solid var(--border-color); background-color: rgba(0, 0, 0, 0.03);">
        <div class="container" style="text-align: center; font-size: var(--font-xs); color: var(--text-muted);">
            <p>&copy; 2026 The Backbenchers University. Office of Academic Affairs &bull; Authorized Access Only.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="session_timer.js"></script>
</body>
</html>
