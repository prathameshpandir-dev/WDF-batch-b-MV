<?php
// ========================================================
// Practical 12: Student & Public Campus Events Page
// File: events.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Dynamic MySQLi Event Loading with Poster Display,
//           Role-Based Admin Banner, Search & Sort, & Registration Modal.
// ========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/config/db.php";

$isLoggedIn = !empty($_SESSION["bbu_logged_in"]);
$userRole = strtolower($_SESSION["user_role"] ?? "");
$isAdmin = ($isLoggedIn && $userRole === "admin");
$userName = htmlspecialchars($_SESSION["user_name"] ?? "");
$userEmail = htmlspecialchars($_SESSION["user_email"] ?? "");

// Search and Sorting inputs
$search = trim($_GET["search"] ?? "");
$sort = trim($_GET["sort"] ?? "date-asc");

// Build MySQLi Query with Prepared Statements
$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($search !== "") {
    $whereClauses[] = "(title LIKE ? OR description LIKE ?)";
    $sw = "%" . $search . "%";
    $params[] = $sw;
    $params[] = $sw;
    $types .= "ss";
}

$orderBy = "event_date ASC, id ASC";
if ($sort === "date-desc") {
    $orderBy = "event_date DESC, id DESC";
} elseif ($sort === "title-asc") {
    $orderBy = "title ASC";
} elseif ($sort === "title-desc") {
    $orderBy = "title DESC";
}

$sql = "SELECT id, title, description, event_date, poster FROM events WHERE " . implode(" AND ", $whereClauses) . " ORDER BY " . $orderBy;
$stmt = $conn->prepare($sql);

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$events = [];
while ($row = $res->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Events &amp; Workshops - The Backbenchers University</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .event-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary);
        }
        .event-poster-wrap {
            height: 190px;
            width: 100%;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .event-poster-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .event-card:hover .event-poster-img {
            transform: scale(1.04);
        }
        .event-date-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.88);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.4);
            backdrop-filter: blur(4px);
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: var(--font-xs);
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .event-card-body {
            padding: var(--space-md);
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-lg);
            margin-top: var(--space-md);
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
                    <p>Accredited NAAC A++ &bull; Campus Activities Cell</p>
                </div>
            </a>
            <div class="header-quick-info">
                <?php if ($isLoggedIn): ?>
                    <span class="badge-tag">👤 <?php echo $userName; ?></span>
                    <a href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'dashboard.php'; ?>" class="btn btn-outline btn-sm">Dashboard</a>
                    <a href="php/logout.php" style="background:#ef4444; color:#fff; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; font-weight:600;">Logout</a>
                <?php else: ?>
                    <a href="login.html" class="btn btn-primary btn-sm">Login</a>
                    <a href="registration.html" class="btn btn-outline btn-sm">Register</a>
                <?php endif; ?>
                <button class="theme-toggle-btn" aria-label="Toggle theme">🌙 <span>Dark</span></button>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label"><span>Navigation Menu</span></div>
            <button class="nav-toggle-btn" id="nav-toggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-menu" id="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="<?php echo $isAdmin ? 'admin_dashboard.php' : 'dashboard.php'; ?>">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="events.php" class="active">Campus Events</a></li>
                    <li><a href="events.html">Events Archive</a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="admin/events.php" style="color: #fbbf24; font-weight: 700;">⚙️ Manage Events (Admin)</a></li>
                        <li><a href="admin/students.php" style="color: #fbbf24; font-weight: 700;">👥 Manage Students (Admin)</a></li>
                        <li><a href="admin/audit_logs.php" style="color: #fbbf24; font-weight: 700;">🛡️ Audit Logs</a></li>
                    <?php endif; ?>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl);">

        <!-- Admin Management Quick Banner (Visible only to authorized Admins) -->
        <?php if ($isAdmin): ?>
            <div class="card" style="background: linear-gradient(135deg, #1e1b4b, #312e81); color: #fff; margin-bottom: var(--space-lg); padding: var(--space-md);">
                <div class="d-flex justify-between align-center flex-wrap gap-md">
                    <div>
                        <div class="badge badge-warning" style="margin-bottom: 4px;">🏢 Administrator Access Detected</div>
                        <h3 style="color: #fff; margin: 0 0 4px 0;">Event Administration Panel Available</h3>
                        <p style="margin: 0; font-size: var(--font-xs); color: #cbd5e1;">
                            You have administrator credentials. You can add new events, upload/replace event posters, and delete records.
                        </p>
                    </div>
                    <div class="d-flex gap-sm">
                        <a href="admin/events.php" class="btn btn-accent btn-md">⚙️ Manage Events (Admin CRUD) &raquo;</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Featured Banner -->
        <div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: var(--text-inverse); border: none; margin-bottom: var(--space-lg);">
            <div class="d-flex flex-col gap-sm">
                <div class="d-flex justify-between align-center flex-wrap">
                    <span class="badge badge-warning" style="font-size: var(--font-xs);">🌟 CAMPUS WORKSHOPS &amp; COMPETITIONS</span>
                    <span style="font-size: var(--font-xs); color: var(--accent-light); font-weight: 600;">Campus Events Directory</span>
                </div>
                <h2 style="color: var(--text-inverse); font-size: var(--font-2xl); margin: 4px 0;">Official Campus Events &amp; Workshops</h2>
                <p style="color: #cbd5e1; font-size: var(--font-sm); margin: 0; max-width: 800px;">
                    Explore active university events, technical symposiums, and cultural festivals fetched directly from the institutional MySQL database.
                </p>
            </div>
        </div>

        <!-- Search & Filter Toolbar -->
        <div class="card" style="margin-bottom: var(--space-lg); padding: var(--space-md);">
            <form method="GET" action="events.php" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: var(--space-md); align-items: flex-end;">
                <div>
                    <label for="event-search" style="font-size: var(--font-xs); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">🔍 Search Events</label>
                    <input type="text" id="event-search" name="search" class="form-control" placeholder="Search by event title or description..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <label for="event-sort" style="font-size: var(--font-xs); font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px;">↕️ Sort Order</label>
                    <select id="event-sort" name="sort" class="form-control">
                        <option value="date-asc" <?php if ($sort === "date-asc") echo 'selected'; ?>>Date: Upcoming First</option>
                        <option value="date-desc" <?php if ($sort === "date-desc") echo 'selected'; ?>>Date: Latest First</option>
                        <option value="title-asc" <?php if ($sort === "title-asc") echo 'selected'; ?>>Title: A to Z</option>
                        <option value="title-desc" <?php if ($sort === "title-desc") echo 'selected'; ?>>Title: Z to A</option>
                    </select>
                </div>
                <div class="d-flex gap-xs">
                    <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
                    <?php if ($search !== "" || $sort !== "date-asc"): ?>
                        <a href="events.php" class="btn btn-outline" style="height: 38px; display: inline-flex; align-items: center;">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Events Grid -->
        <?php if (empty($events)): ?>
            <div class="card" style="text-align: center; padding: var(--space-2xl) var(--space-md); color: var(--text-muted);">
                <div style="font-size: 3rem; margin-bottom: 8px;">🔍</div>
                <h3>No Events Found</h3>
                <p style="font-size: var(--font-sm); margin: 6px 0 16px 0;">No published events matched your query. Please try different keywords.</p>
                <a href="events.php" class="btn btn-outline btn-sm">Clear Search</a>
            </div>
        <?php else: ?>
            <div class="event-grid">
                <?php foreach ($events as $ev): ?>
                    <div class="event-card">
                        <!-- Poster Preview with Date Badge -->
                        <div class="event-poster-wrap">
                            <?php if (!empty($ev["poster"]) && file_exists(__DIR__ . "/" . $ev["poster"])): ?>
                                <img src="<?php echo htmlspecialchars($ev["poster"]); ?>" alt="<?php echo htmlspecialchars($ev["title"]); ?> Poster" class="event-poster-img">
                            <?php else: ?>
                                <div style="color: #94a3b8; font-size: var(--font-sm); font-weight: 600; text-align: center; padding: var(--space-md);">
                                    🎭 Campus Event
                                </div>
                            <?php endif; ?>
                            <span class="event-date-badge">
                                📅 <?php echo date("d M Y", strtotime($ev["event_date"])); ?>
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="event-card-body">
                            <h3 style="font-size: var(--font-md); margin: 0 0 8px 0; color: var(--primary);">
                                <?php echo htmlspecialchars($ev["title"]); ?>
                            </h3>
                            <p style="font-size: var(--font-xs); color: var(--text-secondary); flex: 1; margin: 0 0 var(--space-md) 0; line-height: 1.5;">
                                <?php echo nl2br(htmlspecialchars($ev["description"])); ?>
                            </p>

                            <div class="d-flex justify-between align-center" style="border-top: 1px solid var(--border-color); padding-top: var(--space-sm); margin-top: auto;">
                                <span style="font-size: 11px; color: var(--text-muted);">
                                    Event ID: <b>#<?php echo (int)$ev["id"]; ?></b>
                                </span>
                                <button type="button" class="btn btn-accent btn-sm" onclick="openRegistrationModal('<?php echo htmlspecialchars(addslashes($ev['title'])); ?>', <?php echo (int)$ev['id']; ?>)">
                                    🎟️ Register Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Event Registration Modal -->
    <div class="modal-overlay" id="event-reg-modal" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>🎟️ Event Registration</h3>
                <button type="button" class="modal-close-btn" onclick="closeRegistrationModal()">&times;</button>
            </div>
            <form id="event-reg-form" onsubmit="handleRegistrationSubmit(event)">
                <input type="hidden" id="modal-event-id" name="event_id" value="">
                <div class="modal-body">
                    <p style="margin-bottom: var(--space-sm);">
                        Registering for: <strong id="modal-event-title-display" style="color: var(--secondary);">Event Title</strong>
                    </p>

                    <div class="form-group">
                        <label for="modal-name">Full Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="modal-name" class="form-control" value="<?php echo $userName; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="modal-email">Email Address <span style="color: var(--danger);">*</span></label>
                        <input type="email" id="modal-email" class="form-control" value="<?php echo $userEmail; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="modal-note">Department / Team Name (Optional)</label>
                        <input type="text" id="modal-note" class="form-control" placeholder="e.g. IT Dept Team Beta">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeRegistrationModal()">Cancel</button>
                    <button type="submit" class="btn btn-accent">Confirm Registration</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container footer-grid">
            <div class="footer-col">
                <h4>The Backbenchers University</h4>
                <p>An autonomous premier technical institute dedicated to engineering excellence.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About Institution</a></li>
                    <li><a href="events.php">Campus Events</a></li>
                    <li><a href="events.html">Events Archive</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Campus Activities</h4>
                <p style="font-size: var(--font-xs); color: var(--text-muted);">
                    University Event Management with real-time updates and registrations.
                </p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Office of Student Activities.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        function openRegistrationModal(title, eventId) {
            document.getElementById("modal-event-title-display").textContent = title;
            document.getElementById("modal-event-id").value = eventId;
            const modal = document.getElementById("event-reg-modal");
            modal.style.display = "flex";
            modal.classList.add("active");
        }

        function closeRegistrationModal() {
            const modal = document.getElementById("event-reg-modal");
            modal.style.display = "none";
            modal.classList.remove("active");
        }

        function handleRegistrationSubmit(e) {
            e.preventDefault();
            const title = document.getElementById("modal-event-title-display").textContent;
            alert("Registration confirmed for: " + title + "!\nAn e-pass confirmation has been prepared.");
            closeRegistrationModal();
        }
    </script>
</body>
</html>
