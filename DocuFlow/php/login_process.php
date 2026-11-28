<?php
session_start();

include "../config/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {

        // Check account status
    if ($user["status"] == "Pending") {
        header("Location: ../login.html?error=pending");
        exit();
    }

    if ($user["status"] == "Rejected") {
        header("Location: ../login.html?error=rejected");
        exit();
    }

    if ($user["status"] != "Active") {
        header("Location: ../login.html?error=invalid");
        exit();
    }

    $_SESSION["user_id"] = $user["user_id"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["full_name"] = $user["full_name"];
    $_SESSION["role"] = $user["role"];
    // Generate a new session ID after login
    session_regenerate_id(true);

    header("Location: ../index.html");
    exit();

    } else {
        header("Location: ../login.html?error=invalid");
        exit();
    }

} else {
    header("Location: ../login.html");
    exit();
}
?>