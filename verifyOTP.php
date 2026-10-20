<?php

session_start();

$enteredOTP = trim($_POST['otp']);

if (empty($enteredOTP))
{
    echo "
    <script>
        alert('Please enter the OTP code!');
        window.location.href='VerifyOTP.html';
    </script>
    ";
    exit();
}

if ($enteredOTP == $_SESSION['otp'])
{
    echo "
    <script>
        alert('OTP Verified Successfully!');
        window.location.href='studentpage.html';
    </script>
    ";
}
else
{
    echo "
    <script>
        alert('Invalid OTP! Please try again.');
        window.location.href='VerifyOTP.html';
    </script>
    ";
}

?>