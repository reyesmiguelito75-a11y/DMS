
<?php
session_start();

require_once "../config/db_connect.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit();
}

// Only Admins and Approvers can archive documents
if (!in_array($_SESSION["role"], ["Admin", "Approver"], true)) {
    http_response_code(403);
    die("You do not have permission to archive documents.");
}

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    die("Invalid request method.");
}

// Get submitted information
$document_id = $_POST["document_id"] ?? "";
$reason = trim($_POST["reason"] ?? "");
$archived_by = $_SESSION["user_id"];

// Validate information
if (empty($document_id) || !is_numeric($document_id) || empty($reason)) {
    die("Please provide the document and archive reason.");
}

// Check if the document exists
$check_sql = "SELECT document_id, status
              FROM documents
              WHERE document_id = ?";

$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $document_id);
mysqli_stmt_execute($check_stmt);

$result = mysqli_stmt_get_result($check_stmt);
$document = mysqli_fetch_assoc($result);

if (!$document) {
    die("Document not found.");
}

mysqli_stmt_close($check_stmt);

// Save the document's original status
$original_status = $document["status"];

// Start database transaction
mysqli_begin_transaction($conn);

try {
    // Save the archive record
    $archive_sql = "INSERT INTO archives
                    (document_id, archived_by, reason, archived_at, original_status)
                    VALUES (?, ?, ?, NOW(), ?)";

    $archive_stmt = mysqli_prepare($conn, $archive_sql);
    mysqli_stmt_bind_param(
        $archive_stmt,
        "iiss",
        $document_id,
        $archived_by,
        $reason,
        $original_status
    );

    if (!mysqli_stmt_execute($archive_stmt)) {
        throw new Exception("Archive record could not be saved.");
    }

    mysqli_stmt_close($archive_stmt);

    // Update document status
    $update_sql = "UPDATE documents
                   SET status = 'Archived'
                   WHERE document_id = ?";

    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $document_id);

    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception("Failed to update document status.");
    }

    mysqli_stmt_close($update_stmt);

    // Save all database changes
    mysqli_commit($conn);

    header("Location: ../archive.html");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Archive failed: " . $e->getMessage());
}

mysqli_close($conn);
?>
