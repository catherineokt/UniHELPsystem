<?php
// Enable error reporting to diagnose connection issues
error_reporting(E_ALL);
sql: ini_set('display_errors', 1);

// Use your friend's shared database connection file
require_once "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Links your exact global volunteer stylesheet -->
    <link rel="stylesheet" href="style.css">
    <title>UniHELP - Feedback Received</title>
    <!-- FontAwesome for Star Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feedback-table-box {
            max-width: 950px;
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

    <!-- EXACT VOLUNTEER PAGE HEADER MATCHING STYLE.CSS -->
    <header>
        <!-- Logo -->
        <div class="logo">
            <img src="GraduateIcon.png" alt="Graduation Icon">
            <h1>UniHELP</h1>
        </div>
        <!-- Navigation -->
        <nav class="menu" id="menu">
            <a href="volunteerpage.html">Home</a>
            <a href="">Incoming Request</a>
            <a href="">Session History</a>
            <a href="volunteer_feedback.php">Feedback Received</a>
            <a href="">Profile</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <!-- Main Content Container matching style.css box styling -->
    <div class="feedback-table-box">
        <h1 style="font-size: 26px; text-align: center; margin-bottom: 30px; color: #333;">Feedback Received from Students</h1>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Feedback ID</th>
                        <th>Feedback</th>
                        <th>Overall Rating</th>
                        <th>Comments</th>
                        <th>Date Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all feedback records from the database using $con from db.php
                    $query = "SELECT * FROM feedback ORDER BY submitted_at ASC";
                    $result = mysqli_query($con, $query);

                    if ($result && mysqli_num_rows($result) > 0) {
                        $counter = 1; // Start counting from 1
                        while ($row = mysqli_fetch_assoc($result)) {
                            $rating = intval($row['overall_rating']);
                            $date = date("d/m/Y H:i", strtotime($row['submitted_at']));
                            
                            echo "<tr>";
                            echo "<td style='color: #333;'>#" . $counter . "</td>";
                            echo "<td style='color: #333;'>" . htmlspecialchars($row['feedback']) . "</td>";
                            
                            // Render star icons dynamically based on rating number
                            echo "<td style='color: #e56b53;'>";
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fa-solid fa-star"></i> ';
                                } else {
                                    echo '<i class="fa-solid fa-star" style="color: #ccc;"></i> ';
                                }
                            }
                            echo "</td>";

                            echo "<td style='color: #333;'>" . htmlspecialchars($row['comments']) . "</td>";
                            echo "<td style='color: #666;'>" . $date . "</td>";
                            echo "</tr>";
                            
                            $counter++; // Increment counter for the next row
                        }
                    } else {
                        echo "<tr><td colspan='5' style='color: #666; padding: 20px;'>No feedback received yet.</td></tr>";
                    }

                    mysqli_close($con);
                    ?>
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