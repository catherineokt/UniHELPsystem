<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

session_start();

include("db_connect.php");

// Only process if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo "
        <script>
            alert('Email and Password are required.');
            window.location.href='login.html';
        </script>
        ";
        exit();
    }

    // Query to check student email in your database
    $sql = "SELECT * FROM student WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        // Check if password matches your database column
        if ($password == $row['password']) {

            $otp = rand(100000, 999999);

            $_SESSION['otp'] = $otp;
            $_SESSION['email'] = $email;

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.office365.com';
                $mail->SMTPAuth = true;
                $mail->Username = "E2500280@helplive.edu.my";
                $mail->Password = "*Limks0503";
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Added SMTPOptions to bypass local XAMPP SSL certificate verification errors
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail->setFrom('E2500280@helplive.edu.my', 'UniHELP');
                $mail->addAddress($email);

                $mail->Subject = 'UniHELP OTP Verification';
                $mail->Body = "Your verification code is: $otp";

                $mail->send();

                // Redirect to your OTP verification page
                header("Location: VerifyOTP.html");
                exit();

            } catch (Exception $e) {
                echo "Mail Error: " . $mail->ErrorInfo;
                exit();
            }
            
        } else {
            echo "
            <script>
                alert('Invalid Password');
                window.location.href='login.html';
            </script>
            ";
            exit();
        }

    } else {
        echo "
        <script>
            alert('Email not found');
            window.location.href='login.html';
        </script>
        ";
        exit();
    }

} else {
    header("Location: login.html");
    exit();
}
?>