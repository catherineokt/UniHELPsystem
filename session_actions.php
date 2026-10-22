<?php
// Handles: Cancel / Reschedule (student + volunteer) and Mark Completed (volunteer only)
session_start();

const TEST_MODE = true; // set to false once login.php sets $_SESSION['student_id'] / ['volunteer_id']
 
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: studentpage.html");
    exit;
}
 
$role = ($_POST["role"] ?? "") === "volunteer" ? "volunteer" : "student";
$back = $role === "volunteer" ? "volunteer_calendar_session.php" : "student_calander_session.php";
 
function finish(string $back, string $msg, bool $ok): void {
    $_SESSION["flash"] = ["ok" => $ok, "msg" => $msg];
    header("Location: " . $back);
    exit;
}
 
if (!hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")) {
    finish($back, "Invalid form token. Please refresh the page and try again.", false);
}
 
if ($role === "volunteer") {
    $user_id = $_SESSION["volunteer_id"] ?? (TEST_MODE ? "VOL001" : null);
} else {
    $user_id = $_SESSION["student_id"] ?? (TEST_MODE ? "STD001" : null);
}
if (!$user_id) {
    finish($back, "Please log in first.", false);
}
 
$session_id = filter_input(INPUT_POST, "session_id", FILTER_VALIDATE_INT);
$action     = $_POST["action"] ?? "";
 
if (!$session_id || !in_array($action, ["cancel", "complete", "reschedule"], true)) {
    finish($back, "Invalid request.", false);
}
if ($action === "complete" && $role !== "volunteer") {
    finish($back, "Only the volunteer can mark a session as completed.", false);
}
 
$msg = "";
$ok  = false;
 
try {
    $con->begin_transaction();
 
    // Lock the session row and load it
    $stmt = $con->prepare(
        "SELECT ss.session_id, ss.volunteer_id, ss.date, ss.time, ss.status,
                sr.student_id,
                (TIMESTAMP(ss.date, ss.time) <= NOW()) AS started
         FROM support_session ss
         JOIN support_request sr ON sr.request_id = ss.request_id
         WHERE ss.session_id = ?
         FOR UPDATE"
    );
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
 
    // Only the student or volunteer of this session may act on it
    $owns = $row && (
        ($role === "student"   && $row["student_id"]   === $user_id) ||
        ($role === "volunteer" && $row["volunteer_id"] === $user_id)
    );
    if (!$owns) {
        throw new RuntimeException("Session not found.");
    }
    if (!in_array($row["status"], ["Scheduled", "Rescheduled"], true)) {
        throw new RuntimeException("This session is already " . strtolower($row["status"]) . ".");
    }
 
    if ($action === "cancel") {
        $stmt = $con->prepare("UPDATE support_session SET status = 'Cancelled' WHERE session_id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Session cancelled.";
 
    } elseif ($action === "complete") {
        if (!(int)$row["started"]) {
            throw new RuntimeException("You can only mark a session as completed after its start time.");
        }
        $stmt = $con->prepare("UPDATE support_session SET status = 'Completed' WHERE session_id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Session marked as completed. The student can now give feedback.";
 
    } else { // reschedule
        $newDate = trim($_POST["new_date"] ?? "");
        $newTime = trim($_POST["new_time"] ?? "");
 
        $d = DateTime::createFromFormat("Y-m-d", $newDate);
        if (!$d || $d->format("Y-m-d") !== $newDate) {
            throw new RuntimeException("Please choose a valid new date.");
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $newTime)) {
            throw new RuntimeException("Please choose a valid new time.");
        }
        if (strlen($newTime) === 5) {
            $newTime .= ":00";
        }
 
        // Must be in the future
        $stmt = $con->prepare("SELECT (TIMESTAMP(?, ?) > NOW()) AS future");
        $stmt->bind_param("ss", $newDate, $newTime);
        $stmt->execute();
        $future = (int)$stmt->get_result()->fetch_assoc()["future"];
        $stmt->close();
        if (!$future) {
            throw new RuntimeException("The new date and time must be in the future.");
        }
 
        // Volunteer must be free
        $stmt = $con->prepare(
            "SELECT 1 FROM support_session
             WHERE volunteer_id = ? AND date = ? AND time = ?
               AND status IN ('Scheduled','Rescheduled') AND session_id <> ? LIMIT 1"
        );
        $stmt->bind_param("sssi", $row["volunteer_id"], $newDate, $newTime, $session_id);
        $stmt->execute();
        $clash = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($clash) {
            throw new RuntimeException("The volunteer already has a session at that time.");
        }
 
        // Student must be free
        $stmt = $con->prepare(
            "SELECT 1 FROM support_session ss
             JOIN support_request sr ON sr.request_id = ss.request_id
             WHERE sr.student_id = ? AND ss.date = ? AND ss.time = ?
               AND ss.status IN ('Scheduled','Rescheduled') AND ss.session_id <> ? LIMIT 1"
        );
        $stmt->bind_param("sssi", $row["student_id"], $newDate, $newTime, $session_id);
        $stmt->execute();
        $clash = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($clash) {
            throw new RuntimeException("The student already has a session at that time.");
        }
 
        $stmt = $con->prepare(
            "UPDATE support_session SET date = ?, time = ?, status = 'Rescheduled' WHERE session_id = ?"
        );
        $stmt->bind_param("ssi", $newDate, $newTime, $session_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Session rescheduled.";
    }
 
    $con->commit();
    $ok = true;
 
} catch (mysqli_sql_exception $e) {
    // Database problem (must be caught BEFORE RuntimeException, because
    // mysqli_sql_exception extends RuntimeException). Log it, never show raw SQL errors.
    try { $con->rollback(); } catch (Throwable $x) {}
    error_log($e->getMessage());
    $msg = "Unable to update the session. Please try again.";
} catch (RuntimeException $e) {
    // Our own friendly messages (not found, already cancelled, time clash, ...)
    try { $con->rollback(); } catch (Throwable $x) {}
    $msg = $e->getMessage();
} catch (Throwable $e) {
    try { $con->rollback(); } catch (Throwable $x) {}
    error_log($e->getMessage());
    $msg = "Unable to update the session. Please try again.";
}
 
finish($back, $msg, $ok);