<?php
session_start();

require_once "../config/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Check if all fields are filled
    if (
        empty($full_name) ||
        empty($email) ||
        empty($username) ||
        empty($password)
    ) {
        die("Please fill in all fields.");
    }

    // Check if email is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Please enter a valid email address.");
    }

    // Check if username or email already exists
    $check_sql = "SELECT user_id FROM users
                  WHERE username = ? OR email = ?";

    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
    mysqli_stmt_execute($check_stmt);

    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        die("Username or email is already registered.");
    }

    // Encrypt the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // New accounts are pending and have no assigned role yet
    $role = "User";
    $status = "Pending";

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
        echo "
            <div style='text-align: center; font-family: Arial; margin-top: 100px;'>
                <h2>Account Created Successfully!</h2>
                <p>Your account is waiting for administrator approval.</p>

                <a href='../login.html' style='
                    display: inline-block;
                    padding: 12px 20px;
                    background-color: #1e3a8a;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    margin-top: 15px;
                '>
                    Return to Login Page
                </a>
            </div>
            ";
    } else {
        echo "Registration failed. Please try again.";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

} else {
    echo "Invalid request.";
}
?>