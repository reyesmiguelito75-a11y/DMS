
<?php
session_start();

require_once "../config/db_connect.php";

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

// Only administrators can view pending registrations
if ($_SESSION["role"] != "Admin") {
    http_response_code(403);
    echo json_encode(["error" => "Access denied"]);
    exit();
}

// Get pending users
$sql = "SELECT
            user_id,
            full_name,
            email,
            username,
            created_at
        FROM users
        WHERE status = 'Pending'
        ORDER BY created_at ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to retrieve registrations"]);
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

echo json_encode($users);
?>
