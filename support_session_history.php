<?php
session_start();
require_once "db.php";

// Bypass login check for testing and set default student ID
$student_id = "STD001"; 
$volunteer_id = null;

include "stuheader.php";

// Fetch session history for the default student
$stmt = $con->prepare(
    "SELECT ss.session_id, ss.date, ss.time, ss.supportMode, ss.status, 
            sr.category, sr.description, 
            volunteerStudent.email AS otherEmail,
            f.overall_rating
     FROM support_session ss
     JOIN support_request sr ON sr.request_id = ss.request_id
     JOIN volunteer v ON v.volunteer_id = ss.volunteer_id
     JOIN student volunteerStudent ON volunteerStudent.student_id = v.student_id
     LEFT JOIN feedback f ON f.session_id = ss.session_id
     WHERE sr.student_id = ?
     ORDER BY ss.date DESC, ss.time DESC"
);
$stmt->bind_param("s", $student_id);

$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>UniHELP - Support Session History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .session-table-box {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 14px;
            border: 1px solid black;
        }
        th, td {
            border: 1px solid black;
            padding: 12px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>

    <div class="session-table-box">
        <h1 style="font-size: 26px; text-align: center; margin-bottom: 30px; color: #333;">Support Session History</h1>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Support Category</th>
                        <th>Support Required</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="6" style="color: #666; padding: 20px;">No support sessions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td style="color: #333;"><?= htmlspecialchars($session["otherEmail"]) ?></td>
                                <td style="color: #333;"><?= htmlspecialchars($session["category"]) ?></td>
                                <td style="color: #333;"><?= htmlspecialchars($session["description"]) ?></td>
                                <td style="color: #666;"><?= htmlspecialchars($session["date"] . " " . $session["time"]) ?></td>
                                <td style="color: #333;"><strong><?= htmlspecialchars($session["status"]) ?></strong></td>
                                <td style="color: #e56b53;">
                                    <?php 
                                        if (!empty($session["overall_rating"])) {
                                            $rating = intval($session["overall_rating"]);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fa-solid fa-star"></i> ';
                                                } else {
                                                    echo '<i class="fa-solid fa-star" style="color: #ccc;"></i> ';
                                                }
                                            }
                                        } else {
                                            echo '<span style="color: #999;">Not Rated</span>';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <hr style="margin-top: 50px; border: 0; border-top: 1px solid #ccc;">
    <footer>
        <p style="text-align: center; font-size: 14px; color: #666;">CopyRight®UniHelp System 2026</p>
    </footer>
</body>
</html>