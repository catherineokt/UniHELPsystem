<?php
session_start();
require_once "db_connect.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id       = trim($_POST['student_id'] ?? '');
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $contactNo        = trim($_POST['contactNo'] ?? ''); // Matches your HTML name="contactNo"
    $password         = trim($_POST['password'] ?? '');
    $confirmPassword  = trim($_POST['confirmPassword'] ?? ''); // Matches your HTML name="confirmPassword"

    // Validation checks
    if (empty($student_id) || empty($full_name) || empty($email) || empty($contactNo) || empty($password) || empty($confirmPassword)) {
        echo "<script>alert('All fields are required.'); window.history.back();</script>";
        exit();
    }

    if ($password !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        exit();
    }

    // Check if student ID or email already exists
    $check = mysqli_prepare($conn, "SELECT student_id FROM student WHERE student_id = ? OR email = ?");
    mysqli_stmt_bind_param($check, "ss", $student_id, $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        echo "<script>alert('Student ID or Email already registered.'); window.history.back();</script>";
        mysqli_stmt_close($check);
        exit();
    }
    mysqli_stmt_close($check);

    // Insert into database using your exact table columns: username & contactNo
    $stmt = mysqli_prepare($conn, "INSERT INTO student (student_id, username, email, contactNo, password) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $student_id, $full_name, $email, $contactNo, $password);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Registration successful! Please log in.'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('Registration failed. Please try again.'); window.history.back();</script>";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    header("Location: register.html");
    exit();
}
?>