<?php
// ========================================================
// Practical 14: Administrative Audit Log Viewer (Admin Only)
// File: admin/audit_logs.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Role-Based Access Enforcement, Action Filtering,
//           MySQL Prepared Queries, Empty State, & Design Tokens.
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";

// 1. Strict Administrator Authorization Check
check_auth("admin");

$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");
$adminEmail = htmlspecialchars($_SESSION["user_email"] ?? "admin@backbenchers.edu.in");
$adminRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "admin"));

// 2. Action Filter
$filterAction = trim($_GET["action"] ?? "all");

$whereSql = "1=1";
$params = [];
$types = "";

if ($filterAction !== "" && $filterAction !== "all") {
    $whereSql .= " AND a.action = ?";
    $params[] = $filterAction;
    $types .= "s";
}

// 3. Fetch audit log entries
$sql = "SELECT a.id, a.admin_id, COALESCE(s.name, 'Admin Officer') AS admin_name, 
               COALESCE(s.email, 'admin@backbenchers.edu.in') AS admin_email,
               a.action, a.description, a.record_id, a.created_at
        FROM audit_logs a
        LEFT JOIN students s ON a.admin_id = s.id
        WHERE $whereSql
        ORDER BY a.id DESC 
        LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$logs = [];
while ($row = $res->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();

// Distinct actions for dropdown
$availableActions = [];
$actRes = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
if ($actRes) {
    while ($ar = $actRes->fetch_assoc()) {
        $availableActions[] = $ar["action"];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Log - The Backbenchers University</title>
    <link rel="stylesheet" href="../style.css">
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
        }
        .action-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .action-add { background: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); }
        .action-update { background: rgba(59, 130, 246, 0.15); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.3); }
        .action-delete { background: rgba(239, 68, 68, 0.15); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.3); }
        .action-default { background: rgba(107, 114, 128, 0.15); color: #4b5563; border: 1px solid rgba(107, 114, 128, 0.3); }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header>
        <div class="container header-inner">
            <a href="../index.html" class="header-brand">
                <img src="../images/logo.svg" alt="University Logo" class="header-logo">
                <div class="header-title">
                    <h1>THE BACKBENCHERS UNIVERSITY</h1>
                    <p>Administrative Control Center &bull; Office of Information Security</p>
                </div>
            </a>
            <div class="header-quick-info">
                <span class="badge-tag">👤 <?php echo $adminName; ?></span>
                <span class="admin-badge">🏢 <?php echo $adminRole; ?></span>
                <span class="session-timer-badge" id="session-timer-badge">
                    ⏱️ Session: <span id="session-timer-clock" data-remaining="<?php echo $session_remaining_seconds ?? 900; ?>">14:59</span>
                </span>
                <button class="theme-toggle-btn" aria-label="Toggle Dark/Light theme">🌙 <span>Dark</span></button>
                <a href="../php/logout.php" class="logout-link-btn">🔒 Logout</a>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label"><span>Admin Menu</span></div>
            <button class="nav-toggle-btn" id="nav-toggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-menu" id="nav-menu">
                <ul class="nav-links">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="../admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="students.php">Student Management</a></li>
                    <li><a href="events.php">Event Management</a></li>
                    <li><a href="audit_logs.php" class="active">Audit Logs</a></li>
                    <li><a href="../records.php">Directory</a></li>
                    <li><a href="../events.php">Events</a></li>
                    <li><a href="../dashboard.php">Student View</a></li>
                    <li><a href="../php/logout.php" style="color: #ef4444; font-weight: 600;">Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl);">

        <!-- Hero Banner -->
        <div class="admin-banner">
            <div class="d-flex flex-wrap justify-between align-center gap-md">
                <div>
                    <div class="d-flex align-center gap-sm" style="margin-bottom: 8px;">
                        <span class="admin-badge">System Security</span>
                        <span style="font-size: var(--font-xs); opacity: 0.85;">Security Audit Trail &bull; Table: `audit_logs`</span>
                    </div>
                    <h2 style="font-size: var(--font-2xl); margin-bottom: 6px;">Administrative Activity Audit Log</h2>
                    <p style="margin: 0; opacity: 0.9; font-size: var(--font-sm);">
                        Immutable ledger of all student creation, modification, deletion, and event lifecycle operations performed by authenticated administrators.
                    </p>
                </div>
                <div class="d-flex gap-sm flex-wrap">
                    <a href="students.php" class="btn btn-accent btn-md" style="background-color: #f59e0b; color: #0f172a; font-weight: 600; border: none; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);">👥 Manage Students</a>
                    <a href="../admin_dashboard.php" class="btn btn-outline btn-md" style="color: white; border-color: rgba(255,255,255,0.35); background: rgba(255,255,255,0.08); backdrop-filter: blur(4px);">&laquo; Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card" style="margin-bottom: var(--space-md); padding: var(--space-md);">
            <form method="GET" action="audit_logs.php" class="d-flex justify-between align-center flex-wrap gap-md">
                <div class="d-flex align-center gap-sm">
                    <label for="action-filter" style="font-weight: 600; font-size: var(--font-xs); color: var(--text-muted);">
                        Filter by Action:
                    </label>
                    <select id="action-filter" name="action" class="form-control" style="width: 220px;" onchange="this.form.submit()">
                        <option value="all">All Actions</option>
                        <?php foreach ($availableActions as $act): ?>
                            <option value="<?php echo htmlspecialchars($act); ?>" <?php if ($filterAction === $act) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($act); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="font-size: var(--font-xs); color: var(--text-muted);">
                    Showing <strong><?php echo count($logs); ?></strong> recorded administrative actions
                    <?php if ($filterAction !== "all"): ?>
                        &bull; Filtered by: <code><?php echo htmlspecialchars($filterAction); ?></code>
                        (<a href="audit_logs.php" style="color: var(--secondary); text-decoration: underline;">Reset</a>)
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Audit Logs Table -->
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">Log ID</th>
                            <th style="width: 140px;">Action</th>
                            <th>Description</th>
                            <th style="width: 100px;">Record ID</th>
                            <th>Admin Officer</th>
                            <th style="width: 160px;">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <div style="font-size: 2.5rem; margin-bottom: 8px;">🛡️</div>
                                    <h4 style="margin: 0 0 6px 0;">No Audit Log Entries Found</h4>
                                    <p style="margin: 0; font-size: var(--font-xs);">Administrative actions will be automatically recorded here as students and events are managed.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <?php
                                    $act = $l["action"];
                                    $pillClass = "action-default";
                                    if (strpos($act, "ADD") !== false) $pillClass = "action-add";
                                    elseif (strpos($act, "UPDATE") !== false) $pillClass = "action-update";
                                    elseif (strpos($act, "DELETE") !== false) $pillClass = "action-delete";
                                ?>
                                <tr>
                                    <td><b>#<?php echo (int)$l["id"]; ?></b></td>
                                    <td>
                                        <span class="action-pill <?php echo $pillClass; ?>">
                                            <?php echo htmlspecialchars($l["action"]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($l["description"]); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($l["record_id"])): ?>
                                            <code>#<?php echo (int)$l["record_id"]; ?></code>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600;"><?php echo htmlspecialchars($l["admin_name"]); ?></span>
                                        <span style="display: block; font-size: 11px; color: var(--text-muted);">Admin ID: #<?php echo (int)$l["admin_id"]; ?></span>
                                    </td>
                                    <td>
                                        <span style="font-size: 12px; color: var(--text-secondary);">
                                            📅 <?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($l["created_at"]))); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container footer-grid">
            <div class="footer-col">
                <h4>The Backbenchers University</h4>
                <p>Office of Institutional Information Security &amp; Records Compliance.</p>
            </div>
            <div class="footer-col">
                <h4>Admin Navigation</h4>
                <ul class="footer-links">
                    <li><a href="../admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="students.php">Student Management</a></li>
                    <li><a href="events.php">Event Management</a></li>
                    <li><a href="audit_logs.php">Security Audit Log</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Compliance Standards</h4>
                <p style="font-size: var(--font-xs); color: var(--text-muted);">
                    System Audit Logging for administrative actions with user attribution, timestamping, and role-based access enforcement.
                </p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Office of Institutional Information Security.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script src="../session_timer.js"></script>
</body>
</html>
