<?php
// ========================================================
// Practical 12: Edit Campus Event & Replace Poster
// File: admin/event_edit.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: Prepared Update, Poster Replacement with MIME Check,
//           and Safe File Cleanup.
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";
require_once dirname(__DIR__) . "/php/audit_helper.php";
check_auth("admin");

$adminName = htmlspecialchars($_SESSION["user_name"] ?? "Administrator");

// 1. Validate incoming event ID
$eventId = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($eventId <= 0) {
    header("Location: events.php?error=" . urlencode("Invalid event ID."));
    exit;
}

$uploadRelDir = "uploads/event_posters/";
$uploadServerDir = dirname(__DIR__) . "/" . $uploadRelDir;

$error = "";

// 2. Fetch existing event record using MySQLi Prepared Statement
$stmt = $conn->prepare("SELECT id, title, description, event_date, poster FROM events WHERE id = ? LIMIT 1");
if (!$stmt) {
    header("Location: events.php?error=" . urlencode("Database preparation error."));
    exit;
}

$stmt->bind_param("i", $eventId);
$stmt->execute();
$res = $stmt->get_result();
$event = $res->fetch_assoc();
$stmt->close();

if (!$event) {
    header("Location: events.php?error=" . urlencode("Event record not found for ID #$eventId."));
    exit;
}

// 3. Handle Form Submission (POST with multipart/form-data)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $eventDate = trim($_POST["event_date"] ?? "");
    $posterPath = $event["poster"]; // Preserve existing poster by default

    if ($title === "" || strlen($title) < 3) {
        $error = "Event title is required (minimum 3 characters).";
    } elseif ($description === "" || strlen($description) < 10) {
        $error = "Event description is required (minimum 10 characters).";
    } elseif ($eventDate === "") {
        $error = "Please select a valid event date.";
    } else {
        // Check if user uploaded a replacement poster
        if (isset($_FILES["poster"]) && $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES["poster"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $error = "File upload error code: " . $file["error"];
            } elseif ($file["size"] > 2 * 1024 * 1024) {
                $error = "Poster size must not exceed 2 MB.";
            } else {
                $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
                $allowedExts = ["jpg", "jpeg", "png", "webp"];

                if (!in_array($ext, $allowedExts)) {
                    $error = "Please upload a JPG, PNG, or WebP image.";
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file["tmp_name"]);
                    finfo_close($finfo);

                    $allowedMimes = ["image/jpeg", "image/png", "image/webp"];
                    if (!in_array($mime, $allowedMimes)) {
                        $error = "Please upload a JPG, PNG, or WebP image.";
                    } else {
                        if (!is_dir($uploadServerDir)) {
                            mkdir($uploadServerDir, 0777, true);
                        }
                        $uniqueName = "poster_" . bin2hex(random_bytes(8)) . "_" . time() . "." . $ext;
                        $destination = $uploadServerDir . $uniqueName;

                        if (move_uploaded_file($file["tmp_name"], $destination)) {
                            $oldPoster = $event["poster"];
                            $posterPath = $uploadRelDir . $uniqueName;

                            // Safely delete previous poster file if it exists and is within uploads/event_posters/
                            if (!empty($oldPoster)) {
                                $oldFullPath = dirname(__DIR__) . "/" . $oldPoster;
                                $realUploads = realpath($uploadServerDir);
                                $realOld = realpath($oldFullPath);
                                if ($realOld && strpos($realOld, $realUploads) === 0 && file_exists($oldFullPath)) {
                                    // Do not delete default seeded posters so fresh tests can still reference them
                                    if (!strpos($oldPoster, "poster_hackathon") && !strpos($oldPoster, "poster_symposium") && !strpos($oldPoster, "poster_ai_expo") && !strpos($oldPoster, "poster_cybersecurity") && !strpos($oldPoster, "poster_tarang")) {
                                        @unlink($oldFullPath);
                                    }
                                }
                            }
                        } else {
                            $error = "Failed to upload new poster to server.";
                        }
                    }
                }
            }
        }

        // Update database record if validation succeeded
        if (empty($error)) {
            $upStmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, poster = ? WHERE id = ?");
            if ($upStmt) {
                $upStmt->bind_param("ssssi", $title, $description, $eventDate, $posterPath, $eventId);
                if ($upStmt->execute()) {
                    $upStmt->close();

                    // Practical 14: Log administrative action
                    log_audit_action($conn, (int)($_SESSION["user_id"] ?? 0), "UPDATE_EVENT", "Updated event #$eventId ($title)", $eventId);

                    header("Location: events.php?msg=" . urlencode("Event '$title' (ID #$eventId) updated successfully!"));
                    exit;
                } else {
                    $error = "Database update error: " . $conn->error;
                    $upStmt->close();
                }
            } else {
                $error = "Query preparation failed: " . $conn->error;
            }
        }
    }

    $event["title"] = $title;
    $event["description"] = $description;
    $event["event_date"] = $eventDate;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event #<?php echo (int)$event['id']; ?> - The Backbenchers University</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <!-- Header Section -->
    <header>
        <div class="container header-inner">
            <a href="../index.html" class="header-brand">
                <img src="../images/logo.svg" alt="University Logo" class="header-logo">
                <div class="header-title">
                    <h1>THE BACKBENCHERS UNIVERSITY</h1>
                    <p>Office of Academic Administration</p>
                </div>
            </a>
            <div class="header-quick-info">
                <span class="badge-tag">👤 <?php echo $adminName; ?></span>
                <span class="session-timer-badge" id="session-timer-badge">
                    ⏱️ Session: <span id="session-timer-clock" data-remaining="<?php echo $session_remaining_seconds ?? 900; ?>">14:59</span>
                </span>
                <a href="../php/logout.php" style="background:#ef4444; color:#fff; padding:6px 14px; border-radius:4px; text-decoration:none; font-weight:600; font-size:12px;">
                    🔒 Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav>
        <div class="container nav-container">
            <div class="nav-brand-label"><span>Admin Menu</span></div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="../admin_dashboard.php">Admin Dashboard</a></li>
                    <li><a href="students.php">Student Management</a></li>
                    <li><a href="events.php" class="active">Event Management</a></li>
                    <li><a href="../events.php">Public Events</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container" style="margin-top: var(--space-lg); margin-bottom: var(--space-xl); max-width: 760px;">

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h2>✏️ Edit Event Details (ID #<?php echo (int)$event['id']; ?>)</h2>
                    <p style="color: var(--text-muted); font-size: var(--font-xs); margin-top: 4px;">
                        Update campus event details and optional poster image
                    </p>
                </div>
                <a href="events.php" class="btn btn-outline btn-sm">&laquo; Back to Events</a>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background-color: var(--danger-bg); border-left: 4px solid var(--danger); color: var(--danger); padding: var(--space-md); margin-bottom: var(--space-md); border-radius: var(--radius-sm);">
                    <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="event_edit.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>">

                <div class="d-flex flex-col gap-md">
                    <div class="grid-2-col" style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-md);">
                        <div class="form-group">
                            <label for="title">Event Title <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="event_date">Event Date <span style="color: var(--danger);">*</span></label>
                            <input type="date" id="event_date" name="event_date" class="form-control" value="<?php echo htmlspecialchars($event['event_date']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Event Description <span style="color: var(--danger);">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>

                    <!-- Current Poster Preview -->
                    <div class="form-group">
                        <label>Current Event Poster:</label>
                        <div style="margin-top: 6px;">
                            <?php if (!empty($event["poster"]) && file_exists(dirname(__DIR__) . "/" . $event["poster"])): ?>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="../<?php echo htmlspecialchars($event['poster']); ?>" alt="Current Poster" style="width: 140px; height: 85px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    <span style="font-size: var(--font-xs); color: var(--text-muted);">
                                        <code><?php echo htmlspecialchars($event['poster']); ?></code>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-warning">No poster currently uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Replace Poster Upload -->
                    <div class="form-group">
                        <label for="poster">Upload Replacement Poster (Leave empty to keep current poster):</label>
                        <input type="file" id="poster" name="poster" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small style="color: var(--text-muted); font-size: 11px;">
                            Accepts JPG, PNG, WebP up to 2 MB. If no file is selected, the existing poster is preserved.
                        </small>
                    </div>

                    <div class="d-flex justify-between align-center" style="border-top: 1px solid var(--border-color); padding-top: var(--space-md); margin-top: var(--space-sm);">
                        <a href="events.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-md">💾 Save Event Changes</button>
                    </div>
                </div>
            </form>
        </div>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container footer-bottom">
            <p>&copy; 2026 The Backbenchers University &bull; Office of Academic Affairs.</p>
        </div>
    </footer>

    <script src="../script.js"></script>
    <script src="../session_timer.js"></script>
</body>
</html>
