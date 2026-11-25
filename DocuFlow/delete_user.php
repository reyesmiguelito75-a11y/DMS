<?php
session_start();
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Prevent admin from deleting their own account
    if ($user_id == $_SESSION['user_id']) {
        header("Location: users.php?error=self");
        exit();
    }

    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: users.php?deleted=1");
        exit();
    } else {
        header("Location: users.php?error=delete");
        exit();
    }
} else {
    header("Location: users.php");
    exit();
}
?>