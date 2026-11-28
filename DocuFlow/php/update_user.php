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

    $user_id = intval($_POST["user_id"]);
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $role = $_POST["role"];
    $status = $_POST["status"];

    // Check required fields
    if (
        $user_id <= 0 ||
        empty($full_name) ||
        empty($email) ||
        empty($username) ||
        empty($role) ||
        empty($status)
    ) {
        die("Please fill in all fields.");
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    // Validate role and status
    if (!in_array($role, ["Admin", "Approver", "User"])) {
        die("Invalid role selected.");
    }

    if (!in_array($status, ["Active", "Inactive", "Pending", "Rejected"])) {
        die("Invalid account status.");
    }

    // Prevent changing your own administrator access
    if ($user_id == $_SESSION["user_id"] && ($role != "Admin" || $status != "Active")) {
        die("You cannot remove or deactivate your own administrator access.");
    }

    // Check for duplicate username or email
    $check = mysqli_prepare(
        $conn,
        "SELECT user_id FROM users
         WHERE (username = ? OR email = ?) AND user_id != ?"
    );

    mysqli_stmt_bind_param($check, "ssi", $username, $email, $user_id);
    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) > 0) {
        header("Location: ../users.html?message=duplicate");
        exit();
    }

    mysqli_stmt_close($check);

    // Update user information
    $sql = "UPDATE users
            SET full_name = ?, email = ?, username = ?, role = ?, status = ?
            WHERE user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssssi",
        $full_name,
        $email,
        $username,
        $role,
        $status,
        $user_id
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../users.html?message=updated");
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