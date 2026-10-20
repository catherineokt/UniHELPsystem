<?php
session_start();
require_once "db.php";

include "volheader.php";
if(empty($_SESSION["volunteer_id"])){
    exit("Please log in as a volunteer first.");
}

//replace the authenticated volunteer's ID
$volunteer_id = trim((string)$_SESSION["volunteer_id"]);
$message = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $match_id = filter_input(INPUT_POST, "match_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";
    if(!$matched_id || $match_id < 1 || ! in_array($action, ["Accept", "Reject"], true)){
        $message = "Invalid request action.";
    }else{
        try{
            $con->beginTransaction();
            //only the assigned volunteer can respond
            $stmt = $con->prepare(
                "SELECT rm.match_id, rm.request_id, rm.matchStatus, sr.status AS requestStatus,
                    sr.preferredDate, sr.preferredTime, s.supportMode 
                FROM request_match rm 
                JOIN support_request sr ON sr.request_id = rm.request_id
                WHERE rm.match_id = ? AND rm.volunteer_id = ? FOR UPDATE"
            );

             $stmt->bind_param("is", $matchId, $volunteerId);
            $stmt->execute();

            $match = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if(!$match){
                throw new RuntimeException(
                    "This request was not found from your account."
                );
            }
            if($match["matchStatus"] !== "Pending" || $match["requestStatus"] !== "Pending"){
                throw new RuntimeException(
                    "This request has already been processed."
                );
            }

            $request_id = (int)$match["request_id"];
            if($action === "Accept"){
                //update match status
                $stmt = $con->prepare(
                    "UPDATE request_match SET matchStatus = 'Accepted' 
                     WHERE matched_id = ? AND volunteer_id = ? AND matchStatus = 'Pending'"
                );
                $stmt->bind_param("is", $match_id, $volunteer_id);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    $stmt->close();
                    throw new RuntimeException(
                        "The match could not be accepted."
                    );
                }
                $stmt->close();

                //update the main request status
                $stmt = $con->prepare(
                    "UPDATE support_request SET status = 'Accepted' WHERE request_id = ? AND status = 'Pending'"
                );
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    $stmt->close();
                    throw new RuntimeException(
                        "The request has already been processed."
                    );
                }
                $stmt->close();

                //avoid creating duplicate sessions for request
                $stmt = $con->prepare(
                    "SELECT session_id FROM support_session
                    WHERE request_id = ? LIMIT 1"
                );
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $existingSession = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if(!$existingSession){
                    $session_id = $next_id(
                        $con, "support_session", "session_id"
                    );
                    $sessionDate = $match["preferredDate"];
                    $sessionTime = $match["preferredTime"];
                    $supportMode = $match["supportMode"];
                    $stmt = $con->prepare(
                        "INSERT INTO support_session(session_id, request_id, volunteer_id, date, time, supportMode, status)
                        VALUES (?, ?, ?, ?, ?, ?, 'Scheduled') "
                    );
                    $stmt->bind_param(
                        "iissss", $session_id, $request_id, $volunteer_id, $sessionDate, $sessionTime, $supportMode
                    );
                    $stmt->execute();
                    $stmt->close();
                }
                $message = "Request accepted successfully.";
            }else{
                //reject the match
                $stmt = $con->prepare(
                    "UPDATE request_match SET matchStatus = 'Rejected' 
                     WHERE match_id = ?
                      AND volunteer_id = ?
                      AND matchStatus = 'Pending'"
                );
                $stmt->bind_param("is", $match_id, $volunteer_id);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    $stmt->close();
                    throw new RuntimeException(
                        "The match could not be rejected."
                    );
                }
                $stmt->close();

                //reject the request
                $stmt = $con->prepare(
                    "UPDATE support_request SET status = 'Rejected' 
                     WHERE request_id = ? AND status = 'Pending'"
                );
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    $stmt->close();
                    throw new RuntimeException(
                        "The request has already been processed."
                    );
                }
                $stmt->close();

                $message = "Request rejected successfully.";
            }
            $con->commit();
        }catch (Throwable $e){
            if ($con->errno === 0) {
                // The transaction may completed.
                // rollback() is still safe to attempt when active.
            }
            try {
                $con->rollback();
            } catch (Throwable $rollbackError) {
                // Do not expose database errors to the user.
            }
            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : "Unable to process this request. Please try again.";
        }
    }
}

//display incoming request for volunteer
$stmt = $con->prepare(
    "SELECT rm.match_id, rm.match_status, sr.request_id, sr.category, sr.description, sr.preferredDate, sr.preferredTime, 
        sr.supportMode, sr.status AS requestStatus, s.username AS studentName, s.email AS studentEmail
    FROM request_match rm
    JOIN support_request sr ON sr.request_id = rm.request_id
    JOIN student s ON s.student_id = r.student_id
    WHERE rm.volunteer_id = ?
    ORDER BY rm.createAt DESC"
);
$stmt->bind_param("s", $volunteer_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include "volheader.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Incoming Request</title>
</head>
<body>
    <h1>Incoming Support Request</h1>

    <?php if($message !== ""): ?>
        <p><?= h($message) ?></p>
    <?php endif; ?>
    <?php if (!$requests): ?>
        <p>You have no incoming requests.</p>
    <?php else: ?>
        <?php foreach ($requests as $request): ?>
           <h3>Request #<?= h($request["request_id"]) ?></h3>
           <p>Student: <?= h($request["studentName"]) ?></p>
           <p>Email: <?= h($request["Email"]) ?></p>
           <p>Category: <?= h($request["category"]) ?></p>
           <p>Description: <?= h($request["description"]) ?></p>
           <p>Date: <?= h($request["preferredDate"]) ?></p>
           <p>Time: <?= h($request["preferredTime"]) ?></p>
           <p>Support Mode: <?= h($request["supportMode"]) ?></p>
           <p>Request Status: <?= h($request["requestStatus"]) ?></p>
           <p>Match Status: <?= h($request["matchStatus"]) ?></p>

            <?php if($request["requestStatus"] === "Pending" && $request["matchStatus"] === "Pending"): ?>
                <form method="POST">
                    <input type="hidden" name="match_id" value="<?= h($request["match_id"]) ?>">
                    <button type="submit" name="action" value="Accept">Accept</button>
                    <button type="submit" name="action" value="Reject">Reject</button>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>