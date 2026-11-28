
<?php
session_start();

require_once "../config/db_connect.php";

header("Content-Type: application/json");

// Check administrator access
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "Admin") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Access denied."
    ]);
    exit();
}

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit();
}

// Get user ID
$user_id = $_POST["user_id"] ?? "";

// Validate user ID
if (!is_numeric($user_id) || intval($user_id) <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid user ID."
    ]);
    exit();
}

$user_id = intval($user_id);
$admin_id = intval($_SESSION["user_id"]);

// Prevent administrator from deleting their own account
if ($user_id == $admin_id) {
    echo json_encode([
        "success" => false,
        "message" => "You cannot delete your own account."
    ]);
    exit();
}

// Delete user from database
$sql = "DELETE FROM users WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to process deletion."
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $user_id);

if (mysqli_stmt_execute($stmt)) {

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode([
            "success" => true,
            "message" => "User deleted successfully!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "User not found."
        ]);
    }

} else {
    echo json_encode([
        "success" => false,
        "message" => "Unable to delete user. The account may be linked to existing records."
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
