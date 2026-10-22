<?php
// ========================================================
// Practical 14: Centralized Administrative Action Audit Logger
// File: php/audit_helper.php
// College: The Backbenchers University (B.Tech IT - 2nd Year)
// Purpose: Record security and data modification events
//          (Admin ID, Action, Description, Record ID, Timestamp).
// ========================================================

/**
 * Record an administrative action into the audit_logs table.
 *
 * @param mysqli $conn Active database connection
 * @param int $admin_id Authenticated admin's user ID
 * @param string $action Short action code (e.g. ADD_STUDENT, UPDATE_STUDENT, DELETE_STUDENT)
 * @param string $description Safe human-readable description (NO passwords or tokens)
 * @param int|null $record_id Target record ID affected
 * @return bool True if logged successfully, false otherwise
 */
function log_audit_action($conn, $admin_id, $action, $description, $record_id = null) {
    if (!$conn || $conn->connect_error) {
        return false;
    }

    $admin_id = (int)$admin_id;
    if ($admin_id <= 0) {
        return false;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, action, description, record_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("issi", $admin_id, $action, $description, $record_id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
    } catch (Exception $e) {
        error_log("Audit log recording error: " . $e->getMessage());
        return false;
    }

    return false;
}
