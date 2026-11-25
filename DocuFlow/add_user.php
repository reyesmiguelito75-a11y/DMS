<?php

session_start();
include 'config/db_connect.php';

//  admin can add users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

// check if the form was submitted
if (isset($_POST['add_user'])) {

    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // check if all fields are filled
    if (empty($full_name) || empty($username) ||
        empty($password) || empty($role)) {

        die("Please fill in all fields.");

    }

    // for max char sa pass
    if (strlen($password) < 8) {
        die("Password must be at least 8 characters.");
    }

    // Hash the password before saving
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // add new user or acc
    $sql = "INSERT INTO users
            (full_name, username, password, role)
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $full_name,
        $username,
        $hashed_password,
        $role
    );

    if (mysqli_stmt_execute($stmt)) {

        header("Location: users.php?success=1");
        exit();

    } else {

        if (mysqli_errno($conn) == 1062) {
            die("Username already exists. Please use another username.");
        }

        die("Error adding user: " . mysqli_error($conn));
    }

} else {

    header("Location: users.php");
    exit();

}

?>