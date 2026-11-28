<?php
session_start();

require_once "../config/db_connect.php";

header("Content-Type: application/json");

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "error" => "Please log in first."
    ]);

    exit();
}

header("Content-Type: application/json");

$sql = "SELECT
            versions.version_id,
            documents.title,
            versions.version_number,
            versions.file_name,
            users.full_name,
            versions.uploaded_at,
            versions.notes
        FROM versions
        INNER JOIN documents
            ON versions.document_id = documents.document_id
        INNER JOIN users
            ON versions.uploaded_by = users.user_id
        ORDER BY documents.title ASC, versions.version_number DESC";

$result = mysqli_query($conn, $sql);

$allVersions = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row["version_number"] = "v" . $row["version_number"] . ".0";

        $row["uploaded_at"] = date(
            "M d, Y",
            strtotime($row["uploaded_at"])
        );

        $allVersions[] = $row;
    }
}

echo json_encode($allVersions);

?>
