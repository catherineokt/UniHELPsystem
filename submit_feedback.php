<?php
session_start();
require_once "db.php"; // <-- 1. Fixed: Requires your friend's connection file (db.php)

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
    $rating     = intval($_POST['rating'] ?? 4);
    $comments   = trim($_POST['comments'] ?? '');
    $feedback_title = "Session Feedback"; // 2. Default value for the database 'feedback' column

    if (!$session_id || empty($comments)) {
        echo json_encode(["status" => "error", "message" => "All required fields must be filled."]);
        exit();
    }

    // Insert matching your exact database columns: session_id, feedback, overall_rating, comments
    $stmt = $con->prepare("INSERT INTO feedback (session_id, feedback, overall_rating, comments) VALUES (?, ?, ?, ?)");
    
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "SQL Prepare failed: " . $con->error]);
        exit();
    }

    // Types: i = int, s = string, i = int, s = string -> "isis"
    $stmt->bind_param("isis", $session_id, $feedback_title, $rating, $comments);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Feedback saved successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
    }

    $stmt->close();
    $con->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>