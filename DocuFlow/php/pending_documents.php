
<?php
session_start();

header("Content-Type: application/json");

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "error" => "Please log in first."
    ]);

    exit();
}

// Only Admins and Approvers can view pending documents
if (!in_array($_SESSION["role"], ["Admin", "Approver"], true)) {
    http_response_code(403);

    echo json_encode([
        "error" => "Access denied."
    ]);

    exit();
}
include "../config/db_connect.php";

header("Content-Type: application/json");

$sql = "SELECT 
            documents.document_id,
            documents.title,
            documents.category,
            documents.file_name,
            documents.file_path,
            documents.status,
            documents.uploaded_at,
            users.full_name
        FROM documents
        INNER JOIN users
        ON documents.uploaded_by = users.user_id
        WHERE documents.status = 'Pending'
        ORDER BY documents.uploaded_at DESC";

$result = mysqli_query($conn, $sql);

$pendingDocuments = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row["uploaded_at"] = date(
            "M d, Y",
            strtotime($row["uploaded_at"])
        );

        $pendingDocuments[] = $row;
    }
}

echo json_encode($pendingDocuments);
?>