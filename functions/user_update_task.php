<?php
session_start();
require_once "config.php"; // DB connection

// 1. User Authentication Check
if (!isset($_SESSION['student_number']) || !isset($_SESSION['user_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

// 2. Input Validation and Sanitization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $new_link = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);
    $user_id = $_SESSION['user_id'];

    // Ensure required fields are present and valid
    if ($task_id === false || empty($new_status)) {
        $_SESSION['error_message'] = "Invalid task ID or status.";
        header("Location: ../user_tasks.php");
        exit();
    }

    // Default the link to NULL if it's empty
    $new_link = empty($new_link) ? NULL : $new_link;

    // 3. Database Update
    // IMPORTANT: The query checks if the task is assigned to the current user (WHERE id=? AND assigned_to_id=?)
    $sql = "
        UPDATE tasks 
        SET status = ?, link = ?
        WHERE id = ? AND assigned_to_id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $new_status, $new_link, $task_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Task updated successfully!";
        } else {
            $_SESSION['error_message'] = "Task not found or you are not authorized to update it.";
        }
    } else {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    // 4. Redirect back to the user task page
    header("Location: ../user_tasks.php");
    exit();
} else {
    // If not a POST request, redirect
    header("Location: ../user_tasks.php");
    exit();
}
?>