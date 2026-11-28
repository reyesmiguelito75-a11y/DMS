
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit();
}

include("../config/db_connect.php");

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    die("Invalid request method.");
}

// Get form information
$document_id = intval($_POST["document_id"]);
$notes = trim($_POST["notes"]);
$uploaded_by = $_SESSION["user_id"];

// Check if a document was selected
if ($document_id <= 0) {
    die("Please select a document.");
}

// Check if the file was selected
if (!isset($_FILES["document"]) ||
    $_FILES["document"]["error"] != UPLOAD_ERR_OK) {
    die("Please select a file to upload.");
}

// Get file information
$original_name = basename($_FILES["document"]["name"]);
$file_size = $_FILES["document"]["size"];
$temp_file = $_FILES["document"]["tmp_name"];

// Check file extension
$file_extension = strtolower(
    pathinfo($original_name, PATHINFO_EXTENSION)
);

$allowed_types = array(
    "pdf", "doc", "docx",
    "xls", "xlsx", "ppt", "pptx", "txt"
);

if (!in_array($file_extension, $allowed_types)) {
    die("This file type is not allowed.");
}
// Check the actual file type
$file_mime = mime_content_type($temp_file);

$allowed_mime_types = array(
    "pdf" => "application/pdf",
    "doc" => "application/msword",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "xls" => "application/vnd.ms-excel",
    "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "ppt" => "application/vnd.ms-powerpoint",
    "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "txt" => "text/plain"
);

// Make sure the file matches its extension
if (
    !isset($allowed_mime_types[$file_extension]) ||
    $file_mime != $allowed_mime_types[$file_extension]
) {
    die("The uploaded file does not match its file extension.");
}

// Limit file size to 5 MB
if ($file_size > 5 * 1024 * 1024) {
    die("The file is too large. Maximum size is 5 MB.");
}

// Check if the selected document exists
$check_sql = "SELECT document_id
              FROM documents
              WHERE document_id = ?
              AND status != 'Archived'";
$check_stmt = mysqli_prepare($conn, $check_sql);

mysqli_stmt_bind_param($check_stmt, "i", $document_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) == 0) {
    die("The selected document does not exist.");
}

mysqli_stmt_close($check_stmt);

// Get the current highest version number
$version_sql = "SELECT MAX(version_number) AS latest_version
                FROM versions
                WHERE document_id = ?";

$version_stmt = mysqli_prepare($conn, $version_sql);

mysqli_stmt_bind_param($version_stmt, "i", $document_id);
mysqli_stmt_execute($version_stmt);

$version_result = mysqli_stmt_get_result($version_stmt);
$version_data = mysqli_fetch_assoc($version_result);

$latest_version = $version_data["latest_version"];

if ($latest_version == NULL) {
    $version_number = 1;
} else {
    $version_number = $latest_version + 1;
}

mysqli_stmt_close($version_stmt);

// Create a unique file name
$new_file_name = uniqid("doc_") . "." . $file_extension;

// Set the upload folder
$upload_folder = __DIR__ . "/../uploads/";
$destination = $upload_folder . $new_file_name;


 // Move the uploaded file
if (move_uploaded_file($temp_file, $destination)) {

    $file_path = "uploads/" . $new_file_name;

    // Start database transaction
    mysqli_begin_transaction($conn);

    try {

        // Save the new version
        $insert_sql = "INSERT INTO versions
            (document_id, version_number, file_name,
            file_path, uploaded_by, uploaded_at, notes)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)";

        $insert_stmt = mysqli_prepare($conn, $insert_sql);

        mysqli_stmt_bind_param(
            $insert_stmt,
            "iissis",
            $document_id,
            $version_number,
            $original_name,
            $file_path,
            $uploaded_by,
            $notes
        );

        if (!mysqli_stmt_execute($insert_stmt)) {
            throw new Exception("Failed to save the new version.");
        }

        // Update the document's current file information
        $update_sql = "UPDATE documents
                       SET file_name = ?, file_path = ?
                       WHERE document_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_sql);

        mysqli_stmt_bind_param(
            $update_stmt,
            "ssi",
            $original_name,
            $file_path,
            $document_id
        );

        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Failed to update document information.");
        }

        // Save all database changes
        mysqli_commit($conn);

        mysqli_stmt_close($insert_stmt);
        mysqli_stmt_close($update_stmt);
        mysqli_close($conn);

        header("Location: ../versions.html");
        exit();

    } catch (Exception $error) {

        // Undo database changes
        mysqli_rollback($conn);

        // Remove the uploaded file if saving failed
        if (file_exists($destination)) {
            unlink($destination);
        }

        die("Version upload failed: " . $error->getMessage());
    }

} else {
    die("The file could not be uploaded.");
}

mysqli_close($conn);
?>
