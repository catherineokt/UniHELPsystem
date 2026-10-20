<?php 
require_once "db.php";

include "stuheader.php";
if(empty($_SESSION["student_id"])){
    exit("Please log in as student first.");
}

$student_id = trim((string)$_SESSION["student_id"]);
$message = "";

$request_id = filter_input(
    INPUT_GET, "request_id", FILTER_VALIDATE_INT
);

if(!$request_id || $request_id < 1){
    exit("Invalid request ID.");
}

//to make sure the request is blong to login student
$stmt = $con->prepare(
    "SELECT * FROM support_request WHERE student_id = ? AND student_id = ?"
);
$stmt->bind_param("is", $request_id, $studentId);
$stmt->execute();

$request = $stmt->get_result()->fetch_assoc();
$stmt->close()

if(!$request){
    exit("Request not found.");
}

//process volunteer selection
if($_SERVER["REQUEST_METHOD"] === "POST"){
    $volunteer_id = trim((string)($_POST["volunteer_id"] ?? ""));
    if($request["status"] !== "Pending"){
        $message = "This request not pending.";
    }elseif($volunteer_id === ""){
        $message = "Please select volunteer.";
    }else{
        try {
            $con->begin_transaction();

            // lock the request while processing the selection
            $lock = $con->prepare(
                "SELECT status FROM support_request WHERE request_id = ? AND student_id = ? FOR UPDATE"
            );
            $lock->bind_param("is", $request_id, $studentId);
            $lock->execute();
            $lockedRequest = $lock->get_result()->fetch_assoc();
            $lock->close();

            if (
                !$lockedRequest ||
                $lockedRequest["status"] !== "Pending"
            ) {
                throw new RuntimeException(
                    "This request is no longer pending."
                );
            }

            // check this volunteer matches the request
            $check = $con->prepare(
                "SELECT v.volunteer_id FROM volunteer v 
                 WHERE v.volunteer_id = ? 
                   AND v.availability = 'Active'
                   AND (
                       (v.skill = 'Virtual'
                        AND ? = 'Virtual')
                       OR
                       (v.skill = 'Face to Face'
                        AND ? = 'Face-to-Face')
                   )
                   AND EXISTS (
                       SELECT 1 FROM availability a 
                       WHERE a.volunteer_id = v.volunteer_id
                         AND a.day = ?
                         AND ? >= a.startTime
                         AND ? <= a.endTime
                   )
                LIMIT 1"
            );

            $mode = $request["supportMode"];
            $preferredDate = $request["preferredDate"];
            $preferredTime = $request["preferredTime"];

            $check->bind_param(
                "ssssss", $volunteer_id, $mode, $mode, $preferredDate, $preferredTime, $preferredTime
            );
            $check->execute();
            $matchingVolunteer = $check->get_result()->fetch_assoc();
            $check->close();
            if (!$matchingVolunteer) {
                throw new RuntimeException(
                    "This volunteer no longer matches your request."
                );
            }

            // only one volunteer selection is allowed for one request
            $existing = $con->prepare(
                "SELECT match_id FROM request_match WHERE request_id = ? LIMIT 1"
            );
            $existing->bind_param("i", $request_id);
            $existing->execute();

            $existingMatch = $existing->get_result()->fetch_assoc();
            $existing->close();
            if ($existingMatch) {
                throw new RuntimeException(
                    "A volunteer has already been selected for this request."
                );
            }

            $match_id = next_id($con, "request_match", "match_id");
            $insert = $con->prepare(
                "INSERT INTO request_match
                (
                    match_id, volunteer_id, request_id, matchStatus, createAt
                )
                VALUES (?, ?, ?, 'Pending', NOW())"
            );

            $insert->bind_param(
                "isi", $match_id, $volunteer_id, $request_id
            );
            $insert->execute();
            $insert->close();

            $con->commit();
            header("Location: my-request.php");
            exit;

        } catch (Throwable $e) {
            $con->rollback();

            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : "Unable to select the volunteer. Please try again.";
        }
    }
}

//display suitable volunteer
$stmt = $con->prepare(
    "SELECT
        v.volunteer_id, s.username, s.email,
        GROUP_CONCAT(
            DISTINCT sk.skillName
            ORDER BY sk.skillName
            SEPARATOR ', '
        ) AS skills
     FROM volunteer v
     JOIN student s ON s.student_id = v.student_id
     LEFT JOIN skill sk ON sk.volunteer_id = v.volunteer_id
     WHERE v.availability = 'Active'
       AND (
           (v.skill = 'Virtual' AND ? = 'Virtual')
           OR
           (v.skill = 'Face to Face' AND ? = 'Face-to-Face')
       )
       AND EXISTS (
           SELECT 1 FROM availability a
           WHERE a.volunteer_id = v.volunteer_id
             AND a.day = ?
             AND ? >= a.startTime
             AND ? <= a.endTime
       )
     GROUP BY v.volunteer_id, s.username, s.email
     ORDER BY s.username"
);

$mode = $request["supportMode"];
$preferredDate = $request["preferredDate"];
$preferredTime = $request["preferredTime"];

$stmt->bind_param(
    "sssss",
    $mode, $mode, $preferredDate, $preferredTime, $preferredTime
);

$stmt->execute();
$volunteers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Matching Volunteer Result</title>
</head>
<body>
    <h1>Matching Volunteer Result</h1>
    <p>
        <strong>Category:</strong> <?= h($request["category"]) ?><br>
        <strong>Description:</strong> <?= h($request["description"]) ?><br>
        <strong>Date:</strong> <?= h($request["preferredDate"]) ?><br>
        <strong>Time:</strong> <?= h($request["preferredTime"]) ?><br>
        <strong>Mode:</strong> <?= h($request["supportMode"]) ?>
    </p>
    <h2>Available Volunteers</h2>

    <p>
        Number of matching volunteers: <strong><?= count($volunteers) ?></strong>
    </p>

    <?php if (!$volunteers): ?>
        <p>No volunteers match your preferred date, time and support mode.</p>
    <?php else: ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Volunteer Name</th>
                        <th>Volunteer Email</th>
                        <th>Skills</th>
                        <th>Support Mode</th>
                        <th>Preferred Date</th>
                        <th>Preferred Time</th>
                        <th>Selection</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($volunteers as $index => $volunteer): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= h($volunteer["username"]) ?></td>
                            <td><?= h($volunteer["email"]) ?></td>
                            <td>
                                <?= h($volunteer["skills"] ?: "Not specified") ?>
                            </td>
                            <td><?= h($request["supportMode"]) ?></td>
                            <td><?= h($request["preferredDate"]) ?></td>
                            <td><?= h($request["preferredTime"]) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="volunteer_id" value="<?= h($volunteer["volunteer_id"]) ?>" >
                                    <button type="submit">Select Volunteer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</body>
</html>