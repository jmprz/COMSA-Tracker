<?php
session_start();
require_once "config.php"; // DB connection

// Check for admin session
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Check if the ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid request: User ID not provided.";
    header("Location: ../admin/users.php");
    exit();
}

$id = (int)$_GET['id'];

// Prevent accidental deletion of the current logged-in admin
if ($id == $_SESSION['id']) {
    $_SESSION['error_message'] = "Security alert: You cannot delete your own active admin account.";
    header("Location: ../admin/users.php");
    exit();
}

// Use a prepared statement to prevent SQL injection
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");

if (!$stmt) {
    $_SESSION['error_message'] = "Database error: Failed to prepare statement (" . $conn->error . ")";
    header("Location: ../admin/users.php");
    exit();
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "User with ID **{$id}** successfully deleted.";
    } else {
        $_SESSION['info_message'] = "No user found with ID **{$id}** to delete.";
    }
} else {
    $_SESSION['error_message'] = "Error deleting user: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to the user management page
header("Location: ../admin/users.php");
exit();
?>