
<?php
session_start();

require_once "../config/db_connect.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit();
}

// Only Admins and Approvers can restore documents
if (!in_array($_SESSION["role"], ["Admin", "Approver"], true)) {
    http_response_code(403);
    die("You do not have permission to restore documents.");
}

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    die("Invalid request method.");
}

// Get archive ID
$archive_id = intval($_POST["archive_id"] ?? 0);

if ($archive_id <= 0) {
    die("Invalid archive ID.");
}

// Get the archived document and its original status
$check_sql = "SELECT document_id, original_status
              FROM archives
              WHERE archive_id = ?";

$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $archive_id);
mysqli_stmt_execute($check_stmt);

$result = mysqli_stmt_get_result($check_stmt);
$archive = mysqli_fetch_assoc($result);

mysqli_stmt_close($check_stmt);

if (!$archive) {
    die("Archive record not found.");
}

$document_id = $archive["document_id"];
$original_status = $archive["original_status"];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Restore the document's previous status
    $update_sql = "UPDATE documents
                   SET status = ?
                   WHERE document_id = ?";

    $update_stmt = mysqli_prepare($conn, $update_sql);

    mysqli_stmt_bind_param(
        $update_stmt,
        "si",
        $original_status,
        $document_id
    );

    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception("Failed to restore document status.");
    }

    mysqli_stmt_close($update_stmt);

    // Remove the archive record
    $delete_sql = "DELETE FROM archives
                   WHERE archive_id = ?";

    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $archive_id);

    if (!mysqli_stmt_execute($delete_stmt)) {
        throw new Exception("Failed to remove archive record.");
    }

    mysqli_stmt_close($delete_stmt);

    // Save all database changes
    mysqli_commit($conn);

    // Return to Archive page
    header("Location: ../archive.html");
    exit();

} catch (Exception $error) {
    mysqli_rollback($conn);
    die("Restore failed: " . $error->getMessage());
}

mysqli_close($conn);
?>
