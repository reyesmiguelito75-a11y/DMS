<?php
session_start();

include "../config/db_connect.php";

header("Content-Type: application/json");

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Please log in first."
    ]);
    exit();
}
// Only Admins and Approvers can approve or reject documents
if (!in_array($_SESSION["role"], ["Admin", "Approver"], true)) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "You do not have permission to approve documents."
    ]);

    exit();
}
// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit();
}
// Get submitted information
$document_id = $_POST["document_id"] ?? "";
$decision = $_POST["decision"] ?? "";
$remarks = $_POST["remarks"] ?? "";

$approved_by = $_SESSION["user_id"];


 // Validate input
if (
    empty($document_id) ||
    !is_numeric($document_id) ||
    !in_array($decision, ["Approved", "Rejected"], true)
) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid approval information."
    ]);
    exit();
}


 // Require remarks when rejecting a document
if ($decision == "Rejected" && trim($remarks) == "") {
    echo json_encode([
        "success" => false,
        "message" => "Please provide a reason for rejecting the document."
    ]);
    exit();
}


// Start database transaction
mysqli_begin_transaction($conn);

try {
    // Update document status only if it is still Pending
    $sql = "UPDATE documents
            SET status = ?
            WHERE document_id = ?
            AND status = 'Pending'";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $decision, $document_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        throw new Exception("Document is no longer pending or does not exist.");
    }

    mysqli_stmt_close($stmt);

    // Save approval record
    $sql = "INSERT INTO approvals
            (document_id, approved_by, remarks, decision)
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "iiss",
        $document_id,
        $approved_by,
        $remarks,
        $decision
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to save approval record.");
    }

    mysqli_stmt_close($stmt);

    // Save all changes
    mysqli_commit($conn);

    echo json_encode([
        "success" => true,
        "message" => "Document successfully " . strtolower($decision) . "."
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>