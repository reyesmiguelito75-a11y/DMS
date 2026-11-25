<?php
session_start();
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) != 1) {
    header("Location: users.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

if (isset($_POST['update_user'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    if (empty($full_name) || empty($username) || empty($role)) {
        die("Please fill in all fields.");
    }

    $sql = "UPDATE users
            SET full_name = ?, username = ?, role = ?
            WHERE user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "sssi",
        $full_name,
        $username,
        $role,
        $user_id
    );

    if (mysqli_stmt_execute($stmt)) {
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
        }

        header("Location: users.php?updated=1");
        exit();
    } else {
        if (mysqli_errno($conn) == 1062) {
            die("Username already exists. Please use another username.");
        }

        die("Error updating user: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User - DocuFlow</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <h2>Edit User</h2>

        <form method="POST">

            <label>Full Name</label>
            <input type="text" name="full_name"
                value="<?php echo htmlspecialchars($user['full_name']); ?>"
                required>

            <label>Username</label>
            <input type="text" name="username"
                value="<?php echo htmlspecialchars($user['username']); ?>"
                required>

            <label>User Role</label>
            <select name="role" required>
                <option value="Admin" <?php if ($user['role'] == 'Admin') echo 'selected'; ?>>
                    Administrator
                </option>

                <option value="Approver" <?php if ($user['role'] == 'Approver') echo 'selected'; ?>>
                    Approver
                </option>

                <option value="User" <?php if ($user['role'] == 'User') echo 'selected'; ?>>
                    Staff
                </option>
            </select>

            <button type="submit" name="update_user">Save Changes</button>

            <a href="users.php">Cancel</a>

        </form>
    </div>

</body>
</html>