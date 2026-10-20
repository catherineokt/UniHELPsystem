<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$username = "root";
$password = "";
$database = "unihelp_system";

try {
    $con = new mysqli(
        $host,
        $username,
        $password,
        $database
    );

    $con->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    exit("Database connection failed. Please try again.");
}
?>
