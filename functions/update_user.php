<?php
session_start();
require_once "config.php"; // DB connection

// Check for admin session
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Check if the request is a POST request and required fields are set
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['id'], $_POST['name'], $_POST['email'], $_POST['student_number'], $_POST['role'])) {
    $_SESSION['error_message'] = "Invalid request or missing data for update.";
    header("Location: ../users.php");
    exit();
}

// Collect and Sanitize Data
$id = (int)$_POST['id'];
$name = $conn->real_escape_string($_POST['name']);
$email = $conn->real_escape_string($_POST['email']);
$student_number = $conn->real_escape_string($_POST['student_number']);
$role = $conn->real_escape_string($_POST['role']);
$type = $conn->real_escape_string($_POST['type'] ?? ''); 
$password_plain = $_POST['password'] ?? '';

// Start building the SQL query
$sql = "UPDATE users SET name = ?, email = ?, student_number = ?, role = ?, type = ?";
$params = "sssss";
$param_values = [$name, $email, $student_number, $role, $type];

// 2. Handle Password Update (if a new password was provided)
if (!empty($password_plain)) {
    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $sql .= ", password = ?";
    $params .= "s";
    $param_values[] = $password_hash;
}

// Complete the query with the WHERE clause
$sql .= " WHERE id = ?";
$params .= "i";
$param_values[] = $id;

// 3. Execute Prepared Statement
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error_message'] = "Database error: Failed to prepare statement (" . $conn->error . ")";
    header("Location: ../users.php");
    exit();
}

// Bind parameters
$stmt->bind_param($params, ...$param_values);

if ($stmt->execute()) {
    // Check if any row was actually updated
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "User **{$name}** updated successfully!";
    } else {
        $_SESSION['info_message'] = "User **{$name}** data submitted, but no changes were made.";
    }
} else {
    // Check for duplicate entry error (e.g., duplicate email or student_number)
    if ($conn->errno == 1062) {
        $_SESSION['error_message'] = "Error: User update failed. Email or Student Number already exists.";
    } else {
        $_SESSION['error_message'] = "Error updating user: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();

// Redirect back to the user management page
header("Location: ../users.php");
exit();
?>