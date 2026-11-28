<?php
session_start();

require_once "../config/db_connect.php";

// Check administrator access
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "Admin") {
    http_response_code(403);
    die("Access denied.");
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $role = $_POST["role"];
    $status = $_POST["status"];

    // Check if all fields are filled
    if (
        empty($full_name) ||
        empty($email) ||
        empty($username) ||
        empty($password) ||
        empty($role) ||
        empty($status)
    ) {
        die("Please fill in all fields.");
    }

    // Check valid email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    // Check allowed roles and statuses
    if (!in_array($role, ["Admin", "Approver", "User"])) {
        die("Invalid role selected.");
    }

    if (!in_array($status, ["Active", "Inactive"])) {
        die("Invalid account status.");
    }

    // Check if username or email already exists
    $check = mysqli_prepare(
        $conn,
        "SELECT user_id FROM users WHERE username = ? OR email = ?"
    );

    mysqli_stmt_bind_param($check, "ss", $username, $email);
    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) > 0) {
        header("Location: ../users.html?message=duplicate");
        exit();
    }

    mysqli_stmt_close($check);

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO users
            (full_name, email, username, password, role, status)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssssss",
        $full_name,
        $email,
        $username,
        $hashed_password,
        $role,
        $status
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../users.html?message=success");
        exit();
    } else {
        header("Location: ../users.html?message=error");
        exit();
    }

    mysqli_stmt_close($stmt);

} else {
    echo "Invalid request.";
}

mysqli_close($conn);
?>
