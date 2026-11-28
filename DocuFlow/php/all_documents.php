<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(array("error" => "Not logged in"));
    exit();
}

include("../config/db_connect.php");

// Get all uploaded documents
$sql = "SELECT documents.document_id,
               documents.title,
               documents.category,
               documents.file_name,
               documents.status,
               DATE_FORMAT(documents.uploaded_at, '%b %e, %Y') AS uploaded_at,
               users.full_name
        FROM documents
        JOIN users ON documents.uploaded_by = users.user_id
        WHERE documents.status != 'Archived'
        ORDER BY documents.uploaded_at DESC";

$result = mysqli_query($conn, $sql);

$documents = array();

// Store the documents in an array
while ($row = mysqli_fetch_assoc($result)) {
    $documents[] = $row;
}

// Return the documents as JSON
header("Content-Type: application/json");
echo json_encode($documents);

mysqli_close($conn);
?>