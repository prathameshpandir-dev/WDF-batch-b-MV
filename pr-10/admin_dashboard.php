<?php
// ========================================================
// Practical 10: Authenticated Admin Dashboard
// File: admin_dashboard.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Protection: php/auth_check.php (Admin Role Enforcement & 15m Timeout)
// ========================================================

require_once __DIR__ . "/php/auth_check.php";

// Enforce strict administrator authorization
check_auth("admin");

$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");
$adminEmail = htmlspecialchars($_SESSION["user_email"] ?? "admin@backbenchers.edu.in");
$adminRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "admin"));

// Fetch real summary stats from studenthub database
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;
$recentStudents = [];

if ($conn && !$conn->connect_error) {
    // 1. Total Students
    $r1 = $conn->query("SELECT COUNT(*) AS total FROM students");
    if ($r1) {
        $row = $r1->fetch_assoc();
        $totalStudents = (int)($row["total"] ?? 0);
    }

    // 2. Total Events
    $r2 = $conn->query("SELECT COUNT(*) AS total FROM events");
    if ($r2) {
        $row = $r2->fetch_assoc();
        $totalEvents = (int)($row["total"] ?? 0);
    }

    // 3. Total Event Registrations
    $r3 = $conn->query("SELECT COUNT(*) AS total FROM registrations");
    if ($r3) {
        $row = $r3->fetch_assoc();
        $totalRegistrations = (int)($row["total"] ?? 0);
    }

    // 4. Fetch up to 6 recent students
    $r4 = $conn->query("SELECT id, name, email, mobile, course, year, role, created_at FROM students ORDER BY id DESC LIMIT 6");
    if ($r4) {
        while ($st = $r4->fetch_assoc()) {
            $recentStudents[] = $st;
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
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            color: #ffffff;
            border-radius: var(--radius-md);
            padding: var(--space-xl);
            margin-bottom: var(--space-lg);
            box-shadow: var(--shadow-md);
        }
        .admin-badge {
            background-color: #f59e0b;
            color: #111827;
            padding: 3px 10px;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: var(--font-xs);
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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

    <!-- Navigation Bar -->
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
                    <li><a href="admin/students.php">Student Management (P11)</a></li>
                    <li><a href="admin/events.php">Event Management (P12)</a></li>
                    <li><a href="records.php">Student Records Directory</a></li>
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
                        <span class="admin-badge">🛡️ Role-Based Access Control</span>
                        <span style="font-size: var(--font-xs); opacity: 0.85;">Database: studenthub</span>
                    </div>
                    <h2 style="font-size: var(--font-2xl); margin-bottom: 6px;">Welcome, <?php echo $adminName; ?></h2>
                    <p style="margin: 0; opacity: 0.9; font-size: var(--font-sm);">
                        Logged in as <b><?php echo $adminEmail; ?></b>. You have administrative permissions to view institutional database records.
                    </p>
                </div>
                <div class="d-flex gap-sm flex-wrap">
                    <a href="admin/students.php" class="btn btn-accent btn-md">👥 Manage Students (P11) &raquo;</a>
                    <a href="admin/events.php" class="btn btn-md" style="background-color: var(--secondary); color: white;">🎪 Manage Events (P12) &raquo;</a>
                    <a href="php/logout.php" class="btn btn-outline btn-md" style="border-color: rgba(255,255,255,0.4); color: white;">🔒 Sign Out</a>
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
                            Active Session for <strong><?= $adminName ?></strong> (Admin ID: #<?= $adminId ?>) &bull; Auto-logout after 15 minutes of inactivity.
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

        <!-- Metric KPI Cards Grid -->
        <div class="dash-grid">
            <!-- Total Enrolled Students -->
            <div class="metric-card accent">
                <div class="metric-icon">
                    <img src="images/student.svg" alt="Students">
                </div>
                <div class="metric-details">
                    <h4>Total Registered Users</h4>
                    <div class="metric-value"><?php echo $totalStudents; ?></div>
                    <div class="metric-sub"><a href="admin/students.php" style="color: var(--accent); font-weight: 600;">Manage Students (P11) &raquo;</a></div>
                </div>
            </div>

            <!-- Total Campus Events -->
            <div class="metric-card success">
                <div class="metric-icon">
                    <img src="images/assignment.svg" alt="Events">
                </div>
                <div class="metric-details">
                    <h4>Campus Events Active</h4>
                    <div class="metric-value"><?php echo $totalEvents; ?></div>
                    <div class="metric-sub"><a href="admin/events.php" style="color: var(--secondary); font-weight: 600;">Manage Events (P12) &raquo;</a></div>
                </div>
            </div>

            <!-- Total Event Registrations -->
            <div class="metric-card">
                <div class="metric-icon">
                    <img src="images/library.svg" alt="Registrations">
                </div>
                <div class="metric-details">
                    <h4>Event Registrations</h4>
                    <div class="metric-value"><?php echo $totalRegistrations; ?></div>
                    <div class="metric-sub">Table: `registrations` (M:N Join)</div>
                </div>
            </div>

            <!-- Security Status -->
            <div class="metric-card danger">
                <div class="metric-icon">
                    <img src="images/profile.svg" alt="Security">
                </div>
                <div class="metric-details">
                    <h4>Session Security</h4>
                    <div class="metric-value" style="font-size: 1.25rem; color: var(--success);">Protected</div>
                    <div class="metric-sub">Bcrypt + Regenerated ID</div>
                </div>
            </div>
        </div>

        <!-- Recent Registered Students Table -->
        <div class="card" style="margin-top: var(--space-lg);">
            <div class="card-header d-flex justify-between align-center">
                <div>
                    <h3>👥 Recent Registered Students in MySQL Database</h3>
                    <p style="font-size: var(--font-xs); color: var(--text-muted); margin: 0;">Synchronized live from table <code>studenthub.students</code></p>
                </div>
                <a href="records.php" class="btn btn-outline btn-sm">View Full Database Table &raquo;</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Email Address</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Role</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentStudents)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 20px;">No students found in database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentStudents as $s): ?>
                                <tr>
                                    <td><b>#<?php echo htmlspecialchars((string)$s["id"]); ?></b></td>
                                    <td><?php echo htmlspecialchars($s["name"]); ?></td>
                                    <td><code><?php echo htmlspecialchars($s["email"]); ?></code></td>
                                    <td><?php echo htmlspecialchars($s["mobile"]); ?></td>
                                    <td><?php echo htmlspecialchars($s["course"]); ?></td>
                                    <td>
                                        <?php if (($s["role"] ?? "student") === "admin"): ?>
                                            <span class="badge badge-warning">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Student</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars(substr($s["created_at"] ?? "", 0, 10)); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                    <li><a href="records.php">Student Directory</a></li>
                    <li><a href="dashboard.php">Student Dashboard</a></li>
                    <li><a href="php/logout.php">Sign Out</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Security &amp; Compliance</h4>
                <p style="font-size: var(--font-xs); color: #94a3b8;">
                    This administration console is restricted to verified university officers. All sessions are monitored with 15-minute inactivity timeouts and token hashing.
                </p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University. Practical 10 - Role-Based Access Control (RBAC).</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="session_timer.js"></script>
</body>
</html>
