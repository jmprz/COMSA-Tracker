<?php
require_once "config.php";
session_start();

// Only allow admins
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $column = $_POST['column'];
    $value = $_POST['value'] === 'true' ? 1 : 0;

    // Allow only specific columns to be updated
    $allowedColumns = ['sas_f6', 'transmittal', 'invitation', 'endorsement', 'printed', 'signed'];

    if (!in_array($column, $allowedColumns)) {
        http_response_code(400);
        exit("Invalid column");
    }

    $stmt = $conn->prepare("UPDATE events SET $column = ? WHERE id = ?");
    $stmt->bind_param("ii", $value, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "Database error";
    }

    $stmt->close();
}
?>
