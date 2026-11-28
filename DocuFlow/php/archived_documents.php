
<?php
session_start();

require_once "../config/db_connect.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}
// Only Admins and Approvers can view archived documents
if (!in_array($_SESSION["role"], ["Admin", "Approver"], true)) {
    http_response_code(403);

    echo json_encode([
        "error" => "Access denied."
    ]);

    exit();
}
$sql = "SELECT
            archives.archive_id,
            documents.document_id,
            documents.title,
            documents.category,
            users.full_name,
            archives.reason,
            archives.archived_at
        FROM archives
        INNER JOIN documents
            ON archives.document_id = documents.document_id
        INNER JOIN users
            ON archives.archived_by = users.user_id
        ORDER BY archives.archived_at DESC";

$result = mysqli_query($conn, $sql);

$archived_documents = [];

while ($row = mysqli_fetch_assoc($result)) {
    $row["archived_at"] = date(
        "M d, Y",
        strtotime($row["archived_at"])
    );

    $archived_documents[] = $row;
}

header("Content-Type: application/json");
echo json_encode($archived_documents);
?>
