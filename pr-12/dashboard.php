<?php
// ========================================================
// Practical 10: Authenticated Student Dashboard
// File: dashboard.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Protection: php/auth_check.php (Requires Active Login & Session Timeout)
// ========================================================

require_once __DIR__ . "/php/auth_check.php";

// Verify user is authenticated. If an admin accesses student dashboard, redirect to admin dashboard.
check_auth();

if (isset($_SESSION["user_role"]) && strtolower($_SESSION["user_role"]) === "admin") {
    header("Location: admin_dashboard.php");
    exit;
}

$userName = htmlspecialchars($_SESSION["user_name"] ?? "Student");
$userEmail = htmlspecialchars($_SESSION["user_email"] ?? "");
$userCourse = htmlspecialchars($_SESSION["user_course"] ?? "Information Technology");
$userYear = htmlspecialchars($_SESSION["user_year"] ?? "Second Year (SE)");
$userRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "student"));
$userId = htmlspecialchars((string)($_SESSION["user_id"] ?? ""));

$errorMessage = isset($_GET["error"]) ? htmlspecialchars($_GET["error"]) : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - The Backbenchers University</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .session-timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(59, 130, 246, 0.15);
            color: var(--secondary-light);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: var(--font-xs);
            font-weight: 600;
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
                <span class="badge-tag" style="font-weight: 600;">👤 <?php echo $userName; ?></span>
                <span class="badge-tag">Role: <?php echo $userRole; ?></span>
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="admin_dashboard.php" style="color: var(--accent-light); font-weight: bold;">🏢 Admin Portal</a></li>
                    <?php endif; ?>
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
                        </ul>
                    </li>
                    <li><a href="contact.html">Contact</a></li>
                    <li><a href="php/logout.php" style="color: #ef4444; font-weight: 600;">Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl);">

        <!-- Error / Access Denied Notification Banner -->
        <?php if (!empty($errorMessage)): ?>
            <div class="card" style="background-color: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: var(--space-md); margin-bottom: var(--space-md);">
                <div class="d-flex align-center gap-sm">
                    <span style="font-size: 20px;">⚠️</span>
                    <div>
                        <strong>Notice:</strong> <?php echo $errorMessage; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Student Profile Hero Card -->
        <div class="card">
            <div class="profile-banner">
                <div class="profile-avatar-wrap">
                    <img src="images/student.svg" alt="Student Profile Photo">
                </div>
                <div class="profile-info" style="flex: 1;">
                    <div class="d-flex flex-wrap align-center gap-sm" style="margin-bottom: 4px;">
                        <h2><?php echo $userName; ?></h2>
                        <span class="badge badge-success">Active Session</span>
                        <span class="badge badge-info"><?php echo $userRole; ?> Account</span>
                        <?php if (isset($_COOKIE["bbu_remember_token"])): ?>
                            <span class="badge badge-warning" title="Remember-Me Persistent Token Active">Persistent Token Saved</span>
                        <?php endif; ?>
                    </div>
                    <p><b>Course:</b> <?php echo $userCourse; ?> &nbsp;|&nbsp; <b>Academic Year:</b> <?php echo $userYear; ?></p>
                    <p style="font-size: var(--font-xs); color: var(--text-muted); margin: 0;">
                        <b>University Email:</b> <?php echo $userEmail; ?> &nbsp;|&nbsp; 
                        <b>Authenticated Via:</b> PHP Session (bcrypt verified)
                    </p>
                </div>
                <div class="d-flex flex-col gap-xs" style="min-width: 140px;">
                    <a href="profile.html" class="btn btn-outline btn-sm">View Full Profile</a>
                    <a href="php/logout.php" class="btn btn-sm" style="background-color: #ef4444; color: white;">Sign Out</a>
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
                            Session Security &amp; Inactivity Countdown
                        </h4>
                        <p style="margin: 0; font-size: var(--font-xs); color: var(--text-muted);">
                            Authenticated via PHP Session &bull; User ID: <strong>#<?= $userId ?></strong> &bull; Auto-logout after 15 minutes of inactivity.
                        </p>
                    </div>
                </div>
                <div class="d-flex align-center gap-sm">
                    <span style="font-size: var(--font-xs); font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Time Left:</span>
                    <span class="session-timer-clock" style="font-size: 1.35rem; font-weight: 800; font-family: monospace; background: var(--bg-card); padding: 4px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: var(--secondary);">
                        14:59
                    </span>
                    <a href="php/logout.php" class="btn btn-sm" style="background: #ef4444; color: white; padding: 4px 12px;">Logout</a>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards Grid -->
        <div class="dash-grid">
            <!-- Attendance Card -->
            <div class="metric-card success">
                <div class="metric-icon">
                    <img src="images/attendance.svg" alt="Attendance">
                </div>
                <div class="metric-details">
                    <h4>Overall Attendance</h4>
                    <div class="metric-value">84%</div>
                    <div class="metric-sub" style="color: var(--success); font-weight: 600;">✓ Safe (&gt; 75% Criteria)</div>
                </div>
            </div>

            <!-- CGPA Card -->
            <div class="metric-card accent">
                <div class="metric-icon">
                    <img src="images/profile.svg" alt="CGPA">
                </div>
                <div class="metric-details">
                    <h4>Cumulative CGPA</h4>
                    <div class="metric-value">8.65</div>
                    <div class="metric-sub">Academic Standing: First Class</div>
                </div>
            </div>

            <!-- Fees Card -->
            <div class="metric-card danger">
                <div class="metric-icon">
                    <img src="images/fees.svg" alt="Fees">
                </div>
                <div class="metric-details">
                    <h4>Pending Fees</h4>
                    <div class="metric-value pending-fee-val" style="color: var(--danger);">₹12,500</div>
                    <div class="metric-sub">
                        <button class="btn btn-sm btn-accent" id="pay-fee-btn" style="padding: 3px 8px; margin-top: 2px;">Pay Online</button>
                    </div>
                </div>
            </div>

            <!-- Assignments Card -->
            <div class="metric-card">
                <div class="metric-icon">
                    <img src="images/assignment.svg" alt="Assignments">
                </div>
                <div class="metric-details">
                    <h4>Pending Tasks</h4>
                    <div class="metric-value">2 Due</div>
                    <div class="metric-sub"><a href="assignments.html">Submit by Friday &raquo;</a></div>
                </div>
            </div>
        </div>

        <!-- 2-Column Dashboard Main Split -->
        <div class="dash-main-split">

            <!-- Left Main Column -->
            <div class="d-flex flex-col gap-md">

                <!-- Weekly Schedule Preview -->
                <div class="card">
                    <div class="card-header">
                        <h3>📅 Today's Lecture Schedule</h3>
                        <a href="timetable.html" class="btn btn-outline btn-sm">Full Timetable &raquo;</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Time Slot</th>
                                    <th>Subject Code &amp; Title</th>
                                    <th>Faculty</th>
                                    <th>Room / Lab</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>09:00 - 10:00 AM</td>
                                    <td><b>CS401:</b> Database Management Systems</td>
                                    <td>Dr. Amit Verma</td>
                                    <td>Classroom 304</td>
                                    <td><span class="badge badge-success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td>10:00 - 11:00 AM</td>
                                    <td><b>CS402:</b> Operating Systems</td>
                                    <td>Prof. Rajesh Joshi</td>
                                    <td>Classroom 304</td>
                                    <td><span class="badge badge-success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td>11:15 - 01:15 PM</td>
                                    <td><b>CS403:</b> DBMS Practical Lab (Batch B1)</td>
                                    <td>Dr. Amit Verma</td>
                                    <td>Lab 02 (Cloud Hub)</td>
                                    <td><span class="badge badge-info">Ongoing</span></td>
                                </tr>
                                <tr>
                                    <td>02:00 - 03:00 PM</td>
                                    <td><b>CS404:</b> Software Engineering</td>
                                    <td>Dr. Sneha Kulkarni</td>
                                    <td>Seminar Hall 1</td>
                                    <td><span class="badge badge-warning">Upcoming</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Subject-Wise Attendance Breakdown -->
                <div class="card">
                    <div class="card-header">
                        <h3>📊 Subject-wise Attendance Performance</h3>
                        <a href="attendance.html" class="btn btn-outline btn-sm">Detailed Log &raquo;</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Conducted</th>
                                    <th>Attended</th>
                                    <th>Percentage</th>
                                    <th>Eligibility</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Data Structures &amp; Algorithms</td>
                                    <td>36</td>
                                    <td>32</td>
                                    <td><b>88.8%</b></td>
                                    <td><span class="badge badge-success">Eligible</span></td>
                                </tr>
                                <tr>
                                    <td>Database Management Systems</td>
                                    <td>34</td>
                                    <td>28</td>
                                    <td><b>82.3%</b></td>
                                    <td><span class="badge badge-success">Eligible</span></td>
                                </tr>
                                <tr>
                                    <td>Operating Systems</td>
                                    <td>38</td>
                                    <td>30</td>
                                    <td><b>78.9%</b></td>
                                    <td><span class="badge badge-warning">Borderline</span></td>
                                </tr>
                                <tr>
                                    <td>Software Engineering</td>
                                    <td>30</td>
                                    <td>26</td>
                                    <td><b>86.6%</b></td>
                                    <td><span class="badge badge-success">Eligible</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right Sidebar Column -->
            <div class="d-flex flex-col gap-md">

                <!-- Quick Services Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ Quick Services</h3>
                    </div>
                    <div class="d-flex flex-col gap-xs">
                        <a href="events.php" class="btn btn-outline" style="justify-content: flex-start;">
                            🎪 Register for Campus Events
                        </a>
                        <a href="library.html" class="btn btn-outline" style="justify-content: flex-start;">
                            📚 Search Library Catalog
                        </a>
                        <a href="academics.html" class="btn btn-outline" style="justify-content: flex-start;">
                            🎓 View Academic Rank &amp; Grades
                        </a>
                        <a href="php/logout.php" class="btn btn-outline" style="justify-content: flex-start; color: #ef4444;">
                            🔒 Terminate Session (Logout)
                        </a>
                    </div>
                </div>

                <!-- Upcoming Deadlines Card -->
                <div class="card">
                    <div class="card-header">
                        <h3>⏰ Upcoming Deadlines</h3>
                    </div>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: var(--space-sm); font-size: var(--font-xs);">
                        <li style="border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                            <span class="badge badge-danger">10 Nov</span>
                            <b>DBMS Practical Assignment:</b> SQL Queries &amp; Relational Algebra
                        </li>
                        <li style="border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                            <span class="badge badge-warning">15 Nov</span>
                            <b>Web Tech Project:</b> Session Authentication &amp; RBAC
                        </li>
                        <li style="border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                            <span class="badge badge-info">20 Nov</span>
                            <b>Hackathon 2026:</b> Registration Closes
                        </li>
                    </ul>
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
                <p style="margin-top: var(--space-xs); font-size: var(--font-xs); color: var(--accent-light);">NAAC 'A++' Grade | Approved by AICTE, New Delhi</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About Institution</a></li>
                    <li><a href="events.html">Campus Events</a></li>
                    <li><a href="placements.html">Placement Records</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Student Portal</h4>
                <ul class="footer-links">
                    <li><a href="dashboard.php">Student Dashboard</a></li>
                    <li><a href="attendance.html">Attendance Tracker</a></li>
                    <li><a href="academics.html">Academic Records</a></li>
                    <li><a href="php/logout.php">Sign Out</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Campus Address</h4>
                <p>Knowledge City, Tech Valley Campus<br>Pune - 411048, Maharashtra, India</p>
                <p style="margin-top: 6px;"><b>Email:</b> info@backbenchers.edu.in<br><b>Helpline:</b> +91 20 2765 4321</p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University. Practical 10 - Secure Session Management &amp; Role-Based Access.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="session_timer.js"></script>
</body>
</html>
