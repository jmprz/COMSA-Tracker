<?php
session_start();
require_once "config.php"; // DB connection

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../users.php"); // Redirect if not POST
    exit();
}

// 1. Admin/Authentication Check (Important for an admin-only registration form)
// Check for admin session, otherwise stop execution.
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    // You might want a better error page here, but for now, redirect to login
    header("Location: ../admin/users.php");
    exit();
}

// 2. Collect and Sanitize Data
$name = $conn->real_escape_string($_POST['name']);
$email = $conn->real_escape_string($_POST['email']);
$student_number = $conn->real_escape_string($_POST['student_number']);
$role = $conn->real_escape_string($_POST['role']);
$type = $conn->real_escape_string($_POST['type'] ?? ''); // Use empty string if null
$is_admin = 0; // Default user is not admin

// NOTE: You did not include a checkbox for 'is_admin' in the modal, 
// so we'll assume only 'role' is set. Admins can be designated by the role, 
// or you could check the role and set is_admin=1 explicitly if role is 'executive'.

// Determine if the user should be set as admin based on role (Optional logic)
// if ($role === 'executive') {
//     $is_admin = 1;
// }


// Handle Password
$password_plain = $_POST['password'];

if (empty($password_plain)) {
    // Should not happen as the modal input is 'required', but good practice.
    $_SESSION['error_message'] = "Password is required.";
    header("Location: ../users.php");
    exit();
}

$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);


// 3. SQL Query (Use prepared statements for better security)
$stmt = $conn->prepare("
    INSERT INTO users (name, email, student_number, password, role, type, is_admin)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

// Bind parameters: 'ssssssi' corresponds to string, string, string, string, string, string, integer
$stmt->bind_param("ssssssi", 
    $name, 
    $email, 
    $student_number, 
    $password_hash, 
    $role, 
    $type, 
    $is_admin
);

// 4. Execute and Check Result
if ($stmt->execute()) {
    $_SESSION['success_message'] = "User **{$name}** registered successfully!";
} else {
    // Check for duplicate entry error (e.g., duplicate email or student_number)
    if ($conn->errno == 1062) {
        $_SESSION['error_message'] = "Error: User registration failed. Email or Student Number already exists.";
    } else {
        $_SESSION['error_message'] = "Error: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();

// 5. Redirect back to the user management page
header("Location: ../admin/users.php");
exit();
?>