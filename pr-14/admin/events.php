<?php
// ========================================================
// Practical 12 & Practical 13: Campus Event Management Module (Admin Only)
// File: admin/events.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Event CRUD, Poster Image File Upload & MIME Validation (P12)
//           Integrated Asynchronous Fetch API CRUD with FormData (P13)
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";
require_once dirname(__DIR__) . "/php/audit_helper.php";
check_auth("admin");

$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");
$adminRole = ucfirst(htmlspecialchars($_SESSION["user_role"] ?? "admin"));
$adminId = (int)($_SESSION["user_id"] ?? 0);

$msgSuccess = $_GET["msg"] ?? "";
$msgError = $_GET["error"] ?? "";
$formError = "";

// Directory for uploaded event posters
$uploadRelDir = "uploads/event_posters/";
$uploadServerDir = dirname(__DIR__) . "/" . $uploadRelDir;

// Handle Add Event Form Submission (Fallback POST with multipart/form-data)
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

                    log_audit_action($conn, (int)($_SESSION["user_id"] ?? 0), "ADD_EVENT", "Created event '$title' ($eventDate)", $newEventId);

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
    <title>Event Management - The Backbenchers University</title>
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

        /* Modal Styles for Asynchronous Edit */
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
                    <p>Office of Academic Administration &bull; Campus Activities Cell</p>
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
                    <li><a href="events.php" class="active">Event Management</a></li>
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

        <!-- Notification Alerts (Fallback from GET params) -->
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
                        <span class="admin-badge">Event Administration</span>
                        <span style="font-size: var(--font-xs); opacity: 0.85;">Campus Events &bull; Poster Upload &bull; Real-Time Operations</span>
                    </div>
                    <h2 style="font-size: var(--font-2xl); margin-bottom: 6px;">Campus Event Administration</h2>
                    <p style="margin: 0; opacity: 0.9; font-size: var(--font-sm);">
                        Create, modify, and publish university events with validated poster images (JPG, PNG, WebP &le; 2MB). Real-time asynchronous operations with zero page reloads.
                    </p>
                </div>
                <div class="d-flex gap-sm flex-wrap">
                    <a href="#add-event-form" class="btn btn-accent btn-md" style="background-color: #f59e0b; color: #0f172a; font-weight: 600; border: none; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);">➕ Add New Event</a>
                    <a href="../events.php" class="btn btn-outline btn-md" style="color: white; border-color: rgba(255,255,255,0.35); background: rgba(255,255,255,0.08); backdrop-filter: blur(4px);">🌐 View Student Event Page</a>
                    <a href="../admin_dashboard.php" class="btn btn-outline btn-md" style="color: white; border-color: rgba(255,255,255,0.35); background: rgba(255,255,255,0.08); backdrop-filter: blur(4px);">&laquo; Back to Dashboard</a>
                </div>
            </div>
        </div>

        <!-- Add Event Form Card (Enhanced with Fetch API & FormData) -->
        <div class="card" id="add-event-form" style="margin-bottom: var(--space-xl);">
            <div class="card-header">
                <h3>➕ Create Campus Event &amp; Upload Poster</h3>
                <span class="badge badge-info" style="font-size: 11px;">Max 2 MB &bull; JPG / PNG / WebP</span>
            </div>

            <div id="add-form-error" style="display: none; background-color: var(--danger-bg); border-left: 4px solid var(--danger); color: var(--danger); padding: var(--space-md); margin-bottom: var(--space-md); border-radius: var(--radius-sm);"></div>

            <?php if (!empty($formError)): ?>
                <div style="background-color: var(--danger-bg); border-left: 4px solid var(--danger); color: var(--danger); padding: var(--space-md); margin-bottom: var(--space-md); border-radius: var(--radius-sm);">
                    <strong>⚠️ Validation Error:</strong> <?php echo htmlspecialchars($formError); ?>
                </div>
            <?php endif; ?>

            <form id="create-event-form" method="POST" action="events.php" enctype="multipart/form-data" onsubmit="handleCreateEventSubmit(event)">
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
                        <button type="submit" id="create-event-btn" class="btn btn-primary btn-md">
                            <span id="create-btn-text">🚀 Publish Event Record</span>
                        </button>
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
                        Currently active records in table <code>events</code> (Total: <span id="event-total-count"><?php echo count($events); ?></span>)
                    </p>
                </div>
                <div class="d-flex gap-sm align-center">
                    <input type="text" id="event-search-box" class="form-control" placeholder="Search events..." style="width: 200px; height: 32px; font-size: 12px;" oninput="filterEventsLive()">
                    <a href="../events.php" class="btn btn-outline btn-sm">Preview Student View &raquo;</a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="events-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th style="width: 80px;">Poster</th>
                            <th>Event Title</th>
                            <th style="width: 120px;">Event Date</th>
                            <th>Description</th>
                            <th style="text-align: center; width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="events-tbody">
                        <?php if (empty($events)): ?>
                            <tr id="empty-events-row">
                                <td colspan="6" style="text-align: center; padding: var(--space-xl); color: var(--text-muted);">
                                    No campus events found in the database. Use the form above to add an event.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $ev): ?>
                                <tr id="row-event-<?php echo (int)$ev["id"]; ?>">
                                    <td><strong>#<?php echo (int)$ev["id"]; ?></strong></td>
                                    <td class="col-event-poster">
                                        <?php if (!empty($ev["poster"]) && file_exists(dirname(__DIR__) . "/" . $ev["poster"])): ?>
                                            <a href="../<?php echo htmlspecialchars($ev['poster']); ?>" target="_blank" title="View Full Poster">
                                                <img src="../<?php echo htmlspecialchars($ev['poster']); ?>" alt="Poster" class="poster-thumb">
                                            </a>
                                        <?php else: ?>
                                            <div class="poster-placeholder">No Poster</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="col-event-title"><?php echo htmlspecialchars($ev["title"]); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info col-event-date" style="font-size: 11px;">
                                            📅 <?php echo date("d M Y", strtotime($ev["event_date"])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="col-event-desc" style="font-size: var(--font-xs); color: var(--text-secondary); max-width: 380px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                            <?php echo htmlspecialchars($ev["description"]); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-cell" style="justify-content: center;">
                                            <!-- Edit Action (Asynchronous Fetch Modal with fallback to event_edit.php) -->
                                            <button type="button" class="btn btn-outline btn-sm" style="padding: 3px 8px;" onclick="openEditEventModal(<?php echo (int)$ev['id']; ?>)" title="Edit via Fetch API">
                                                ✏️ Edit
                                            </button>

                                            <!-- Delete Action (Asynchronous Fetch DELETE) -->
                                            <button type="button" class="btn btn-danger btn-sm" style="padding: 3px 8px; background-color: #ef4444; color: white;" onclick="deleteEventAsync(<?php echo (int)$ev['id']; ?>, '<?php echo htmlspecialchars(addslashes($ev['title'])); ?>')" title="Delete via Fetch API">
                                                🗑️ Delete
                                            </button>

                                            <!-- Fallback form for non-JS / automated tests -->
                                            <noscript>
                                                <form method="POST" action="event_delete.php" style="display: inline;">
                                                    <input type="hidden" name="event_id" value="<?php echo (int)$ev['id']; ?>">
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
        </div>

    </main>

    <!-- Asynchronous Edit Event Modal (Practical 13 Fetch API with FormData) -->
    <div class="modal-overlay" id="edit-event-modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="edit-event-modal-title" style="margin: 0; font-size: var(--font-lg);">✏️ Edit Campus Event (Fetch API)</h3>
                <button type="button" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--text-muted);" onclick="closeEditEventModal()">&times;</button>
            </div>
            <form id="edit-event-form" onsubmit="handleEditEventSubmit(event)" enctype="multipart/form-data">
                <input type="hidden" id="edit-event-id" name="id" value="">
                <input type="hidden" name="_method" value="PUT">

                <div class="modal-body">
                    <div id="modal-event-val-error" style="display: none; background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: var(--space-sm); border-radius: var(--radius-sm); margin-bottom: var(--space-md); font-size: var(--font-xs);"></div>

                    <div class="d-flex flex-col gap-sm">
                        <!-- Event Title -->
                        <div class="form-group">
                            <label for="edit-title">Event Title <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="edit-title" name="title" class="form-control" required minlength="3">
                        </div>

                        <!-- Event Date -->
                        <div class="form-group">
                            <label for="edit-date">Event Date <span style="color: var(--danger);">*</span></label>
                            <input type="date" id="edit-date" name="event_date" class="form-control" required>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="edit-desc">Event Description <span style="color: var(--danger);">*</span></label>
                            <textarea id="edit-desc" name="description" class="form-control" rows="3" required minlength="10"></textarea>
                        </div>

                        <!-- Poster Image -->
                        <div class="form-group">
                            <label for="edit-poster">Update Poster Image (Optional &bull; Max 2 MB)</label>
                            <input type="file" id="edit-poster" name="poster" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 4px;">
                                Leave empty to preserve current poster file.
                            </small>
                            <div id="edit-current-poster-preview" style="margin-top: 8px;"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEditEventModal()">Cancel</button>
                    <button type="submit" id="edit-event-submit-btn" class="btn btn-primary">
                        <span id="edit-btn-text">Save Event</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Office of Academic Affairs.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script src="../session_timer.js"></script>
    <script>
        // ========================================================
        // Practical 13: Integrated Event Fetch API Engine
        // Communicates asynchronously with ../api/events.php
        // Uses FormData for poster uploads without manual Content-Type header.
        // ========================================================

        const EVENT_API = "../api/events.php";

        // Show live feedback alert
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

        // 1. Asynchronous Event Creation via Fetch API + FormData
        async function handleCreateEventSubmit(e) {
            e.preventDefault();
            const form = document.getElementById("create-event-form");
            const formData = new FormData(form);

            const submitBtn = document.getElementById("create-event-btn");
            const btnText = document.getElementById("create-btn-text");
            const originalText = btnText.textContent;
            submitBtn.disabled = true;
            btnText.innerHTML = `<span class="spinner"></span> Uploading...`;

            const errBox = document.getElementById("add-form-error");
            errBox.style.display = "none";

            try {
                // DO NOT set Content-Type header when sending FormData! The browser sets multipart boundary automatically.
                const response = await fetch(EVENT_API, {
                    method: "POST",
                    body: formData
                });

                const data = await response.json().catch(() => ({ success: false, message: "Invalid JSON response" }));

                if (!response.ok || !data.success) {
                    errBox.style.display = "block";
                    errBox.innerHTML = `<strong>⚠️ Validation Failed:</strong> ${escapeHtml(data.message || "Could not create event.")}`;
                    return;
                }

                // Success: dynamically prepend to table without page reload
                form.reset();
                showLiveAlert(data.message, "success");

                const ev = data.data;
                const tbody = document.getElementById("events-tbody");
                const emptyRow = document.getElementById("empty-events-row");
                if (emptyRow) emptyRow.remove();

                const posterHtml = ev.poster 
                    ? `<a href="../${escapeHtml(ev.poster)}" target="_blank"><img src="../${escapeHtml(ev.poster)}" alt="Poster" class="poster-thumb"></a>`
                    : `<div class="poster-placeholder">No Poster</div>`;

                const dateFormatted = new Date(ev.event_date).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });

                const tr = document.createElement("tr");
                tr.id = `row-event-${ev.id}`;
                tr.style.backgroundColor = "rgba(16, 185, 129, 0.08)";
                tr.innerHTML = `
                    <td><strong>#${ev.id}</strong></td>
                    <td class="col-event-poster">${posterHtml}</td>
                    <td><strong class="col-event-title">${escapeHtml(ev.title)}</strong></td>
                    <td><span class="badge badge-info col-event-date" style="font-size: 11px;">📅 ${dateFormatted}</span></td>
                    <td>
                        <div class="col-event-desc" style="font-size: var(--font-xs); color: var(--text-secondary); max-width: 380px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            ${escapeHtml(ev.description)}
                        </div>
                    </td>
                    <td>
                        <div class="action-cell" style="justify-content: center;">
                            <button type="button" class="btn btn-outline btn-sm" style="padding: 3px 8px;" onclick="openEditEventModal(${ev.id})">✏️ Edit</button>
                            <button type="button" class="btn btn-danger btn-sm" style="padding: 3px 8px; background-color: #ef4444; color: white;" onclick="deleteEventAsync(${ev.id}, '${escapeJs(ev.title)}')">🗑️ Delete</button>
                        </div>
                    </td>
                `;
                tbody.insertBefore(tr, tbody.firstChild);

                const countEl = document.getElementById("event-total-count");
                if (countEl) countEl.textContent = parseInt(countEl.textContent || "0", 10) + 1;
            } catch (err) {
                errBox.style.display = "block";
                errBox.textContent = "Network Error: Could not connect to API server.";
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = originalText;
            }
        }

        // 2. Open Edit Event Modal (Fetch API)
        async function openEditEventModal(id) {
            try {
                const res = await fetch(`${EVENT_API}?id=${id}`);
                const data = await res.json();

                if (!res.ok || !data.success) {
                    showLiveAlert(data.message || "Failed to load event data.", "error");
                    return;
                }

                const ev = data.data;
                document.getElementById("edit-event-id").value = ev.id;
                document.getElementById("edit-title").value = ev.title;
                document.getElementById("edit-date").value = ev.event_date;
                document.getElementById("edit-desc").value = ev.description;
                document.getElementById("edit-poster").value = "";

                const preview = document.getElementById("edit-current-poster-preview");
                if (ev.poster) {
                    preview.innerHTML = `<span style="font-size:11px; color:var(--text-muted);">Current Poster:</span><br><img src="../${escapeHtml(ev.poster)}" alt="Current" style="height:40px; border-radius:4px; margin-top:4px;">`;
                } else {
                    preview.innerHTML = `<span style="font-size:11px; color:var(--text-muted);">No current poster.</span>`;
                }

                document.getElementById("modal-event-val-error").style.display = "none";
                document.getElementById("edit-event-modal-overlay").classList.add("active");
            } catch (err) {
                showLiveAlert("Network Error: Could not load event details.", "error");
            }
        }

        function closeEditEventModal() {
            document.getElementById("edit-event-modal-overlay").classList.remove("active");
        }

        // 3. Submit Edit Event via Fetch API + FormData
        async function handleEditEventSubmit(e) {
            e.preventDefault();
            const form = document.getElementById("edit-event-form");
            const formData = new FormData(form);
            const id = document.getElementById("edit-event-id").value;

            const submitBtn = document.getElementById("edit-event-submit-btn");
            const btnText = document.getElementById("edit-btn-text");
            const originalText = btnText.textContent;
            submitBtn.disabled = true;
            btnText.innerHTML = `<span class="spinner"></span> Saving...`;

            const errBox = document.getElementById("modal-event-val-error");
            errBox.style.display = "none";

            try {
                const response = await fetch(EVENT_API, {
                    method: "POST",
                    body: formData
                });

                const data = await response.json().catch(() => ({ success: false, message: "Invalid JSON response" }));

                if (!response.ok || !data.success) {
                    errBox.style.display = "block";
                    errBox.innerHTML = `<strong>⚠️ Validation Failed:</strong> ${escapeHtml(data.message || "Update failed.")}`;
                    return;
                }

                closeEditEventModal();
                showLiveAlert(data.message, "success");

                // Update table row dynamically in DOM
                const ev = data.data;
                const row = document.getElementById(`row-event-${id}`);
                if (row) {
                    row.querySelector(".col-event-title").textContent = ev.title;
                    row.querySelector(".col-event-desc").textContent = ev.description;
                    const dateFormatted = new Date(ev.event_date).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
                    row.querySelector(".col-event-date").textContent = `📅 ${dateFormatted}`;

                    if (ev.poster) {
                        const posterCol = row.querySelector(".col-event-poster");
                        posterCol.innerHTML = `<a href="../${escapeHtml(ev.poster)}" target="_blank"><img src="../${escapeHtml(ev.poster)}" alt="Poster" class="poster-thumb"></a>`;
                    }
                }
            } catch (err) {
                errBox.style.display = "block";
                errBox.textContent = "Network Error: Could not save event updates.";
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = originalText;
            }
        }

        // 4. Delete Event via Fetch API (DELETE method, zero page reload)
        async function deleteEventAsync(id, title) {
            if (!confirm(`Are you sure you want to delete event: ${title} (ID #${id})?\n\nThis action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(`${EVENT_API}?id=${id}`, {
                    method: "DELETE"
                });

                const data = await response.json().catch(() => ({ success: false, message: "Invalid JSON response" }));

                if (!response.ok || !data.success) {
                    showLiveAlert(data.message || "Failed to delete event.", "error");
                    return;
                }

                // Smooth DOM removal without page reload
                const row = document.getElementById(`row-event-${id}`);
                if (row) {
                    row.style.transition = "all 0.3s ease";
                    row.style.opacity = "0";
                    row.style.transform = "translateX(-20px)";
                    setTimeout(() => {
                        row.remove();
                        const countEl = document.getElementById("event-total-count");
                        if (countEl) {
                            const cur = parseInt(countEl.textContent || "0", 10);
                            countEl.textContent = Math.max(0, cur - 1);
                        }
                    }, 300);
                }

                showLiveAlert(data.message, "success");
            } catch (err) {
                showLiveAlert("Network Error: Could not delete event.", "error");
            }
        }

        // 5. Live Search Filter
        function filterEventsLive() {
            const query = document.getElementById("event-search-box").value.toLowerCase().trim();
            const rows = document.querySelectorAll("#events-tbody tr");

            rows.forEach(r => {
                if (r.id === "empty-events-row") return;
                const text = r.textContent.toLowerCase();
                r.style.display = text.includes(query) ? "" : "none";
            });
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
