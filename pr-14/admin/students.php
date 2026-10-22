<?php
// ========================================================
// Practical 11 & Practical 13: Student Management Module (Admin Only)
// File: admin/students.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Server-Side Pagination, Prepared Queries, Search & Filters (P11)
//           Integrated Asynchronous Fetch API CRUD (P13) via api/students.php
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";
require_once dirname(__DIR__) . "/php/audit_helper.php";

// Strict Admin-Only Authorization Enforcement
check_auth("admin");

$adminId = (int)($_SESSION["user_id"] ?? 0);
$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");
$adminEmail = htmlspecialchars($_SESSION["user_email"] ?? "admin@backbenchers.edu.in");
$adminRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "admin"));

// Read feedback messages from redirect (fallback)
$msgSuccess = $_GET["msg"] ?? "";
$msgError = $_GET["error"] ?? "";

// Read search and filter criteria from GET query parameters
$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");
$yearFilter = trim($_GET["year"] ?? "");

// Pagination variables
$page = max(1, (int)($_GET["page"] ?? 1));
$perPage = 8; // Display 8 students per page for clean exam demonstration

// Build dynamic WHERE clause using MySQLi Prepared Statements
$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($search !== "") {
    $whereClauses[] = "(name LIKE ? OR email LIKE ? OR mobile LIKE ? OR course LIKE ?)";
    $searchWildcard = "%" . $search . "%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
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

$whereSql = implode(" AND ", $whereClauses);

// 1. Calculate Total Records matching filter criteria
$countSql = "SELECT COUNT(*) AS total FROM students WHERE " . $whereSql;
$countStmt = $conn->prepare($countSql);

if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = (int)($countResult->fetch_assoc()["total"] ?? 0);
$countStmt->close();

$totalPages = max(1, ceil($totalRecords / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// 2. Fetch paginated records using prepared statements
$dataSql = "SELECT id, name, email, mobile, course, year, role, gender, created_at 
            FROM students 
            WHERE " . $whereSql . " 
            ORDER BY id DESC 
            LIMIT ? OFFSET ?";

$dataStmt = $conn->prepare($dataSql);
$dataTypes = $types . "ii";
$dataParams = array_merge($params, [$perPage, $offset]);

$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$dataResult = $dataStmt->get_result();

$students = [];
while ($row = $dataResult->fetch_assoc()) {
    $students[] = $row;
}
$dataStmt->close();

// Fetch distinct courses and years for filter dropdowns
$availableCourses = [];
$cRes = $conn->query("SELECT DISTINCT course FROM students WHERE course != '' ORDER BY course ASC");
if ($cRes) {
    while ($cRow = $cRes->fetch_assoc()) {
        $availableCourses[] = $cRow["course"];
    }
}

$availableYears = [];
$yRes = $conn->query("SELECT DISTINCT year FROM students WHERE year != '' ORDER BY year ASC");
if ($yRes) {
    while ($yRow = $yRes->fetch_assoc()) {
        $availableYears[] = $yRow["year"];
    }
}

// Helper function to build pagination links while preserving filters
function makePageUrl($p, $search, $course, $year) {
    $query = ["page" => $p];
    if ($search !== "") $query["search"] = $search;
    if ($course !== "" && $course !== "all") $query["course"] = $course;
    if ($year !== "" && $year !== "all") $query["year"] = $year;
    return "students.php?" . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - The Backbenchers University</title>
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
        .filter-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            margin-bottom: var(--space-lg);
            box-shadow: var(--shadow-sm);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1fr auto;
            gap: var(--space-md);
            align-items: flex-end;
        }
        @media (max-width: 900px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
        .empty-state {
            text-align: center;
            padding: var(--space-2xl) var(--space-md);
            color: var(--text-muted);
        }
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: var(--space-sm);
        }
        .action-cell {
            white-space: nowrap;
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .table-responsive {
            overflow-x: auto;
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-md);
            margin-top: var(--space-lg);
            padding-top: var(--space-md);
            border-top: 1px solid var(--border-color);
        }
        .pagination-buttons {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        .page-link-btn {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-surface);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: var(--font-xs);
            font-weight: 600;
            transition: var(--transition-fast);
        }
        .page-link-btn:hover:not(.disabled) {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .page-link-btn.active {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .page-link-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* Modal Styles for Asynchronous Fetch CRUD */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: var(--space-md);
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            max-width: 580px;
            width: 100%;
            box-shadow: var(--shadow-xl);
            animation: modalFadeIn 0.2s ease-out;
            overflow: hidden;
        }
        @keyframes modalFadeIn {
            from { transform: translateY(-15px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: var(--space-md) var(--space-lg);
            background: var(--bg-alt);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: var(--space-lg);
            max-height: 75vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: var(--space-md) var(--space-lg);
            background: var(--bg-alt);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: var(--space-sm);
        }
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
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
                    <p>Accredited NAAC A++ &bull; Office of Academic Administration</p>
                </div>
            </a>
            <div class="header-quick-info">
                <span class="badge-tag">👤 <?php echo $adminName; ?></span>
                <span class="admin-badge">🏢 <?php echo $adminRole; ?></span>
                <span class="session-timer-badge" id="session-timer-badge">
                    ⏱️ Session: <span id="session-timer-clock" data-remaining="<?php echo $session_remaining_seconds ?? 900; ?>">14:59</span>
                </span>
                <button class="theme-toggle-btn" aria-label="Toggle Dark/Light theme">🌙 <span>Dark</span></button>
                <a href="../php/logout.php" class="logout-link-btn">
                    🔒 Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label">
                <span>Admin Menu</span>
            </div>
            <button class="nav-toggle-btn" id="nav-toggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-menu" id="nav-menu">
                <ul class="nav-links">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="../admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="students.php" class="active">Student Management</a></li>
                    <li><a href="events.php">Event Management</a></li>
                    <li><a href="audit_logs.php">Audit Logs</a></li>
                    <li><a href="../records.php">Directory</a></li>
                    <li><a href="../events.php">Public Events</a></li>
                    <li><a href="../dashboard.php">Student View</a></li>
                    <li><a href="../php/logout.php" style="color: #ef4444; font-weight: 600;">Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl);">

        <!-- Live Asynchronous Notification Banner -->
        <div id="live-alert" style="display: none; padding: var(--space-md); border-radius: var(--radius-sm); margin-bottom: var(--space-md);">
            <div class="d-flex align-center gap-sm">
                <span id="alert-icon" style="font-size: 20px;"></span>
                <div id="alert-message" style="font-weight: 500;"></div>
            </div>
        </div>

        <!-- Notification Alerts (Fallback from GET query params) -->
        <?php if (!empty($msgSuccess)): ?>
            <div class="card" style="background-color: var(--success-bg); border-left: 4px solid var(--success); color: var(--success); padding: var(--space-md); margin-bottom: var(--space-md);">
                <div class="d-flex align-center gap-sm">
                    <span style="font-size: 20px;">✅</span>
                    <div><strong>Success:</strong> <?php echo htmlspecialchars($msgSuccess); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($msgError)): ?>
            <div class="card" style="background-color: var(--danger-bg); border-left: 4px solid var(--danger); color: var(--danger); padding: var(--space-md); margin-bottom: var(--space-md);">
                <div class="d-flex align-center gap-sm">
                    <span style="font-size: 20px;">⚠️</span>
                    <div><strong>Error:</strong> <?php echo htmlspecialchars($msgError); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hero Banner -->
        <div class="admin-banner">
            <div class="d-flex flex-wrap justify-between align-center gap-md">
                <div>
                    <div class="d-flex align-center gap-sm" style="margin-bottom: 8px;">
                        <span class="admin-badge">Student Administration</span>
                        <span style="font-size: var(--font-xs); opacity: 0.85;">Institutional Directory &bull; Real-Time CRUD Operations &bull; Database: `students`</span>
                    </div>
                    <h2 style="font-size: var(--font-2xl); margin-bottom: 6px;">Student Management System</h2>
                    <p style="margin: 0; opacity: 0.9; font-size: var(--font-sm);">
                        Manage institutional student records directly in <code>studenthub.students</code> with real-time asynchronous CRUD operations.
                    </p>
                </div>
                <div class="d-flex gap-sm flex-wrap">
                    <button type="button" class="btn btn-accent btn-md" onclick="openAddModal()" style="background-color: #f59e0b; color: #0f172a; font-weight: 600; border: none; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);">
                        ➕ Add Student
                    </button>
                    <a href="student_add.php" class="btn btn-outline btn-md" style="color: white; border-color: rgba(255,255,255,0.35); background: rgba(255,255,255,0.08); backdrop-filter: blur(4px);" title="Standard form add">
                        Form Add
                    </a>
                    <a href="../admin_dashboard.php" class="btn btn-outline btn-md" style="color: white; border-color: rgba(255,255,255,0.35); background: rgba(255,255,255,0.08); backdrop-filter: blur(4px);">&laquo; Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar Card -->
        <div class="filter-card">
            <form method="GET" action="students.php" class="filter-grid" id="filter-form">
                <!-- Search Box -->
                <div>
                    <label for="search-input" style="font-size: var(--font-xs); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">🔍 Search Students</label>
                    <input type="text" id="search-input" name="search" class="form-control" placeholder="Search by name, email, or mobile..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <!-- Course Filter Dropdown -->
                <div>
                    <label for="course-filter" style="font-size: var(--font-xs); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">🎓 Filter by Course</label>
                    <select id="course-filter" name="course" class="form-control">
                        <option value="all">All Courses</option>
                        <?php foreach ($availableCourses as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php if ($courseFilter === $c) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($c); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Academic Year Filter Dropdown -->
                <div>
                    <label for="year-filter" style="font-size: var(--font-xs); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">📅 Filter by Year</label>
                    <select id="year-filter" name="year" class="form-control">
                        <option value="all">All Academic Years</option>
                        <?php foreach ($availableYears as $y): ?>
                            <option value="<?php echo htmlspecialchars($y); ?>" <?php if ($yearFilter === $y) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($y); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Action Buttons -->
                <div class="d-flex gap-xs">
                    <button type="submit" class="btn btn-primary" style="height: 38px;">Apply Filters</button>
                    <?php if ($search !== "" || ($courseFilter !== "" && $courseFilter !== "all") || ($yearFilter !== "" && $yearFilter !== "all")): ?>
                        <a href="students.php" class="btn btn-outline" style="height: 38px; display: inline-flex; align-items: center;" title="Reset filters">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div style="margin-top: var(--space-sm); font-size: var(--font-xs); color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    Matching Records: <strong id="total-count-display"><?php echo $totalRecords; ?></strong> 
                    <?php if ($search !== ""): ?>
                        &bull; Keywords: <em>"<?php echo htmlspecialchars($search); ?>"</em>
                    <?php endif; ?>
                </div>
                <div>
                    Showing Page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong>
                </div>
            </div>
        </div>

        <!-- Student Records Table -->
        <div class="table-responsive">
            <table id="students-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Academic Year</th>
                        <th>Role</th>
                        <th style="text-align: center; width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="students-tbody">
                    <?php if (empty($students)): ?>
                        <tr id="empty-row">
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">🔍</div>
                                    <h3>No students found.</h3>
                                    <p style="margin: 6px 0 14px 0; font-size: var(--font-sm);">
                                        No student records matched your search keyword or selected filter criteria.
                                    </p>
                                    <a href="students.php" class="btn btn-outline btn-sm">Clear Search &amp; Show All</a>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openAddModal()">+ Add New Student (Fetch)</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $st): ?>
                            <tr id="row-student-<?php echo (int)$st["id"]; ?>">
                                <td><strong>#<?php echo (int)$st["id"]; ?></strong></td>
                                <td>
                                    <strong class="col-name"><?php echo htmlspecialchars($st["name"]); ?></strong>
                                    <?php if (!empty($st["gender"]) && $st["gender"] !== "Unspecified"): ?>
                                        <span class="col-gender" style="font-size: 11px; color: var(--text-muted); display: block;">(<?php echo htmlspecialchars($st["gender"]); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="col-email" href="mailto:<?php echo htmlspecialchars($st["email"]); ?>" style="color: var(--secondary); text-decoration: none;">
                                        <?php echo htmlspecialchars($st["email"]); ?>
                                    </a>
                                </td>
                                <td class="col-mobile"><?php echo htmlspecialchars($st["mobile"]); ?></td>
                                <td class="col-course"><?php echo htmlspecialchars($st["course"]); ?></td>
                                <td>
                                    <span class="badge badge-info col-year" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($st["year"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (strtolower($st["role"]) === "admin"): ?>
                                        <span class="badge badge-warning col-role" style="font-size: 11px;">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-success col-role" style="font-size: 11px;">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-cell" style="justify-content: center;">
                                        <!-- Edit Action (Asynchronous Fetch Modal with fallback to student_edit.php) -->
                                        <button type="button" class="btn btn-outline btn-sm" style="padding: 3px 8px;" onclick="openEditModal(<?php echo (int)$st['id']; ?>)" title="Edit via Fetch API">
                                            ✏️ Edit
                                        </button>

                                        <!-- Delete Action (Asynchronous Fetch DELETE with confirmation) -->
                                        <button type="button" class="btn btn-danger btn-sm" style="padding: 3px 8px; background-color: #ef4444; color: white;" onclick="deleteStudentAsync(<?php echo (int)$st['id']; ?>, '<?php echo htmlspecialchars(addslashes($st['name'])); ?>')" title="Delete via Fetch API">
                                            🗑️ Delete
                                        </button>

                                        <!-- Standard POST fallback form for non-JS/automation testing -->
                                        <noscript>
                                            <form method="POST" action="student_delete.php" style="display: inline;">
                                                <input type="hidden" name="student_id" value="<?php echo (int)$st["id"]; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 3px 8px;">Delete</button>
                                            </form>
                                        </noscript>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div style="font-size: var(--font-xs); color: var(--text-muted);">
                    Showing <strong><?php echo $offset + 1; ?></strong> to <strong><?php echo min($offset + $perPage, $totalRecords); ?></strong> of <strong><?php echo $totalRecords; ?></strong> students
                </div>

                <div class="pagination-buttons">
                    <!-- Previous Page -->
                    <a href="<?php echo makePageUrl(max(1, $page - 1), $search, $courseFilter, $yearFilter); ?>" 
                       class="page-link-btn <?php if ($page <= 1) echo 'disabled'; ?>">
                        &laquo; Previous
                    </a>

                    <!-- Numbered Pages -->
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?php echo makePageUrl($p, $search, $courseFilter, $yearFilter); ?>" 
                           class="page-link-btn <?php if ($p === $page) echo 'active'; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Next Page -->
                    <a href="<?php echo makePageUrl(min($totalPages, $page + 1), $search, $courseFilter, $yearFilter); ?>" 
                       class="page-link-btn <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                        Next &raquo;
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- Asynchronous Add / Edit Student Modal (Practical 13 Fetch API) -->
    <div class="modal-overlay" id="student-modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modal-title" style="margin: 0; font-size: var(--font-lg);">➕ Add New Student (Fetch API)</h3>
                <button type="button" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--text-muted);" onclick="closeStudentModal()">&times;</button>
            </div>
            <form id="student-fetch-form" onsubmit="handleStudentSubmit(event)">
                <input type="hidden" id="modal-student-id" value="">

                <div class="modal-body">
                    <!-- Validation error alert inside modal -->
                    <div id="modal-val-error" style="display: none; background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: var(--space-sm); border-radius: var(--radius-sm); margin-bottom: var(--space-md); font-size: var(--font-xs);"></div>

                    <div class="d-flex flex-col gap-sm">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label for="modal-name">Student Full Name <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="modal-name" class="form-control" placeholder="e.g. Full Name" required minlength="3">
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="modal-email">University Email <span style="color: var(--danger);">*</span></label>
                            <input type="email" id="modal-email" class="form-control" placeholder="rahul.sharma@backbenchers.edu.in" required>
                        </div>

                        <!-- Mobile & Gender -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                            <div class="form-group">
                                <label for="modal-mobile">Mobile Number (10 Digits) <span style="color: var(--danger);">*</span></label>
                                <input type="tel" id="modal-mobile" class="form-control" placeholder="9876543210" pattern="[0-9]{10}" required>
                            </div>
                            <div class="form-group">
                                <label for="modal-gender">Gender</label>
                                <select id="modal-gender" class="form-control">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Course & Year -->
                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: var(--space-md);">
                            <div class="form-group">
                                <label for="modal-course">Course <span style="color: var(--danger);">*</span></label>
                                <select id="modal-course" class="form-control" required>
                                    <option value="">-- Select Course --</option>
                                    <option value="B.Tech Information Technology">B.Tech Information Technology</option>
                                    <option value="B.Tech Computer Engineering">B.Tech Computer Engineering</option>
                                    <option value="B.Tech AI & Data Science">B.Tech AI & Data Science</option>
                                    <option value="B.Tech Electronics & Telecom">B.Tech Electronics & Telecom</option>
                                    <option value="B.Tech Mechanical Engineering">B.Tech Mechanical Engineering</option>
                                    <option value="MCA">MCA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal-year">Academic Year <span style="color: var(--danger);">*</span></label>
                                <select id="modal-year" class="form-control" required>
                                    <option value="">-- Select Year --</option>
                                    <option value="First Year (FE)">First Year (FE)</option>
                                    <option value="Second Year (SE)">Second Year (SE)</option>
                                    <option value="Third Year (TE)">Third Year (TE)</option>
                                    <option value="Final Year (BE)">Final Year (BE)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="form-group">
                            <label for="modal-role">Account Role</label>
                            <select id="modal-role" class="form-control">
                                <option value="student">Student</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="modal-password" id="modal-pwd-label">Password (Min 8 Characters) <span style="color: var(--danger);">*</span></label>
                            <input type="password" id="modal-password" class="form-control" placeholder="••••••••" minlength="8">
                            <small id="modal-pwd-help" style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 4px;">
                                Required when creating a student. Securely hashed using bcrypt.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeStudentModal()">Cancel</button>
                    <button type="submit" id="modal-submit-btn" class="btn btn-primary">
                        <span id="modal-btn-text">Save Student</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container footer-grid">
            <div class="footer-col">
                <h4>The Backbenchers University</h4>
                <p>Autonomous Engineering Institution &bull; Accredited NAAC A++.</p>
            </div>
            <div class="footer-col">
                <h4>Admin Modules</h4>
                <ul class="footer-links">
                    <li><a href="../admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="students.php">Student Management</a></li>
                    <li><a href="events.php">Event Management</a></li>
                    <li><a href="audit_logs.php">System Audit Log</a></li>
                    <li><a href="../records.php">Student Records Directory</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Academic Administration</h4>
                <p style="font-size: var(--font-xs); color: var(--text-muted);">
                    Institutional student directory featuring MySQLi prepared queries, role-based security, pagination, and asynchronous CRUD operations.
                </p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Office of Academic Administration.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script src="../session_timer.js"></script>
    <script>
        // ========================================================
        // Integrated JavaScript Fetch API Engine
        // Communicates asynchronously with ../api/students.php
        // Zero full page reloads & seamless DOM manipulation.
        // ========================================================

        const STUDENT_API = "../api/students.php";
        const currentAdminId = <?php echo $adminId; ?>;

        // 1. Show live feedback alerts
        function showLiveAlert(msg, type = "success") {
            const box = document.getElementById("live-alert");
            const icon = document.getElementById("alert-icon");
            const txt = document.getElementById("alert-message");

            box.style.display = "block";
            txt.textContent = msg;

            if (type === "success") {
                box.style.backgroundColor = "var(--success-bg)";
                box.style.borderLeft = "4px solid var(--success)";
                box.style.color = "var(--success)";
                icon.textContent = "✅";
            } else {
                box.style.backgroundColor = "var(--danger-bg)";
                box.style.borderLeft = "4px solid var(--danger)";
                box.style.color = "var(--danger)";
                icon.textContent = "⚠️";
            }

            box.scrollIntoView({ behavior: "smooth", block: "nearest" });
            setTimeout(() => {
                box.style.display = "none";
            }, 5000);
        }

        // 2. Open Add Modal
        function openAddModal() {
            document.getElementById("modal-title").textContent = "➕ Add New Student";
            document.getElementById("modal-student-id").value = "";
            document.getElementById("modal-name").value = "";
            document.getElementById("modal-email").value = "";
            document.getElementById("modal-mobile").value = "";
            document.getElementById("modal-gender").value = "Male";
            document.getElementById("modal-course").value = "";
            document.getElementById("modal-year").value = "";
            document.getElementById("modal-role").value = "student";
            document.getElementById("modal-password").value = "";
            document.getElementById("modal-password").required = true;
            document.getElementById("modal-pwd-label").innerHTML = 'Password (Min 8 Characters) <span style="color:var(--danger);">*</span>';
            document.getElementById("modal-pwd-help").textContent = "Required when creating a new student account.";
            document.getElementById("modal-btn-text").textContent = "Create Student Record";
            document.getElementById("modal-val-error").style.display = "none";
            document.getElementById("student-modal-overlay").classList.add("active");
        }

        // 3. Open Edit Modal (Loads data via Fetch API)
        async function openEditModal(id) {
            try {
                const res = await fetch(`${STUDENT_API}?id=${id}`);
                const data = await res.json();

                if (!res.ok || !data.success) {
                    showLiveAlert(data.message || "Failed to load student record.", "error");
                    return;
                }

                const s = data.data;
                document.getElementById("modal-title").textContent = `✏️ Edit Student #${s.id}`;
                document.getElementById("modal-student-id").value = s.id;
                document.getElementById("modal-name").value = s.name;
                document.getElementById("modal-email").value = s.email;
                document.getElementById("modal-mobile").value = s.mobile;
                document.getElementById("modal-gender").value = s.gender || "Other";
                document.getElementById("modal-course").value = s.course;
                document.getElementById("modal-year").value = s.year;
                document.getElementById("modal-role").value = s.role;
                document.getElementById("modal-password").value = "";
                document.getElementById("modal-password").required = false;
                document.getElementById("modal-pwd-label").innerHTML = 'New Password (Optional)';
                document.getElementById("modal-pwd-help").textContent = "Leave blank to preserve existing bcrypt password.";
                document.getElementById("modal-btn-text").textContent = "Update Student Record";
                document.getElementById("modal-val-error").style.display = "none";
                document.getElementById("student-modal-overlay").classList.add("active");
            } catch (err) {
                showLiveAlert("Network Error: Could not load student data.", "error");
            }
        }

        function closeStudentModal() {
            document.getElementById("student-modal-overlay").classList.remove("active");
        }

        // 4. Submit Add / Edit Form via Fetch API
        async function handleStudentSubmit(e) {
            e.preventDefault();
            const id = document.getElementById("modal-student-id").value;
            const isEdit = Boolean(id);

            const payload = {
                name: document.getElementById("modal-name").value.trim(),
                email: document.getElementById("modal-email").value.trim(),
                mobile: document.getElementById("modal-mobile").value.trim(),
                gender: document.getElementById("modal-gender").value,
                course: document.getElementById("modal-course").value,
                year: document.getElementById("modal-year").value,
                role: document.getElementById("modal-role").value
            };

            const pwd = document.getElementById("modal-password").value;
            if (pwd) payload.password = pwd;
            if (isEdit) payload.id = parseInt(id, 10);

            const submitBtn = document.getElementById("modal-submit-btn");
            const btnText = document.getElementById("modal-btn-text");
            const originalText = btnText.textContent;
            submitBtn.disabled = true;
            btnText.innerHTML = `<span class="spinner"></span> Saving...`;

            const method = isEdit ? "PUT" : "POST";

            try {
                const response = await fetch(STUDENT_API, {
                    method: method,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const data = await response.json().catch(() => ({ success: false, message: "Invalid JSON response" }));

                if (!response.ok || !data.success) {
                    const errBox = document.getElementById("modal-val-error");
                    errBox.style.display = "block";
                    errBox.innerHTML = `<strong>⚠️ Validation Failed:</strong> ${escapeHtml(data.message || "Operation failed.")}`;
                    return;
                }

                closeStudentModal();
                showLiveAlert(data.message, "success");

                // Update DOM dynamically without full-page reload
                if (isEdit) {
                    const row = document.getElementById(`row-student-${id}`);
                    if (row) {
                        row.querySelector(".col-name").textContent = payload.name;
                        const emailLink = row.querySelector(".col-email");
                        if (emailLink) {
                            emailLink.textContent = payload.email;
                            emailLink.href = `mailto:${payload.email}`;
                        }
                        const mobCol = row.querySelector(".col-mobile");
                        if (mobCol) mobCol.textContent = payload.mobile;
                        const crsCol = row.querySelector(".col-course");
                        if (crsCol) crsCol.textContent = payload.course;
                        const yrCol = row.querySelector(".col-year");
                        if (yrCol) yrCol.textContent = payload.year;
                    }
                } else {
                    // Prepend newly added student to table
                    const newStudent = data.data;
                    const tbody = document.getElementById("students-tbody");
                    const emptyRow = document.getElementById("empty-row");
                    if (emptyRow) emptyRow.remove();

                    const tr = document.createElement("tr");
                    tr.id = `row-student-${newStudent.id}`;
                    tr.style.backgroundColor = "rgba(16, 185, 129, 0.08)";
                    tr.innerHTML = `
                        <td><strong>#${newStudent.id}</strong></td>
                        <td>
                            <strong class="col-name">${escapeHtml(newStudent.name)}</strong>
                            ${newStudent.gender && newStudent.gender !== "Other" ? `<span class="col-gender" style="font-size:11px; color:var(--text-muted); display:block;">(${escapeHtml(newStudent.gender)})</span>` : ""}
                        </td>
                        <td>
                            <a class="col-email" href="mailto:${escapeHtml(newStudent.email)}" style="color:var(--secondary); text-decoration:none;">
                                ${escapeHtml(newStudent.email)}
                            </a>
                        </td>
                        <td class="col-mobile">${escapeHtml(newStudent.mobile)}</td>
                        <td class="col-course">${escapeHtml(newStudent.course)}</td>
                        <td><span class="badge badge-info col-year" style="font-size:11px;">${escapeHtml(newStudent.year)}</span></td>
                        <td>
                            ${newStudent.role === "admin" 
                                ? `<span class="badge badge-warning col-role" style="font-size:11px;">Admin</span>`
                                : `<span class="badge badge-success col-role" style="font-size:11px;">Student</span>`}
                        </td>
                        <td>
                            <div class="action-cell" style="justify-content: center;">
                                <button type="button" class="btn btn-outline btn-sm" style="padding: 3px 8px;" onclick="openEditModal(${newStudent.id})">✏️ Edit</button>
                                <button type="button" class="btn btn-danger btn-sm" style="padding: 3px 8px; background-color:#ef4444; color:white;" onclick="deleteStudentAsync(${newStudent.id}, '${escapeJs(newStudent.name)}')">🗑️ Delete</button>
                            </div>
                        </td>
                    `;
                    tbody.insertBefore(tr, tbody.firstChild);

                    const countDisp = document.getElementById("total-count-display");
                    if (countDisp) countDisp.textContent = parseInt(countDisp.textContent || "0", 10) + 1;
                }
            } catch (err) {
                const errBox = document.getElementById("modal-val-error");
                errBox.style.display = "block";
                errBox.textContent = "Network Error: Could not connect to API server.";
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = originalText;
            }
        }

        // 5. Delete Student via Fetch API (DELETE method, zero page reload)
        async function deleteStudentAsync(id, name) {
            if (id === currentAdminId) {
                showLiveAlert("Action Denied: You cannot delete your own active administrator account!", "error");
                return;
            }

            if (!confirm(`Are you sure you want to delete student: ${name} (ID #${id})?\n\nThis action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(`${STUDENT_API}?id=${id}`, {
                    method: "DELETE"
                });

                const data = await response.json().catch(() => ({ success: false, message: "Invalid JSON response" }));

                if (!response.ok || !data.success) {
                    showLiveAlert(data.message || "Failed to delete student.", "error");
                    return;
                }

                // Smooth DOM removal without page reload
                const row = document.getElementById(`row-student-${id}`);
                if (row) {
                    row.style.transition = "all 0.3s ease";
                    row.style.opacity = "0";
                    row.style.transform = "translateX(-20px)";
                    setTimeout(() => {
                        row.remove();
                        const countDisp = document.getElementById("total-count-display");
                        if (countDisp) {
                            const cur = parseInt(countDisp.textContent || "0", 10);
                            countDisp.textContent = Math.max(0, cur - 1);
                        }
                    }, 300);
                }

                showLiveAlert(data.message, "success");
            } catch (err) {
                showLiveAlert("Network Error: Could not delete student record.", "error");
            }
        }

        function escapeHtml(str) {
            if (!str) return "";
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function escapeJs(str) {
            if (!str) return "";
            return String(str).replace(/'/g, "\\'");
        }
    </script>
</body>
</html>
