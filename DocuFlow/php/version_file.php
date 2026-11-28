
<?php
session_start();

require_once "../config/db_connect.php";

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    die("Please log in first.");
}

// Get the version ID
$version_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if ($version_id <= 0) {
    http_response_code(400);
    die("Invalid version ID.");
}

// Get the specific version information
$sql = "SELECT file_name, file_path
        FROM versions
        WHERE version_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $version_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$version = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$version) {
    http_response_code(404);
    die("Version not found.");
}

// Get the uploads folder and file path
$upload_folder = realpath(__DIR__ . "/../uploads");
$file_path = realpath(__DIR__ . "/../" . $version["file_path"]);

// Make sure the file is inside the uploads folder
if (
    $upload_folder === false ||
    $file_path === false ||
    strpos(
        strtolower($file_path),
        strtolower($upload_folder . DIRECTORY_SEPARATOR)
    ) !== 0
) {
    http_response_code(404);
    die("File not found.");
}

// Check if the file exists
if (!is_file($file_path)) {
    http_response_code(404);
    die("File not found.");
}

// Get the file type
$file_mime = mime_content_type($file_path);

// Check if download was requested
$download = isset($_GET["download"]) && $_GET["download"] == "1";

$disposition = $download ? "attachment" : "inline";

// Clean the original filename
$file_name = basename($version["file_name"]);
$file_name = str_replace(
    array("\r", "\n", '"'),
    "",
    $file_name
);

// Send file headers
header("Content-Type: " . $file_mime);
header("Content-Disposition: " . $disposition . "; filename=\"" . $file_name . "\"");
header("Content-Length: " . filesize($file_path));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store, no-cache, must-revalidate");

// Send the file
readfile($file_path);

mysqli_close($conn);
exit();
?>
