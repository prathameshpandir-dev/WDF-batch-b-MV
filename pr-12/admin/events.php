<?php
// ========================================================
// Practical 12: Campus Event Management Module (Admin Only)
// File: admin/events.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Event CRUD, Poster Image File Upload & MIME Validation,
//           File Size Checks (<=2MB), & MySQLi Prepared Statements.
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";
check_auth("admin");

$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");
$adminRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "admin"));

$msgSuccess = $_GET["msg"] ?? "";
$msgError = $_GET["error"] ?? "";
$formError = "";

// Directory for uploaded event posters
$uploadRelDir = "uploads/event_posters/";
$uploadServerDir = dirname(__DIR__) . "/" . $uploadRelDir;

// Handle Add Event Form Submission (POST with multipart/form-data)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add_event") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $eventDate = trim($_POST["event_date"] ?? "");
    $posterPath = null;

    // 1. Basic field validation
    if ($title === "" || strlen($title) < 3) {
        $formError = "Event title is required (minimum 3 characters).";
    } elseif ($description === "" || strlen($description) < 10) {
        $formError = "Event description is required (minimum 10 characters).";
    } elseif ($eventDate === "") {
        $formError = "Please select a valid event date.";
    } else {
        // 2. Poster File Upload Validation
        if (isset($_FILES["poster"]) && $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES["poster"];

            // Check upload error code
            if ($file["error"] !== UPLOAD_ERR_OK) {
                $formError = "File upload failed with error code: " . $file["error"];
            }
            // Check file size (max 2 MB = 2 * 1024 * 1024 bytes)
            elseif ($file["size"] > 2 * 1024 * 1024) {
                $formError = "Poster size must not exceed 2 MB.";
            } else {
                // Validate extension
                $originalName = $file["name"];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExts = ["jpg", "jpeg", "png", "webp"];

                if (!in_array($ext, $allowedExts)) {
                    $formError = "Please upload a JPG, PNG, or WebP image.";
                } else {
                    // Validate real MIME type using PHP Fileinfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file["tmp_name"]);
                    finfo_close($finfo);

                    $allowedMimes = ["image/jpeg", "image/png", "image/webp"];
                    if (!in_array($mime, $allowedMimes)) {
                        $formError = "Please upload a JPG, PNG, or WebP image.";
                    } else {
                        // Generate unique, safe filename to prevent overwriting or script execution
                        if (!is_dir($uploadServerDir)) {
                            mkdir($uploadServerDir, 0777, true);
                        }
                        $uniqueName = "poster_" . bin2hex(random_bytes(8)) . "_" . time() . "." . $ext;
                        $destination = $uploadServerDir . $uniqueName;

                        if (move_uploaded_file($file["tmp_name"], $destination)) {
                            $posterPath = $uploadRelDir . $uniqueName;
                        } else {
                            $formError = "Failed to save uploaded poster to server directory.";
                        }
                    }
                }
            }
        }

        // 3. Insert event record if validation passed
        if (empty($formError)) {
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, poster) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $title, $description, $eventDate, $posterPath);
                if ($stmt->execute()) {
                    $newEventId = $stmt->insert_id;
                    $stmt->close();
                    header("Location: events.php?msg=" . urlencode("Event added successfully. (Event ID #$newEventId)"));
                    exit;
                } else {
                    $formError = "Database insert error: " . $conn->error;
                    $stmt->close();
                }
            } else {
                $formError = "Failed to prepare SQL query: " . $conn->error;
            }
        }
    }
}

// Fetch all events from database using MySQLi
$events = [];
$res = $conn->query("SELECT id, title, description, event_date, poster FROM events ORDER BY event_date ASC, id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
    $res->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management (Practical 12) - The Backbenchers University</title>
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
        }
        .poster-thumb {
            width: 72px;
            height: 48px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            background: #1e293b;
        }
        .poster-placeholder {
            width: 72px;
            height: 48px;
            border-radius: var(--radius-sm);
            background: #e2e8f0;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
            border: 1px dashed #cbd5e1;
        }
        .action-cell {
            white-space: nowrap;
            display: flex;
            gap: 6px;
            align-items: center;
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
                    <p>Office of Academic Administration &bull; Practical 12</p>
                </div>
            </a>
            <div class="header-quick-info">
                <span class="badge-tag">👤 <?php echo $adminName; ?></span>
                <span class="admin-badge">🏢 <?php echo $adminRole; ?></span>
                <span class="session-timer-badge" id="session-timer-badge">
                    ⏱️ Session: <span id="session-timer-clock" data-remaining="<?php echo $session_remaining_seconds ?? 900; ?>">14:59</span>
                </span>
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
                    <li><a href="students.php">Student Management (P11)</a></li>
                    <li><a href="events.php" class="active">Event Management (P12)</a></li>
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
                    <div><strong>Notice:</strong> <?php echo htmlspecialchars($msgError); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hero Banner -->
        <div class="admin-banner">
            <div class="d-flex flex-wrap justify-between align-center gap-md">
                <div>
                    <div class="d-flex align-center gap-sm" style="margin-bottom: 8px;">
                        <span class="admin-badge">Practical 12</span>
                        <span style="font-size: var(--font-xs); opacity: 0.85;">Event CRUD with Secure Poster File Upload &bull; Table: `events`</span>
                    </div>
                    <h2 style="font-size: var(--font-2xl); margin-bottom: 6px;">Campus Event Administration</h2>
                    <p style="margin: 0; opacity: 0.9; font-size: var(--font-sm);">
                        Create, modify, and publish university events with validated poster images (JPG, PNG, WebP up to 2MB).
                    </p>
                </div>
                <div class="d-flex gap-sm flex-wrap">
                    <a href="#add-event-form" class="btn btn-accent btn-md">➕ Add New Event</a>
                    <a href="../events.php" class="btn btn-outline btn-md" style="color: white; border-color: rgba(255,255,255,0.4);">🌐 View Student Event Page</a>
                </div>
            </div>
        </div>

        <!-- Add Event Form Card -->
        <div class="card" id="add-event-form" style="margin-bottom: var(--space-xl);">
            <div class="card-header">
                <h3>➕ Create Campus Event &amp; Upload Poster</h3>
                <span class="badge badge-info" style="font-size: 11px;">Max 2 MB &bull; JPG / PNG / WebP</span>
            </div>

            <?php if (!empty($formError)): ?>
                <div style="background-color: var(--danger-bg); border-left: 4px solid var(--danger); color: var(--danger); padding: var(--space-md); margin-bottom: var(--space-md); border-radius: var(--radius-sm);">
                    <strong>⚠️ Validation Error:</strong> <?php echo htmlspecialchars($formError); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="events.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_event">

                <div class="d-flex flex-col gap-md">
                    <div class="grid-2-col" style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-md);">
                        <!-- Event Title -->
                        <div class="form-group">
                            <label for="event-title">Event Title <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="event-title" name="title" class="form-control" placeholder="e.g. CodeFest 2026: 24-Hour National Hackathon" required>
                        </div>

                        <!-- Event Date -->
                        <div class="form-group">
                            <label for="event-date">Event Date <span style="color: var(--danger);">*</span></label>
                            <input type="date" id="event-date" name="event_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                        </div>
                    </div>

                    <!-- Event Description -->
                    <div class="form-group">
                        <label for="event-desc">Event Description <span style="color: var(--danger);">*</span></label>
                        <textarea id="event-desc" name="description" class="form-control" rows="3" placeholder="Provide full details about the workshop, hackathon, or cultural fest..." required></textarea>
                    </div>

                    <!-- Poster Image Upload -->
                    <div class="form-group">
                        <label for="event-poster">Event Poster Image (JPG, PNG, WebP &bull; Max 2 MB)</label>
                        <input type="file" id="event-poster" name="poster" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small style="color: var(--text-muted); font-size: 11px; display: block; margin-top: 4px;">
                            Uploaded posters undergo strict server-side MIME type verification and are stored with generated unique filenames inside <code>uploads/event_posters/</code>.
                        </small>
                    </div>

                    <div class="d-flex justify-between align-center" style="border-top: 1px solid var(--border-color); padding-top: var(--space-md);">
                        <button type="reset" class="btn btn-outline">Reset Form</button>
                        <button type="submit" class="btn btn-primary btn-md">🚀 Publish Event Record</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Existing Events Table -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h3>📋 Published Events Directory</h3>
                    <p style="color: var(--text-muted); font-size: var(--font-xs); margin-top: 4px;">
                        Currently active records in table <code>events</code> (Total: <?php echo count($events); ?>)
                    </p>
                </div>
                <a href="../events.php" class="btn btn-outline btn-sm">Preview Student View &raquo;</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th style="width: 80px;">Poster</th>
                            <th>Event Title</th>
                            <th style="width: 120px;">Event Date</th>
                            <th>Description</th>
                            <th style="text-align: center; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: var(--space-xl); color: var(--text-muted);">
                                    No campus events found in the database. Use the form above to add an event.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $ev): ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$ev["id"]; ?></strong></td>
                                    <td>
                                        <?php if (!empty($ev["poster"]) && file_exists(dirname(__DIR__) . "/" . $ev["poster"])): ?>
                                            <a href="../<?php echo htmlspecialchars($ev['poster']); ?>" target="_blank" title="View Full Poster">
                                                <img src="../<?php echo htmlspecialchars($ev['poster']); ?>" alt="Poster" class="poster-thumb">
                                            </a>
                                        <?php else: ?>
                                            <div class="poster-placeholder">No Poster</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ev["title"]); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info" style="font-size: 11px;">
                                            📅 <?php echo date("d M Y", strtotime($ev["event_date"])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: var(--font-xs); color: var(--text-secondary); max-width: 380px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                            <?php echo htmlspecialchars($ev["description"]); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-cell" style="justify-content: center;">
                                            <a href="event_edit.php?id=<?php echo (int)$ev['id']; ?>" class="btn btn-outline btn-sm" style="padding: 3px 8px;" title="Edit Event Details">
                                                ✏️ Edit
                                            </a>

                                            <form method="POST" action="event_delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete event: <?php echo htmlspecialchars(addslashes($ev['title'])); ?> (ID #<?php echo (int)$ev['id']; ?>)?');">
                                                <input type="hidden" name="event_id" value="<?php echo (int)$ev['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 3px 8px; background-color: #ef4444; color: white;" title="Delete Event">
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
        </div>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Practical 12 Implementation.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script src="../session_timer.js"></script>
</body>
</html>
