<?php
// ========================================================
// Practical 10 & Practical 14: Authenticated Admin Dashboard
// File: admin_dashboard.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Protection: php/auth_check.php (Admin Role Enforcement & 15m Timeout)
// Practical 14 Features: Live Database Statistics, Latest Registrations (JOIN),
//                        Recent Audit Trail, & Role-Based Navigation.
// ========================================================

require_once __DIR__ . "/php/auth_check.php";

// Strict administrator authorization check (Students & unauthenticated users rejected)
check_auth("admin");

$adminId = (int)($_SESSION["user_id"] ?? 0);
$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");
$adminEmail = htmlspecialchars($_SESSION["user_email"] ?? "admin@backbenchers.edu.in");
$adminRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "admin"));

// Dynamic Statistics from MySQL Database `studenthub`
$totalUsers = 0;
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;
$totalAuditLogs = 0;
$latestRegistrations = [];
$recentStudents = [];
$recentAuditLogs = [];

if ($conn && !$conn->connect_error) {
    // 1. Total Users (all accounts in system: students + admins)
    $rUsers = $conn->query("SELECT COUNT(*) AS total FROM students");
    if ($rUsers) {
        $totalUsers = (int)($rUsers->fetch_assoc()["total"] ?? 0);
    }

    // 2. Total Enrolled Students (role = 'student')
    $rStudents = $conn->query("SELECT COUNT(*) AS total FROM students WHERE role = 'student'");
    if ($rStudents) {
        $totalStudents = (int)($rStudents->fetch_assoc()["total"] ?? 0);
    }

    // 3. Total Events
    $rEvents = $conn->query("SELECT COUNT(*) AS total FROM events");
    if ($rEvents) {
        $totalEvents = (int)($rEvents->fetch_assoc()["total"] ?? 0);
    }

    // 4. Total Event Registrations
    $rRegs = $conn->query("SELECT COUNT(*) AS total FROM registrations");
    if ($rRegs) {
        $totalRegistrations = (int)($rRegs->fetch_assoc()["total"] ?? 0);
    }

    // 5. Total Audit Logs
    $rLogs = $conn->query("SELECT COUNT(*) AS total FROM audit_logs");
    if ($rLogs) {
        $totalAuditLogs = (int)($rLogs->fetch_assoc()["total"] ?? 0);
    }

    // 6. Practical 14: Latest Registrations (INNER JOIN registrations + students + events)
    $sqlReg = "SELECT r.id, s.name AS student_name, s.email AS student_email, s.course AS student_course,
                      e.title AS event_title, e.event_date, r.registered_at
               FROM registrations r
               JOIN students s ON r.student_id = s.id
               JOIN events e ON r.event_id = e.id
               ORDER BY r.registered_at DESC, r.id DESC 
               LIMIT 6";
    $resReg = $conn->query($sqlReg);
    if ($resReg) {
        while ($regRow = $resReg->fetch_assoc()) {
            $latestRegistrations[] = $regRow;
        }
    }

    // 7. Recent Students (table `students`)
    $rRecentSt = $conn->query("SELECT id, name, email, mobile, course, year, role, created_at FROM students ORDER BY id DESC LIMIT 5");
    if ($rRecentSt) {
        while ($st = $rRecentSt->fetch_assoc()) {
            $recentStudents[] = $st;
        }
    }

    // 8. Recent Audit Logs (Practical 14)
    $sqlAudit = "SELECT a.id, COALESCE(s.name, 'Admin') AS admin_name, a.action, a.description, a.record_id, a.created_at
                 FROM audit_logs a
                 LEFT JOIN students s ON a.admin_id = s.id
                 ORDER BY a.id DESC 
                 LIMIT 5";
    $resAudit = $conn->query($sqlAudit);
    if ($resAudit) {
        while ($aRow = $resAudit->fetch_assoc()) {
            $recentAuditLogs[] = $aRow;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrative Control Center - The Backbenchers University</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #2563eb 100%);
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: var(--space-xl);
            margin-bottom: var(--space-lg);
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.35), 0 8px 10px -6px rgba(15, 23, 42, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .admin-banner h2 {
            color: #ffffff !important;
            font-size: var(--font-2xl);
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
        }
        .admin-banner p {
            color: #e2e8f0 !important;
            margin: 0;
            font-size: var(--font-sm);
            line-height: 1.5;
        }
        .admin-badge {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(6px);
            padding: 4px 12px;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: var(--font-xs);
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .logout-link-btn {
            background-color: #ef4444 !important;
            color: #ffffff !important;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: var(--font-xs);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: var(--transition-fast);
        }
        .logout-link-btn:hover {
            background-color: #dc2626 !important;
        }
        .session-timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: var(--font-xs);
            font-weight: 600;
        }
        .action-pill-sm {
            padding: 2px 7px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .pill-add { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .pill-update { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
        .pill-delete { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
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
                    <p>Office of Academic Affairs &amp; Examination Administration</p>
                </div>
            </a>
            <div class="header-quick-info">
                <span class="badge-tag" style="font-weight: 600;">👤 <?php echo $adminName; ?></span>
                <span class="admin-badge">🏢 <?php echo $adminRole; ?></span>
                <span class="session-timer-badge" id="session-timer-badge" title="Session automatically terminates after 15 minutes of inactivity">
                    ⏱️ Session: <span id="session-timer-clock" data-remaining="<?php echo $session_remaining_seconds ?? 900; ?>">14:59</span>
                </span>
                <a href="php/logout.php" class="logout-link-btn" title="End your secure session">
                    🔒 Logout
                </a>
                <button class="theme-toggle-btn" aria-label="Toggle Dark/Light theme">
                    🌙 <span>Dark</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Role-Based Navigation Bar (Admin Menu) -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label">
                <span>Admin Menu</span>
            </div>
            <button class="nav-toggle-btn" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-menu" id="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="admin_dashboard.php" class="active">Admin Dashboard</a></li>
                    <li><a href="admin/students.php">Student Management</a></li>
                    <li><a href="admin/events.php">Event Management</a></li>
                    <li><a href="admin/audit_logs.php">Audit Logs</a></li>
                    <li><a href="records.php">Student Records</a></li>
                    <li><a href="events.php">Campus Events</a></li>
                    <li><a href="dashboard.php">Student View</a></li>
                    <li><a href="php/logout.php" style="color: #ef4444; font-weight: 600;">Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Admin Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl);">

        <!-- Admin Hero Banner -->
        <div class="admin-banner">
            <div class="d-flex flex-wrap justify-between align-center gap-md">
                <div>
                    <div class="d-flex align-center gap-sm" style="margin-bottom: 8px;">
                        <span class="admin-badge">🛡️ Admin Control Center</span>
                        <span style="font-size: var(--font-xs); opacity: 0.85;">Database: studenthub</span>
                    </div>
                    <h2 style="font-size: var(--font-2xl); margin-bottom: 6px;">Welcome, <?php echo $adminName; ?></h2>
                    <p style="margin: 0; opacity: 0.9; font-size: var(--font-sm);">
                        Logged in as <b><?php echo $adminEmail; ?></b> (Admin ID: #<?php echo $adminId; ?>). Real-time database metrics and audit logging active.
                    </p>
                </div>
                <div class="d-flex gap-sm flex-wrap">
                    <a href="admin/students.php" class="btn btn-accent btn-md" style="background-color: #f59e0b; color: #0f172a; font-weight: 600; border: none; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);">👥 Manage Students &raquo;</a>
                    <a href="admin/events.php" class="btn btn-md" style="background-color: rgba(255, 255, 255, 0.18); color: white; border: 1px solid rgba(255, 255, 255, 0.3); backdrop-filter: blur(4px);">🎪 Manage Events &raquo;</a>
                    <a href="admin/audit_logs.php" class="btn btn-outline btn-md" style="border-color: rgba(255,255,255,0.35); color: white; background: rgba(255,255,255,0.08); backdrop-filter: blur(4px);">🛡️ View Audit Logs</a>
                </div>
            </div>
        </div>

        <!-- Live Session Security & Inactivity Timer Banner -->
        <div class="card" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.08), rgba(59, 130, 246, 0.12)); border: 1px solid rgba(59, 130, 246, 0.25); padding: var(--space-md); margin-bottom: var(--space-lg);">
            <div class="d-flex justify-between align-center flex-wrap gap-md">
                <div class="d-flex align-center gap-sm">
                    <span style="font-size: 28px;">⏱️</span>
                    <div>
                        <h4 style="margin: 0 0 2px 0; font-size: var(--font-base); font-weight: 700; color: var(--primary);">
                            Administrator Session Security &amp; Activity Status
                        </h4>
                        <p style="margin: 0; font-size: var(--font-xs); color: var(--text-muted);">
                            Active Session for <strong><?php echo $adminName; ?></strong> (Admin ID: #<?php echo $adminId; ?>) &bull; Auto-logout after 15 minutes of inactivity.
                        </p>
                    </div>
                </div>
                <div class="d-flex align-center gap-sm">
                    <span style="font-size: var(--font-xs); font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Time Remaining:</span>
                    <span class="session-timer-clock" style="font-size: 1.35rem; font-weight: 800; font-family: monospace; background: var(--bg-card); padding: 4px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: var(--secondary);">
                        14:59
                    </span>
                    <a href="php/logout.php" class="btn btn-sm" style="background: #ef4444; color: white; padding: 4px 12px;">Logout</a>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards Grid (Practical 14 Database Statistics) -->
        <div class="dash-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
            <!-- 1. Total Registered Accounts/Users -->
            <div class="metric-card">
                <div class="metric-icon">
                    <img src="images/profile.svg" alt="Total Users">
                </div>
                <div class="metric-details">
                    <h4>Total Users</h4>
                    <div class="metric-value"><?php echo $totalUsers; ?></div>
                    <div class="metric-sub">Table: `students` (All roles)</div>
                </div>
            </div>

            <!-- 2. Total Enrolled Students -->
            <div class="metric-card accent">
                <div class="metric-icon">
                    <img src="images/student.svg" alt="Students">
                </div>
                <div class="metric-details">
                    <h4>Total Students</h4>
                    <div class="metric-value"><?php echo $totalStudents; ?></div>
                    <div class="metric-sub"><a href="admin/students.php" style="color: var(--accent); font-weight: 600;">Role: `student` &raquo;</a></div>
                </div>
            </div>

            <!-- 3. Total Campus Events -->
            <div class="metric-card success">
                <div class="metric-icon">
                    <img src="images/assignment.svg" alt="Events">
                </div>
                <div class="metric-details">
                    <h4>Total Events</h4>
                    <div class="metric-value"><?php echo $totalEvents; ?></div>
                    <div class="metric-sub"><a href="admin/events.php" style="color: var(--secondary); font-weight: 600;">Campus Events &raquo;</a></div>
                </div>
            </div>

            <!-- 4. Total Event Registrations -->
            <div class="metric-card">
                <div class="metric-icon">
                    <img src="images/library.svg" alt="Registrations">
                </div>
                <div class="metric-details">
                    <h4>Total Registrations</h4>
                    <div class="metric-value"><?php echo $totalRegistrations; ?></div>
                    <div class="metric-sub">Table: `registrations`</div>
                </div>
            </div>

            <!-- 5. System Audit Log Entries -->
            <div class="metric-card danger">
                <div class="metric-icon" style="font-size: 26px; display: flex; align-items: center; justify-content: center;">
                    🛡️
                </div>
                <div class="metric-details">
                    <h4>Audit Trail Logs</h4>
                    <div class="metric-value" style="color: #6366f1;"><?php echo $totalAuditLogs; ?></div>
                    <div class="metric-sub"><a href="admin/audit_logs.php" style="color: #6366f1; font-weight: 600;">View Security Logs &raquo;</a></div>
                </div>
            </div>
        </div>

        <!-- Latest Registrations Table (SQL JOIN) -->
        <div class="card" style="margin-top: var(--space-lg);">
            <div class="card-header d-flex justify-between align-center flex-wrap gap-sm">
                <div>
                    <h3>🎟️ Latest Event Registrations</h3>
                    <p style="font-size: var(--font-xs); color: var(--text-muted); margin: 0;">
                        Live SQL JOIN query connecting <code>registrations</code>, <code>students</code>, and <code>events</code> tables
                    </p>
                </div>
                <span class="badge badge-info">Total Active Registrations: <?php echo $totalRegistrations; ?></span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">Reg ID</th>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Course</th>
                            <th>Registered Event</th>
                            <th>Event Date</th>
                            <th>Registration Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestRegistrations)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    <div style="font-size: 2rem; margin-bottom: 6px;">📋</div>
                                    <strong>No event registrations recorded yet.</strong>
                                    <p style="font-size: 11px; margin: 4px 0 0 0;">Students can register for published campus events from the events catalog.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latestRegistrations as $reg): ?>
                                <tr>
                                    <td><b>#<?php echo (int)$reg["id"]; ?></b></td>
                                    <td><strong><?php echo htmlspecialchars($reg["student_name"]); ?></strong></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($reg["student_email"]); ?>" style="color: var(--secondary); text-decoration: none;">
                                            <?php echo htmlspecialchars($reg["student_email"]); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($reg["student_course"] ?? "Engineering"); ?></td>
                                    <td>
                                        <span class="badge badge-info" style="font-size: 11px;">
                                            🎪 <?php echo htmlspecialchars($reg["event_title"]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; color: var(--text-secondary);">
                                            📅 <?php echo date("d M Y", strtotime($reg["event_date"])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; color: var(--text-muted);">
                                            <?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($reg["registered_at"]))); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2-Column Split: Recent Students + Recent Audit Logs -->
        <div class="dash-main-split" style="margin-top: var(--space-lg);">

            <!-- Left: Recent Registered Students Table -->
            <div class="card">
                <div class="card-header d-flex justify-between align-center">
                    <div>
                        <h3>👥 Recent Registered Students</h3>
                        <p style="font-size: var(--font-xs); color: var(--text-muted); margin: 0;">Synchronized live from table <code>students</code></p>
                    </div>
                    <a href="admin/students.php" class="btn btn-outline btn-sm">Manage &raquo;</a>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentStudents)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">No students found in database.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentStudents as $s): ?>
                                    <tr>
                                        <td><b>#<?php echo htmlspecialchars((string)$s["id"]); ?></b></td>
                                        <td><?php echo htmlspecialchars($s["name"]); ?></td>
                                        <td><span style="font-size: 11px;"><?php echo htmlspecialchars($s["course"]); ?></span></td>
                                        <td>
                                            <?php if (($s["role"] ?? "student") === "admin"): ?>
                                                <span class="badge badge-warning" style="font-size: 10px;">Admin</span>
                                            <?php else: ?>
                                                <span class="badge badge-info" style="font-size: 10px;">Student</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars(substr($s["created_at"] ?? "", 0, 10)); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Recent Security Audit Trail -->
            <div class="card">
                <div class="card-header d-flex justify-between align-center">
                    <div>
                        <h3>🛡️ Recent Audit Trail</h3>
                        <p style="font-size: var(--font-xs); color: var(--text-muted); margin: 0;">Table: <code>audit_logs</code></p>
                    </div>
                    <a href="admin/audit_logs.php" class="btn btn-outline btn-sm">Full Log &raquo;</a>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAuditLogs)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                        No audit log entries recorded yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAuditLogs as $al): ?>
                                    <?php
                                        $act = $al["action"];
                                        $pillClass = "pill-update";
                                        if (strpos($act, "ADD") !== false) $pillClass = "pill-add";
                                        elseif (strpos($act, "DELETE") !== false) $pillClass = "pill-delete";
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="action-pill-sm <?php echo $pillClass; ?>">
                                                <?php echo htmlspecialchars($al["action"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 11px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($al['description']); ?>">
                                                <?php echo htmlspecialchars($al["description"]); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 11px; color: var(--text-muted);">
                                                <?php echo htmlspecialchars(date("h:i A", strtotime($al["created_at"]))); ?>
                                            </span>
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

    <!-- University Footer -->
    <footer>
        <div class="container footer-grid">
            <div class="footer-col">
                <h4>The Backbenchers University</h4>
                <p>An autonomous premier technical institute dedicated to engineering excellence, cutting-edge innovation, and holistic student development.</p>
            </div>
            <div class="footer-col">
                <h4>Admin Navigation</h4>
                <ul class="footer-links">
                    <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="admin/students.php">Student Management</a></li>
                    <li><a href="admin/events.php">Event Management</a></li>
                    <li><a href="admin/audit_logs.php">System Audit Log</a></li>
                    <li><a href="php/logout.php">Sign Out</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Administrative Standards</h4>
                <p style="font-size: var(--font-xs); color: #94a3b8;">
                    Administrative Dashboard with real-time SQL metrics, Latest Registrations JOIN, Security Audit Logging, and Strict Role-Based Access Control.
                </p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University. Office of Academic Administration.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="session_timer.js"></script>
</body>
</html>
