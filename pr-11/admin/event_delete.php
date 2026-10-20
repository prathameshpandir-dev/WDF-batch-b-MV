<?php
// ========================================================
// Practical 12: Delete Campus Event Record (Admin Only)
// File: admin/event_delete.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: POST-Only Enforcement, Prepared Delete Statement,
//           Foreign Key Registration Cascade, and Safe Poster Removal.
// ========================================================

require_once dirname(__DIR__) . "/php/auth_check.php";
check_auth("admin");

// 1. Strictly enforce POST request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: events.php?error=" . urlencode("Invalid request method. Deletion must use POST."));
    exit;
}

// 2. Validate event ID
$eventId = (int)($_POST["event_id"] ?? 0);
if ($eventId <= 0) {
    header("Location: events.php?error=" . urlencode("Invalid event ID."));
    exit;
}

// 3. Retrieve event information to access poster path before deletion
$stmt = $conn->prepare("SELECT id, title, poster FROM events WHERE id = ? LIMIT 1");
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
    header("Location: events.php?error=" . urlencode("Event record not found or already deleted."));
    exit;
}

$eventTitle = $event["title"];
$posterRelPath = $event["poster"];

// 4. Delete event from database using MySQLi Prepared Statement
// (Registrations table has ON DELETE CASCADE constraint)
$delStmt = $conn->prepare("DELETE FROM events WHERE id = ?");
if (!$delStmt) {
    header("Location: events.php?error=" . urlencode("Failed to prepare delete statement: " . $conn->error));
    exit;
}

$delStmt->bind_param("i", $eventId);

if ($delStmt->execute()) {
    $delStmt->close();

    // 5. Clean up uploaded poster file safely if it is within uploads/event_posters/
    if (!empty($posterRelPath)) {
        $uploadBase = realpath(dirname(__DIR__) . "/uploads/event_posters");
        $fileFullPath = dirname(__DIR__) . "/" . $posterRelPath;
        $realFile = realpath($fileFullPath);

        // Security: Ensure path is strictly inside uploads/event_posters to prevent directory traversal
        if ($realFile && $uploadBase && strpos($realFile, $uploadBase) === 0 && file_exists($fileFullPath)) {
            // Keep default seeded posters safe for test repeatability
            $isDefaultPoster = (strpos($posterRelPath, "poster_hackathon") !== false ||
                                strpos($posterRelPath, "poster_symposium") !== false ||
                                strpos($posterRelPath, "poster_ai_expo") !== false ||
                                strpos($posterRelPath, "poster_cybersecurity") !== false ||
                                strpos($posterRelPath, "poster_tarang") !== false);

            if (!$isDefaultPoster) {
                @unlink($fileFullPath);
            }
        }
    }

    header("Location: events.php?msg=" . urlencode("Event '$eventTitle' (ID #$eventId) deleted successfully."));
    exit;
} else {
    $err = $conn->error;
    $delStmt->close();
    header("Location: events.php?error=" . urlencode("Failed to delete event: " . $err));
    exit;
}
?>
