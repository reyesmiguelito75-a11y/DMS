<?php
session_start();

require_once "../config/db_connect.php";

// Check administrator access
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "Admin") {
    http_response_code(403);
    die("Access denied.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = intval($_POST["user_id"]);
    $action = $_POST["action"];

    // Approve registration
    if ($action == "approve") {

        $role = $_POST["role"];

        // Check allowed roles
        if (!in_array($role, ["Admin", "Approver", "User"])) {
            die("Invalid role selected.");
        }

        $sql = "UPDATE users
                SET status = 'Active', role = ?
                WHERE user_id = ? AND status = 'Pending'";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $role, $user_id);

    }

    // Reject registration by deleting the account
    elseif ($action == "reject") {

    $sql = "DELETE FROM users
            WHERE user_id = ? AND status = 'Pending'";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    }else {
        die("Invalid action.");
    }

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../pending_users.html");
        exit();
    } else {
        echo "Failed to process registration.";
    }

    mysqli_stmt_close($stmt);

} else {
    echo "Invalid request.";
}

mysqli_close($conn);
?>