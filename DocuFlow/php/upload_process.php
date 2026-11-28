<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit();
}

include("../config/db_connect.php");
// Only accept form submissions
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../upload.html");
    exit();
}
// Get form information
$title = trim($_POST["title"]);
$category = $_POST["category"];
$department = trim($_POST["department"]);
$description = trim($_POST["description"]);
$uploaded_by = $_SESSION["user_id"];

// Check required document information
if (empty($title) || empty($category) || empty($department)) {
    die("Please complete all required fields.");
}

// Check allowed document categories
$allowed_categories = array(
    "Reports",
    "Forms",
    "Policies",
    "Others"
);

if (!in_array($category, $allowed_categories)) {
    die("Invalid document category.");
}

// Check if a file was selected
if (!isset($_FILES["document"]) || $_FILES["document"]["error"] != UPLOAD_ERR_OK) {
    die("Please select a file to upload.");
}
// Get file information
$original_name = basename($_FILES["document"]["name"]);
$file_size = $_FILES["document"]["size"];
$temp_file = $_FILES["document"]["tmp_name"];
// Check file extension
$file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

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

// Create a unique file name
$new_file_name = uniqid("doc_") . "." . $file_extension;

// Set the upload folder
$upload_folder = __DIR__ . "/../uploads/";
$destination = $upload_folder . $new_file_name;

// Move the uploaded file into the uploads folder
if (move_uploaded_file($temp_file, $destination)) {

    $file_path = "uploads/" . $new_file_name;

    // Save document information into the database
    $sql = "INSERT INTO documents
            (title, description, category, department,
             file_name, file_path, uploaded_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssi",
        $title,
        $description,
        $category,
        $department,
        $original_name,
        $file_path,
        $uploaded_by
    );

    // Start database transaction
    mysqli_begin_transaction($conn);

    if (mysqli_stmt_execute($stmt)) {

    // Get the ID of the newly uploaded document
    $document_id = mysqli_insert_id($conn);

    // Set the first version number
    $version_number = 1;

    // Save the first version into the versions table
    $version_sql = "INSERT INTO versions
                    (document_id, version_number, file_name, file_path, uploaded_by, uploaded_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";

    $version_stmt = mysqli_prepare($conn, $version_sql);

    mysqli_stmt_bind_param(
        $version_stmt,
        "iissi",
        $document_id,
        $version_number,
        $original_name,
        $file_path,
        $uploaded_by
    );

    if (mysqli_stmt_execute($version_stmt)) {
            // Save both database records permanently
        mysqli_commit($conn);

        mysqli_stmt_close($version_stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        header("Location: ../index.html");
        exit();

    } else {

    // Undo the document database insertion
    mysqli_rollback($conn);

    // Remove the uploaded file
    if (file_exists($destination)) {
        unlink($destination);
    }

    echo "Upload failed. The document and version were not saved.";

    }

    mysqli_stmt_close($version_stmt);

} else {
    // Undo database changes
    mysqli_rollback($conn);

    // Remove the uploaded file
    if (file_exists($destination)) {
        unlink($destination);
    }
    echo "Failed to save document information.";
}
    mysqli_stmt_close($stmt);
} else {
    echo "The file could not be uploaded.";
}
mysqli_close($conn);
?>