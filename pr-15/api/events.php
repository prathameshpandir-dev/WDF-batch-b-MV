<?php
// ========================================================
// Practical 13: RESTful PHP JSON API for Event CRUD
// File: api/events.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Features: JSON Responses, HTTP Status Codes, MySQLi Prepared Statements,
//           Poster Upload with MIME & Size Validation, RBAC & Audit Logging.
// Supported Methods: GET (List/Read), POST (Create),
//                    PUT/POST (Update), DELETE (Remove)
// ========================================================

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/php/audit_helper.php";

function sendJsonResponse($statusCode, $success, $message, $data = null) {
    http_response_code($statusCode);
    $payload = [
        "success" => $success,
        "message" => $message
    ];
    if ($data !== null) {
        $payload["data"] = $data;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Check Database Connection
if (!$conn || $conn->connect_error) {
    sendJsonResponse(500, false, "Database connection failure: " . ($conn ? $conn->connect_error : "Unavailable"));
}

// 2. Strict Role-Based Access Control
if (empty($_SESSION["bbu_logged_in"]) || empty($_SESSION["user_id"])) {
    sendJsonResponse(401, false, "Authentication required. Please log in first.");
}

$currentRole = strtolower(trim($_SESSION["user_role"] ?? ""));
if ($currentRole !== "admin") {
    sendJsonResponse(403, false, "Access Denied: Administrator role required to manage events.");
}

$adminId = (int)$_SESSION["user_id"];

// 3. Resolve Request Method & Parse Input
$method = strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");

$rawInput = file_get_contents("php://input");
$inputData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}
if (!empty($_POST)) {
    $inputData = array_merge($inputData, $_POST);
}

if (isset($_GET["_method"])) {
    $method = strtoupper(trim($_GET["_method"]));
} elseif (isset($inputData["_method"])) {
    $method = strtoupper(trim($inputData["_method"]));
}

$uploadRelDir = "uploads/event_posters/";
$uploadServerDir = dirname(__DIR__) . "/" . $uploadRelDir;

// ========================================================
// METHOD: GET (List all events or single event)
// ========================================================
if ($method === "GET") {
    // Single event
    if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
        $eventId = (int)$_GET["id"];
        $stmt = $conn->prepare("SELECT id, title, description, event_date, poster FROM events WHERE id = ? LIMIT 1");
        if (!$stmt) {
            sendJsonResponse(500, false, "Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $res = $stmt->get_result();
        $event = $res->fetch_assoc();
        $stmt->close();

        if ($event) {
            sendJsonResponse(200, true, "Event retrieved successfully.", $event);
        } else {
            sendJsonResponse(404, false, "Event with ID #$eventId not found.");
        }
    }

    // List events with search
    $search = trim($_GET["search"] ?? "");
    $whereSql = "1=1";
    $params = [];
    $types = "";

    if ($search !== "") {
        $whereSql .= " AND (title LIKE ? OR description LIKE ?)";
        $sw = "%" . $search . "%";
        $params[] = $sw;
        $params[] = $sw;
        $types .= "ss";
    }

    $stmt = $conn->prepare("SELECT id, title, description, event_date, poster FROM events WHERE $whereSql ORDER BY event_date ASC, id DESC");
    if (!$stmt) {
        sendJsonResponse(500, false, "Query preparation failed: " . $conn->error);
    }

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

    sendJsonResponse(200, true, "Events retrieved successfully.", [
        "count" => count($events),
        "events" => $events
    ]);
}

// ========================================================
// METHOD: POST (Create Event) or PUT (Update via _method=PUT / action=update)
// ========================================================
elseif ($method === "POST" || $method === "PUT") {
    $action = $inputData["action"] ?? "";
    $isUpdate = ($method === "PUT" || $action === "update" || $action === "update_event" || !empty($inputData["id"]) && $method !== "POST_CREATE");

    // --- UPDATE EVENT ---
    if ($isUpdate) {
        $eventId = (int)($inputData["id"] ?? $_GET["id"] ?? 0);
        if ($eventId <= 0) {
            sendJsonResponse(400, false, "Valid event ID is required for update.");
        }

        // Fetch existing event
        $checkStmt = $conn->prepare("SELECT id, title, poster FROM events WHERE id = ? LIMIT 1");
        if (!$checkStmt) {
            sendJsonResponse(500, false, "Database error: " . $conn->error);
        }
        $checkStmt->bind_param("i", $eventId);
        $checkStmt->execute();
        $existRes = $checkStmt->get_result();
        $existingEvent = $existRes->fetch_assoc();
        $checkStmt->close();

        if (!$existingEvent) {
            sendJsonResponse(404, false, "Event with ID #$eventId not found.");
        }

        $title = trim($inputData["title"] ?? "");
        $description = trim($inputData["description"] ?? "");
        $eventDate = trim($inputData["event_date"] ?? "");
        $posterPath = $existingEvent["poster"];

        if ($title === "" || strlen($title) < 3) {
            sendJsonResponse(400, false, "Event title is required (minimum 3 characters).");
        }
        if ($description === "" || strlen($description) < 10) {
            sendJsonResponse(400, false, "Event description is required (minimum 10 characters).");
        }
        if ($eventDate === "") {
            sendJsonResponse(400, false, "Please select a valid event date.");
        }

        // Handle Poster File Upload if provided
        if (isset($_FILES["poster"]) && $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES["poster"];
            if ($file["error"] !== UPLOAD_ERR_OK) {
                sendJsonResponse(400, false, "File upload failed with error code: " . $file["error"]);
            }
            if ($file["size"] > 2 * 1024 * 1024) {
                sendJsonResponse(400, false, "Poster size must not exceed 2 MB.");
            }

            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $allowedExts = ["jpg", "jpeg", "png", "webp"];
            if (!in_array($ext, $allowedExts)) {
                sendJsonResponse(400, false, "Please upload a valid JPG, PNG, or WebP image.");
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file["tmp_name"]);
            finfo_close($finfo);

            $allowedMimes = ["image/jpeg", "image/png", "image/webp"];
            if (!in_array($mime, $allowedMimes)) {
                sendJsonResponse(400, false, "Please upload a valid JPG, PNG, or WebP image.");
            }

            if (!is_dir($uploadServerDir)) {
                mkdir($uploadServerDir, 0777, true);
            }
            $uniqueName = "poster_" . bin2hex(random_bytes(8)) . "_" . time() . "." . $ext;
            $destination = $uploadServerDir . $uniqueName;

            if (move_uploaded_file($file["tmp_name"], $destination)) {
                $oldPoster = $existingEvent["poster"];
                $posterPath = $uploadRelDir . $uniqueName;

                // Clean up old custom poster safely
                if (!empty($oldPoster)) {
                    $oldFullPath = dirname(__DIR__) . "/" . $oldPoster;
                    $realUploads = realpath($uploadServerDir);
                    $realOld = realpath($oldFullPath);
                    if ($realOld && $realUploads && strpos($realOld, $realUploads) === 0 && file_exists($oldFullPath)) {
                        if (!strpos($oldPoster, "poster_hackathon") && !strpos($oldPoster, "poster_symposium") && !strpos($oldPoster, "poster_ai_expo") && !strpos($oldPoster, "poster_cybersecurity") && !strpos($oldPoster, "poster_tarang")) {
                            @unlink($oldFullPath);
                        }
                    }
                }
            } else {
                sendJsonResponse(500, false, "Failed to save uploaded poster file.");
            }
        }

        $upStmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, poster = ? WHERE id = ?");
        if (!$upStmt) {
            sendJsonResponse(500, false, "Query preparation failed: " . $conn->error);
        }
        $upStmt->bind_param("ssssi", $title, $description, $eventDate, $posterPath, $eventId);

        if ($upStmt->execute()) {
            $upStmt->close();
            log_audit_action($conn, $adminId, "UPDATE_EVENT", "Updated event #$eventId ($title)", $eventId);

            sendJsonResponse(200, true, "Event '$title' (ID #$eventId) updated successfully.", [
                "id" => $eventId,
                "title" => $title,
                "description" => $description,
                "event_date" => $eventDate,
                "poster" => $posterPath
            ]);
        } else {
            $err = $conn->error;
            $upStmt->close();
            sendJsonResponse(500, false, "Database update error: " . $err);
        }
    }

    // --- CREATE NEW EVENT ---
    else {
        $title = trim($inputData["title"] ?? "");
        $description = trim($inputData["description"] ?? "");
        $eventDate = trim($inputData["event_date"] ?? "");
        $posterPath = null;

        if ($title === "" || strlen($title) < 3) {
            sendJsonResponse(400, false, "Event title is required (minimum 3 characters).");
        }
        if ($description === "" || strlen($description) < 10) {
            sendJsonResponse(400, false, "Event description is required (minimum 10 characters).");
        }
        if ($eventDate === "") {
            sendJsonResponse(400, false, "Please select a valid event date.");
        }

        // Poster upload validation
        if (isset($_FILES["poster"]) && $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES["poster"];
            if ($file["error"] !== UPLOAD_ERR_OK) {
                sendJsonResponse(400, false, "File upload failed with error code: " . $file["error"]);
            }
            if ($file["size"] > 2 * 1024 * 1024) {
                sendJsonResponse(400, false, "Poster size must not exceed 2 MB.");
            }

            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $allowedExts = ["jpg", "jpeg", "png", "webp"];
            if (!in_array($ext, $allowedExts)) {
                sendJsonResponse(400, false, "Please upload a valid JPG, PNG, or WebP image.");
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file["tmp_name"]);
            finfo_close($finfo);

            $allowedMimes = ["image/jpeg", "image/png", "image/webp"];
            if (!in_array($mime, $allowedMimes)) {
                sendJsonResponse(400, false, "Please upload a valid JPG, PNG, or WebP image.");
            }

            if (!is_dir($uploadServerDir)) {
                mkdir($uploadServerDir, 0777, true);
            }
            $uniqueName = "poster_" . bin2hex(random_bytes(8)) . "_" . time() . "." . $ext;
            $destination = $uploadServerDir . $uniqueName;

            if (move_uploaded_file($file["tmp_name"], $destination)) {
                $posterPath = $uploadRelDir . $uniqueName;
            } else {
                sendJsonResponse(500, false, "Failed to save uploaded poster to server.");
            }
        }

        $insStmt = $conn->prepare("INSERT INTO events (title, description, event_date, poster) VALUES (?, ?, ?, ?)");
        if (!$insStmt) {
            sendJsonResponse(500, false, "Query preparation failed: " . $conn->error);
        }

        $insStmt->bind_param("ssss", $title, $description, $eventDate, $posterPath);
        if ($insStmt->execute()) {
            $newEventId = $insStmt->insert_id;
            $insStmt->close();

            log_audit_action($conn, $adminId, "ADD_EVENT", "Created event '$title' ($eventDate)", $newEventId);

            sendJsonResponse(201, true, "Event '$title' (ID #$newEventId) added successfully.", [
                "id" => $newEventId,
                "title" => $title,
                "description" => $description,
                "event_date" => $eventDate,
                "poster" => $posterPath
            ]);
        } else {
            $err = $conn->error;
            $insStmt->close();
            sendJsonResponse(500, false, "Database insert failed: " . $err);
        }
    }
}

// ========================================================
// METHOD: DELETE (Delete Event)
// ========================================================
elseif ($method === "DELETE") {
    $eventId = (int)($inputData["id"] ?? $_GET["id"] ?? 0);
    if ($eventId <= 0) {
        sendJsonResponse(400, false, "Valid event ID is required for deletion.");
    }

    $stmt = $conn->prepare("SELECT id, title, poster FROM events WHERE id = ? LIMIT 1");
    if (!$stmt) {
        sendJsonResponse(500, false, "Database error: " . $conn->error);
    }
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $res = $stmt->get_result();
    $event = $res->fetch_assoc();
    $stmt->close();

    if (!$event) {
        sendJsonResponse(404, false, "Event with ID #$eventId not found or already deleted.");
    }

    $eventTitle = $event["title"];
    $posterRelPath = $event["poster"];

    $delStmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    if (!$delStmt) {
        sendJsonResponse(500, false, "Failed to prepare delete statement: " . $conn->error);
    }
    $delStmt->bind_param("i", $eventId);

    if ($delStmt->execute()) {
        $delStmt->close();

        // Safe poster cleanup
        if (!empty($posterRelPath)) {
            $uploadBase = realpath(dirname(__DIR__) . "/uploads/event_posters");
            $fileFullPath = dirname(__DIR__) . "/" . $posterRelPath;
            $realFile = realpath($fileFullPath);
            if ($realFile && $uploadBase && strpos($realFile, $uploadBase) === 0 && file_exists($fileFullPath)) {
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

        log_audit_action($conn, $adminId, "DELETE_EVENT", "Deleted event #$eventId ($eventTitle)", $eventId);

        sendJsonResponse(200, true, "Event '$eventTitle' (ID #$eventId) deleted successfully.", [
            "id" => $eventId
        ]);
    } else {
        $err = $conn->error;
        $delStmt->close();
        sendJsonResponse(500, false, "Database delete error: " . $err);
    }
} else {
    sendJsonResponse(405, false, "HTTP Method '$method' not allowed.");
}
?>
