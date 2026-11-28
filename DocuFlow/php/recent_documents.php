<?php
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

include "../config/db_connect.php";

// gets the last 5 documents
$sql = "SELECT
            documents.title,
            users.full_name,
            documents.uploaded_at,
            documents.status
        FROM documents
        INNER JOIN users
        ON documents.uploaded_by = users.user_id
        ORDER BY documents.uploaded_at DESC
        LIMIT 5";

$result = mysqli_query($conn, $sql);

$documents = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row["uploaded_at"] = date(
            "M j, Y",
            strtotime($row["uploaded_at"])
        );

        $documents[] = $row;
    }
}

echo json_encode($documents);

mysqli_close($conn);
?>