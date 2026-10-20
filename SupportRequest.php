
<?php
require_once "db.php";

// Include your student header only after handling redirects.
// Make sure the login page sets $_SESSION["student_id"].

if (empty($_SESSION["student_id"])) {
    exit("Please log in as a student first.");
}

$student_id = trim((string)$_SESSION["student_id"]);
$error = "";

$category = "";
$description = "";
$preferredDate = "";
$preferredTime = "";
$supportMode = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $preferredDate = trim($_POST["preferredDate"] ?? "");
    $preferredTime = trim($_POST["preferredTime"] ?? "");
    $supportMode = trim($_POST["supportMode"] ?? "");

    // Valid category and support mode values.
    $validCategories = [
        "Academic",
        "Technology",
        "New Student",
        "General"
    ];

    $validModes = [
        "Virtual",
        "Face-to-Face"
    ];

    if (
        !in_array($category, $validCategories, true) ||
        !in_array($supportMode, $validModes, true) ||
        $description === "" ||
        strlen($description) > 500 ||
        $preferredDate === "" ||
        $preferredTime === ""
    ) {
        $error = "Please complete all fields correctly.";

    } elseif ($preferredDate < date("Y-m-d")) {
        $error = "Please choose today or a future date.";

    } else {
        try {
            // Check whether the student exists.
            $check = $con->prepare(
                "SELECT student_id
                 FROM student
                 WHERE student_id = ?"
            );

            $check->bind_param("s", $student_id);
            $check->execute();

            $student = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$student) {
                throw new RuntimeException(
                    "Your student account could not be found."
                );
            }

            // Generate the next integer request ID.
            $request_id = nextId(
                $con,
                "support_request",
                "request_id"
            );

            // Insert the support request.
            $stmt = $con->prepare(
                "INSERT INTO support_request
                (
                    request_id,
                    student_id,
                    category,
                    description,
                    preferredDate,
                    preferredTime,
                    supportMode,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );

            $stmt->bind_param(
                "issssss",
                $request_id,
                $student_id,
                $category,
                $description,
                $preferredDate,
                $preferredTime,
                $supportMode
            );

            $stmt->execute();
            $stmt->close();

            // Redirect to the matching results page.
            header(
                "Location: matching-results.php?request_id=" .
                $request_id
            );
            exit;

        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : "Unable to save the request. Please try again.";
        }
    }
}

include "stuheader.php";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Support Request</title>
</head>
<body>
    <h1>Support Request</h1>
    <form action="request.php" method="POST">
        <div class="req-container">
            <div class="req-form">
                <label for="category">Category</label>
                <span>:</span>
                <select name="category" id="category">
                    <option value="">Select category</option>
                    <option value="Academic">Academic</option>
                    <option value="Technology">Technology</option>
                    <option value="New Student">New Student</option>
                    <option value="General">General Student</option>
                </select>
            </div>

            <div class="req-form">
                <label for="description">Description</label>
                <span>:</span>
                <textarea name="description" id="description" placeholder="Add text here"></textarea>
            </div>
        
            <div class="req-form">
                <label for="preferredDate">Preferred Date</label>
                <span>:</span>
                <input type="date" id="preferredDate" name="preferredDate">
            </div>
        
            <div class="req-form">
                <label for="preferredTime">Preferred Time</label>
                <span>:</span>
                <input type="time" id="preferredTime" name="preferredTime">
            </div>
        
            <div class="req-form">
                <label for="supportMode">Support Mode</label>
                <span>:</span>
                <select name="supportMode" id="supportMode">
                    <option value="">Select support mode</option>
                    <option value="Virtual">Virtual</option>
                    <option value="Face-to-Face">Face-to-Face</option>
                </select>
            </div>
        </div>
        <button>Find volunteers</button>
    </form>
</body>
</html>

