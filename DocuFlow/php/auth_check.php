
<?php
session_start();

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

// Check if the user is logged in
if (
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["username"]) &&
    isset($_SESSION["full_name"]) &&
    isset($_SESSION["role"])
) {

    echo json_encode([
        "logged_in" => true,
        "user_id" => $_SESSION["user_id"],
        "username" => $_SESSION["username"],
        "full_name" => $_SESSION["full_name"],
        "role" => $_SESSION["role"]
    ]);

} else {

    // Clear invalid session data
    $_SESSION = [];

    echo json_encode([
        "logged_in" => false
    ]);

}
?>