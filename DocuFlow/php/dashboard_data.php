<?php
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

include "../config/db_connect.php";

// Count all documents
$sqlTotal = "SELECT COUNT(*) AS total FROM documents";
$resultTotal = mysqli_query($conn, $sqlTotal);
$total = mysqli_fetch_assoc($resultTotal)["total"];

// Count pending documents
$sqlPending = "SELECT COUNT(*) AS total FROM documents WHERE status = 'Pending'";
$resultPending = mysqli_query($conn, $sqlPending);
$pending = mysqli_fetch_assoc($resultPending)["total"];

// Count approved documents
$sqlApproved = "SELECT COUNT(*) AS total FROM documents WHERE status = 'Approved'";
$resultApproved = mysqli_query($conn, $sqlApproved);
$approved = mysqli_fetch_assoc($resultApproved)["total"];

// Count archived documents
$sqlArchived = "SELECT COUNT(*) AS total FROM documents WHERE status = 'Archived'";
$resultArchived = mysqli_query($conn, $sqlArchived);
$archived = mysqli_fetch_assoc($resultArchived)["total"];

echo json_encode([
    "total" => $total,
    "pending" => $pending,
    "approved" => $approved,
    "archived" => $archived
]);

mysqli_close($conn);
?>