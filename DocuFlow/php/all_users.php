
<?php
session_start();

require_once "../config/db_connect.php";
// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

// Only administrators can access the users list
if ($_SESSION["role"] != "Admin") {
    http_response_code(403);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Access denied"]);
    exit();
}

// Get users without displaying their passwords
$sql = "SELECT
            user_id,
            full_name,
            username,
            role,
            status,
            created_at
        FROM users
        ORDER BY user_id ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Failed to retrieve users"]);
    exit();
}

$users = [];

while ($row = mysqli_fetch_assoc($result)) {

    $row["created_at"] = date(
        "M d, Y",
        strtotime($row["created_at"])
    );

    $users[] = $row;
}

header("Content-Type: application/json");
echo json_encode($users);
?>
