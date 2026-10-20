<?php
// ========================================================
// Practical 11: Student Management Module (Admin Only)
// File: admin/students.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Admin Authentication Enforcement, Search, Filter,
//           Server-Side Pagination, and CRUD Operations.
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";

// Strict Admin-Only Authorization Enforcement
check_auth("admin");

$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");
$adminEmail = htmlspecialchars($_SESSION["user_email"] ?? "admin@backbenchers.edu.in");
$adminRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "admin"));

// Read feedback messages from redirect
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
    $query = [
        "page" => $p
    ];
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
    <title>Student Management (Practical 11) - The Backbenchers University</title>
    <link rel="stylesheet" href="../style.css">
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
                    <li><a href="students.php" class="active">Student Management (P11)</a></li>
                    <li><a href="events.php">Event Management (P12)</a></li>
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

        <!-- Notification Alerts -->
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
                        <span class="admin-badge">Practical 11</span>
                        <span style="font-size: var(--font-xs); opacity: 0.85;">MySQLi Prepared Statements &bull; CRUD &bull; Search &bull; Filter &bull; Pagination</span>
                    </div>
                    <h2 style="font-size: var(--font-2xl); margin-bottom: 6px;">Student Management System</h2>
                    <p style="margin: 0; opacity: 0.9; font-size: var(--font-sm);">
                        Manage institutional student records directly in the <code>studenthub.students</code> table.
                    </p>
                </div>
                <div class="d-flex gap-sm flex-wrap">
                    <a href="student_add.php" class="btn btn-accent btn-md">➕ Add New Student</a>
                    <a href="../admin_dashboard.php" class="btn btn-outline btn-md" style="color: white; border-color: rgba(255,255,255,0.4);">&laquo; Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar Card -->
        <div class="filter-card">
            <form method="GET" action="students.php" class="filter-grid">
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
                    Matching Records: <strong><?php echo $totalRecords; ?></strong> 
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
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Academic Year</th>
                        <th>Role</th>
                        <th style="text-align: center; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state-icon">🔍</div>
                                    <h3>No students found.</h3>
                                    <p style="margin: 6px 0 14px 0; font-size: var(--font-sm);">
                                        No student records matched your search keyword or selected filter criteria.
                                    </p>
                                    <a href="students.php" class="btn btn-outline btn-sm">Clear Search &amp; Show All</a>
                                    <a href="student_add.php" class="btn btn-primary btn-sm">+ Add New Student</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$st["id"]; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($st["name"]); ?></strong>
                                    <?php if (!empty($st["gender"]) && $st["gender"] !== "Unspecified"): ?>
                                        <span style="font-size: 11px; color: var(--text-muted); display: block;">(<?php echo htmlspecialchars($st["gender"]); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($st["email"]); ?>" style="color: var(--secondary); text-decoration: none;">
                                        <?php echo htmlspecialchars($st["email"]); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($st["mobile"]); ?></td>
                                <td><?php echo htmlspecialchars($st["course"]); ?></td>
                                <td>
                                    <span class="badge badge-info" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($st["year"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (strtolower($st["role"]) === "admin"): ?>
                                        <span class="badge badge-warning" style="font-size: 11px;">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-success" style="font-size: 11px;">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-cell" style="justify-content: center;">
                                        <!-- Edit Action -->
                                        <a href="student_edit.php?id=<?php echo (int)$st["id"]; ?>" class="btn btn-outline btn-sm" style="padding: 3px 8px;" title="Edit Student Information">
                                            ✏️ Edit
                                        </a>

                                        <!-- Delete Action with POST and Confirmation Dialog -->
                                        <form method="POST" action="student_delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete student: <?php echo htmlspecialchars(addslashes($st['name'])); ?> (ID #<?php echo (int)$st['id']; ?>)?');">
                                            <input type="hidden" name="student_id" value="<?php echo (int)$st["id"]; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 3px 8px; background-color: #ef4444; color: white;" title="Delete Record">
                                                🗑️ Delete
                                            </button>
                                        </form>
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
                    <li><a href="students.php">Student Management (Practical 11)</a></li>
                    <li><a href="events.php">Event Management (Practical 12)</a></li>
                    <li><a href="../records.php">Student Records Directory</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Practical Syllabus</h4>
                <p style="font-size: var(--font-xs); color: var(--text-muted);">
                    Practical 11: MySQLi Prepared Statements, CRUD, Server-Side Validation, Search, Filter &amp; Pagination.
                </p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Practical 11 Implementation.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script src="../session_timer.js"></script>
</body>
</html>
