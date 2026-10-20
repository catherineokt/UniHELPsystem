<?php
session_start();
require_once "db.php";

// Login check removed so the page loads directly
$student_id = "STD001"; // Default fallback student ID for testing
$session_id = filter_input(INPUT_GET, "session_id", FILTER_VALIDATE_INT) ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>UniHELP - Submit Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header>
        <div class="logo">
            <img src="GraduateIcon.png" alt="Graduation Icon">
            <h1>UniHELP</h1>
        </div>
        <nav class="menu" id="menu">
            <a href="studentpage.html">Home</a>
            <a href="">My Request</a>
            <a href="support_session_history.php">Session History</a>
            <a href="student_feedback.php">Feedback</a>
            <a href="">Profile</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <div class="container" style="width: 450px; margin-top: 40px;">
        <div class="text-center">
            <h1 style="font-size: 24px; margin-bottom: 25px; color: #333;">Submit Feedback</h1>
        </div>

        <form id="feedback-form" onsubmit="handleFormSubmit(event)">
            <!-- Hidden inputs for database foreign keys -->
            <input type="hidden" id="session-id" value="<?= htmlspecialchars($session_id) ?>">
            <input type="hidden" id="student-id" value="<?= htmlspecialchars($student_id) ?>">

            <!-- Added back your Feedback input field -->
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="feedback-input">Feedback :</label>
                <input type="text" id="feedback-input" class="form-control" required placeholder="Give your feedback" style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Overall Rating :</label>
                <div id="star-container" style="display: flex; gap: 8px; margin-top: 5px;">
                    <button type="button" class="star-btn" data-value="1" style="background:none; border:none; font-size:24px; cursor:pointer; color:#e56b53;"><i class="fa-solid fa-star"></i></button>
                    <button type="button" class="star-btn" data-value="2" style="background:none; border:none; font-size:24px; cursor:pointer; color:#e56b53;"><i class="fa-solid fa-star"></i></button>
                    <button type="button" class="star-btn" data-value="3" style="background:none; border:none; font-size:24px; cursor:pointer; color:#e56b53;"><i class="fa-solid fa-star"></i></button>
                    <button type="button" class="star-btn" data-value="4" style="background:none; border:none; font-size:24px; cursor:pointer; color:#e56b53;"><i class="fa-solid fa-star"></i></button>
                    <button type="button" class="star-btn" data-value="5" style="background:none; border:none; font-size:24px; cursor:pointer; color:#ccc;"><i class="fa-solid fa-star"></i></button>
                    <input type="hidden" id="rating-value" value="4">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="feedback-comments">Comments :</label>
                <textarea id="feedback-comments" class="form-control" rows="4" required placeholder="Add comments here" style="resize: none;"></textarea>
            </div>

            <button type="submit" class="btn">Submit feedback</button>
        </form>
    </div>

    <div id="toast-notification" style="position: fixed; bottom: 20px; right: 20px; background: #000B3D; color: #fff; padding: 15px 20px; border-radius: 8px; display: none; box-shadow: 0px 4px 10px rgba(0,0,0,0.2);">
        <strong>Success!</strong> Your feedback has been successfully submitted.
    </div>

    <hr style="margin-top: 50px; border: 0; border-top: 1px solid #ccc;">
    <footer>
        <p style="text-align: center; font-size: 14px; color: #666;">CopyRight®UniHelp System 2026</p>
    </footer>

    <script>
        const stars = document.querySelectorAll('.star-btn');
        const ratingInput = document.getElementById('rating-value');

        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const val = index + 1;
                ratingInput.value = val;
                stars.forEach((s, idx) => {
                    s.style.color = idx < val ? "#e56b53" : "#ccc";
                });
            });
        });

        async function handleFormSubmit(event) {
            event.preventDefault();
            
            const formData = new URLSearchParams();
            formData.append('session_id', document.getElementById('session-id').value);
            formData.append('student_id', document.getElementById('student-id').value);
            formData.append('feedback', document.getElementById('feedback-input').value); // Added feedback value
            formData.append('rating', ratingInput.value);
            formData.append('comments', document.getElementById('feedback-comments').value);

            try {
                const response = await fetch('submit_feedback.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                });
                
                const result = await response.json();

                if (result.status === "success") {
                    const toast = document.getElementById('toast-notification');
                    toast.style.display = "block";
                    
                    setTimeout(() => {
                        toast.style.display = "none";
                    }, 3500);

                    document.getElementById('feedback-form').reset();
                    ratingInput.value = 4;
                    stars.forEach((s, idx) => {
                        s.style.color = idx < 4 ? "#e56b53" : "#ccc";
                    });
                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                console.error('Error connecting to database script:', error);
                alert('Could not submit feedback. Make sure your local server (XAMPP/PHP) is running.');
            }
        }
    </script>
</body>
</html>