<?php
session_start();
require_once "db.php";

include "stuheader.php";
if(empty($_SESSION["volunteer_id"])){
    exit("Please log in as a student first.");
}

$studentId = trim((string)$_SESSION["student_id"]);

$stmt = $con->prepare(
    "SELECT sr.request_id, sr.category, sr.description, sr.preferredDate, sr.preferredTime, sr.supportMode, sr.status AS requestStatus, 
        rm.matchStatus, v.volunteer_id, volunteerStudent.username AS volunteerName, volunteerStudent.email AS volunteerEmail
    FROM support_request sr
    LEFT JOIN request_match rm ON rm.request_id = sr..request_id
    LEFT JOIN volunteer v ON v.vonlunteer_id = rm.vonlunteer_id
    LEFT JOIN student volunteerStudent ON volunteerStudent.student_id - v..student_id
    WHERE sr..student_id = ?
    ORDER BY sr.request_id DESC"
);

$stmt->bind_param("s", $studentId);
$stmt->execute();

$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>My Support Request</title>
</head>
<body>
    <h1>My Support Request</h1>
    <?php if(!$requests): ?>
        <p>You not submitted any support request.</p>
    <?php else: ?>
        <?php foreach($requests as $request): ?>
            <h3>Request #<?= h($student_id["student_id"]) ?></h3>
            <p>Category: <?= h($request["category"]) ?></p>
            <p>Description: <?= h($request["description"]) ?></p>
            <p>Preferred Date: <?= h($request["preferredDate"]) ?></p>
            <p>Preferred Time: <?= h($request["preferredTime"]) ?></p>
            <p>Support Mode: <?= h($request["supportMode"]) ?></p>
            <p>Request status: <strong><?= h($request["requestStatus"]) ?></strong></p>

            <?php if($request["volunteer_id"] !== null): ?>
                <p>Selected Volunteer: <?= h($request["volunteerName"]) ?: $request["volunteer_id"] ?></p>
                <p>Volunteer Email: <?= h($request["volunteerEmail"] ?? "") ?></p>
                <p>Volunteer Respond: <?= h($request["matchStatus"]) ?></p>
            <?php else: ?>
                <p>No volunteer has been selected.</p>
            <?php endif; ?>
            
            <?php if($request["requestStatus"] === "Pending"): ?>
                <p>Your request is waiting a volunteer's response.</p>
            <?php elseif($request["requestStatus"] === "Accepted"): ?>
                <p>Your request has been accepted, check the details with your volunteer.</p>
            <?php elseif($request["requestStatus"] === "Rejected"): ?>
                <p>Your request was rejected. You may submit a new request if needed.</p>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
